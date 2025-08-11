<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: /index.php");
    exit;
}

$usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';

if (isset($_SESSION['username'])) {
    $usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        $currentUser = $users[$_SESSION['username']] ?? null;
        
        if ($currentUser && ($currentUser['banned'] ?? false)) {
            header("Location: ../bans.php");
            exit;
        }
    }
}

if (!file_exists($usersFile)) {
    die("Error: users.json file not found at: " . $usersFile);
}

$jsonData = file_get_contents($usersFile);
if ($jsonData === false) {
    die("Error: Failed to read users.json");
}

$users = json_decode($jsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("JSON format error: " . json_last_error_msg());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = [
            'message' => 'All fields are required',
            'shake' => true
        ];
        header("Location: login.php");
        exit;
    }

    if (!isset($users[$username])) {
        $_SESSION['login_error'] = [
            'message' => 'Invalid username or password',
            'shake' => true,
            'field' => 'username'
        ];
        header("Location: login.php");
        exit;
    }

    if (password_verify($password, $users[$username]['password'])) {
        $_SESSION['username'] = $username;
        
        // Set admin flag if user is admin
        if ($users[$username]['admin'] ?? false) {
            $_SESSION['admin'] = true;
        }
       
        $_SESSION['login_success'] = true;
        header("Location: /index.php");
        exit;
    }

    $_SESSION['login_error'] = [
        'message' => 'Invalid username or password',
        'shake' => true,
        'field' => 'password'
    ];
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DartWorld</title>
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
        
        .login-container.shake {
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-10px); }
            40%, 80% { transform: translateX(10px); }
        }
        
        .login-container::before {
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
        
        .login-button i {
            margin-right: 0.5rem;
        }
        
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
        
        .auth-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
        }
        
        .register {
            text-align: left;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes successPulse {
            0% { box-shadow: 0 0 0 0 rgba(9, 195, 114, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(9, 195, 114, 0); }
            100% { box-shadow: 0 0 0 0 rgba(9, 195, 114, 0); }
        }
        
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out, successPulse 1.5s ease-in-out 0.3s;
        }
        
        .success-message i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container <?php echo isset($_SESSION['login_error']['shake']) && $_SESSION['login_error']['shake'] ? 'shake' : '' ?>">
        <h2>Welcome Back</h2>
        
        <form action="login.php" method="post">
            <div class="input-group <?php echo isset($_SESSION['login_error']['field']) && $_SESSION['login_error']['field'] === 'username' ? 'error' : '' ?>">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                <div class="error-message <?php 
                    echo (isset($_SESSION['login_error']['field']) && 
                        $_SESSION['login_error']['field'] === 'username') ? 'show' : '' ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo isset($_SESSION['login_error']['message']) ? $_SESSION['login_error']['message'] : '' ?>
                </div>
            </div>
            
            <div class="input-group <?php echo isset($_SESSION['login_error']['field']) && $_SESSION['login_error']['field'] === 'password' ? 'error' : '' ?>">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message <?php 
                    echo (isset($_SESSION['login_error']['field']) && 
                        $_SESSION['login_error']['field'] === 'password') ? 'show' : '' ?>">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo isset($_SESSION['login_error']['message']) ? $_SESSION['login_error']['message'] : '' ?>
                </div>
            </div>
            
            <button type="submit" class="login-button">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div class="links-container">
                <a href="register.php" class="auth-link register">
                    Create Account
                </a>
                <a href="reset/reset_form.php" class="auth-link forgot-password">
                    Forgot Password?
                </a>
            </div>
        </form>
    </div>

    <script>
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
        
        const loginContainer = document.querySelector('.login-container');
        if (loginContainer.classList.contains('shake')) {
            loginContainer.addEventListener('animationend', () => {
                loginContainer.classList.remove('shake');
            });
        }

        <?php if (isset($_SESSION['login_success'])): ?>
            setTimeout(() => {
                const successMsg = document.createElement('div');
                successMsg.className = 'success-message';
                successMsg.innerHTML = '<i class="fas fa-check-circle"></i> Login successful! Redirecting...';
                document.body.appendChild(successMsg);
   
                setTimeout(() => {
                    successMsg.style.opacity = '0';
                    setTimeout(() => successMsg.remove(), 300);
                }, 2000);
            }, 100);
        <?php unset($_SESSION['login_success']); endif; ?>
    </script>
</body>
</html>

<?php
if (isset($_SESSION['login_error'])) {
    unset($_SESSION['login_error']);
}
?>
