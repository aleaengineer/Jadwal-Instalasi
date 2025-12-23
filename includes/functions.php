<!-- includes/functions.php -->
<?php
function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function formatPhoneNumber($number) {
    // Clean the number
    $cleanNumber = preg_replace('/[^0-9]/', '', $number);
    
    // Format for display
    if (strlen($cleanNumber) >= 10) {
        return substr($cleanNumber, 0, 3) . '-' . substr($cleanNumber, 3, 3) . '-' . substr($cleanNumber, 6, 4) . (strlen($cleanNumber) > 10 ? '-' . substr($cleanNumber, 10) : '');
    }
    
    return $number;
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'open' => '<span class="badge bg-primary">Diproses</span>',
        'success' => '<span class="badge bg-success">Selesai</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getStatusClass($status) {
    return 'status-' . $status;
}

function checkDailyLimit($pdo, $date) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE tanggal = ?");
    $stmt->execute([$date]);
    return $stmt->fetchColumn();
}

function generateWifiUsername($name, $prefix = 'wifi_') {
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    return $prefix . substr($cleanName, 0, 20);
}

function generateWifiPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>