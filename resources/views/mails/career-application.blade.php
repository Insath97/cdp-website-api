<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Job Application: {{ $application->fullname }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #2c3e50;">New Job Application Received</h2>
    <p>A candidate has applied for a job via the public website.</p>

    <table style="border-collapse: collapse; width: 100%; max-width: 600px;">
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 180px;">Application Code:</td>
            <td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 14px; font-weight: bold; color: #2980b9;">{{ $application->application_code }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Full Name:</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $application->fullname }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Email Address:</td>
            <td style="padding: 8px; border: 1px solid #ddd;"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Phone Number:</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $application->phone_number }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Applied Position:</td>
            <td style="padding: 8px; border: 1px solid #ddd;">{{ $application->career ? $application->career->title : 'N/A' }} @if($application->career && $application->career->department) ({{ $application->career->department }}) @endif</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Status:</td>
            <td style="padding: 8px; border: 1px solid #ddd; text-transform: capitalize;">{{ $application->status }}</td>
        </tr>
    </table>

    @if($application->cover_letter)
        <h3 style="margin-top: 20px; color: #2c3e50;">Cover Letter</h3>
        <div style="padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9; max-width: 600px; white-space: pre-wrap;">{{ $application->cover_letter }}</div>
    @endif

    <p style="margin-top: 20px;"><em>Note: The candidate's resume/CV is attached to this email.</em></p>
    <br>
    <p>Thank you,<br>{{ config('app.name') }} System</p>
</body>
</html>
