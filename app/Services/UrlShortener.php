<?php
namespace App\Services;

use App\Models\Url;
use App\Models\AdImpression;
use App\Services\AdNetworkService;
use Ramsey\Uuid\Uuid;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

class UrlShortener {
    private $adNetworkService;
    
    public function __construct() {
        $this->adNetworkService = new AdNetworkService();
    }
    
    /**
     * URL kısaltma işlemi
     */
    public function shortenUrl($originalUrl, $userId = null, $customCode = null, $meta = []) {
        // URL doğrulama
        if (!$this->validateUrl($originalUrl)) {
            throw new \InvalidArgumentException("Geçersiz URL formatı");
        }
        
        // Özel kod kontrolü
        $code = $customCode ?: $this->generateUniqueCode();
        
        // URL veritabanına kaydet
        $url = new Url();
        $url->user_id = $userId;
        $url->original_url = $originalUrl;
        $url->short_code = $code;
        $url->is_custom = !empty($customCode);
        $url->meta_title = $meta['title'] ?? null;
        $url->meta_description = $meta['description'] ?? null;
        $url->expires_at = $meta['expires_at'] ?? null;
        $url->password = $meta['password'] ?? null;
        $url->utm_source = $meta['utm_source'] ?? null;
        $url->utm_medium = $meta['utm_medium'] ?? null;
        $url->utm_campaign = $meta['utm_campaign'] ?? null;
        $url->monetization = $meta['monetization'] ?? false;
        
        if (!$url->save()) {
            throw new \RuntimeException("URL kaydedilemedi");
        }
        
        // QR kodu oluştur (eğer istenirse)
        if ($meta['generate_qr'] ?? false) {
            $this->generateQrCode($code);
        }
        
        return [
            'short_url' => url($code),
            'code' => $code,
            'qr_code' => ($meta['generate_qr'] ?? false) ? url("qr/$code") : null,
            'stats_url' => $userId ? url("dashboard/url/$code") : null
        ];
    }
    
    /**
     * URL yönlendirme işlemi
     */
    public function redirectUrl($code) {
        // URL'yi veritabanından al
        $url = Url::where('short_code', $code)->first();
        
        if (!$url) {
            throw new \RuntimeException("URL bulunamadı");
        }
        
        // Erişim kontrolleri
        $this->checkUrlAccess($url);
        
        // Reklam gösterimi (eğer monetize edilmişse)
        if ($url->monetization) {
            $this->showAdBeforeRedirect($url);
        }
        
        // UTM parametrelerini ekle
        $finalUrl = $this->appendUtmParameters($url);
        
        return $finalUrl;
    }
    
    /**
     * Reklam gösterimi ve tıklama işlemi
     */
    private function showAdBeforeRedirect(Url $url) {
        // Kullanıcı için uygun reklamları getir
        $ads = $this->adNetworkService->getAdsForUser($url->user_id, 1);
        
        if (count($ads) > 0) {
            $ad = $ads[0];
            
            // Reklam gösterimini kaydet
            $impression = new AdImpression();
            $impression->ad_id = $ad->id;
            $impression->url_id = $url->id;
            $impression->ip_address = $_SERVER['REMOTE_ADDR'];
            $impression->country_code = $this->getCountryCode();
            $impression->device_type = $this->getDeviceType();
            $impression->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            if ($impression->save()) {
                // Reklam sayfasına yönlendir
                $_SESSION['ad_impression_id'] = $impression->id;
                $_SESSION['target_url'] = $this->appendUtmParameters($url);
                header("Location: " . url("ad/" . $ad->id));
                exit();
            }
        }
    }
    
    /**
     * Reklam tıklamasını işle
     */
    public function processAdClick($adId) {
        $impressionId = $_SESSION['ad_impression_id'] ?? null;
        $targetUrl = $_SESSION['target_url'] ?? null;
        
        if ($impressionId && $targetUrl) {
            // Tıklamayı kaydet
            $this->adNetworkService->recordClick($impressionId);
            
            // Hedef URL'e yönlendir
            unset($_SESSION['ad_impression_id']);
            unset($_SESSION['target_url']);
            header("Location: " . $targetUrl);
            exit();
        }
        
        throw new \RuntimeException("Geçersiz reklam tıklaması");
    }
    
    // Diğer yardımcı metodlar...
    private function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    private function generateUniqueCode() {
        // Basit bir rastgele kod üretici
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Kodun benzersiz olduğundan emin ol
        if (Url::where('short_code', $code)->exists()) {
            return $this->generateUniqueCode();
        }
        
        return $code;
    }
    
    private function checkUrlAccess(Url $url) {
        // Şifre korumalı URL kontrolü
        if ($url->password && !isset($_SESSION['url_access'][$url->id])) {
            header("Location: " . url("url-password/" . $url->short_code));
            exit();
        }
        
        // Süre dolmuş URL kontrolü
        if ($url->expires_at && strtotime($url->expires_at) < time()) {
            throw new \RuntimeException("Bu URL'nin süresi dolmuş");
        }
    }
    
    private function appendUtmParameters(Url $url) {
        $originalUrl = $url->original_url;
        $params = [];
        
        if ($url->utm_source) {
            $params['utm_source'] = $url->utm_source;
        }
        if ($url->utm_medium) {
            $params['utm_medium'] = $url->utm_medium;
        }
        if ($url->utm_campaign) {
            $params['utm_campaign'] = $url->utm_campaign;
        }
        
        if (empty($params)) {
            return $originalUrl;
        }
        
        $separator = strpos($originalUrl, '?') === false ? '?' : '&';
        return $originalUrl . $separator . http_build_query($params);
    }
    
    private function generateQrCode($code) {
        $renderer = new Png();
        $renderer->setHeight(256);
        $renderer->setWidth(256);
        
        $writer = new Writer($renderer);
        $filePath = storage_path('qr_codes/' . $code . '.png');
        
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        $writer->writeFile(url($code), $filePath);
    }
    
    private function getCountryCode() {
        try {
            $reader = new Reader(storage_path('geoip/GeoLite2-City.mmdb'));
            $record = $reader->city($_SERVER['REMOTE_ADDR']);
            return $record->country->isoCode;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function getDeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (stripos($userAgent, 'mobile') !== false) {
            return 'mobile';
        } elseif (stripos($userAgent, 'tablet') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }
}