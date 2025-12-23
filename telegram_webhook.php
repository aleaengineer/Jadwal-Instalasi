<!-- telegram_webhook.php -->
<?php
require_once 'config/database.php';
require_once 'config/bot.php';

// Mendapatkan data dari Telegram
$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) {
    http_response_code(400);
    exit();
}

// Mendapatkan chat_id dan pesan
$chat_id = $update['message']['chat']['id'] ?? null;
$message = $update['message']['text'] ?? '';

if (!$chat_id || !$message) {
    exit();
}

// Proses pesan
processMessage($chat_id, $message, $pdo);

function processMessage($chat_id, $message, $pdo) {
    $message = trim($message);
    
    // Perintah bantuan
    if ($message === '/start' || $message === '/help') {
        $help_text = "🤖 <b>Selamat datang di Bot Jadwal WiFi!</b>\n\n";
        $help_text .= "📝 <b>Perintah yang tersedia:</b>\n";
        $help_text .= "/jadwal [tanggal] - Lihat jadwal instalasi\n";
        $help_text .= "Contoh: /jadwal 2025-12-23\n";
        $help_text .= "/hari_ini - Lihat jadwal hari ini\n";
        $help_text .= "/help - Tampilkan bantuan ini\n\n";
        $help_text .= "🔍 Format tanggal: YYYY-MM-DD\n";
        $help_text .= "💡 Contoh: /jadwal 2025-12-23";
        
        sendMessage($chat_id, $help_text);
        return;
    }
    
    // Perintah jadwal hari ini
    if ($message === '/hari_ini') {
        $tanggal = date('Y-m-d');
        showSchedule($chat_id, $tanggal, $pdo);
        return;
    }
    
    // Perintah jadwal dengan tanggal
    if (preg_match('/^\/jadwal\s+(.+)$/', $message, $matches)) {
        $date_input = trim($matches[1]);
        
        // Coba parsing tanggal
        $timestamp = strtotime($date_input);
        if ($timestamp !== false) {
            $tanggal = date('Y-m-d', $timestamp);
            showSchedule($chat_id, $tanggal, $pdo);
        } else {
            sendMessage($chat_id, "❌ Format tanggal tidak valid!\n\nGunakan format: YYYY-MM-DD\nContoh: /jadwal 2025-12-23");
        }
        return;
    }
    
    // Jika tidak ada perintah yang cocok
    sendMessage($chat_id, "❓ Perintah tidak dikenali. Gunakan /help untuk melihat daftar perintah.");
}

function showSchedule($chat_id, $tanggal, $pdo) {
    try {
        // Query untuk mendapatkan jadwal
        $stmt = $pdo->prepare("SELECT * FROM instalasi WHERE tanggal = ? ORDER BY created_at ASC");
        $stmt->execute([$tanggal]);
        $installations = $stmt->fetchAll();
        
        // Hitung jumlah instalasi
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM instalasi WHERE tanggal = ?");
        $countStmt->execute([$tanggal]);
        $total_count = $countStmt->fetchColumn();
        
        if (empty($installations)) {
            $response = "📅 <b>Jadwal Instalasi Tanggal " . date('d M Y', strtotime($tanggal)) . "</b>\n\n";
            $response .= "📭 Tidak ada jadwal instalasi untuk tanggal ini.";
            sendMessage($chat_id, $response);
            return;
        }
        
        // Format response
        $response = "📅 <b>Jadwal Instalasi Tanggal " . date('d M Y', strtotime($tanggal)) . "</b>\n";
        $response .= "📊 Total: " . $total_count . " instalasi (max 15/hari)\n";
        $response .= "🎯 Kapasitas: " . round(($total_count/15)*100) . "%\n\n";
        $response .= "=========================\n\n";
        
        foreach ($installations as $index => $install) {
            $status_icon = '';
            switch ($install['status']) {
                case 'pending':
                    $status_icon = '🟡';
                    break;
                case 'open':
                    $status_icon = '🔵';
                    break;
                case 'success':
                    $status_icon = '🟢';
                    break;
            }
            
            $response .= ($index + 1) . ". " . $status_icon . " <b>" . htmlspecialchars($install['nama']) . "</b>\n";
            $response .= "📱 No HP: " . htmlspecialchars($install['no_hp']) . "\n";
            $response .= "📦 Paket: " . htmlspecialchars($install['paket']) . "\n";
            $response .= "🔑 Username: " . htmlspecialchars($install['username_wifi']) . "\n";
            $response .= "🔐 Password: " . htmlspecialchars($install['password_wifi']) . "\n";
            $response .= "📊 Status: " . ucfirst($install['status']) . "\n";
            $response .= "———————————————\n\n";
        }
        
        sendMessage($chat_id, $response);
        
    } catch (Exception $e) {
        sendMessage($chat_id, "❌ Terjadi kesalahan saat mengambil data: " . $e->getMessage());
    }
}
?>