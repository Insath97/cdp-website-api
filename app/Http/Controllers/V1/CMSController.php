<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCmsContentRequest;
use App\Models\CmsContent;
use App\Traits\ActivityLogTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CMSController extends Controller implements HasMiddleware
{
    use ActivityLogTrait, FileUploadTrait;

    /**
     * Get the middleware assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:CMS Index', only: ['index']),
            new Middleware('permission:CMS Update', only: ['update']),
        ];
    }

    /**
     * Get all CMS content.
     */
    public function index(Request $request)
    {
        try {
            $page = $request->query('page');
            $query = CmsContent::query();

            if ($page) {
                $query->where('page', $page);
            }

            $contents = $query->get()->groupBy(['page', 'section']);

            return response()->json([
                'status' => 'success',
                'message' => 'CMS contents retrieved successfully',
                'data' => $contents
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve CMS contents',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(string $id) {}

    public function edit(string $id) {}

    public function update(UpdateCmsContentRequest $request)
    {
        try {
            DB::beginTransaction();

            $contents = $request->validated()['contents'];
            $updatedContents = [];

            $fileTypes = ['image', 'video', 'pdf', 'file'];

            foreach ($contents as $index => $item) {
                $page = $item['page'];
                $section = $item['section'];
                $key = $item['key'];
                $type = $item['type'];
                $value = $item['value'];
                $label = $item['label'] ?? null;
                $metadata = $item['metadata'] ?? [];

                // Find existing content to handle file replacement
                $existing = CmsContent::where('page', $page)
                    ->where('section', $section)
                    ->where('key', $key)
                    ->first();

                // Handle File Uploads
                if (in_array($type, $fileTypes) && $request->hasFile("contents.{$index}.value")) {
                    $file = $request->file("contents.{$index}.value");
                    $oldPath = $existing ? $existing->value : null;

                    // Capture basic metadata before moving the file
                    $metadata = array_merge($metadata, [
                        'original_name' => $file->getClientOriginalName(),
                        'extension' => $file->getClientOriginalExtension(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    $value = $this->handleFileUpload(
                        $request,
                        "contents.{$index}.value",
                        $oldPath,
                        "cms/{$page}/{$section}",
                        $key
                    );
                }

                $content = CmsContent::updateOrCreate(
                    [
                        'page' => $page,
                        'section' => $section,
                        'key' => $key
                    ],
                    [
                        'value' => $value,
                        'type' => $type,
                        'label' => $label ?? ($existing ? $existing->label : null),
                        'metadata' => !empty($metadata) ? $metadata : ($existing ? $existing->metadata : null)
                    ]
                );

                $updatedContents[] = $content;
            }

            DB::commit();

            $this->logActivity('CMS', 'Update', "Bulk updated " . count($updatedContents) . " CMS items");

            return response()->json([
                'status' => 'success',
                'message' => 'CMS contents updated successfully',
                'data' => $updatedContents
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update CMS contents',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy(string $id) {}
}
