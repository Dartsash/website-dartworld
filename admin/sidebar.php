<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-crown"></i>
        <span>DartWorld</span>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Главная</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="players.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'players.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Игроки</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="statistics.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'statistics.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Статистика</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
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