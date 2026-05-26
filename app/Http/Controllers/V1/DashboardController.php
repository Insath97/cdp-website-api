<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Branch;
use App\Models\ContactType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Carbon\Carbon;

class DashboardController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:Dashboard Index', only: ['index']),
        ];
    }

    /**
     * Display a listing of the dashboard statistics.
     */
    public function index(Request $request)
    {
        try {
            // 1. Cards statistics
            $totalUsers = User::count();
            $totalContacts = Contact::count();
            $publishedBlogs = Event::where('status', 'approved')->count();
            $totalBranches = Branch::count();

            // 2. User Growth (Cumulative over the last 6 months)
            $userGrowthLabels = [];
            $userGrowthData = [];
            
            // Get count of users before the start of the 6-month window
            $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
            $runningTotal = User::where('created_at', '<', $sixMonthsAgo)->count();

            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $userGrowthLabels[] = $month->format('M');

                $newUsersInMonth = User::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();

                $runningTotal += $newUsersInMonth;
                $userGrowthData[] = $runningTotal;
            }

            // 3. Weekly Activity (Inquiries vs Leads for the current week, Mon-Sun)
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            // Fetch IDs of contact types related to leads
            $leadTypeIds = ContactType::where(function ($query) {
                $query->where('slug', 'like', '%lead%')
                      ->orWhere('code', 'like', '%lead%')
                      ->orWhere('name', 'like', '%lead%');
            })->pluck('id')->toArray();

            // Fetch weekly contacts
            $weeklyContacts = Contact::whereBetween('created_at', [$startOfWeek, $endOfWeek])->get();

            $weeklyActivityLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $inquiriesData = array_fill(0, 7, 0);
            $leadsData = array_fill(0, 7, 0);

            foreach ($weeklyContacts as $contact) {
                $dayOfWeek = $contact->created_at->dayOfWeek;
                $targetIndex = ($dayOfWeek === 0) ? 6 : $dayOfWeek - 1; // Map Sunday to 6, Monday to 0, etc.

                if (in_array($contact->contact_type_id, $leadTypeIds)) {
                    $leadsData[$targetIndex]++;
                } else {
                    $inquiriesData[$targetIndex]++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => [
                    'cards' => [
                        'total_users' => $totalUsers,
                        'total_contacts' => $totalContacts,
                        'published_blogs' => $publishedBlogs,
                        'total_branches' => $totalBranches,
                    ],
                    'user_growth' => [
                        'labels' => $userGrowthLabels,
                        'data' => $userGrowthData,
                    ],
                    'weekly_activity' => [
                        'labels' => $weeklyActivityLabels,
                        'inquiries' => $inquiriesData,
                        'leads' => $leadsData,
                    ],
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
