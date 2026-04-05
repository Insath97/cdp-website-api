<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'page',
        'section',
        'key',
        'value',
        'type',
        'label',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    // Define constants for the different types for easier code handling
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_PDF = 'pdf';
    const TYPE_SVG = 'svg';
    const TYPE_FILE = 'file';
    const TYPE_LINK = 'link';
    const TYPE_ICON = 'icon';

    /**
     * Scope to filter by page and section.
     */
    public function scopePageContent($query, string $page)
    {
        return $query->where('page', $page);
    }

    /**
     * Helper to get structured content for a page (Raw Data).
     */
    public static function getStructuredPageContent(string $page)
    {
        $contents = self::where('page', $page)->get();

        $structured = [];
        foreach ($contents as $content) {
            $structured[$content->section][$content->key] = $content->value;
        }

        return $structured;
    }

    /**
     * Helper to get public-ready structured content.
     * Handles URL masking and includes type/metadata.
     */
    public static function getPublicPageContent(string $page)
    {
        $contents = self::where('page', $page)->get();
        $fileTypes = [self::TYPE_IMAGE, self::TYPE_VIDEO, self::TYPE_PDF, self::TYPE_FILE];

        $structured = [];
        foreach ($contents as $content) {
            $value = $content->value;

            // Mask URLs if it's a file
            if (in_array($content->type, $fileTypes)) {
                $value = $value ? asset($value) : null;
            }

            $structured[$content->section][$content->key] = [
                'value' => $value,
                'type' => $content->type,
                'metadata' => $content->metadata
            ];
        }

        return $structured;
    }
}
