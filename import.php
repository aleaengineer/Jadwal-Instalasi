<!-- import.php -->
<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

requireLogin();

$error = '';
$success = '';
$imported_count = 0;
$duplicate_count = 0;
$error_count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Terjadi kesalahan saat mengupload file';
    } elseif (!in_array($file['type'], ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])) {
        $error = 'File harus berupa Excel (.xls/.xlsx) atau CSV';
    } else {
        try {
            // Load file
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row (assuming first row is header)
            $header = array_shift($rows);
            
            // Validasi header minimal
            if (count($header) < 6) {
                $error = 'Format file tidak sesuai. File harus memiliki minimal 6 kolom: Tanggal, Nama, No HP, Paket, Username, Password';
            } else {
                foreach ($rows as $index => $row) {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    // Extract data (assuming columns: Tanggal, Nama, No HP, Paket, Username, Password)
                    $tanggal = trim($row[0] ?? '');
                    $nama = trim($row[1] ?? '');
                    $no_hp = trim($row[2] ?? '');
                    $paket = trim($row[3] ?? '');
                    $username_wifi = trim($row[4] ?? '');
                    $password_wifi = trim($row[5] ?? '');
                    
                    // Validate required fields
                    if (empty($tanggal) || empty($nama) || empty($no_hp) || empty($paket) || empty($username_wifi) || empty($password_wifi)) {
                        $errors[] = "Baris " . ($index + 2) . ": Data tidak lengkap";
                        $error_count++;
                        continue;
                    }
                    
                    // Format tanggal (jika dalam format Excel date)
                    if (is_numeric($tanggal)) {
                        $tanggal = date('Y-m-d', PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($tanggal));
                    } else {
                        // Try to parse date
                        $timestamp = strtotime($tanggal);
                        if ($timestamp !== false) {
                            $tanggal = date('Y-m-d', $timestamp);
                        } else {
                            $errors[] = "Baris " . ($index + 2) . ": Format tanggal tidak valid ($tanggal)";
                            $error_count++;
                            continue;
                        }
                    }
                    
                    try {
                        // Check daily limit (15 per day)
                        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE tanggal = ?");
                        $countStmt->execute([$tanggal]);
                        $count = $countStmt->fetchColumn();
                        
                        if ($count >= 15) {
                            $errors[] = "Baris " . ($index + 2) . ": Tanggal $tanggal sudah penuh (15 instalasi)";
                            $error_count++;
                            continue;
                        }
                        
                        // Check for duplicates (same name and date)
                        $checkStmt = $pdo->prepare("SELECT id FROM instalasi WHERE nama = ? AND tanggal = ?");
                        $checkStmt->execute([$nama, $tanggal]);
                        
                        if ($checkStmt->fetch()) {
                            $duplicate_count++;
                            continue;
                        }
                        
                        // Insert data
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
                        
                        $imported_count++;
                        
                    } catch (PDOException $e) {
                        $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                        $error_count++;
                    }
                }
                
                $success = "Import selesai! $imported_count data berhasil diimport";
                if ($duplicate_count > 0) {
                    $success .= ", $duplicate_count data duplikat dilewati";
                }
                if ($error_count > 0) {
                    $success .= ", $error_count data gagal diimport";
                }
            }
            
        } catch (Exception $e) {
            $error = 'Gagal membaca file: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Jadwal - WiFi Admin</title>
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
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                            Import Jadwal dari Excel
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Import Form -->
                        <form method="POST" enctype="multipart/form-data" id="importForm">
                            <div class="mb-4">
                                <label class="form-label">Pilih File Excel</label>
                                <input type="file" class="form-control" name="excel_file" accept=".xls,.xlsx,.csv" required>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    File harus berformat .xls, .xlsx, atau .csv
                                </div>
                            </div>

                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-list-check me-2"></i>
                                        Format File yang Dibutuhkan
                                    </h6>
                                    <ul class="mb-0">
                                        <li>Kolom pertama: Tanggal (format: YYYY-MM-DD atau DD/MM/YYYY)</li>
                                        <li>Kolom kedua: Nama Pelanggan</li>
                                        <li>Kolom ketiga: No HP</li>
                                        <li>Kolom keempat: Paket</li>
                                        <li>Kolom kelima: Username</li>
                                        <li>Kolom keenam: Password</li>
                                        <li>Baris pertama akan dianggap sebagai header</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-success" id="importBtn">
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Import File
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview Sample -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-table me-2"></i>
                            Contoh Format File
                        </h5>
                    </div>
                    <div class="card-body mb-3">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama</th>
                                        <th>No HP</th>
                                        <th>Paket</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <tr>
                                        <td>2025-12-23</td>
                                        <td>FARHAN</td>
                                        <td>08123456789</td>
                                        <td>PROMO-140</td>
                                        <td>skr_farhan</td>
                                        <td>6farhan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Error Details -->
                <?php if (!empty($errors)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Detail Kesalahan Import
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong><?= count($errors) ?> kesalahan ditemukan:</strong>
                        </div>
                        <div class="overflow-auto" style="max-height: 300px;">
                            <ul class="list-group">
                                <?php foreach ($errors as $err): ?>
                                <li class="list-group-item list-group-item-danger">
                                    <?= htmlspecialchars($err) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show loading state during import
        document.getElementById('importForm').addEventListener('submit', function() {
            const btn = document.getElementById('importBtn');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Sedang Mengimport...';
            btn.disabled = true;
        });
    </script>
</body>
</html>