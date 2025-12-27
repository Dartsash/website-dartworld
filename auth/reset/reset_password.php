<?php
session_start();

/*
  Для продакшена лучше НЕ включать display_errors.
  Если надо отладить — временно включи:
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
*/
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Лог ошибок (опционально)
$logFile = __DIR__ . '/../error.log';
ini_set('error_log', $logFile);

function readJson($path, $default) {
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

$baseDir = realpath(__DIR__ . '/../../'); // /var/www/site
$usersFile  = $baseDir ? ($baseDir . '/db/users.json') : '';
$tokensFile = $baseDir ? ($baseDir . '/db/reset_tokens.json') : '';

$pageError = null;
$pageField = null;
$shake = false;

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$tokenValid = false;
$tokenEmail = null;

try {
    if (!$baseDir) {
        throw new Exception("baseDir not resolved");
    }

    // Грузим токены
    $tokens = readJson($tokensFile, []);
    if (!is_array($tokens)) $tokens = [];

    // Чистим просроченные
    $now = time();
    $tokens = array_values(array_filter($tokens, function($t) use ($now) {
        $exp = (int)($t['expires'] ?? 0);
        return $exp > $now;
    }));
    // Сохраняем очищенный список (не критично)
    @writeJson($tokensFile, $tokens);

    // Проверка токена (для GET и POST)
    if ($token !== '') {
        foreach ($tokens as $t) {
            if (($t['token'] ?? '') === $token) {
                $tokenValid = true;
                $tokenEmail = (string)($t['email'] ?? '');
                break;
            }
        }
    }

    // Если POST — меняем пароль
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$tokenValid || !$tokenEmail) {
            $pageError = "Недействительная или просроченная ссылка для сброса пароля";
            $shake = true;
        } else {
            $password = (string)($_POST['password'] ?? '');
            $confirm  = (string)($_POST['confirm_password'] ?? '');

            if (strlen($password) < 6) {
                $pageError = "Password must be at least 6 characters";
                $pageField = "password";
                $shake = true;
            } elseif ($password !== $confirm) {
                $pageError = "Passwords do not match";
                $pageField = "confirm_password";
                $shake = true;
            } else {
                $users = readJson($usersFile, []);
                if (!is_array($users)) $users = [];

                $updated = false;
                foreach ($users as $uname => $u) {
                    if (!is_array($u)) continue;
                    if (isset($u['email']) && strcasecmp($u['email'], $tokenEmail) === 0) {
                        $users[$uname]['password'] = password_hash($password, PASSWORD_BCRYPT);
                        $updated = true;
                        break;
                    }
                }

                if (!$updated) {
                    $pageError = "User not found";
                    $shake = true;
                } else {
                    if (!writeJson($usersFile, $users)) {
                        throw new Exception("Failed to write users.json");
                    }

                    // Удаляем использованный токен
                    $tokens = array_values(array_filter($tokens, fn($t) => ($t['token'] ?? '') !== $token));
                    @writeJson($tokensFile, $tokens);

                    $_SESSION['success'] = "Password successfully changed. Please login.";
                    header("Location: ../login.php");
                    exit;
                }
            }
        }
    } else {
        // GET
        if ($token === '') {
            $pageError = "Invalid reset link";
        } elseif (!$tokenValid) {
            $pageError = "Invalid or expired reset link";
        }
    }

} catch (Throwable $e) {
    error_log("reset_password.php error: " . $e->getMessage());
    $pageError = "Server error. Please try again later.";
    $shake = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | DartWorld</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --error: #ff3860;
            --success: #09c372;
            --text: #2c3e50;
            --light: #f5f5f5;
            --white: #ffffff;
            --gray: #e0e0e0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
        }

        .login-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            margin: 1rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
            animation: fadeIn 0.6s ease-out;
        }

        .login-container.shake { animation: shake 0.5s; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-10px); }
            40%, 80% { transform: translateX(10px); }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-weight: 600;
            font-size: 1.8rem;
        }

        .input-group { margin-bottom: 1.25rem; position: relative; }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }

        .input-group.error input { border-color: var(--error); }
        .input-group.error input:focus { box-shadow: 0 0 0 3px rgba(255, 56, 96, 0.1); }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            height: 0;
        }
        .error-message.show {
            opacity: 1;
            transform: translateY(0);
            height: auto;
            margin-top: 0.5rem;
        }
        .error-message i { margin-right: 0.5rem; font-size: 1rem; }

        .login-button {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .login-button i { margin-right: 0.5rem; }

        .links-container {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        .auth-link {
            color: var(--text);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .auth-link:hover { color: var(--primary); text-decoration: underline; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .info {
            font-size: 0.9rem;
            opacity: 0.85;
            text-align: center;
            margin-top: -0.5rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
<div class="login-container <?php echo $shake ? 'shake' : ''; ?>">
    <h2>Reset Password</h2>
    <div class="info">Enter a new password for your account</div>

    <?php if ($pageError): ?>
        <div class="error-message show" style="justify-content:center;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($pageError); ?>
        </div>
    <?php endif; ?>

    <?php if ($token !== '' && $tokenValid): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">

            <div class="input-group <?php echo ($pageField === 'password') ? 'error' : ''; ?>">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message <?php echo ($pageField === 'password') ? 'show' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $pageField === 'password' ? htmlspecialchars($pageError ?? '') : ''; ?>
                </div>
            </div>

            <div class="input-group <?php echo ($pageField === 'confirm_password') ? 'error' : ''; ?>">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div class="error-message <?php echo ($pageField === 'confirm_password') ? 'show' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $pageField === 'confirm_password' ? htmlspecialchars($pageError ?? '') : ''; ?>
                </div>
            </div>

            <button type="submit" class="login-button">
                <i class="fas fa-key"></i> Change Password
            </button>

            <div class="links-container">
                <a href="../login.php" class="auth-link register">Back to Login</a>
                <a href="reset_form.php" class="auth-link forgot-password">Request new link</a>
            </div>
        </form>
    <?php else: ?>
        <div class="links-container">
            <a href="../login.php" class="auth-link register">Back to Login</a>
            <a href="reset_form.php" class="auth-link forgot-password">Request new link</a>
        </div>
    <?php endif; ?>
</div>

<script>
    // убираем красную подсветку при вводе
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const group = this.closest('.input-group');
            if (group && group.classList.contains('error')) {
                group.classList.remove('error');
                const errorMsg = group.querySelector('.error-message');
                if (errorMsg) errorMsg.classList.remove('show');
            }
        });
    });

    const box = document.querySelector('.login-container');
    if (box.classList.contains('shake')) {
        box.addEventListener('animationend', () => box.classList.remove('shake'));
    }
</script>
</body>
</html>
