<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Work Order Due Date Reminder</title>
</head>
<body>
    
    <p><strong>Work Order Due Date Reminder</strong></p>
    
    <p>The following work order is due within 24 hours and requires your immediate attention:</p>
    
    <p><strong>Work Order Details:</strong></p>
    <ul>
        <li><strong>Document Number:</strong> {{ $body->document_number }}</li>
        <li><strong>Equipment:</strong> {{ $body->equipment_name }}</li>
        <li><strong>Due Date:</strong> {{ $body->due_date }}</li>
        <li><strong>Maintenance Type:</strong> {{ $body->maintenance_type }}</li>
        @if(isset($body->priority) && !empty($body->priority))
        <li><strong>Priority:</strong> {{ $body->priority }}</li>
        @endif
    </ul>
    
    
    <p>Please ensure the work order is completed on time and update the status upon completion.</p>

    <br>

    <p>Thanks & Regards,</p>
</body>
</html>
