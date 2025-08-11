<?php
// Включить буферизацию вывода
ob_start();

// Начало сессии
session_start();

// Отключить вывод ошибок на экран
ini_set('display_errors', 0);
error_reporting(0);

// Пути к файлам
define('USERS_JSON', '/home/container/webroot/db/users.json');
define('TOKENS_JSON', '/home/container/webroot/db/reset_tokens.json');
define('PHPMailer_PATH', '/home/container/webroot/auth/phpmailer/src/');

// Проверка существования PHPMailer
require PHPMailer_PATH . 'PHPMailer.php';
require PHPMailer_PATH . 'SMTP.php';
require PHPMailer_PATH . 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Функция для записи ошибок в лог
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '/home/container/webroot/auth/error.log');
}

// Функция для генерации токена
function generateToken($length = 32) {
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception $e) {
        logError("Ошибка генерации токена: " . $e->getMessage());
        die("Произошла ошибка при генерации токена безопасности");
    }
}

// Функция для проверки существования email
function emailExists($email, $usersFile) {
    if (!file_exists($usersFile)) {
        logError("Файл users.json не найден");
        return false;
    }
    
    $data = file_get_contents($usersFile);
    if ($data === false) {
        logError("Не удалось прочитать users.json");
        return false;
    }
    
    $users = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError("Ошибка декодирования users.json: " . json_last_error_msg());
        return false;
    }
    
    foreach ($users as $user) {
        if (isset($user['email']) && $user['email'] === $email) {
            return true;
        }
    }
    
    return false;
}

// Функция для сохранения токена
function saveResetToken($email, $token, $tokenFile) {
    $tokens = [];
    
    if (file_exists($tokenFile)) {
        $data = file_get_contents($tokenFile);
        if ($data !== false) {
            $tokens = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                logError("Ошибка декодирования reset_tokens.json: " . json_last_error_msg());
                $tokens = [];
            }
        }
    }
    
    // Удаляем старые токены для этого email
    $tokens = array_filter($tokens, function($entry) use ($email) {
        return $entry['email'] !== $email;
    });
    
    // Добавляем новый токен
    $tokens[] = [
        'email' => $email,
        'token' => $token,
        'expires' => time() + 3600
    ];
    
    $result = file_put_contents($tokenFile, json_encode($tokens, JSON_PRETTY_PRINT));
    if ($result === false) {
        logError("Не удалось записать в файл $tokenFile");
        return false;
    }
    
    return true;
}

// Основной код
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: reset_form.php");
        exit;
    }

    if (empty($_POST['email'])) {
        $_SESSION['error'] = "Email не был предоставлен";
        header("Location: reset_form.php");
        exit;
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Пожалуйста, введите корректный email адрес";
        header("Location: reset_form.php");
        exit;
    }

    if (!emailExists($email, USERS_JSON)) {
        $_SESSION['error'] = "Пользователь с таким email не найден";
        header("Location: reset_form.php");
        exit;
    }

    $token = generateToken();
    if (!saveResetToken($email, $token, TOKENS_JSON)) {
        throw new Exception("Не удалось сохранить токен сброса");
    }

    $mail = new PHPMailer(true);
    
    // Настройки SMTP (отключен вывод отладки)
    $mail->SMTPDebug = 0; // 0 = off (for production use)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'qunerysav@gmail.com';
    $mail->Password = 'cfmp ptoh kxux zfqq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom('qunerysav@gmail.com', 'DartWorld');
    $mail->addAddress($email);
    $mail->isHTML(true);
    
    $resetLink = "https://dartworld.ru/auth/reset/reset_password.php?token=$token";
    
    $mail->Subject = 'Сброс пароля';
    $mail->Body    = "Для сброса пароля перейдите по ссылке: <a href='$resetLink'>$resetLink</a><br>
                     Ссылка действительна в течение 1 часа.";
    $mail->AltBody = "Для сброса пароля перейдите по ссылке: $resetLink (действительна 1 час)";

    if (!$mail->send()) {
        throw new Exception("Ошибка отправки письма: " . $mail->ErrorInfo);
    }
    
    $_SESSION['success'] = "Письмо с инструкциями отправлено на ваш email";
    
} catch (Exception $e) {
    logError("Exception in send_reset.php: " . $e->getMessage());
    $_SESSION['error'] = "Произошла ошибка. Пожалуйста, попробуйте позже.";
}

// Перенаправление обратно на форму сброса
header("Location: reset_form.php");
exit;