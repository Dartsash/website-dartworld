<?php
session_start();

// Проверка авторизации и прав администратора
$usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
if (!file_exists($usersFile)) die("Ошибка: файл users.json не найден");

$users = json_decode(file_get_contents($usersFile), true);
$currentUser = $users[$_SESSION['username']] ?? null;

if (!$currentUser || !($currentUser['admin'] ?? false)) {
    header("Location: /index.php");
    exit;
}

// Определяем текущую страницу для подсветки меню
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - DartWorld</title>
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
            --error: #ff4d4d;
            --success: #4dff88;
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
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        
        /* Сообщения */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: rgba(77, 255, 136, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: rgba(255, 77, 77, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        /* Настройки */
        .settings-container {
            background: var(--dark-light);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .settings-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .settings-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(110, 0, 255, 0.1);
            text-decoration: none;
            color: var(--text);
        }
        
        .settings-card:hover {
            background: rgba(110, 0, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .settings-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(110, 0, 255, 0.1);
            color: var(--primary-light);
        }
        
        .settings-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }
        
        .settings-card p {
            font-size: 0.85rem;
            color: var(--text-light);
            line-height: 1.5;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
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
            <h1 class="page-title">Настройки системы</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>
        
        <!-- Сообщения об ошибках/успехе -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Основные настройки -->
        <div class="settings-container">
            <h2 class="settings-title"><i class="fas fa-sliders-h"></i> Основные настройки</h2>
            
            <div class="settings-grid">
                <!-- Основные -->
                <a href="settings_general.php" class="settings-card">
                    <div class="settings-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3>Основные</h3>
                    <p>Название сайта, логотип, контакты и базовые параметры</p>
                </a>
                
                <!-- Внешний вид -->
                <a href="settings_appearance.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <h3>Внешний вид</h3>
                    <p>Темы оформления, цветовая схема, шрифты</p>
                </a>
                
                <!-- Безопасность -->
                <a href="settings_security.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Безопасность</h3>
                    <p>Настройки доступа, пароли, защита данных</p>
                </a>
                
                <!-- Пользователи -->
                <a href="settings_users.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Пользователи</h3>
                    <p>Управление ролями, правами доступа</p>
                </a>
                
                <!-- Кликер -->
                <a href="settings_clicker.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <h3>Кликер</h3>
                    <p>Настройки игры, бонусы, ограничения</p>
                </a>
                
                <!-- Резервные копии -->
                <a href="settings_backup.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Резервные копии</h3>
                    <p>Создание и восстановление резервных копий</p>
                </a>
            </div>
        </div>
        
        <!-- Дополнительные настройки -->
        <div class="settings-container">
            <h2 class="settings-title"><i class="fas fa-tools"></i> Дополнительные настройки</h2>
            
            <div class="settings-grid">
                <!-- Уведомления -->
                <a href="settings_notifications.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(142, 68, 173, 0.1); color: #8e44ad;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Уведомления</h3>
                    <p>Настройка email и системных уведомлений</p>
                </a>
                
                <!-- API -->
                <a href="settings_api.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>API</h3>
                    <p>Настройки интеграций и доступа к API</p>
                </a>
                
                <!-- SEO -->
                <a href="settings_seo.php" class="settings-card">
                    <div class="settings-icon" style="background: rgba(44, 62, 80, 0.1); color: #2c3e50;">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>SEO</h3>
                    <p>Оптимизация для поисковых систем</p>
                </a>
            </div>
        </div>
    </main>
</body>
</html>