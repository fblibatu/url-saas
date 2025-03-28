<?php
namespace App\Validators;

class UserValidator {
    public static function validateRegister($data) {
        $errors = [];
        
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors['username'] = "Kullanıcı adı en az 3 karakter olmalıdır";
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Geçerli bir e-posta adresi girin";
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = "Şifre en az 8 karakter olmalıdır";
        }
        
        return $errors;
    }
    
    public static function validateAdCreation($data) {
        $errors = [];
        
        if (empty($data['title']) || strlen($data['title']) < 5) {
            $errors['title'] = "Reklam başlığı en az 5 karakter olmalıdır";
        }
        
        if (empty($data['target_url']) || !filter_var($data['target_url'], FILTER_VALIDATE_URL)) {
            $errors['target_url'] = "Geçerli bir hedef URL girin";
        }
        
        if (empty($data['cpc']) || $data['cpc'] <= 0) {
            $errors['cpc'] = "CPC pozitif bir değer olmalıdır";
        }
        
        return $errors;
    }
    
    public static function validatePaymentMethod($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = "Ödeme yöntemi adı gereklidir";
        }
        
        if (empty($data['code']) || !preg_match('/^[a-z_]+$/', $data['code'])) {
            $errors['code'] = "Geçerli bir kod girin (sadece küçük harf ve alt çizgi)";
        }
        
        return $errors;
    }
}