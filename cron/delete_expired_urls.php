<?php
require __DIR__ . '/../app/Core/bootstrap.php';

use App\Models\Url;

// Süresi dolmuş URL'leri sil
$expiredUrls = Url::where('expires_at', '<=', date('Y-m-d H:i:s'))->get();

foreach ($expiredUrls as $url) {
    $url->delete();
    log_message("Expired URL deleted: " . $url->short_code);
}

// Kullanılmayan kısa kodları temizle
$thresholdDate = date('Y-m-d H:i:s', strtotime('-1 year'));
$inactiveUrls = Url::where('created_at', '<=', $thresholdDate)
    ->whereDoesntHave('statistics')
    ->get();

foreach ($inactiveUrls as $url) {
    $url->delete();
    log_message("Inactive URL deleted: " . $url->short_code);
}

log_message("Cron job completed: delete_expired_urls");