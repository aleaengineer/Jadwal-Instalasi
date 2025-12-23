<?php
// Konfigurasi Telegram Bot
define('TELEGRAM_BOT_TOKEN', '8174552460:AAGOaQLf-fvj4mwRdA2sWiH8qejAkz_EhAk');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);

function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($reply_markup) {
        $data['reply_markup'] = $reply_markup;
    }
    
    $ch = curl_init(TELEGRAM_API_URL . '/sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

function getWebhookInfo() {
    $ch = curl_init(TELEGRAM_API_URL . '/getWebhookInfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function setWebhook($url) {
    $data = [
        'url' => $url
    ];
    
    $ch = curl_init(TELEGRAM_API_URL . '/setWebhook');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}
?>