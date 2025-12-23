<!-- navbar.php (update) -->
<?php
require_once 'config/auth.php';
$user = getUser();
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-wifi"></i> Jadwal App
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-calendar3"></i> Hari Ini
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jadwal.php">
                        <i class="bi bi-calendar-week"></i> Jadwal
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-circle"></i> Tambah
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="tambah.php"><i class="bi bi-plus-circle me-2"></i>Tambah Manual</a></li>
                        <li><a class="dropdown-item" href="import.php"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import Excel</a></li>
                    </ul>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
                        <li><a class="dropdown-item" href="setup_webhook.php" target="_blank"><i class="bi bi-telegram me-2"></i>Setup Telegram Bot</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>