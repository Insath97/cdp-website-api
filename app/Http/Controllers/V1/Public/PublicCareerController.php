<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\Career;
use App\Models\CareerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CreateCareerRequest;
use App\Http\Requests\StoreCareerApplicationRequest;
use App\Mail\CareerNotificationMail;
use App\Mail\CareerApplicationMail;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Str;

class PublicCareerController extends Controller
{
    use ActivityLogTrait, FileUploadTrait;
    /**
     * Display a listing of active and unexpired career postings.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = Career::with(['responsibilities', 'requirements', 'benefits'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('due_date')
                        ->orWhere('due_date', '>=', now()->toDateString());
                });

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('department') && $request->department != '') {
                $query->where('department', $request->department);
            }

            if ($request->has('location') && $request->location != '') {
                $query->where('location', $request->location);
            }

            if ($request->has('job_type') && $request->job_type != '') {
                $query->where('job_type', $request->job_type);
            }

            $query->orderBy('created_at', 'desc');
            $careers = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Public career postings retrieved successfully',
                'data' => $careers,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public career postings',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified active and unexpired career posting.
     */
    public function show(string $idOrSlug)
    {
        try {
            $career = Career::with(['responsibilities', 'requirements', 'benefits'])
                ->where(function ($q) use ($idOrSlug) {
                    $q->where('id', $idOrSlug)
                        ->orWhere('slug', $idOrSlug);
                })
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('due_date')
                        ->orWhere('due_date', '>=', now()->toDateString());
                })
                ->first();

            if (! $career) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Career posting not found or has expired',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Career posting retrieved successfully',
                'data' => $career,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve career posting',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
    
    /**
     * Submit a career application from the public website.
     */
    public function apply(StoreCareerApplicationRequest $request)
    {
        try {
            $data = $request->validated();

            // 1. Generate unique application code
            // APP-{YYYYMMDD}-{RANDOM_HEX_6_CHARS}
            $date = date('Ymd');
            do {
                $code = 'APP-' . $date . '-' . strtoupper(Str::random(6));
            } while (CareerApplication::query()->where('application_code', $code)->exists());
            
            $data['application_code'] = $code;

            // 2. Handle resume file upload
            if ($request->hasFile('resume')) {
                $filepath = $this->handleFileUpload(
                    $request,
                    'resume',
                    null,
                    'resumes',
                    'resume_' . $code
                );
                if ($filepath) {
                    $data['resume_path'] = $filepath;
                }
            }

            // 3. Set default status
            $data['status'] = 'applied';

            // 4. Create career application record
            $application = CareerApplication::create($data);

            // Load career relationship for the email
            $application->load('career');

            // 5. Send notification email to the administrator
            $notificationEmail = SystemSetting::getValue('career_mail', config('mail.from.address'));
            
            if ($notificationEmail) {
                try {
                    Mail::to($notificationEmail)->send(new CareerApplicationMail($application));
                    $this->logActivity('MAIL_SENT', 'Career Application', "Job application notification sent to: {$notificationEmail}");
                } catch (\Exception $mailException) {
                    $this->logActivity('MAIL_FAILED', 'Career Application', "Failed to send job application email to: {$notificationEmail}. Error: " . $mailException->getMessage());
                }
            }

            $this->logActivity('CREATE', 'Career Application', "Job application {$code} submitted successfully by {$application->fullname}");

            return response()->json([
                'status' => 'success',
                'message' => 'Your application has been submitted successfully!',
                'application_code' => $code,
                'data' => $application
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit application',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display a listing of career applications for public tracking/validation.
     */
    public function applications(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = CareerApplication::query()->with('career');

            if ($request->has('email') && $request->email != '') {
                $query->where('email', $request->email);
            }

            if ($request->has('phone_number') && $request->phone_number != '') {
                $query->where('phone_number', $request->phone_number);
            }

            if ($request->has('application_code') && $request->application_code != '') {
                $query->where('application_code', $request->application_code);
            }

            $query->orderBy('created_at', 'desc');
            $applications = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Career applications retrieved successfully',
                'data' => $applications,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve applications',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
