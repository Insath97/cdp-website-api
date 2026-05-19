<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Career Post: {{ $title }}</title>
</head>
<body>
    @php
        $frontendBaseUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/');
        $applyUrl = $frontendBaseUrl . '/careers/' . $slug;
        $statusLabel = !is_null($is_active) && $is_active ? 'Active' : 'Inactive';
    @endphp

    <h1>New Career Post Created</h1>
    <p>A new career post has been created successfully.</p>

    <p><strong>Title:</strong> {{ $title }}</p>
    <p><strong>Department:</strong> {{ $department }}</p>
    <p>
        <strong>Apply Link:</strong>
        <a href="{{ $applyUrl }}">{{ $applyUrl }}</a>
    </p>

    <p>Please review the post and publish it when ready.</p>
    <br>
    <p>Thank you,<br>{{ config('app.name') }} Team</p>
</body>
</html>
