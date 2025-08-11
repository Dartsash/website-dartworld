<?php
session_start();

$users = json_decode(file_get_contents('../db/users.json'), true);
$leaderboard = [];

// Формируем массив лидеров, исключая пользователей с 0 кликов
foreach ($users as $username => $data) {
    if (isset($data['clicks']) && $data['clicks'] > 0) {
        $leaderboard[] = ['username' => $username, 'clicks' => $data['clicks']];
    }
}

// Сортируем лидеров по количеству кликов в убывающем порядке
usort($leaderboard, function($a, $b) {
    return $b['clicks'] - $a['clicks'];
});

// Оставляем только топ-5 лидеров
$leaderboard = array_slice($leaderboard, 0, 5);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: white;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 10px;
        }
        h2 {
            margin-top: 0;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h2>Топ по кликам</h2>
    <ul>
        <?php foreach ($leaderboard as $leader): ?>
            <li><?php echo htmlspecialchars($leader['username']); ?>: <?php echo $leader['clicks']; ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>