<!-- tambah.php (update with search parameter support) -->
<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

$error = '';
$success = '';

// Get search parameter for redirect back
$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    $nama = trim($_POST['nama']);
    $no_hp = trim($_POST['no_hp']);
    $paket = trim($_POST['paket']);
    $username_wifi = trim($_POST['username_wifi']);
    $password_wifi = trim($_POST['password_wifi']);

    // Validate input
    if (empty($tanggal) || empty($nama) || empty($no_hp) || empty($paket) || empty($username_wifi) || empty($password_wifi)) {
        $error = 'Semua field harus diisi';
    } else {
        try {
            // Check daily limit
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE tanggal = ?");
            $countStmt->execute([$tanggal]);
            $count = $countStmt->fetchColumn();

            if ($count >= 15) {
                $error = 'Tanggal ini sudah penuh (maksimal 15 instalasi per hari)';
            } else {
                // Insert new installation
                $stmt = $pdo->prepare("INSERT INTO instalasi (tanggal, nama, no_hp, paket, username_wifi, password_wifi, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $tanggal,
                    $nama,
                    $no_hp,
                    $paket,
                    $username_wifi,
                    $password_wifi,
                    $_SESSION['user_id']
                ]);

                $success = 'Instalasi berhasil ditambahkan';

                // Reset form or redirect back with search parameter
                if ($search) {
                    header("Location: index.php?date=$tanggal&search=" . urlencode($search));
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Instalasi - WiFi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-plus-circle me-2"></i>
                            Tambah Instalasi Baru
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Instalasi</label>
                                    <input type="date" class="form-control" name="tanggal"
                                        value="<?= htmlspecialchars($_POST['tanggal'] ?? (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'))) ?>"
                                        required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Pelanggan</label>
                                    <input type="text" class="form-control" name="nama"
                                        value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No HP</label>
                                    <input type="text" class="form-control" name="no_hp"
                                        value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Paket</label>
                                    <select class="form-select" name="paket" required>
                                        <option value="">Pilih Paket</option>
                                        <option value="PROMO-140" <?= (($_POST['paket'] ?? '') === 'PROMO-140') ? 'selected' : '' ?>>PROMO-140</option>
                                        <option value="PROMO-166" <?= (($_POST['paket'] ?? '') === 'PROMO-166') ? 'selected' : '' ?>>PROMO-166</option>
                                        <option value="PROMO-200" <?= (($_POST['paket'] ?? '') === 'PROMO-200') ? 'selected' : '' ?>>PROMO-200</option>
                                        <option value="PROMO-250" <?= (($_POST['paket'] ?? '') === 'PROMO-250') ? 'selected' : '' ?>>PROMO-250</option>
                                        <option value="PROMO-350" <?= (($_POST['paket'] ?? '') === 'PROMO-350') ? 'selected' : '' ?>>PROMO-350</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username_wifi"
                                        value="<?= htmlspecialchars($_POST['username_wifi'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="text" class="form-control" name="password_wifi"
                                        value="<?= htmlspecialchars($_POST['password_wifi'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <div class="btn-group" role="group">
                                    <a href="index.php?date=<?= htmlspecialchars($_GET['date'] ?? date('Y-m-d')) ?>&search=<?= htmlspecialchars($search) ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Kembali
                                    </a>
                                    <a href="import.php" class="btn btn-outline-success ms-2">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
                                    </a>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        document.querySelector('input[name="tanggal"]').min = new Date().toISOString().split('T')[0];
    </script>
</body>

</html>