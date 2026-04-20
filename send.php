<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$configFile = __DIR__ . '/contact-config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Configuration file not found.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require $configFile;

$turnstileSecret = trim((string)($config['secret_key'] ?? ''));
$toEmail         = trim((string)($config['to_email'] ?? ''));
$fromEmail       = trim((string)($config['from_email'] ?? 'no-reply@adventure-avv.ru'));
$fromName        = trim((string)($config['from_name'] ?? 'Adventure AVV'));
$subjectPrefix   = trim((string)($config['subject_prefix'] ?? 'Message from site'));
$maxMessageLen   = (int)($config['max_message_length'] ?? 5000);
$allowedOrigin   = trim((string)($config['allowed_origin'] ?? ''));
$rateWindow      = max(60, (int)($config['rate_limit_window'] ?? 600));
$rateMaxAttempts = max(1, (int)($config['rate_limit_max_attempts'] ?? 5));

function jsonResponse(bool $success, string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function cleanHeaderValue(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function post(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function getClientIp(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function containsText(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }

    return strpos($haystack, $needle) !== false;
}

function safeLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }

    return strlen($value);
}

function ensureRateLimit(string $ip, int $window, int $maxAttempts): void
{
    $dir = __DIR__ . '/tmp/contact-rate-limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    if (!is_dir($dir) || !is_writable($dir)) {
        return;
    }

    $file = $dir . '/' . sha1($ip) . '.json';
    $now = time();

    $data = [
        'attempts' => [],
    ];

    if (is_file($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode((string)$raw, true);

        if (is_array($decoded) && isset($decoded['attempts']) && is_array($decoded['attempts'])) {
            $data = $decoded;
        }
    }

    $attempts = array_values(array_filter($data['attempts'], static function ($timestamp) use ($now, $window) {
        return is_numeric($timestamp) && ((int)$timestamp > ($now - $window));
    }));

    if (count($attempts) >= $maxAttempts) {
        jsonResponse(false, 'Too many attempts. Please try again later.', 429);
    }

    $attempts[] = $now;
    $data['attempts'] = $attempts;

    file_put_contents($file, json_encode($data), LOCK_EX);
}

$originHeader  = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
$refererHeader = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));

if ($allowedOrigin !== '') {
    $originOk  = ($originHeader === $allowedOrigin);
    $refererOk = ($refererHeader !== '' && strpos($refererHeader, $allowedOrigin) === 0);

    if (!$originOk && !$refererOk) {
        jsonResponse(false, 'Invalid request origin.', 403);
    }
}

$honeypot = post('website');
if ($honeypot !== '') {
    jsonResponse(false, 'Spam detected.', 400);
}

$ip = getClientIp();
ensureRateLimit($ip, $rateWindow, $rateMaxAttempts);

$name    = post('name');
$email   = post('email');
$subject = post('subject');
$message = post('message');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    jsonResponse(false, 'Please fill in all required fields.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', 400);
}

if (safeLength($name) > 120) {
    jsonResponse(false, 'Name is too long.', 400);
}

if (safeLength($subject) > 180) {
    jsonResponse(false, 'Subject is too long.', 400);
}

if (safeLength($message) > $maxMessageLen) {
    jsonResponse(false, 'Message is too long.', 400);
}

$turnstileToken = post('cf-turnstile-response');

if ($turnstileSecret === '' || containsText($turnstileSecret, 'PASTE_TURNSTILE_')) {
    jsonResponse(false, 'Turnstile secret key is not configured.', 500);
}

if ($turnstileToken === '') {
    jsonResponse(false, 'Please complete the captcha.', 400);
}

$verifyData = http_build_query([
    'secret'   => $turnstileSecret,
    'response' => $turnstileToken,
    'remoteip' => $ip
]);

$verifyOptions = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $verifyData,
        'timeout' => 15
    ]
];

$verifyContext = stream_context_create($verifyOptions);
$verifyResult = @file_get_contents(
    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    false,
    $verifyContext
);

if ($verifyResult === false) {
    jsonResponse(false, 'Captcha verification failed.', 500);
}

$turnstileResponse = json_decode($verifyResult, true);

if (!is_array($turnstileResponse) || empty($turnstileResponse['success'])) {
    jsonResponse(false, 'Captcha failed. Please try again.', 400);
}

$safeName    = cleanHeaderValue($name);
$safeEmail   = filter_var($email, FILTER_SANITIZE_EMAIL);
$safeSubject = cleanHeaderValue($subject);

$mailSubject = cleanHeaderValue($subjectPrefix . ': ' . $safeSubject);

$mailBody = [];
$mailBody[] = "New message from adventure-avv.ru";
$mailBody[] = "";
$mailBody[] = "Name: " . $safeName;
$mailBody[] = "Email: " . $safeEmail;
$mailBody[] = "Subject: " . $safeSubject;
$mailBody[] = "IP: " . $ip;
$mailBody[] = "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
$mailBody[] = "";
$mailBody[] = "Message:";
$mailBody[] = $message;

$body = implode("\r\n", $mailBody);

if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Recipient email is not configured correctly.', 500);
}

$encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: ' . $encodedFromName . ' <' . cleanHeaderValue($fromEmail) . '>';
$headers[] = 'Reply-To: ' . cleanHeaderValue($safeEmail);
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = @mail(
    $toEmail,
    $mailSubject,
    $body,
    implode("\r\n", $headers)
);

if (!$sent) {
    jsonResponse(false, 'Message could not be sent.', 500);
}

jsonResponse(true, 'Message sent successfully.');