<?php
session_start();

$usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
if (!file_exists($usersFile)) die("Ошибка: файл users.json не найден");

$users = json_decode(file_get_contents($usersFile), true);
$currentUser = $users[$_SESSION['username']] ?? null;

if (!$currentUser || !($currentUser['admin'] ?? false)) {
    header("Location: /index.php");
    exit;
}

$totalUsers = count($users);
$totalClicks = array_sum(array_column($users, 'clicks'));
$activeUsers = count(array_filter($users, fn($u) => ($u['clicks'] ?? 0) > 0));

usort($users, fn($a, $b) => ($b['clicks'] ?? 0) <=> ($a['clicks'] ?? 0));
$topUsers = array_slice($users, 0, 5);

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - DartWorld</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Статистические карточки */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--dark-light);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-light);
        }
        
        /* Графики */
        .chart-container {
            background: var(--dark-light);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .chart-title {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--text);
        }
        
        /* Топ пользователей */
        .top-player {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .top-player-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(110, 0, 255, 0.2);
            color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .top-player-info {
            flex: 1;
        }
        
        .top-player-name {
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .top-player-clicks {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .top-player-rank {
            font-weight: 600;
            color: var(--primary-light);
            font-size: 1.25rem;
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
            <h1 class="page-title">Статистика системы</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>
        
        <!-- Статистические карточки -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Всего пользователей</div>
                <div class="stat-value"><?= $totalUsers ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Всего кликов</div>
                <div class="stat-value"><?= number_format($totalClicks) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Активных игроков</div>
                <div class="stat-value"><?= $activeUsers ?></div>
            </div>
        </div>
        
        <!-- График топ пользователей -->
        <div class="chart-container">
            <h2 class="chart-title">Активность топ-5 игроков</h2>
            <canvas id="topUsersChart" height="100"></canvas>
        </div>
        
        <!-- Список топ пользователей -->
        <div class="chart-container">
            <h2 class="chart-title">Лучшие игроки</h2>
            <?php foreach ($topUsers as $index => $user): ?>
                <div class="top-player">
                    <div class="top-player-avatar">
                        <?= strtoupper(substr($user['username'] ?? '?', 0, 1)) ?>
                    </div>
                    <div class="top-player-info">
                        <div class="top-player-name">
                            <?= htmlspecialchars($user['username'] ?? 'Неизвестный') ?>
                            <?php if ($user['admin'] ?? false): ?>
                                <span class="admin-badge">Админ</span>
                            <?php endif; ?>
                        </div>
                        <div class="top-player-clicks">
                            Кликов: <?= number_format($user['clicks'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="top-player-rank">
                        #<?= $index + 1 ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        new Chart(
            document.getElementById('topUsersChart'),
            {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_map(fn($u) => $u['username'], $topUsers)) ?>,
                    datasets: [{
                        label: 'Клики',
                        data: <?= json_encode(array_map(fn($u) => $u['clicks'], $topUsers)) ?>,
                        backgroundColor: '#9a4dff',
                        borderColor: '#6e00ff',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            }
        );
    </script>
</body>
</html>
