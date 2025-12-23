<!-- jadwal.php (update with search) -->
<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

// Get date range (current month by default)
$month = $_GET['month'] ?? date('Y-m');
$first_day = date('Y-m-01', strtotime($month));
$last_day = date('Y-m-t', strtotime($month));

// Get installations for the month
$stmt = $pdo->prepare("SELECT tanggal, COUNT(*) as count FROM instalasi WHERE tanggal BETWEEN ? AND ? GROUP BY tanggal");
$stmt->execute([$first_day, $last_day]);
$installations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Generate calendar data
$days_in_month = date('t', strtotime($month));
$first_day_of_week = date('w', strtotime($first_day));
$weeks = ceil(($days_in_month + $first_day_of_week) / 7);

// Get search term for global search
$search = $_GET['search'] ?? '';
$search_results = [];

if ($search) {
    $searchStmt = $pdo->prepare("SELECT * FROM instalasi WHERE nama LIKE ? OR username_wifi LIKE ? ORDER BY tanggal ASC");
    $searchStmt->execute(["%$search%", "%$search%"]);
    $search_results = $searchStmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Instalasi - WiFi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan Nama atau Username WiFi..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                    </div>
                    <?php if ($search): ?>
                    <div class="col-md-2">
                        <a href="?month=<?= $month ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Search Results -->
        <?php if ($search): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list me-2"></i>
                    Hasil Pencarian untuk "<?= htmlspecialchars($search) ?>"
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($search_results)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">Tidak ditemukan hasil pencarian</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>Username WiFi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_results as $result): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($result['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($result['nama']) ?></td>
                                    <td><?= htmlspecialchars($result['username_wifi']) ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= $result['status'] ?>">
                                            <?= ucfirst($result['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?date=<?= $result['tanggal'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-calendar me-1"></i> Lihat
                                        </a>
                                        <a href="edit.php?id=<?= $result['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Calendar Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Jadwal Instalasi</h2>
            <div class="d-flex gap-2">
                <a href="?month=<?= date('Y-m', strtotime($month . ' -1 month')) ?>&search=<?= htmlspecialchars($search) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <h4 class="mb-0 mx-3"><?= date('F Y', strtotime($month)) ?></h4>
                <a href="?month=<?= date('Y-m', strtotime($month . ' +1 month')) ?>&search=<?= htmlspecialchars($search) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Calendar -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">Minggu</th>
                                <th class="text-center">Senin</th>
                                <th class="text-center">Selasa</th>
                                <th class="text-center">Rabu</th>
                                <th class="text-center">Kamis</th>
                                <th class="text-center">Jumat</th>
                                <th class="text-center">Sabtu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $day_counter = 1;
                            for ($week = 0; $week < $weeks; $week++): ?>
                                <tr style="height: 100px;">
                                    <?php for ($day = 0; $day < 7; $day++):
                                        $is_current_month = ($week == 0 && $day < $first_day_of_week) ? false : ($day_counter <= $days_in_month);
                                        $current_date = '';
                                        
                                        if ($is_current_month) {
                                            $current_date = date('Y-m-d', strtotime($month . '-' . str_pad($day_counter, 2, '0', STR_PAD_LEFT)));
                                            $count = $installations[$current_date] ?? 0;
                                            $day_counter++;
                                        }
                                        ?>
                                        <td class="p-1 align-top" style="height: 100px; width: 14.28%;">
                                            <?php if ($is_current_month): ?>
                                                <div class="text-end">
                                                    <small class="text-muted"><?= date('j', strtotime($current_date)) ?></small>
                                                </div>
                                                <?php if ($count > 0): ?>
                                                    <a href="index.php?date=<?= $current_date ?>&search=<?= htmlspecialchars($search) ?>" class="btn btn-sm btn-outline-primary w-100 mt-1 position-relative">
                                                        <?= $count ?> Instalasi
                                                        <?php if ($count >= 15): ?>
                                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                                Penuh
                                                            </span>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="tambah.php?date=<?= $current_date ?>&search=<?= htmlspecialchars($search) ?>" class="btn btn-sm btn-outline-secondary w-100 mt-1">
                                                        Tambah
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Keterangan</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-2" style="width: 20px; height: 20px; background-color: #0d6efd; border-radius: 4px;"></div>
                            <small>Instalasi terjadwal</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="position-relative me-2" style="width: 20px; height: 20px; background-color: #6c757d; border-radius: 4px;">
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5rem;">Penuh</span>
                            </div>
                            <small>Kapasitas penuh (15/hari)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="me-2" style="width: 20px; height: 20px; background-color: #6c757d; border: 1px dashed #dee2e6; border-radius: 4px;"></div>
                            <small>Tidak ada instalasi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>