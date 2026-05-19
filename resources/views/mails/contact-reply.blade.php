<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body>
    <p>Hello {{ $contact->first_name }} {{ $contact->last_name }},</p>

    <p>{{ $messageBody }}</p>

    <hr>

    <p>
        Regards,<br>
        {{ config('app.name') }}
    </p>
</body>
</html>
