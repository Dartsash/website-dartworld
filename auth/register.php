<?php
session_start();

// Проверка бана перед всеми другими проверками
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка reCAPTCHA
    $recaptcha_secret = '6Ld2EwkqAAAAAC1FLfdSaMhMaFkGYLPLWLKoaUI9';
    $response = $_POST['g-recaptcha-response'] ?? '';
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => $recaptcha_secret, 'response' => $response];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $reCaptchaResult = json_decode($result);
    
    if (!$reCaptchaResult->success) {
        $_SESSION['error'] = [
            'type' => 'captcha',
            'message' => 'Please complete the CAPTCHA verification.',
            'shake' => true
        ];
        header("Location: register.php");
        exit;
    }
    
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Валидация
    if (strlen($username) > 9 || preg_match('/[А-Яа-яЁё]/u', $username)) {
        $_SESSION['error'] = [
            'type' => 'invalidusername',
            'message' => 'Username must be 1-9 Latin characters (a-z, 0-9, _)',
            'shake' => true,
            'field' => 'username'
        ];
        header("Location: register.php");
        exit;
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = [
            'type' => 'weakpassword',
            'message' => 'Password must be at least 6 characters',
            'shake' => true,
            'field' => 'password'
        ];
        header("Location: register.php");
        exit;
    }
    
    $usersFile = '../db/users.json';
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
    
    if (isset($users[$username])) {
        $_SESSION['error'] = [
            'type' => 'userexists',
            'message' => 'Username already taken',
            'shake' => true,
            'field' => 'username'
        ];
        header("Location: register.php");
        exit;
    }
    
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            $_SESSION['error'] = [
                'type' => 'emailexists',
                'message' => 'Email already registered',
                'shake' => true,
                'field' => 'email'
            ];
            header("Location: register.php");
            exit;
        }
    }
    
    $users[$username] = [
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'clicks' => 0,
        'admin' => false,
        'registered_at' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    
    $_SESSION['username'] = $username;
    header("Location: loading.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --error: #ff3860;
            --success: #09c372;
            --text: #2c3e50;
            --light: #f5f5f5;
            --white: #ffffff;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
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
        
        .register-container {
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
        }
        
        .register-container.shake {
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-10px); }
            40%, 80% { transform: translateX(10px); }
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
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
        
        .input-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }
        
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }
        
        .input-group.error input {
            border-color: var(--error);
        }
        
        .input-group.error input:focus {
            box-shadow: 0 0 0 3px rgba(255, 56, 96, 0.1);
        }
        
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
        
        .error-message i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        .register-button {
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
        }
        
        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .registered-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .registered-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .g-recaptcha {
            margin: 1.5rem 0;
            display: flex;
            justify-content: center;
        }
        
        /* Анимация появления */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-container {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="register-container <?php echo isset($_SESSION['error']['shake']) && $_SESSION['error']['shake'] ? 'shake' : '' ?>">
        <h2>Create Account</h2>
        
        <form action="register.php" method="post">
            <div class="input-group <?php echo isset($_SESSION['error']['field']) && $_SESSION['error']['field'] === 'username' ? 'error' : '' ?>">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       maxlength="9" pattern="[A-Za-z0-9_]+" 
                       title="1-9 Latin characters (a-z, 0-9, _)"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                <div class="error-message <?php 
                    echo (isset($_SESSION['error']['type']) && 
                        in_array($_SESSION['error']['type'], ['invalidusername', 'userexists'])) ? 'show' : '' ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo isset($_SESSION['error']['message']) ? $_SESSION['error']['message'] : '' ?>
                </div>
            </div>
            
            <div class="input-group <?php echo isset($_SESSION['error']['field']) && $_SESSION['error']['field'] === 'email' ? 'error' : '' ?>">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                <div class="error-message <?php 
                    echo (isset($_SESSION['error']['type']) && 
                        $_SESSION['error']['type'] === 'emailexists') ? 'show' : '' ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo isset($_SESSION['error']['message']) ? $_SESSION['error']['message'] : '' ?>
                </div>
            </div>
            
            <div class="input-group <?php echo isset($_SESSION['error']['field']) && $_SESSION['error']['field'] === 'password' ? 'error' : '' ?>">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message <?php 
                    echo (isset($_SESSION['error']['type']) && 
                        $_SESSION['error']['type'] === 'weakpassword') ? 'show' : '' ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo isset($_SESSION['error']['message']) ? $_SESSION['error']['message'] : '' ?>
                </div>
            </div>
            
            <div class="g-recaptcha" data-sitekey="6Ld2EwkqAAAAAOcmywRpqu63j79Sr562Q_nSF9_n"></div>
            
            <div class="error-message <?php 
                echo (isset($_SESSION['error']['type']) && 
                    $_SESSION['error']['type'] === 'captcha') ? 'show' : '' ?>" style="text-align: center;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo isset($_SESSION['error']['message']) ? $_SESSION['error']['message'] : '' ?>
            </div>
            
            <button type="submit" class="register-button">
                <i class="fas fa-user-plus"></i> Register Now
            </button>
        </form>
        
        <a href="login.php" class="registered-link">
            Already have an account? <strong>Sign In</strong>
        </a>
    </div>

    <script>
        // Убираем сообщение об ошибке при начале ввода
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const errorField = this.closest('.input-group');
                if (errorField && errorField.classList.contains('error')) {
                    errorField.classList.remove('error');
                    const errorMsg = errorField.querySelector('.error-message');
                    if (errorMsg) errorMsg.classList.remove('show');
                }
            });
        });
        
        // Убираем тряску после анимации
        const registerContainer = document.querySelector('.register-container');
        if (registerContainer.classList.contains('shake')) {
            registerContainer.addEventListener('animationend', () => {
                registerContainer.classList.remove('shake');
            });
        }
    </script>
</body>
</html>

<?php
// Очищаем ошибку после показа
if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
}
?>