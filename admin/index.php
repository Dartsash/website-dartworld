<?php
// Всегда в первой строке!
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Проверка существования файла
$usersFile = dirname(__DIR__) . '/db/users.json';  // Поднимемся на уровень выше
if (!file_exists($usersFile)) {
    die("Файл users.json не найден по пути: " . $usersFile);
}

// Чтение JSON с проверкой ошибок
$users = json_decode(file_get_contents($usersFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Ошибка в JSON: " . json_last_error_msg());
}

// Проверка авторизации
if (empty($_SESSION['username']) || !isset($users[$_SESSION['username']])) {
    header("Location: /auth/login.php");
    exit;
}

// Проверка прав администратора
if (!$users[$_SESSION['username']]['admin']) {
    die("Доступ запрещен: требуются права администратора");
}

// Определяем текущую страницу для меню
$currentPage = basename($_SERVER['PHP_SELF']);

// Дальше идет ваш HTML-код...
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная панель - DartWorld</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6e00ff;
            --primary-light: #9a4dff;
            --dark: #0f0f1a;
            --dark-light: #1a1a2e;
            --text: #e0e0e0;
            --text-light: #a0a0a0;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background-color: var(--dark);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }
        
        /* Сайдбар */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-light);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(110, 0, 255, 0.1);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo i {
            font-size: 1.8rem;
        }
        
        .nav-menu {
            list-style: none;
            margin-top: 2rem;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(110, 0, 255, 0.2);
            color: var(--primary-light);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        /* Основной контент */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-width: 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            gap: 15px;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .welcome-message {
            background: rgba(110, 0, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }
        
        .quick-overview {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            border-left: 4px solid var(--primary);
        }
        
        .quick-overview h2 {
            margin-top: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .header {
                flex-wrap: wrap;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Сайдбар -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-crown"></i>
            <span>DartWorld</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="players.php" class="nav-link <?= ($currentPage == 'players.php') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Игроки</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="statistics.php" class="nav-link <?= ($currentPage == 'statistics.php') ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Статистика</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Настройки</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выйти</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Основной контент -->
    <main class="main-content">
        <div class="header">
            <h1 class="page-title">Админ панель</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>
        
        <div class="welcome-message">
            <h2>Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
            <p>Вы находитесь в админ-панели DartWorld. Используйте меню слева для навигации.</p>
        </div>
        
        <div class="quick-overview">
            <h2>Быстрый обзор</h2>
            <p>Здесь могут быть краткие статистические данные или важные уведомления.</p>
        </div>
    </main>
</body>
</html>