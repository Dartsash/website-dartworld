<?php
$flagFile = 'maintenance.flag';

// Включить/выключить режим
if (isset($argv[1])) {
    if ($argv[1] === 'on') {
        file_put_contents($flagFile, '1');
        echo "Режим обслуживания ВКЛЮЧЕН\n";
    } elseif ($argv[1] === 'off') {
        if (file_exists($flagFile)) {
            unlink($flagFile);
        }
        echo "Режим обслуживания ВЫКЛЮЧЕН\n";
    }
} else {
    echo "Использование: php maintenance.php [on|off]\n";
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Технические работы</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f06, #4a90e2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
        }

        .container {
            max-width: 600px;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }

        .logo {
            width: 150px;
            margin-bottom: 1.5rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://i.gifer.com/GcHF.gif" alt="Логотип" class="logo">
        <h1>Технические работы</h1>
        <p>Наш сайт сейчас недоступен по причине технических работ. Пожалуйста, зайдите позже.</p>
        <div class="spinner"></div>
    </div>
</body>
</html>