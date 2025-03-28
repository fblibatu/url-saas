<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Url;
use App\Services\UrlShortener;
use App\Helpers\ApiResponse;

class UrlController extends Controller {
    private $urlShortener;
    
    public function __construct() {
        $this->urlShortener = new UrlShortener();
    }
    
    public function shorten($request) {
        $data = $request->getJson();
        $user = $request->user;
        
        try {
            $result = $this->urlShortener->shortenUrl(
                $data['url'],
                $user->id,
                $data['custom_code'] ?? null,
                [
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'password' => $data['password'] ?? null,
                    'expires_at' => $data['expires_at'] ?? null,
                    'monetization' => $data['monetization'] ?? false,
                    'utm_source' => $data['utm_source'] ?? null,
                    'utm_medium' => $data['utm_medium'] ?? null,
                    'utm_campaign' => $data['utm_campaign'] ?? null
                ]
            );
            
            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
    
    public function getUrlStats($request, $code) {
        $user = $request->user;
        $url = Url::where('short_code', $code)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$url) {
            return ApiResponse::error("URL bulunamadı", 404);
        }
        
        $analytics = new AnalyticsService();
        $stats = $analytics->getUrlStats($url->id);
        
        return ApiResponse::success([
            'url' => $url->toArray(),
            'stats' => $stats
        ]);
    }
    
    public function deleteUrl($request, $code) {
        $user = $request->user;
        $url = Url::where('short_code', $code)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$url) {
            return ApiResponse::error("URL bulunamadı", 404);
        }
        
        if ($url->delete()) {
            return ApiResponse::success(["message" => "URL silindi"]);
        }
        
        return ApiResponse::error("URL silinirken hata oluştu", 500);
    }
}