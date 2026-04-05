<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\CmsContent;
use Illuminate\Http\Request;

class PublicCMSController extends Controller
{
    /**
     * Get CMS content for a specific page.
     * Following existing pattern: 
     * - Returns status, message, and structured data.
     * - Masks URL paths for file types.
     * - Removes sensitive administrative data.
     */
    public function getPageContent(string $page)
    {
        try {
            $structuredContent = CmsContent::getPublicPageContent($page);

            return response()->json([
                'status' => 'success',
                'message' => empty($structuredContent) 
                    ? "No content found for the requested page." 
                    : "CMS content retrieved successfully",
                'data' => $structuredContent
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve CMS contents',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
