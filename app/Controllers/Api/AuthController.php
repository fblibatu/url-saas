<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\User;
use App\Core\Security;
use App\Helpers\ApiResponse;

class AuthController extends Controller {
    public function register($request) {
        $data = $request->getJson();
        
        try {
            // Validasyon
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                throw new \InvalidArgumentException("Tüm alanlar gereklidir");
            }
            
            // Kullanıcı oluştur
            $user = new User();
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->password = Security::hashPassword($data['password']);
            $user->role = 'user';
            
            if (!$user->save()) {
                throw new \RuntimeException("Kullanıcı oluşturulamadı");
            }
            
            // API token oluştur
            $token = $this->generateApiToken($user);
            
            return ApiResponse::success([
                'user' => $user->toArray(),
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
    
    public function login($request) {
        $data = $request->getJson();
        
        try {
            $user = User::where('email', $data['email'])->first();
            
            if (!$user || !Security::verifyPassword($data['password'], $user->password)) {
                throw new \InvalidArgumentException("Geçersiz kimlik bilgileri");
            }
            
            // API token oluştur
            $token = $this->generateApiToken($user);
            
            return ApiResponse::success([
                'user' => $user->toArray(),
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 401);
        }
    }
    
    private function generateApiToken(User $user) {
        $token = bin2hex(random_bytes(32));
        
        // Token'ı veritabanına kaydet
        $user->api_token = $token;
        $user->api_token_expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $user->save();
        
        return $token;
    }
}