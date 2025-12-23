<!-- index.php (update) -->
<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();
$user = getUser();

// Get filter parameters
$date = $_GET['date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Get installations for the selected date with search
$sql = "SELECT * FROM instalasi WHERE tanggal = :tanggal";
$params = [':tanggal' => $date];

if ($status && $status !== 'all') {
    $sql .= " AND status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $sql .= " AND (nama LIKE :search OR username_wifi LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY created_at ASC LIMIT 15";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$installations = $stmt->fetchAll();

// Get installation count for the date
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE tanggal = :tanggal");
$countStmt->execute([':tanggal' => $date]);
$installCount = $countStmt->fetchColumn();

// Get status counts for sidebar
$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE status = 'pending'");
$pendingStmt->execute();
$pendingCount = $pendingStmt->fetchColumn();

$openStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE status = 'open'");
$openStmt->execute();
$openCount = $openStmt->fetchColumn();

$successStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE status = 'success'");
$successStmt->execute();
$successCount = $successStmt->fetchColumn();

$cancleStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE status = 'cancle'");
$cancleStmt->execute();
$cancleCount = $cancleStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Instalasi WiFi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 sidebar p-0">
                <div class="sidebar-header p-3">
                    <h4 class="text-white mb-0">Jadwal App</h4>
                    <p class="text-white-50 small mb-0"><?= htmlspecialchars($user['username']) ?></p>
                </div>

                <div class="sidebar-menu p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a href="index.php" class="nav-link">
                                <i class="bi bi-calendar3 me-2"></i>
                                Jadwal Hari Ini
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a href="jadwal.php" class="nav-link">
                                <i class="bi bi-calendar-week me-2"></i>
                                Lihat Jadwal
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#tambahMenu">
                                <i class="bi bi-plus-circle me-2"></i>
                                Tambah Instalasi
                                <i class="bi bi-chevron-down float-end"></i>
                            </a>
                            <div class="collapse" id="tambahMenu">
                                <ul class="nav flex-column ms-4 mt-2">
                                    <li class="nav-item mb-1">
                                        <a href="tambah.php" class="nav-link py-1 text-white-50">
                                            <i class="bi bi-pencil me-2"></i> Manual
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="import.php" class="nav-link py-1 text-white-50">
                                            <i class="bi bi-file-earmark-spreadsheet me-2"></i> Import Excel
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-stats p-3 mt-3">
                    <h6 class="text-white">Statistik</h6>
                    <div class="stat-item mb-2">
                        <small class="text-white-50">Pending</small>
                        <span class="badge bg-warning float-end"><?= $pendingCount ?></span>
                    </div>
                    <div class="stat-item mb-2">
                        <small class="text-white-50">Proses</small>
                        <span class="badge bg-primary float-end"><?= $openCount ?></span>
                    </div>
                    <div class="stat-item mb-2">
                        <small class="text-white-50">Selesai</small>
                        <span class="badge bg-success float-end"><?= $successCount ?></span>
                    </div>
                    <div class="stat-item mb-2">
                        <small class="text-white-50">Cancle</small>
                        <span class="badge bg-danger float-end"><?= $cancleCount ?></span>
                    </div>

                     <!-- Telegram Bot Info -->
                    <div class="mt-3 pt-2 border-top border-secondary">
                        <h6 class="text-white">Telegram Bot</h6>
                        <small class="text-white-50">
                            <i class="bi bi-telegram"></i> 
                            <a href="setup_webhook.php" class="text-white-50" target="_blank">Setup Webhook</a>
                        </small>
                    </div>
                </div>

                <div class="sidebar-footer p-3">
                    <a href="logout.php" class="btn btn-danger w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Jadwal Instalasi - <?= date('d M Y', strtotime($date)) ?></h2>
                    <div class="d-flex gap-2">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle"
                                data-bs-toggle="dropdown">
                                Status: <?= $status ? ucfirst($status) : 'Semua' ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?date=<?= $date ?>&status=all">Semua</a></li>
                                <li><a class="dropdown-item" href="?date=<?= $date ?>&status=pending">Pending</a></li>
                                <li><a class="dropdown-item" href="?date=<?= $date ?>&status=open">Diproses</a></li>
                                <li><a class="dropdown-item" href="?date=<?= $date ?>&status=success">Selesai</a></li>
                                <li><a class="dropdown-item" href="?date=<?= $date ?>&status=cancle">Cancle</a></li>
                            </ul>
                        </div>
                        <a href="tambah.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </a>
                    </div>
                </div>

                <!-- Search Form -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Cari berdasarkan Nama atau Username..."
                                        value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i> Cari
                                </button>
                            </div>
                            <?php if ($search): ?>
                                <div class="col-md-2">
                                    <a href="?date=<?= $date ?>&status=<?= $status ?>"
                                        class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-x-circle me-1"></i> Reset
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Kapasitas Harian (<?= $installCount ?>/15)</span>
                            <span><?= round(($installCount / 15) * 100) ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-<?php
                            echo $installCount < 5 ? 'success' :
                                ($installCount < 10 ? 'warning' :
                                    ($installCount < 15 ? 'danger' : 'danger'));
                            ?>" role="progressbar" style="width: <?= ($installCount / 15) * 100 ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Search Results Info -->
                <?php if ($search): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                        <?php if (count($installations) > 0): ?>
                            (<?= count($installations) ?> hasil ditemukan)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Installation List -->
                <?php if (empty($installations)): ?>
                    <div class="card text-center p-5">
                        <?php if ($search): ?>
                            <i class="bi bi-search text-muted mb-3" style="font-size: 3rem;"></i>
                            <h4 class="text-muted">Tidak ada hasil pencarian</h4>
                            <p class="text-muted">Tidak ditemukan instalasi dengan nama atau username
                                "<?= htmlspecialchars($search) ?>"</p>
                        <?php else: ?>
                            <i class="bi bi-calendar-x text-muted mb-3" style="font-size: 3rem;"></i>
                            <h4 class="text-muted">Tidak ada instalasi</h4>
                            <p class="text-muted">Untuk tanggal ini belum ada jadwal instalasi.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($installations as $install): ?>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="card installation-card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($install['nama']) ?></h5>
                                                <span class="badge status-badge status-<?= $install['status'] ?>">
                                                    <?= ucfirst($install['status']) ?>
                                                </span>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="edit.php?id=<?= $install['id'] ?>">
                                                            <i class="bi bi-pencil me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="changeStatus(<?= $install['id'] ?>, 'pending')">
                                                            <i class="bi bi-clock me-2"></i>Pending
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="changeStatus(<?= $install['id'] ?>, 'open')">
                                                            <i class="bi bi-arrow-repeat me-2"></i>Diproses
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="changeStatus(<?= $install['id'] ?>, 'success')">
                                                            <i class="bi bi-check-circle me-2"></i>Selesai
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            onclick="changeStatus(<?= $install['id'] ?>, 'cancle')">
                                                            <i class="bi bi-x-circle me-2"></i>Cancle
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="install-details">
                                            <div class="detail-item mb-2">
                                                <i class="bi bi-telephone me-2"></i>
                                                <span><?= htmlspecialchars($install['no_hp']) ?></span>
                                            </div>
                                            <div class="detail-item mb-2">
                                                <i class="bi bi-box-seam me-2"></i>
                                                <span><?= htmlspecialchars($install['paket']) ?></span>
                                            </div>
                                            <div class="detail-item mb-2">
                                                <i class="bi bi-person me-2"></i>
                                                <span><?= htmlspecialchars($install['username_wifi']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="bi bi-key me-2"></i>
                                                <span><?= htmlspecialchars($install['password_wifi']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        function changeStatus(id, status) {
            if (confirm('Ubah status instalasi ini?')) {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id, status: status })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Gagal mengubah status');
                        }
                    });
            }
        }
    </script>
</body>

</html>