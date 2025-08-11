<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo "Not authorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clicks'])) {
        $username = $_SESSION['username'];
        $dbPath = __DIR__ . '/../db/users.json'; // Правильный путь к файлу
        
        // Проверяем существование директории и файла
        if (!file_exists(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0777, true);
        }
        if (!file_exists($dbPath)) {
            file_put_contents($dbPath, '{}');
        }
        
        $users = json_decode(file_get_contents($dbPath), true);
        $users[$username]['clicks'] = intval($_POST['clicks']);
        
        file_put_contents($dbPath, json_encode($users, JSON_PRETTY_PRINT));
        echo "Clicks updated";
    } else {
        echo "No clicks data";
    }
}
?>