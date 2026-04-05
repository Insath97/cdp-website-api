# CDP Empire — CMS Module API Documentation

> [!NOTE]
> This documentation covers the **CMS Content API** for the CDP Empire backend. All admin endpoints require a valid JWT Bearer Token.

---

## Overview

The CMS module allows you to manage all dynamic website content (text, images, videos, PDFs, SVGs, and icons) through a unified API. Content is organized by three keys:

| Field | Type | Description |
|---|---|---|
| `page` | `string` | The website page (e.g., `home`, `about`, `contact`) |
| `section` | `string` | A section within the page (e.g., `hero`, `features`, `footer`) |
| `key` | `string` | The unique identifier for a content item within a section |

The combination of `page + section + key` is **unique** and always refers to one specific content block.

---

## Endpoints

### 1. `GET /api/v1/cms` — List All Content (Admin)
**Auth Required:** ✅ `Bearer Token` + `CMS Index` permission

Retrieve all CMS content, optionally filtered by page.

**Query Parameters:**
| Parameter | Required | Description |
|---|---|---|
| `page` | No | Filter by page name (e.g., `?page=home`) |

**Example Request:**
```http
GET /api/v1/cms?page=home
Authorization: Bearer {token}
```

**Success Response `200`:**
```json
{
  "status": "success",
  "message": "CMS contents retrieved successfully",
  "data": {
    "home": {
      "hero": [
        {
          "id": 1,
          "page": "home",
          "section": "hero",
          "key": "title",
          "value": "Welcome to CDP Empire",
          "type": "text",
          "label": "Hero Title",
          "metadata": null,
          "created_at": "2026-04-05T04:00:00.000000Z",
          "updated_at": "2026-04-05T04:00:00.000000Z"
        }
      ]
    }
  }
}
```

---

### 2. `POST /api/v1/cms/update` — Bulk Update CMS Content (Admin)
**Auth Required:** ✅ `Bearer Token` + `CMS Update` permission  
**Content-Type:** `multipart/form-data` *(required if uploading files)*

Update one or more CMS content blocks in a single request. Each item in the `contents` array targets a specific `page + section + key` block. Uses `updateOrCreate` — it will create the block if it doesn't exist.

**Request Body:**
```json
{
  "contents": [
    {
      "page": "string (required)",
      "section": "string (required)",
      "key": "string (required)",
      "type": "string (required) — one of: text, textarea, image, video, pdf, svg, file, link, icon",
      "value": "string | file (nullable)",
      "label": "string (nullable)",
      "metadata": "object (nullable)"
    }
  ]
}
```

> [!IMPORTANT]
> When the `type` is `image`, `video`, `pdf`, or `file`, the `value` field must be sent as a **file upload** (multipart/form-data), not a string.

---

## Payload Examples by Content Type

### 📝 Text
```
POST /api/v1/cms/update
Content-Type: application/json

{
  "contents": [
    {
      "page": "home",
      "section": "hero",
      "key": "title",
      "type": "text",
      "value": "Welcome to CDP Empire",
      "label": "Hero Title"
    }
  ]
}
```

### 📄 Textarea
```
POST /api/v1/cms/update
Content-Type: application/json

{
  "contents": [
    {
      "page": "about",
      "section": "mission",
      "key": "description",
      "type": "textarea",
      "value": "We are committed to delivering world-class education...",
      "label": "Mission Description"
    }
  ]
}
```

### 🔗 Link
```
POST /api/v1/cms/update
Content-Type: application/json

{
  "contents": [
    {
      "page": "home",
      "section": "hero",
      "key": "cta_url",
      "type": "link",
      "value": "https://cdp.lk/courses",
      "label": "Hero CTA Button URL"
    }
  ]
}
```

### 🎨 Icon (FontAwesome / CSS class or URL)
```
POST /api/v1/cms/update
Content-Type: application/json

{
  "contents": [
    {
      "page": "home",
      "section": "features",
      "key": "support_icon",
      "type": "icon",
      "value": "fas fa-headset",
      "label": "Support Feature Icon"
    }
  ]
}
```

### 🖼️ Image (File Upload)
```
POST /api/v1/cms/update
Content-Type: multipart/form-data

contents[0][page]    = home
contents[0][section] = hero
contents[0][key]     = banner_image
contents[0][type]    = image
contents[0][value]   = @/path/to/banner.jpg  ← FILE
contents[0][label]   = Hero Banner Image
```

**Auto-stored metadata:**
```json
{
  "original_name": "banner.jpg",
  "extension": "jpg",
  "mime_type": "image/jpeg",
  "size": 204800
}
```

### 🎬 Video (File Upload)
```
POST /api/v1/cms/update
Content-Type: multipart/form-data

contents[0][page]    = home
contents[0][section] = hero
contents[0][key]     = background_video
contents[0][type]    = video
contents[0][value]   = @/path/to/hero.mp4   ← FILE
contents[0][label]   = Hero Background Video
```

**You can also pass custom metadata manually (e.g., duration):**
```
contents[0][metadata][duration] = 0:32
```

### 📋 PDF (File Upload)
```
POST /api/v1/cms/update
Content-Type: multipart/form-data

contents[0][page]    = about
contents[0][section] = company
contents[0][key]     = brochure
contents[0][type]    = pdf
contents[0][value]   = @/path/to/brochure.pdf  ← FILE
contents[0][label]   = Company Brochure
```

### 🎭 SVG (File Upload or URL)
> SVGs can be uploaded as files or stored as raw paths/URLs.

**As a File:**
```
POST /api/v1/cms/update
Content-Type: multipart/form-data

contents[0][page]    = home
contents[0][section] = footer
contents[0][key]     = logo_svg
contents[0][type]    = svg
contents[0][value]   = @/path/to/logo.svg  ← FILE
contents[0][label]   = Footer SVG Logo
```

**As a URL string:**
```json
{
  "contents": [
    {
      "page": "home",
      "section": "footer",
      "key": "logo_svg",
      "type": "svg",
      "value": "https://cdp.lk/assets/logo.svg",
      "label": "Footer SVG Logo URL"
    }
  ]
}
```

### 🗂️ Generic File (PDF, ZIP, etc.)
```
POST /api/v1/cms/update
Content-Type: multipart/form-data

contents[0][page]    = resources
contents[0][section] = downloads
contents[0][key]     = course_guide
contents[0][type]    = file
contents[0][value]   = @/path/to/guide.pdf  ← FILE
contents[0][label]   = Course Guide PDF
```

---

## Bulk Update (Mixed Types in One Request)

You can mix any content types in a single `POST` call. For example, update the hero title, a banner, and a CTA link in one request:

```
POST /api/v1/cms/update
Content-Type: multipart/form-data

contents[0][page]    = home
contents[0][section] = hero
contents[0][key]     = title
contents[0][type]    = text
contents[0][value]   = "Welcome to CDP Empire"

contents[1][page]    = home
contents[1][section] = hero
contents[1][key]     = banner
contents[1][type]    = image
contents[1][value]   = @/path/to/new-banner.jpg

contents[2][page]    = home
contents[2][section] = hero
contents[2][key]     = cta_url
contents[2][type]    = link
contents[2][value]   = "https://cdp.lk/enroll"
```

---

## 3. `GET /api/v1/public/cms/{page}` — Get Page Content (Public)
**Auth Required:** ❌ No authentication needed

This is the **frontend-facing** endpoint. It returns sanitized and URL-masked content for a specific page.

> [!WARNING]
> Internal fields like `id`, `label`, `created_at`, `updated_at` are **NOT** returned here. This protects your CMS structure from being publicly exposed.

**Path Parameters:**
| Parameter | Required | Description |
|---|---|---|
| `{page}` | Yes | The page to fetch (e.g., `home`, `about`) |

**Example Request:**
```http
GET /api/v1/public/cms/home
```

**Success Response `200`:**
```json
{
  "status": "success",
  "message": "CMS content retrieved successfully",
  "data": {
    "hero": {
      "title": {
        "value": "Welcome to CDP Empire",
        "type": "text",
        "metadata": null
      },
      "banner": {
        "value": "https://api.cdp.lk/uploads/cms/home/hero/banner.jpg",
        "type": "image",
        "metadata": {
          "original_name": "banner.jpg",
          "extension": "jpg",
          "mime_type": "image/jpeg",
          "size": 204800
        }
      },
      "background_video": {
        "value": "https://api.cdp.lk/uploads/cms/home/hero/background_video.mp4",
        "type": "video",
        "metadata": {
          "original_name": "bg.mp4",
          "mime_type": "video/mp4",
          "size": 15728640,
          "duration": "0:32"
        }
      },
      "cta_url": {
        "value": "https://cdp.lk/enroll",
        "type": "link",
        "metadata": null
      }
    },
    "features": {
      "icon_support": {
        "value": "fas fa-headset",
        "type": "icon",
        "metadata": null
      }
    }
  }
}
```

**Empty Page Response `200`:**
```json
{
  "status": "success",
  "message": "No content found for the requested page.",
  "data": []
}
```

---

## Validation Rules Reference

| Field | Rule |
|---|---|
| `contents` | `required`, `array` |
| `contents.*.page` | `required`, `string` |
| `contents.*.section` | `required`, `string` |
| `contents.*.key` | `required`, `string` |
| `contents.*.type` | `required`, `in:text,textarea,image,video,pdf,svg,file,link,icon` |
| `contents.*.value` | `nullable` (file or string) |
| `contents.*.label` | `nullable`, `string` |
| `contents.*.metadata` | `nullable`, `array` |

---

## Content Type Reference

| `type` | Value Format | File Upload | URL Masking (Public API) |
|---|---|---|---|
| `text` | Plain string | ❌ | ❌ |
| `textarea` | Multi-line string | ❌ | ❌ |
| `link` | URL string | ❌ | ❌ |
| `icon` | CSS class or URL | ❌ | ❌ |
| `svg` | File or URL string | ✅ | ❌* |
| `image` | File | ✅ | ✅ |
| `video` | File | ✅ | ✅ |
| `pdf` | File | ✅ | ✅ |
| `file` | File | ✅ | ✅ |

> *SVG URL masking is only applied if the `value` is a relative file path, not a full external URL.

---

## File Storage Structure

Uploaded files are stored in the `public/uploads/` folder following this pattern:

```
public/
└── uploads/
    └── cms/
        └── {page}/
            └── {section}/
                └── {key}.{extension}
```

**Example:**
```
uploads/cms/home/hero/banner_image.jpg
uploads/cms/about/team/profile_video.mp4
uploads/cms/resources/downloads/course_guide.pdf
```
