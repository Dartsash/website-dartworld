<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (isset($_SESSION['username'])) {
    $usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        $currentUser = $users[$_SESSION['username']] ?? null;
        
        if ($currentUser && ($currentUser['banned'] ?? false)) {
            header("Location: /bans.php");
            exit;
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$message = $_SESSION['reset_message'] ?? '';
$error = $_SESSION['reset_error'] ?? '';
unset($_SESSION['reset_message'], $_SESSION['reset_error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сброс пароля</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #dff0d8; border-left: 4px solid #3c763d; }
        .error { background-color: #f2dede; border-left: 4px solid #a94442; }
        .form-group { margin-bottom: 15px; }
        input[type="email"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Сброс пароля</h2>
    
    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="send_email.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        
        <div class="form-group">
            <label for="email">Введите ваш email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <button type="submit">Отправить ссылку для сброса</button>
    </form>
</body>
</html>
