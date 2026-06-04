<?php
// Function to send notification
function sendNotification($userId, $title, $message, $type = 'general') {
    global $conn;
    
    // Get user's push token and settings
    $stmt = $conn->prepare("
        SELECT us.push_notification_token, us.device_type, us.notification_enabled, u.email 
        FROM user_settings us
        JOIN users u ON us.user_id = u.id
        WHERE us.user_id = ?
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData || !$userData['notification_enabled']) {
        return false;
    }
    
    $pushToken = $userData['push_notification_token'];
    $deviceType = $userData['device_type'];
    $email = $userData['email'];
    
    // Send push notification via OneSignal
    if (!empty($pushToken)) {
        sendOneSignalNotification($pushToken, $title, $message, $deviceType);
    }
    
    // Send email notification (optional)
    if ($type === 'new_message') {
        sendEmailNotification($email, $title, $message);
    }
    
    return true;
}

// Function to send OneSignal push notification
function sendOneSignalNotification($pushToken, $title, $message, $deviceType = 'web') {
    // Replace with your OneSignal App ID and API Key
    $appId = 'YOUR_ONESIGNAL_APP_ID';
    $apiKey = 'YOUR_ONESIGNAL_API_KEY';
    
    $content = array(
        "en" => $message
    );
    
    $fields = array(
        'app_id' => $appId,
        'include_player_ids' => array($pushToken),
        'data' => array("foo" => "bar"),
        'contents' => $content,
        'headings' => array("en" => $title),
        'priority' => 10
    );
    
    $fields = json_encode($fields);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $apiKey
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Function to send email notification
function sendEmailNotification($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@whistleguard.com' . "\r\n";
    
    $htmlMessage = "
    <html>
    <head>
        <title>{$subject}</title>
        <style>
            body { font-family: Arial, sans-serif; background: #0a0e1a; color: #fff; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 20px; text-align: center; border-radius: 10px; }
            .content { background: rgba(20, 24, 36, 0.95); padding: 20px; border-radius: 10px; margin-top: 20px; }
            .button { background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>WhistleGuard Notification</h2>
            </div>
            <div class='content'>
                <h3>{$subject}</h3>
                <p>{$message}</p>
                <a href='https://yourdomain.com/admin-dashboard.php' class='button'>View Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return mail($to, $subject, $htmlMessage, $headers);
}

// Function to send test notification
function sendTestNotification($userId, $pushToken, $deviceType) {
    sendOneSignalNotification($pushToken, 'Test Notification', 'Your notifications are working! Welcome to WhistleGuard.', $deviceType);
}
?>