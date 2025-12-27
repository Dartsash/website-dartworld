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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset | DartWorld</title>
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
        
        .reset-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            margin: 1rem;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        
        .reset-container::before {
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
        
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
        }
        
        .reset-button {
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
        
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .reset-button i {
            margin-right: 0.5rem;
        }
        
        .login-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .message i {
            margin-right: 0.5rem;
        }
        
        .error {
            background-color: rgba(255, 56, 96, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        .success {
            background-color: rgba(9, 195, 114, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Анимация пульсации для кнопки */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .reset-button.loading {
            animation: pulse 1.5s infinite;
            position: relative;
        }
        
        .reset-button.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(255,255,255,0) 0%, 
                rgba(255,255,255,0.3) 50%, 
                rgba(255,255,255,0) 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form id="resetForm" action="send_reset" method="POST">
            <div class="form-group">
                <label for="email">Enter your email:</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            
            <button type="submit" class="reset-button" id="submitButton">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>
        
        <a href="../login.php" class="login-link">
            Back to Login Page
        </a>
    </div>

    <script>
        // Loading animation on form submission
        document.getElementById('resetForm').addEventListener('submit', function() {
            const button = document.getElementById('submitButton');
            button.classList.add('loading');
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sending...';
        });
        
        // Focus on email field on page load
        document.getElementById('email').focus();
    </script>
</body>
</html>
