<?php
session_start();

/**
 * Логи: /var/www/site/auth/error.log
 */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_reporting', E_ALL);

$logFile = __DIR__ . '/../error.log';
ini_set('error_log', $logFile);

function logErrorMsg(string $msg): void {
    error_log(date('[Y-m-d H:i:s] ') . $msg);
}

// База проекта: /var/www/site
$baseDir = realpath(__DIR__ . '/../../');
if ($baseDir === false) {
    logErrorMsg("baseDir not resolved");
    $_SESSION['error'] = "Ошибка сервера (path).";
    header("Location: reset_form.php");
    exit;
}

$usersFile  = $baseDir . '/db/users.json';
$tokensFile = $baseDir . '/db/reset_tokens.json';

// PHPMailer: /var/www/site/auth/phpmailer/src/...
$phpmailerDir = realpath(__DIR__ . '/../phpmailer/src');
if ($phpmailerDir === false) {
    logErrorMsg("PHPMailer dir not found: " . __DIR__ . '/../phpmailer/src');
    $_SESSION['error'] = "Ошибка сервера (PHPMailer missing).";
    header("Location: reset_form.php");
    exit;
}

require $phpmailerDir . '/PHPMailer.php';
require $phpmailerDir . '/SMTP.php';
require $phpmailerDir . '/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

function readJsonArray(string $path, $default) {
    if (!file_exists($path)) return $default;
    $raw = @file_get_contents($path);
    if ($raw === false) return $default;

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) return $default;

    return $data;
}

function writeJson(string $path, $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;

    return file_put_contents($path, $json, LOCK_EX) !== false;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: reset_form.php");
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $_SESSION['error'] = "Email не был предоставлен";
        header("Location: reset_form.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Пожалуйста, введите корректный email адрес";
        header("Location: reset_form.php");
        exit;
    }

    // users.json должен быть объектом: { "username": { "email": "...", ... }, ... }
    $users = readJsonArray($usersFile, []);
    $found = false;
    foreach ($users as $u) {
        if (isset($u['email']) && $u['email'] === $email) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['error'] = "Пользователь с таким email не найден";
        header("Location: reset_form.php");
        exit;
    }

    // Токены (массив)
    $tokens = readJsonArray($tokensFile, []);
    if (!is_array($tokens)) $tokens = [];

    // удалить старые токены этого email
    $tokens = array_values(array_filter($tokens, fn($t) => ($t['email'] ?? '') !== $email));

    $token = bin2hex(random_bytes(32));

    $tokens[] = [
        'email' => $email,
        'token' => $token,
        'expires' => time() + 3600,
    ];

    // если файла нет — создадим
    if (!file_exists($tokensFile)) {
        @file_put_contents($tokensFile, "[]");
    }

    if (!writeJson($tokensFile, $tokens)) {
        throw new \Exception("Failed to write reset_tokens.json: $tokensFile");
    }

    // SMTP (ВАЖНО: пароль ты уже засветил — лучше сразу поменяй его в Google!)
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'qunerysav@gmail.com';
    $mail->Password   = 'cfmp ptoh kxux zfqq'; // лучше перенести в env
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('qunerysav@gmail.com', 'DartWorld');
    $mail->addAddress($email);
    $mail->isHTML(true);

    // ССЫЛКА НА ТВОЙ ДОМЕН .pro (а не .ru)
    $resetLink = "https://dartworld.pro/auth/reset/reset_password.php?token=" . urlencode($token);

    $mail->Subject = 'Сброс пароля';
    $mail->Body    = "Для сброса пароля перейдите по ссылке: <a href='$resetLink'>$resetLink</a><br>Ссылка действительна 1 час.";
    $mail->AltBody = "Для сброса пароля перейдите по ссылке: $resetLink (действительна 1 час)";

    $mail->send();

    $_SESSION['success'] = "Письмо с инструкциями отправлено на ваш email";
    header("Location: reset_form.php");
    exit;

} catch (\Throwable $e) {
    logErrorMsg("send_reset.php error: " . $e->getMessage());
    $_SESSION['error'] = "Произошла ошибка. Пожалуйста, попробуйте позже.";
    header("Location: reset_form.php");
    exit;
}
