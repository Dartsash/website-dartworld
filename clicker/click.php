<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

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

$dbPath = __DIR__ . '/../db/users.json';

if (!file_exists(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0777, true);
}
if (!file_exists($dbPath)) {
    file_put_contents($dbPath, '{}');
}

$clicks = 0;
$users = json_decode(file_get_contents($dbPath), true);

if (isset($users[$_SESSION['username']])) {
    $clicks = $users[$_SESSION['username']]['clicks'];
    $_SESSION['clicks'] = $clicks;
} else {
    $users[$_SESSION['username']] = ['clicks' => 0];
    file_put_contents($dbPath, json_encode($users, JSON_PRETTY_PRINT));
    $_SESSION['clicks'] = 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DartWorld Clicker</title>
    <link rel="icon" href="https://www.shutterstock.com/image-vector/dw-logo-design-vector-template-600nw-2422008885.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #ff6b6b;
            --text: #f8f9fa;
            --dark: #212529;
            --light: #e9ecef;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }
        
        .header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .user-info {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            width: 100%;
            max-width: 800px;
            text-align: center;
        }
        
        .clicker-container {
            position: relative;
            margin: 2rem 0;
        }
        
        .clicker-button {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: var(--accent);
            border: none;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .clicker-button:hover {
            transform: scale(1.05);
        }
        
        .clicker-button:active {
            transform: scale(0.95);
        }
        
        .clicker-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 70%);
            transform: translate(-50%, -50%) scale(0);
            opacity: 0;
            border-radius: 50%;
            transition: transform 0.5s, opacity 0.5s;
        }
        
        .clicker-button:active::after {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            transition: transform 0s, opacity 0s;
        }
        
        .counter {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .click-effect {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            pointer-events: none;
            animation: float 1s ease-out forwards;
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--x), var(--y)) scale(0);
                opacity: 0;
            }
        }
        
        .leaderboard {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 250px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            padding: 1rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        
        .leaderboard h3 {
            margin-bottom: 1rem;
            color: var(--accent);
            text-align: center;
        }
        
        .leaderboard-list {
            list-style: none;
        }
        
        .leaderboard-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer {
            margin-top: auto;
            padding: 1rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .stats-panel {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 2rem;
            width: 100%;
            max-width: 500px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent);
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .leaderboard {
                position: static;
                width: 100%;
                margin-bottom: 2rem;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
            <div><?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>
    </div>

    <div class="leaderboard">
        <h3>🏆 Топ игроков</h3>
        <div class="leaderboard-content">
            <iframe src="leaderboard.php" width="100%" height="300" style="border:none; border-radius:8px;"></iframe>
        </div>
    </div>

    <div class="main-content">
        <h1>DartWorld Clicker</h1>
        <p>Кликай на кнопку, чтобы зарабатывать очки!</p>
        
        <div class="clicker-container">
            <button id="clickButton" class="clicker-button">CLICK!</button>
        </div>
        
        <div class="counter">
            Очков: <span id="clickCount"><?= $clicks ?></span>
        </div>
        
        <div class="stats-panel">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="clicks-per-second">0</div>
                    <div class="stat-label">Кликов/сек</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="session-clicks">0</div>
                    <div class="stat-label">Кликов за сессию</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>© DartWorld 2025 | Все права защищены</p>
    </div>

    <script>
        const clickButton = document.getElementById('clickButton');
        const clickCount = document.getElementById('clickCount');
        const clicksPerSecond = document.getElementById('clicks-per-second');
        const sessionClicks = document.getElementById('session-clicks');
        
        let clickCounter = <?= $clicks ?>;
        let sessionClickCounter = 0;
        let clickTimes = [];
        let lastClickTime = 0;
        const clickLimit = 15;
        
        clickButton.addEventListener('click', function(e) {
            const now = Date.now();
            if (now - lastClickTime < 1000 / clickLimit) return;
            lastClickTime = now;
            
            clickCounter++;
            sessionClickCounter++;
            clickCount.textContent = clickCounter;
            sessionClicks.textContent = sessionClickCounter;
            
            clickTimes.push(now);
            clickTimes = clickTimes.filter(time => now - time < 1000);
            clicksPerSecond.textContent = clickTimes.length;
            
            createClickEffect(e);
            
            updateClicks();
        });
        
        function createClickEffect(e) {
            const effect = document.createElement('div');
            effect.className = 'click-effect';
            
            const rect = clickButton.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            effect.style.left = `${x}px`;
            effect.style.top = `${y}px`;
            effect.style.setProperty('--x', `${(Math.random() - 0.5) * 100}px`);
            effect.style.setProperty('--y', `${(Math.random() - 0.5) * 100}px`);
            
            clickButton.appendChild(effect);
            
            setTimeout(() => {
                effect.remove();
            }, 1000);
        }
        
        function updateClicks() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_clicks.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(`clicks=${clickCounter}`);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.querySelector('.leaderboard-content iframe').contentWindow.location.reload();
                }
            };
        }
        
        let autoClickerInterval;
        function startAutoClicker() {
            if (!autoClickerInterval) {
                autoClickerInterval = setInterval(() => {
                    const event = { clientX: 100, clientY: 100 };
                    clickButton.dispatchEvent(new MouseEvent('click', event));
                }, 100);
            }
        }

        // startAutoClicker();
    </script>
</body>
</html>
