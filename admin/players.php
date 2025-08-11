<?php
session_start();

// Проверка бана перед всеми другими проверками
if (isset($_SESSION['username'])) {
    $usersFile = $_SERVER['DOCUMENT_ROOT'] . '/db/users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        $currentUser = $users[$_SESSION['username']] ?? null;
        
        if ($currentUser && ($currentUser['banned'] ?? false)) {
            header("Location: /bans.php");
            exit;
        }
    }
}

$users = json_decode(file_get_contents($usersFile), true);
$currentUser = $users[$_SESSION['username']] ?? null;

if (!$currentUser || !($currentUser['admin'] ?? false)) {
    header("Location: /index.php");
    exit;
}

// Обработка удаления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $usernameToDelete = $_POST['username'] ?? '';
    
    if ($usernameToDelete === $_SESSION['username']) {
        $_SESSION['error_message'] = "Вы не можете удалить самого себя";
        header("Location: players.php");
        exit;
    }
    
    if (isset($users[$usernameToDelete])) {
        unset($users[$usernameToDelete]);
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $_SESSION['success_message'] = "Пользователь {$usernameToDelete} успешно удалён";
        header("Location: players.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Нельзя удалить этого пользователя";
    }
}

// Обработка бана/разбана
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ban'])) {
    $usernameToBan = $_POST['username'] ?? '';
    
    if (isset($users[$usernameToBan])) {
        $users[$usernameToBan]['banned'] = !($users[$usernameToBan]['banned'] ?? false);
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $_SESSION['success_message'] = "Пользователь {$usernameToBan} " . 
            ($users[$usernameToBan]['banned'] ? "забанен" : "разбанен");
        header("Location: players.php");
        exit;
    }
}

// Обработка изменения пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $usernameToEdit = $_POST['username'] ?? '';
    $newClicks = intval($_POST['clicks'] ?? 0);
    $isAdmin = isset($_POST['is_admin']);
    
    if (isset($users[$usernameToEdit])) {
        // Не позволяем снять админку самому себе
        if ($usernameToEdit === $_SESSION['username'] && !$isAdmin) {
            $_SESSION['error_message'] = "Вы не можете снять права администратора с самого себя";
        } else {
            $users[$usernameToEdit]['clicks'] = $newClicks;
            $users[$usernameToEdit]['admin'] = $isAdmin;
            
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
            $_SESSION['success_message'] = "Данные пользователя {$usernameToEdit} успешно обновлены";
        }
        header("Location: players.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Пользователь не найден";
        header("Location: players.php");
        exit;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление игроками - DartWorld</title>
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
        
        /* Список игроков */
        .players-list {
            background: var(--dark-light);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .player-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .current-user {
            background: rgba(110, 0, 255, 0.05);
            border-left: 3px solid var(--primary);
        }
        
        .player-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .player-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(110, 0, 255, 0.2);
            color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .player-details {
            line-height: 1.4;
        }
        
        .player-name {
            font-weight: 600;
        }
        
        .player-clicks {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .admin-badge {
            background: rgba(110, 0, 255, 0.2);
            color: var(--primary-light);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .player-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-edit {
            background: rgba(110, 0, 255, 0.1);
            color: var(--primary-light);
            border: 1px solid rgba(110, 0, 255, 0.3);
        }
        
        .btn-edit:hover {
            background: rgba(110, 0, 255, 0.2);
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        
        /* Модальные окна */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .modal-content {
            background: var(--dark-light);
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transform: translateY(-50px);
            transition: all 0.3s ease;
            border: 1px solid rgba(110, 0, 255, 0.2);
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text);
            text-align: center;
        }
        
        .modal-text {
            color: var(--text-light);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .modal-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            min-width: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-btn-cancel {
            background: rgba(255,255,255,0.1);
            color: var(--text);
        }
        
        .modal-btn-cancel:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .modal-btn-confirm {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }
        
        .modal-btn-confirm:hover {
            background: rgba(239, 68, 68, 1);
        }
        
        /* Стили для формы редактирования */
        .edit-form-group {
            margin-bottom: 1.5rem;
        }
        
        .edit-form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }
        
        .edit-form-input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            color: var(--text);
        }
        
        .edit-form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .edit-form-checkbox input {
            width: 18px;
            height: 18px;
        }
        
        .modal-btn-save {
            background: var(--primary);
            color: white;
        }
        
        .modal-btn-save:hover {
            background: var(--primary-light);
        }
        .banned-badge {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
.btn-ban {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-unban {
    background: rgba(77, 255, 136, 0.1);
    color: #4dff88;
    border: 1px solid rgba(77, 255, 136, 0.3);
}

.btn-delete {
    background: rgba(255, 51, 51, 0.1);
    color: #ff3333;
    border: 1px solid rgba(255, 51, 51, 0.3);
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
            <h1 class="page-title">Управление игроками</h1>
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
        
        <!-- Список игроков -->
        <div class="players-list">
            <?php foreach ($users as $username => $user): ?>
                <div class="player-item <?= ($username === $_SESSION['username']) ? 'current-user' : '' ?>">
                    <div class="player-info">
                        <div class="player-avatar">
                            <?= strtoupper(substr($username, 0, 1)) ?>
                        </div>
                        <div class="player-details">
                            <div class="player-name">
                                <?= htmlspecialchars($username) ?>
                                <?php if ($user['admin'] ?? false): ?>
                                    <span class="admin-badge">Админ</span>
                                <?php endif; ?>
                                <?php if ($user['banned'] ?? false): ?>
                                    <span class="banned-badge">Забанен</span>
                                <?php endif; ?>
                            </div>
                            <div class="player-clicks">
                                Кликов: <?= number_format($user['clicks'] ?? 0) ?>
                            </div>
                        </div>
                    </div>
<div class="player-actions">
    <!-- Кнопка редактирования -->
    <button class="btn btn-edit" onclick="showEditModal(...)">
        <i class="fas fa-edit"></i>
        <span>Изменить</span>
    </button>
    
    <?php if ($username !== $_SESSION['username']): ?>
        <!-- Кнопка бана/разбана -->
        <form method="POST" style="display: inline;">
            <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            <button type="submit" name="toggle_ban" class="btn <?= ($user['banned'] ?? false) ? 'btn-unban' : 'btn-ban' ?>">
                <i class="fas <?= ($user['banned'] ?? false) ? 'fa-unlock' : 'fa-lock' ?>"></i>
                <span><?= ($user['banned'] ?? false) ? 'Разбанить' : 'Забанить' ?></span>
            </button>
        </form>
        
        <!-- Кнопка удаления -->
        <button class="btn btn-delete" onclick="showDeleteModal('<?= htmlspecialchars($username) ?>')">
            <i class="fas fa-trash"></i>
            <span>Удалить</span>
        </button>
    <?php endif; ?>
</div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3 class="modal-title">Подтвердите действие</h3>
            <p class="modal-text">Вы уверены, что хотите удалить этого пользователя?</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="hideDeleteModal()">
                    <i class="fas fa-times"></i>
                    Нет, отмена
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="username" id="deleteUsername">
                    <button type="submit" name="delete_user" class="modal-btn modal-btn-confirm">
                        <i class="fas fa-check"></i>
                        Да, удалить
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h3 class="modal-title" id="editModalTitle">Редактирование пользователя</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="username" id="editUsername">
                <input type="hidden" name="edit_user" value="1">
                
                <div class="edit-form-group">
                    <label class="edit-form-label">Количество кликов:</label>
                    <input type="number" class="edit-form-input" name="clicks" id="editClicks" min="0" required>
                </div>
                
                <div class="edit-form-checkbox">
                    <input type="checkbox" name="is_admin" id="editIsAdmin">
                    <label>Администратор</label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="hideEditModal()">
                        <i class="fas fa-times"></i>
                        Отмена
                    </button>
                    <button type="submit" class="modal-btn modal-btn-save">
                        <i class="fas fa-save"></i>
                        Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Показ модального окна удаления
        function showDeleteModal(username) {
            document.getElementById('deleteUsername').value = username;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        // Скрытие модального окна удаления
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Показ модального окна редактирования
        function showEditModal(username, clicks, isAdmin) {
            document.getElementById('editUsername').value = username;
            document.getElementById('editClicks').value = clicks;
            document.getElementById('editIsAdmin').checked = isAdmin;
            
            // Обновляем заголовок
            document.getElementById('editModalTitle').textContent = 
                `Редактирование: ${username}`;
            
            document.getElementById('editModal').classList.add('active');
        }
        
        // Скрытие модального окна редактирования
        function hideEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Закрытие при клике вне окна
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                hideDeleteModal();
                hideEditModal();
            }
        });
    </script>
</body>
</html>