<?php
session_start();

// Устанавливаем язык по умолчанию, если он не установлен
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'ru'; // Язык по умолчанию - русский
}

// Обработка смены языка
if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    // Перенаправляем чтобы применить изменения
    header("Location: settings.php");
    exit();
}

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

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit();
}

// Переводы для разных языков
$translations = [
    'en' => [
        'home' => 'Home',
        'settings' => 'Settings',
        'choose_language' => 'Choose Language',
        'english' => 'English',
        'russian' => 'Russian',
        'save' => 'Save Changes',
        'discord' => 'Discord',
        'appearance' => 'Appearance',
        'theme' => 'Theme',
        'dark' => 'Dark',
        'light' => 'Light',
        'system' => 'System',
        'language_settings' => 'Language Settings',
        'ui_settings' => 'UI Settings'
    ],
    'ru' => [
        'home' => 'Главная',
        'settings' => 'Настройки',
        'choose_language' => 'Выберите язык',
        'english' => 'Английский',
        'russian' => 'Русский',
        'save' => 'Сохранить',
        'discord' => 'Discord',
        'appearance' => 'Внешний вид',
        'theme' => 'Тема',
        'dark' => 'Тёмная',
        'light' => 'Светлая',
        'system' => 'Системная',
        'language_settings' => 'Настройки языка',
        'ui_settings' => 'Настройки интерфейса'
    ]
];

// Выбираем переводы на основе текущего языка
$lang = $_SESSION['language'];
$trans = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $trans['settings']; ?> - DartWorld</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg" type="image/jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6e00ff;
            --primary-dark: #4a00b3;
            --secondary: #00ffcc;
            --dark: #0f0f1a;
            --darker: #0a0a12;
            --light: #f0f0ff;
            --lighter: #ffffff;
            --gray: #2a2a3a;
            --neon-glow: 0 0 10px rgba(110, 0, 255, 0.7), 0 0 20px rgba(110, 0, 255, 0.5);
            --neon-glow-hover: 0 0 15px rgba(110, 0, 255, 0.9), 0 0 30px rgba(110, 0, 255, 0.7);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        /* Светлая тема */
        :root.light-theme {
            --dark: #f5f5ff;
            --darker: #e0e0ff;
            --light: #0f0f1a;
            --lighter: #000000;
            --gray: #d5d5e5;
            --neon-glow: 0 0 10px rgba(110, 0, 255, 0.3), 0 0 20px rgba(110, 0, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            min-height: 100vh;
            overflow-x: hidden;
            transition: var(--transition);
        }

        .header {
            background: rgba(15, 15, 26, 0.8);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(110, 0, 255, 0.3);
            transition: var(--transition);
        }

        .light-theme .header {
            background: rgba(245, 245, 255, 0.8);
            border-bottom: 1px solid rgba(110, 0, 255, 0.1);
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 600;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: var(--neon-glow);
            letter-spacing: 1px;
            transition: var(--transition);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .main {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8rem 2rem 4rem;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .settings-container {
            width: 100%;
            max-width: 800px;
            background: rgba(15, 15, 26, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(110, 0, 255, 0.2);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s forwards 0.2s;
        }

        .light-theme .settings-container {
            background: rgba(245, 245, 255, 0.6);
            border: 1px solid rgba(110, 0, 255, 0.1);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: var(--neon-glow);
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 1px;
            text-align: center;
            transition: var(--transition);
        }

        .settings-section {
            margin-bottom: 2.5rem;
        }

        .settings-section h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .settings-section h2::before {
            content: '';
            display: block;
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--light);
            transition: var(--transition);
        }

        select, input[type="submit"] {
            width: 100%;
            padding: 1rem;
            border-radius: 8px;
            border: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
        }

        select {
            background-color: var(--gray);
            color: var(--light);
            border: 1px solid rgba(110, 0, 255, 0.3);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23f0f0ff'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
        }

        .light-theme select {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230f0f1a'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
        }

        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(110, 0, 255, 0.3);
        }

        input[type="submit"] {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--light);
            font-weight: 600;
            cursor: pointer;
            border: none;
            margin-top: 1rem;
            box-shadow: var(--neon-glow);
            position: relative;
            overflow: hidden;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: var(--neon-glow-hover);
        }

        input[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s ease;
        }

        input[type="submit"]:hover::before {
            left: 100%;
        }

        .theme-options {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .theme-option {
            flex: 1;
            position: relative;
        }

        .theme-option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .theme-option label {
            display: block;
            padding: 1rem;
            background-color: var(--gray);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .theme-option input:checked + label {
            border-color: var(--primary);
            background-color: rgba(110, 0, 255, 0.2);
            box-shadow: 0 0 10px rgba(110, 0, 255, 0.3);
        }

        .theme-option label:hover {
            background-color: rgba(110, 0, 255, 0.1);
        }

        .footer {
            background: rgba(15, 15, 26, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid rgba(110, 0, 255, 0.3);
            transition: var(--transition);
        }

        .light-theme .footer {
            background: rgba(245, 245, 255, 0.8);
            border-top: 1px solid rgba(110, 0, 255, 0.1);
        }

        .footer p {
            color: var(--light);
            opacity: 0.7;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .main {
                padding: 7rem 1rem 3rem;
            }

            .settings-container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            .theme-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">DartWorld</div>
        <nav class="nav-links">
            <a href="index.php"><?php echo $trans['home']; ?></a>
            <a href="settings.php"><?php echo $trans['settings']; ?></a>
            <a href="https://discord.gg/Fc5gZZxZ" target="_blank"><?php echo $trans['discord']; ?></a>
        </nav>
    </header>

    <main class="main">
        <div class="settings-container">
            <h1><?php echo $trans['settings']; ?></h1>
            
            <div class="settings-section">
                <h2><?php echo $trans['language_settings']; ?></h2>
                <form method="post" action="settings.php">
                    <div class="form-group">
                        <label for="language"><?php echo $trans['choose_language']; ?></label>
                        <select name="language" id="language">
                            <option value="en" <?php echo $lang == 'en' ? 'selected' : ''; ?>><?php echo $trans['english']; ?></option>
                            <option value="ru" <?php echo $lang == 'ru' ? 'selected' : ''; ?>><?php echo $trans['russian']; ?></option>
                        </select>
                    </div>
                    
                    <div class="settings-section">
                        <h2><?php echo $trans['appearance']; ?></h2>
                        <div class="form-group">
                            <label><?php echo $trans['theme']; ?></label>
                            <div class="theme-options">
                                <div class="theme-option">
                                    <input type="radio" id="dark" name="theme" value="dark" checked>
                                    <label for="dark"><?php echo $trans['dark']; ?></label>
                                </div>
                                <div class="theme-option">
                                    <input type="radio" id="light" name="theme" value="light">
                                    <label for="light"><?php echo $trans['light']; ?></label>
                                </div>
                                <div class="theme-option">
                                    <input type="radio" id="system" name="theme" value="system">
                                    <label for="system"><?php echo $trans['system']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="submit" value="<?php echo $trans['save']; ?>">
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>© DartWorld 2022 - 2025 | All rights reserved</p>
    </footer>

    <script>
        // Анимация элементов при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            const settingsSections = document.querySelectorAll('.settings-section');
            settingsSections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.animation = `fadeInUp 0.5s forwards ${0.3 + index * 0.1}s`;
            });
            
            // Обработка темы (можно доработать для сохранения в localStorage)
            const themeRadios = document.querySelectorAll('input[name="theme"]');
            themeRadios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    document.documentElement.className = e.target.value + '-theme';
                });
            });
        });
    </script>
</body>
</html>