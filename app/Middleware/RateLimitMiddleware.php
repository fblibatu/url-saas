<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Helpers\ApiResponse;
use App\Core\Security;

class RateLimitMiddleware extends Middleware {
    protected $limit = 100; // Dakikada maksimum istek sayısı
    protected $interval = 60; // Saniye cinsinden zaman aralığı
    
    public function handle($request, $next) {
        $key = 'rate_limit:' . ($request->user->id ?? $request->ip());
        
        try {
            Security::rateLimit($key, $this->limit, $this->interval);
            return $next($request);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 429);
        }
    }
}