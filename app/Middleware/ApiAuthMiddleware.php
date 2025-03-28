<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Models\User;
use App\Helpers\ApiResponse;

class ApiAuthMiddleware extends Middleware {
    public function handle($request, $next) {
        $token = $request->getHeader('Authorization');
        
        if (empty($token)) {
            return ApiResponse::error("Yetkilendirme tokenı gereklidir", 401);
        }
        
        $user = User::where('api_token', $token)
            ->where('api_token_expires', '>', date('Y-m-d H:i:s'))
            ->first();
            
        if (!$user) {
            return ApiResponse::error("Geçersiz veya süresi dolmuş token", 401);
        }
        
        // Kullanıcıyı request'e ekle
        $request->user = $user;
        
        return $next($request);
    }
}