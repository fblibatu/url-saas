<?php
namespace App\Services;

use App\Models\Ad;
use App\Models\AdImpression;
use App\Models\Url;
use App\Models\User;
use App\Services\PaymentService;
use GeoIp2\Database\Reader;

class AdNetworkService {
    private $geoIpReader;
    private $paymentService;
    
    public function __construct() {
        $this->geoIpReader = new Reader(storage_path('geoip/GeoLite2-City.mmdb'));
        $this->paymentService = new PaymentService();
    }
    
    /**
     * Reklam oluşturur veya günceller
     */
    public function createOrUpdateAd($data, $userId, $adId = null) {
        // Validasyon
        if (empty($data['title']) || empty($data['target_url']) || empty($data['cpc'])) {
            throw new \InvalidArgumentException("Reklam başlığı, hedef URL ve CPC gereklidir");
        }
        
        $cpc = (float)$data['cpc'];
        if ($cpc <= 0) {
            throw new \InvalidArgumentException("CPC pozitif bir değer olmalıdır");
        }
        
        $user = User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException("Kullanıcı bulunamadı");
        }
        
        // Bütçe kontrolü
        $dailyBudget = isset($data['daily_budget']) ? (float)$data['daily_budget'] : null;
        $totalBudget = isset($data['total_budget']) ? (float)$data['total_budget'] : null;
        
        if ($adId) {
            $ad = Ad::find($adId);
            if (!$ad || $ad->user_id != $userId) {
                throw new \InvalidArgumentException("Reklam bulunamadı veya yetkiniz yok");
            }
        } else {
            $ad = new Ad();
            $ad->user_id = $userId;
            $ad->status = 'active';
        }
        
        $ad->title = $data['title'];
        $ad->description = $data['description'] ?? null;
        $ad->target_url = $data['target_url'];
        $ad->image_url = $data['image_url'] ?? null;
        $ad->ad_type = $data['ad_type'] ?? 'banner';
        $ad->cpc = $cpc;
        $ad->daily_budget = $dailyBudget;
        $ad->total_budget = $totalBudget;
        $ad->target_country = $data['target_country'] ?? null;
        $ad->target_device = $data['target_device'] ?? 'all';
        $ad->start_date = $data['start_date'] ?? null;
        $ad->end_date = $data['end_date'] ?? null;
        
        return $ad->save();
    }
    
    /**
     * Reklam gösterimi kaydeder
     */
    public function recordImpression($adId, $urlId = null) {
        $ad = Ad::find($adId);
        if (!$ad || $ad->status !== 'active') {
            return false;
        }
        
        // Bütçe kontrolü
        if ($this->isOverBudget($ad)) {
            $ad->status = 'paused';
            $ad->save();
            return false;
        }
        
        // Coğrafi konum belirleme
        $countryCode = $this->getCountryCode();
        $deviceType = $this->getDeviceType();
        
        // Gösterimi kaydet
        $impression = new AdImpression();
        $impression->ad_id = $ad->id;
        $impression->url_id = $urlId;
        $impression->ip_address = $_SERVER['REMOTE_ADDR'];
        $impression->country_code = $countryCode;
        $impression->device_type = $deviceType;
        $impression->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        return $impression->save();
    }
    
    /**
     * Reklam tıklaması kaydeder
     */
    public function recordClick($impressionId) {
        $impression = AdImpression::find($impressionId);
        if (!$impression || $impression->clicked) {
            return false;
        }
        
        $ad = Ad::find($impression->ad_id);
        if (!$ad || $ad->status !== 'active') {
            return false;
        }
        
        // Bütçe kontrolü
        if ($this->isOverBudget($ad)) {
            $ad->status = 'paused';
            $ad->save();
            return false;
        }
        
        // Tıklama maliyetini hesapla
        $cost = $ad->cpc;
        
        // Reklamverenin bakiyesini kontrol et
        $advertiser = User::find($ad->user_id);
        if ($advertiser->balance < $cost) {
            $ad->status = 'paused';
            $ad->save();
            return false;
        }
        
        // Tıklamayı kaydet
        $impression->clicked = 1;
        $impression->cost = $cost;
        $impression->save();
        
        // Reklamverenden ücreti al
        $this->paymentService->recordAdSpend($ad->user_id, $cost, $ad->id);
        
        // URL sahibine ödeme yap (eğer varsa)
        if ($impression->url_id) {
            $url = Url::find($impression->url_id);
            if ($url && $url->user_id && $url->monetization) {
                $earnings = $cost * 0.7; // %70 kullanıcıya, %30 site sahibine
                $this->paymentService->recordUrlEarnings($url->user_id, $earnings, $url->id);
            }
        }
        
        return true;
    }
    
    /**
     * Reklam bütçesini kontrol eder
     */
    private function isOverBudget(Ad $ad) {
        // Günlük bütçe kontrolü
        if ($ad->daily_budget) {
            $todaySpent = AdImpression::where('ad_id', $ad->id)
                ->where('clicked', 1)
                ->whereDate('created_at', date('Y-m-d'))
                ->sum('cost');
                
            if ($todaySpent >= $ad->daily_budget) {
                return true;
            }
        }
        
        // Toplam bütçe kontrolü
        if ($ad->total_budget) {
            $totalSpent = AdImpression::where('ad_id', $ad->id)
                ->where('clicked', 1)
                ->sum('cost');
                
            if ($totalSpent >= $ad->total_budget) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ziyaretçinin ülke kodunu belirler
     */
    private function getCountryCode() {
        try {
            $record = $this->geoIpReader->city($_SERVER['REMOTE_ADDR']);
            return $record->country->isoCode;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Ziyaretçinin cihaz tipini belirler
     */
    private function getDeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (stripos($userAgent, 'mobile') !== false) {
            return 'mobile';
        } elseif (stripos($userAgent, 'tablet') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    /**
     * Kullanıcı için uygun reklamları getirir
     */
    public function getAdsForUser($userId = null, $limit = 5) {
        $query = Ad::where('status', 'active')
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', date('Y-m-d H:i:s'));
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', date('Y-m-d H:i:s'));
            })
            ->orderBy('cpc', 'DESC');
        
        // Coğrafi hedefleme
        $countryCode = $this->getCountryCode();
        if ($countryCode) {
            $query->where(function($q) use ($countryCode) {
                $q->whereNull('target_country')
                  ->orWhere('target_country', $countryCode);
            });
        }
        
        // Cihaz hedefleme
        $deviceType = $this->getDeviceType();
        $query->where(function($q) use ($deviceType) {
            $q->where('target_device', 'all')
              ->orWhere('target_device', $deviceType);
        });
        
        // Kullanıcının kendi reklamlarını gösterme
        if ($userId) {
            $query->where('user_id', '!=', $userId);
        }
        
        return $query->limit($limit)->get();
    }
}