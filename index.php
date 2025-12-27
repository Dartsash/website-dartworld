<?php
session_start();

// Язык по умолчанию
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'ru';
}

// Переводы
$translations = [
    'en' => [
        'welcome' => 'Welcome to DartWorld',
        'motivating_clicker' => 'A clicker that motivates',
        'enter' => 'Enter',
        'logged_in_as' => 'Logged in as',
        'logout' => 'Logout',
        'discord' => 'Discord',
        'settings' => 'Settings',
        'top_players' => '🏆 Top Players',
        'no_players' => 'No players yet. Be the first!',
        'level' => 'Level',
        'error_loading' => 'Error loading player data',
        'admin_panel' => 'Admin Panel'
    ],
    'ru' => [
        'welcome' => 'Добро пожаловать в DartWorld',
        'motivating_clicker' => 'Кликер, который мотивирует',
        'enter' => 'Войти',
        'logged_in_as' => 'Вы вошли как',
        'logout' => 'Выйти',
        'discord' => 'Discord',
        'settings' => 'Настройки',
        'top_players' => '🏆 Топ игроков',
        'no_players' => 'Пока нет игроков. Будьте первым!',
        'level' => 'Уровень',
        'error_loading' => 'Ошибка загрузки данных игроков',
        'admin_panel' => 'Админ-панель'
    ]
];

$lang = $_SESSION['language'];
if (!isset($translations[$lang])) {
    $lang = 'ru';
    $_SESSION['language'] = 'ru';
}
$trans = $translations[$lang];

// Надёжный путь к users.json (index.php лежит в корне сайта)
$usersFile = __DIR__ . '/db/users.json';

function loadUsers(string $path): array {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) return [];
    return $data;
}

// Проверка авторизации
if (!isset($_SESSION['username'])) {
    header("Location: /auth/login.php");
    exit;
}

$users = loadUsers($usersFile);
$username = (string)$_SESSION['username'];
$currentUser = $users[$username] ?? null;

// Проверка бана
if ($currentUser && ($currentUser['banned'] ?? false)) {
    header("Location: /bans.php");
    exit;
}

// Админ?
$isAdmin = (bool)($currentUser['admin'] ?? false);

// Таблица лидеров
function loadPlayersFromUsers(array $users): array {
    $players = [];
    foreach ($users as $uname => $userData) {
        if (is_array($userData) && isset($userData['clicks'])) {
            $clicks = (int)$userData['clicks'];
            $players[] = [
                'username' => (string)$uname,
                'clicks' => $clicks,
                'level' => intdiv($clicks, 1000) + 1
            ];
        }
    }
    return $players;
}

$players = loadPlayersFromUsers($users);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DartWorld</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6e00ff;
            --primary-dark: #4a00b3;
            --secondary: #00ffcc;
            --dark: #0f0f1a;
            --light: #f0f0ff;
            --neon-glow: 0 0 10px rgba(110, 0, 255, 0.7), 0 0 20px rgba(110, 0, 255, 0.5);
            --neon-glow-hover: 0 0 15px rgba(110, 0, 255, 0.9), 0 0 30px rgba(110, 0, 255, 0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            min-height: 100vh;
        }

        .header {
            background: rgba(15, 15, 26, 0.8);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(110, 0, 255, 0.3);
            animation: fadeInDown 0.5s ease-out;
        }

        .logo {
            justify-self: start;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 600;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: var(--neon-glow);
            letter-spacing: 1px;
        }

        .nav-links {
            justify-self: center;
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a:hover { color: var(--secondary); }

        .admin-link { color: var(--secondary) !important; font-weight: 700; }
        .admin-link i { color: gold; }

        .user-section { justify-self: end; display: flex; align-items: center; }

        .user-info { display: flex; align-items: center; gap: 0.8rem; }

        .user-info .username {
            font-weight: 600;
            font-size: 0.9rem;
            opacity: 0.9;
            margin-right: 0.5rem;
        }

        .user-dropdown { position: relative; display: inline-block; }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--neon-glow);
            user-select: none;
        }

        .avatar:hover { transform: scale(1.1); box-shadow: var(--neon-glow-hover); }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: rgba(30, 30, 50, 0.95);
            min-width: 200px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            z-index: 1;
            padding: 1rem;
            border: 1px solid rgba(110, 0, 255, 0.4);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.3s ease-out;
        }

        .dropdown-content a {
            color: var(--light);
            text-decoration: none;
            display: block;
            transition: all 0.2s;
            border-radius: 5px;
            padding: 0.5rem;
        }

        .dropdown-content a:hover {
            background: rgba(110, 0, 255, 0.3);
            color: var(--secondary);
        }

        .dropdown-content .username {
            display: block;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.8;
            border-bottom: 1px solid rgba(110, 0, 255, 0.3);
        }

        .dropdown-content .logout-btn {
            width: 100%;
            text-align: left;
            background: rgba(255, 0, 64, 0.2);
            color: #ff0040;
            border: 1px solid #ff0040;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .dropdown-content .logout-btn:hover {
            background: rgba(255, 0, 64, 0.4);
            box-shadow: 0 0 10px rgba(255, 0, 64, 0.5);
        }

        .dropdown-content i { width: 20px; text-align: center; margin-right: 0.5rem; }

        .show { display: block; }

        .main-content { padding-top: 6rem; min-height: 100vh; }

        .hero-section {
            text-align: center;
            padding: 6rem 2rem;
            background: url('https://images.unsplash.com/photo-1639762681057-408e52192e55?q=80&w=2232&auto=format&fit=crop') center/cover;
            position: relative;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 15, 26, 0.7);
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }

        h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: 'Orbitron', sans-serif;
        }

        .hero-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }

        .enter-btn {
            background: rgba(110, 0, 255, 0.3);
            color: white;
            border: 2px solid var(--primary);
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }

        .enter-btn:hover {
            background: rgba(110, 0, 255, 0.5);
            transform: translateY(-3px);
            box-shadow: var(--neon-glow-hover);
        }

        .leaderboard-section {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(30, 30, 50, 0.9), rgba(20, 20, 40, 0.9));
            border-radius: 15px;
            border: 1px solid rgba(110, 0, 255, 0.4);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease-out 0.9s both;
        }

        .leaderboard-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: white;
            font-family: 'Orbitron', sans-serif;
            text-shadow: var(--neon-glow);
        }

        .leaderboard-list { list-style: none; }

        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.8rem;
            background: rgba(40, 40, 60, 0.6);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .leaderboard-item:nth-child(1) { animation: fadeInUp 0.5s ease-out 1.1s both; }
        .leaderboard-item:nth-child(2) { animation: fadeInUp 0.5s ease-out 1.2s both; }
        .leaderboard-item:nth-child(3) { animation: fadeInUp 0.5s ease-out 1.3s both; }
        .leaderboard-item:nth-child(4) { animation: fadeInUp 0.5s ease-out 1.4s both; }
        .leaderboard-item:nth-child(5) { animation: fadeInUp 0.5s ease-out 1.5s both; }

        .leaderboard-item:hover {
            background: rgba(110, 0, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(110, 0, 255, 0.3);
        }

        .player-rank { font-weight: bold; width: 40px; text-align: center; font-size: 1.2rem; }

        .player-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(110, 0, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: bold;
        }

        .player-info { flex-grow: 1; }
        .player-name { font-weight: 600; }
        .player-level { font-size: 0.8rem; opacity: 0.7; }

        .player-score { font-weight: bold; color: var(--secondary); min-width: 100px; text-align: right; }

        .top-1 { border-left: 4px solid gold; }
        .top-2 { border-left: 4px solid silver; }
        .top-3 { border-left: 4px solid #cd7f32; }

        .top-1 .player-rank { color: gold; text-shadow: 0 0 8px gold; }
        .top-2 .player-rank { color: silver; text-shadow: 0 0 8px silver; }
        .top-3 .player-rank { color: #cd7f32; text-shadow: 0 0 8px #cd7f32; }

        .no-players, .error-loading { text-align: center; padding: 2rem; opacity: 0.7; animation: fadeIn 0.5s ease-out; }

        .footer {
            text-align: center;
            padding: 2rem;
            background: rgba(15, 15, 26, 0.8);
            margin-top: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            .hero-section { padding: 4rem 1rem; }
            .leaderboard-section { padding: 1.5rem; margin: 2rem auto; }
            .nav-links { gap: 1rem; font-size: 0.9rem; }
            .user-info .username { display: none; }
        }

        /* Если у пользователя включено "уменьшить движения" — не скрываем контент */
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>

<body>
<header class="header">
    <div class="logo">DartWorld</div>

    <nav class="nav-links">
        <a href="https://discord.gg/zxSVV4UZD4">
            <i class="fab fa-discord"></i> <?php echo htmlspecialchars($trans['discord']); ?>
        </a>
        <a href="settings.php">
            <i class="fas fa-cog"></i> <?php echo htmlspecialchars($trans['settings']); ?>
        </a>

        <?php if ($isAdmin): ?>
            <a href="admin/" class="admin-link">
                <i class="fas fa-crown"></i> <?php echo htmlspecialchars($trans['admin_panel']); ?>
            </a>
        <?php endif; ?>
    </nav>

    <div class="user-section">
        <div class="user-info">
            <div class="user-dropdown" id="userDropdown">
                <div class="avatar" onclick="toggleDropdown()">
                    <?php
                    $first = function_exists('mb_substr')
                        ? mb_substr(htmlspecialchars($username), 0, 1)
                        : substr(htmlspecialchars($username), 0, 1);
                    echo $first;
                    ?>
                </div>

                <div class="dropdown-content" id="dropdownContent">
                    <span class="username">
                        <?php echo htmlspecialchars($trans['logged_in_as'] . ' ' . $username); ?>
                    </span>

                    <a href="settings.php"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($trans['settings']); ?></a>

                    <?php if ($isAdmin): ?>
                        <a href="admin/"><i class="fas fa-crown"></i> <?php echo htmlspecialchars($trans['admin_panel']); ?></a>
                    <?php endif; ?>

                    <form method="post" action="logout.php">
                        <button type="submit" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> <?php echo htmlspecialchars($trans['logout']); ?>
                        </button>
                    </form>
                </div>
            </div>

            <span class="username"><?php echo htmlspecialchars($username); ?></span>
        </div>
    </div>
</header>

<main class="main-content">
    <section class="hero-section">
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($trans['welcome']); ?></h1>
            <p class="hero-text"><?php echo htmlspecialchars($trans['motivating_clicker']); ?></p>
            <button class="enter-btn" onclick="location.href='/clicker/click.php'">
                <?php echo htmlspecialchars($trans['enter']); ?>
            </button>
        </div>
    </section>

    <section class="leaderboard-section">
        <h2 class="leaderboard-title"><?php echo htmlspecialchars($trans['top_players']); ?></h2>

        <div class="leaderboard-container">
            <?php
            if (count($players) > 0) {
                usort($players, fn($a, $b) => $b['clicks'] <=> $a['clicks']);

                echo '<ul class="leaderboard-list">';
                foreach (array_slice($players, 0, 5) as $index => $player) {
                    $rank = $index + 1;
                    $rankClass = $rank <= 3 ? "top-$rank" : "";
                    $firstLetter = function_exists('mb_substr')
                        ? mb_substr($player['username'], 0, 1)
                        : substr($player['username'], 0, 1);

                    echo '<li class="leaderboard-item ' . htmlspecialchars($rankClass) . '">';
                    echo '<div class="player-rank">#' . $rank . '</div>';
                    echo '<div class="player-avatar">' . htmlspecialchars($firstLetter) . '</div>';
                    echo '<div class="player-info">';
                    echo '<div class="player-name">' . htmlspecialchars($player['username']) . '</div>';
                    echo '<div class="player-level">' . htmlspecialchars($trans['level']) . ' ' . (int)$player['level'] . '</div>';
                    echo '</div>';
                    echo '<div class="player-score">' . number_format((int)$player['clicks']) . '</div>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="no-players">' . htmlspecialchars($trans['no_players']) . '</p>';
            }
            ?>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© DartWorld 2022-2025 | All rights reserved</p>
</footer>

<script>
    function toggleDropdown() {
        document.getElementById('dropdownContent').classList.toggle('show');
    }

    // Закрыть меню при клике вне
    window.addEventListener('click', function (event) {
        const dropdown = document.getElementById('userDropdown');
        if (!dropdown.contains(event.target)) {
            document.getElementById('dropdownContent').classList.remove('show');
        }
    });
</script>
</body>
</html>
