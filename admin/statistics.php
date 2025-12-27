<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
if (!file_exists($usersFile)) die("Ошибка: файл users.json не найден");

$usersData = json_decode(file_get_contents($usersFile), true);
if (!is_array($usersData)) die("Ошибка: users.json сломан");

$currentUser = $usersData[$_SESSION['username']] ?? null;
if (!$currentUser || !($currentUser['admin'] ?? false)) {
    header("Location: /index.php");
    exit;
}

/**
 * ВАЖНО: превращаем usersData (username => data) в список, где username внутри
 */
$usersList = [];
$totalClicks = 0;
$activeUsers = 0;

foreach ($usersData as $uname => $u) {
    if (!is_array($u)) $u = [];
    $clicks = (int)($u['clicks'] ?? 0);

    $totalClicks += $clicks;
    if ($clicks > 0) $activeUsers++;

    $u['username'] = $uname;
    $u['clicks'] = $clicks;
    $usersList[] = $u;
}

$totalUsers = count($usersData);

// сортировка топа
usort($usersList, fn($a, $b) => ($b['clicks'] ?? 0) <=> ($a['clicks'] ?? 0));
$topUsers = array_slice($usersList, 0, 5);

$currentPage = basename($_SERVER['PHP_SELF']);

/**
 * Мониторинг (история)
 * Пишем снапшот в /db/stats_history.json раз в 10 минут (или при пустой истории)
 */
$historyFile = $_SERVER['DOCUMENT_ROOT'] . '/db/stats_history.json';

if (!file_exists($historyFile)) {
    @file_put_contents($historyFile, "[]");
}

$historyRaw = @file_get_contents($historyFile);
$history = json_decode($historyRaw ?: "[]", true);
if (!is_array($history)) $history = [];

$now = time();
$lastTs = (int)($history[count($history) - 1]['ts'] ?? 0);

$shouldAppend = (count($history) === 0) || (($now - $lastTs) >= 600); // 600s = 10 минут

if ($shouldAppend) {
    $topMap = [];
    foreach ($topUsers as $tu) {
        $topMap[$tu['username']] = (int)($tu['clicks'] ?? 0);
    }

    $history[] = [
        'ts' => $now,
        'totalUsers' => $totalUsers,
        'totalClicks' => $totalClicks,
        'activeUsers' => $activeUsers,
        'top' => $topMap
    ];

    // чтобы файл не рос бесконечно: оставляем последние 500 записей
    if (count($history) > 500) {
        $history = array_slice($history, -500);
    }

    @file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
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
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Montserrat', sans-serif; }
        body { background-color:var(--dark); color:var(--text); display:flex; min-height:100vh; }

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
        .logo i { font-size: 1.8rem; }
        .nav-menu { list-style:none; margin-top:2rem; }
        .nav-item { margin-bottom:0.5rem; }
        .nav-link {
            display:flex; align-items:center; gap:0.75rem;
            padding:0.75rem 1rem; color:var(--text-light);
            text-decoration:none; border-radius:6px; transition:all .2s;
        }
        .nav-link:hover, .nav-link.active { background:rgba(110,0,255,.2); color:var(--primary-light); }
        .nav-link i { width:20px; text-align:center; }

        .main-content { flex:1; margin-left:var(--sidebar-width); padding:2rem; }
        .header {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:2rem; padding-bottom:1.5rem;
            border-bottom:1px solid rgba(255,255,255,.1);
        }
        .page-title { font-size:1.75rem; font-weight:600; }
        .user-info { display:flex; align-items:center; gap:1rem; }
        .user-avatar {
            width:40px; height:40px; border-radius:50%;
            background:var(--primary); color:white;
            display:flex; align-items:center; justify-content:center; font-weight:bold;
        }

        .stats-grid {
            display:grid;
            grid-template-columns: repeat(3, 1fr);
            gap:20px;
            margin-bottom:2rem;
        }
        .stat-card {
            background: var(--dark-light);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .stat-title { font-size:.9rem; color:var(--text-light); margin-bottom:.5rem; }
        .stat-value { font-size:2rem; font-weight:600; color:var(--primary-light); }

        .chart-container {
            background: var(--dark-light);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .chart-title { font-size:1.25rem; margin-bottom:1.5rem; color:var(--text); }

        .admin-badge {
            background: rgba(110, 0, 255, 0.2);
            color: var(--primary-light);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .top-player {
            display:flex; align-items:center;
            padding:1rem;
            border-bottom:1px solid rgba(255,255,255,0.05);
        }
        .top-player-avatar {
            width:40px; height:40px; border-radius:50%;
            background:rgba(110,0,255,.2); color:var(--primary-light);
            display:flex; align-items:center; justify-content:center;
            font-weight:bold; margin-right:1rem;
        }
        .top-player-info { flex:1; }
        .top-player-name { font-weight:600; display:flex; align-items:center; }
        .top-player-clicks { font-size:.85rem; color:var(--text-light); }
        .top-player-rank { font-weight:600; color:var(--primary-light); font-size:1.25rem; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-crown"></i><span>DartWorld</span></div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                    <i class="fas fa-home"></i><span>Главная</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="players.php" class="nav-link <?= ($currentPage == 'players.php') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i><span>Игроки</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="statistics.php" class="nav-link <?= ($currentPage == 'statistics.php') ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i><span>Статистика</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i><span>Настройки</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i><span>Выйти</span>
                </a>
            </li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1 class="page-title">Статистика системы</h1>
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>

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

        <div class="chart-container">
            <h2 class="chart-title">Мониторинг активности топ-5 (по времени)</h2>
            <canvas id="topUsersChart" height="110"></canvas>
        </div>

        <div class="chart-container">
            <h2 class="chart-title">Лучшие игроки (текущий топ)</h2>
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
                    <div class="top-player-rank">#<?= $index + 1 ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

<script>
const history = <?= json_encode($history, JSON_UNESCAPED_UNICODE) ?>;

// labels = время
const labels = history.map(h => {
  const d = new Date(h.ts * 1000);
  return d.toLocaleString('ru-RU', { hour: '2-digit', minute: '2-digit' });
});

// usernames = все, кто попадал в top в истории
const usernames = Array.from(new Set(
  history.flatMap(h => h.top ? Object.keys(h.top) : [])
));

const datasets = usernames.map(name => ({
  label: name,
  data: history.map(h => (h.top && h.top[name] !== undefined) ? h.top[name] : null),
  borderWidth: 2,
  spanGaps: true,
}));

new Chart(document.getElementById('topUsersChart'), {
  type: 'line',
  data: { labels, datasets },
  options: {
    responsive: true,
    plugins: { legend: { display: true } },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(255, 255, 255, 0.1)' },
        ticks: { color: 'rgba(255, 255, 255, 0.7)' }
      },
      x: {
        grid: { color: 'rgba(255, 255, 255, 0.1)' },
        ticks: { color: 'rgba(255, 255, 255, 0.7)' }
      }
    }
  }
});
</script>
</body>
</html>
