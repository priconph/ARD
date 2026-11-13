@php
    // $formatted = isset($data->deadline_for_submission) ? date('F j, Y', strtotime($data->deadline_for_submission)) : 'N/A';
    // $type = isset($auditResultType) ? "{$auditResultType} Audit Report" : "Audit Report";
@endphp
<!DOCTYPE html>
<html>
<head>
    <title>Audit Report Approved</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.5;
        }
        p, label, td {
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn-approve {
            background-color: #28a745;
        }
        .btn-reject {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div>
        <span style="font-size: 18px; font-weight: bold;">Good day,</span>
        <br><br>
        
        <p>
            {{-- An **{{ $type }}** has been submitted and is currently awaiting your review and approval. --}}
            An <strong>{{ $type }} AUDIT</strong> has been approved.
        </p>
        <strong>Audit details are as follows:</strong>
        <table>
            <tr>
                <td><strong>Audit Control No.:</strong></td>
                <td>{{ $ctrl_no }}</td>
            </tr>
            <tr>
                <td><strong>Purpose of audit:</strong></td>
                <td>{{ $data['purpose_of_audit'] }}</td>
            </tr>
            <tr>
                <td><strong>Audit Date:</strong></td>
                <td>{{ $data['audit_date_from'] }} TO {{ $data['audit_date_to'] }}</td>
            </tr>
        </table>
        <br>
           
        {{-- <p>
            For more information.
        </p> --}}
        
        <span style="font-size: 14px;">Thank you.</span>
    </div>

    {{-- Email Footer --}}
    <hr style="border: 0; border-top: 1px solid #ccc; margin-top: 30px;">
    
    <div style="margin-top: 15px;">
        <p style="font-size: 12px; color: #666;">
            <b>Notice of Disclaimer:</b><br>
            <i>This message contains confidential information intended for a specific individual and purpose. If you are not the intended recipient, you should delete this message. Any disclosure, copying, or distribution of this message, or the taking of any action based on it, is strictly prohibited.</i>
        </p>
    </div>

    <div style="margin-top: 20px;">
        <span style="font-size: 16px; color: #0056b3;">
            <b>For concerns regarding this system, please contact ISS at local numbers 205, 206, or 208.</b>
        </span>
    </div>
</body>
</html>