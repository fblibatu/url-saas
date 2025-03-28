<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Core\Security;
use App\Services\EmailService;

class AuthController extends Controller {
    public function showLogin() {
        if (auth()->check()) {
            return redirect('/dashboard');
        }
        return view('auth/login');
    }
    
    public function login($request) {
        $data = $request->getParsedBody();
        
        // Doğrulama
        if (empty($data['email']) || empty($data['password'])) {
            return back()->with('error', 'E-posta ve şifre gereklidir');
        }
        
        // Kullanıcıyı bul
        $user = User::findByEmail($data['email']);
        
        if (!$user || !Security::verifyPassword($data['password'], $user->password)) {
            return back()->with('error', 'Geçersiz e-posta veya şifre');
        }
        
        // E-posta doğrulaması kontrolü
        if (!$user->email_verified_at) {
            return back()->with('error', 'Hesabınızı doğrulamanız gerekiyor');
        }
        
        // Oturumu başlat
        auth()->login($user);
        $user->recordLogin();
        
        // Yönlendir
        return redirect('/dashboard');
    }
    
    public function showRegister() {
        return view('auth/register');
    }
    
    public function register($request) {
        $data = $request->getParsedBody();
        
        // Doğrulama
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return back()->with('error', 'Tüm alanlar gereklidir');
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Geçersiz e-posta formatı');
        }
        
        if (strlen($data['password']) < 8) {
            return back()->with('error', 'Şifre en az 8 karakter olmalıdır');
        }
        
        // Kullanıcı var mı kontrol et
        if (User::findByEmail($data['email'])) {
            return back()->with('error', 'Bu e-posta zaten kullanımda');
        }
        
        // Kullanıcı oluştur
        $user = new User();
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password = Security::hashPassword($data['password']);
        $user->role = 'user';
        $user->balance = 0.00;
        
        if (!$user->save()) {
            return back()->with('error', 'Kayıt sırasında bir hata oluştu');
        }
        
        // Doğrulama e-postası gönder
        $emailService = new EmailService();
        $emailService->sendVerificationEmail($user);
        
        // Kullanıcıyı bilgilendir
        return redirect('/login')->with('success', 'Kayıt başarılı! Lütfen e-postanızı doğrulayın.');
    }
    
    public function logout() {
        auth()->logout();
        return redirect('/');
    }
    
    public function verifyEmail($request) {
        $token = $request->getQueryParam('token');
        
        if (empty($token)) {
            return redirect('/login')->with('error', 'Geçersiz doğrulama bağlantısı');
        }
        
        // Token'ı doğrula
        $user = User::where('remember_token', $token)->first();
        
        if (!$user) {
            return redirect('/login')->with('error', 'Geçersiz veya süresi dolmuş doğrulama bağlantısı');
        }
        
        // E-postayı doğrula
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->remember_token = null;
        $user->save();
        
        // Oturumu başlat
        auth()->login($user);
        $user->recordLogin();
        
        return redirect('/dashboard')->with('success', 'E-postanız başarıyla doğrulandı!');
    }
    
    // Diğer auth metodları...
}