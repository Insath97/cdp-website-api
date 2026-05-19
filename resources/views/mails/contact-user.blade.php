<!DOCTYPE html>
<html>
<head>
    <title>New Contact Enquiry - {{ config('app.name') }}</title>
</head>
<body>  
            <p>Hello Admin,</p>
            <p>You have received a new contact message from your website. Here are the details:</p>
            
            <table>
                <tr>
                    <th>First Name</th>
                    <td>{{ $data['first_name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Last Name</th>
                    <td>{{ $data['last_name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Email Address</th>
                    <td>
                        <strong>Email:</strong> {{ $user['email'] }}<br>
                    </td>
                </tr>
                <tr>
                    <th>Subject</th>
                    <td><strong>{{ $data['subject'] ?? 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td>
                        <strong>{{ $data['message'] ?? 'N/A' }}</strong>
                    </td>
                </tr>
            </table>
</body>
</html>
