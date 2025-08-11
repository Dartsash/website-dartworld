function updateUserClicks($username) {
    $usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
    if (!file_exists($usersFile)) return false;
    
    $users = json_decode(file_get_contents($usersFile), true);
    
    if (!isset($users[$username])) return false;
    
    // Инициализируем структуру кликов, если ее нет
    if (!isset($users[$username]['clicks'])) {
        $users[$username]['clicks'] = [
            'total' => 0,
            'today' => 0,
            'history' => []
        ];
    }
    
    // Увеличиваем счетчики
    $users[$username]['clicks']['total']++;
    $users[$username]['clicks']['today']++;
    
    // Добавляем запись в историю
    $users[$username]['clicks']['history'][] = [
        'timestamp' => time(),
        'page' => basename($_SERVER['PHP_SELF'])
    ];
    
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    return true;
}