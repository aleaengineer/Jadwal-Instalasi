<?php
require_once 'config/bot.php';

// Ganti dengan URL webhook Anda (harus HTTPS)
$webhook_url = 'http://localhost:8000/telegram_webhook.php';

echo "<h2>Telegram Bot Webhook Setup</h2>";

// Set webhook
$result = setWebhook($webhook_url);

if ($result['ok']) {
    echo "<p style='color: green;'>✅ Webhook berhasil diset!</p>";
    echo "<p>URL: " . $webhook_url . "</p>";
} else {
    echo "<p style='color: red;'>❌ Gagal menyetel webhook: " . ($result['description'] ?? 'Unknown error') . "</p>";
}

echo "<hr>";

// Get webhook info
$info = getWebhookInfo();
if ($info['ok']) {
    echo "<h3>Webhook Info:</h3>";
    echo "<pre>" . json_encode($info['result'], JSON_PRETTY_PRINT) . "</pre>";
}
?>