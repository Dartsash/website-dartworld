<?php
session_start();

$usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';

// Проверка существования файла и авторизации пользователя
if (!file_exists($usersFile) || !isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Получение данных пользователей
$users = json_decode(file_get_contents($usersFile), true);
$currentUser = $users[$_SESSION['username']] ?? null;

// Перенаправление если пользователь не забанен
if (!$currentUser || !($currentUser['banned'] ?? false)) {
    header("Location: index.php");
    exit;
}

// Если код дошёл до этого места - пользователь действительно забанен
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Доступ ограничен</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@500;700&family=Inter:wght@400;500&display=swap');

    :root {
      --primary: #ff3d3d;
      --secondary: #ff7e5f;
      --background: #1c1c1e;
      --glass: rgba(255, 255, 255, 0.05);
      --highlight: #ffccbc;
      --text: #ffffff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
      color: var(--text);
      background: linear-gradient(135deg, #2c2c2e, #1f1f22);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: "";
      position: absolute;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle at center, rgba(255, 63, 63, 0.1), transparent 70%),
                  radial-gradient(circle at top left, rgba(255, 126, 95, 0.08), transparent 70%);
      animation: spinBg 60s linear infinite;
      z-index: 0;
    }

    @keyframes spinBg {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .ban-container {
      background: var(--glass);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
      border-radius: 1.5rem;
      padding: 3rem;
      max-width: 600px;
      width: 90%;
      backdrop-filter: blur(15px);
      z-index: 1;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .ban-container:hover {
      transform: scale(1.02);
    }

    h1 {
      font-family: 'Rubik', sans-serif;
      font-size: 2.5rem;
      margin-bottom: 1rem;
      color: var(--highlight);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.7rem;
    }

    .ban-icon {
      font-size: 2.3rem;
      animation: pulse 1.6s infinite ease-in-out;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }

    p {
      font-size: 1.1rem;
      opacity: 0.9;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .contact-btn {
      display: inline-block;
      padding: 0.85rem 2.2rem;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: none;
      border-radius: 2rem;
      color: white;
      font-weight: 600;
      font-size: 1.05rem;
      text-decoration: none;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(255, 61, 61, 0.3);
      transition: all 0.3s ease;
    }

    .contact-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(255, 126, 95, 0.4);
    }

    .footer {
      margin-top: 2.5rem;
      font-size: 0.85rem;
      opacity: 0.6;
    }

    .corner {
      position: absolute;
      width: 80px;
      height: 80px;
      border: 2px solid rgba(255, 204, 188, 0.15);
    }

    .corner-tl {
      top: 20px;
      left: 20px;
      border-right: none;
      border-bottom: none;
      border-radius: 1rem 0 0 0;
    }

    .corner-br {
      bottom: 20px;
      right: 20px;
      border-left: none;
      border-top: none;
      border-radius: 0 0 1rem 0;
    }

    @media (max-width: 500px) {
      h1 {
        font-size: 2rem;
      }

      .contact-btn {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
      }

      .ban-container {
        padding: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="ban-container">
    <div class="corner corner-tl"></div>
    <div class="corner corner-br"></div>

    <h1><span class="ban-icon">⛔</span> Доступ ограничен</h1>
    <p>Ваша учетная запись была заблокирована за нарушение правил.</p>
    <p>Если вы считаете, что это ошибка — свяжитесь с поддержкой для выяснения подробностей.</p>
    <a class="contact-btn" href="https://discord.gg/zxSVV4UZD4">Написать в поддержку</a>
    <div class="footer">© DartWorld 2022 - 2025 | All rights reserved</div>
  </div>
</body>
</html>