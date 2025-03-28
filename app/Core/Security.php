<?php
namespace App\Core;

class Security {
    /**
     * XSS korumalı input temizleme
     */
    public static function cleanInput($input, $stripTags = true, $encodeSpecialChars = true) {
        if (is_array($input)) {
            return array_map(function($item) use ($stripTags, $encodeSpecialChars) {
                return self::cleanInput($item, $stripTags, $encodeSpecialChars);
            }, $input);
        }

        // Trim işlemi
        $cleaned = trim($input);
        
        // Strip tags (isteğe bağlı)
        if ($stripTags) {
            $cleaned = strip_tags($cleaned);
        }
        
        // HTML özel karakterlerini kodla (isteğe bağlı)
        if ($encodeSpecialChars) {
            $cleaned = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        }
        
        // SQL enjeksiyonuna karşı koruma için ters slash'ları temizle
        $cleaned = stripslashes($cleaned);
        
        return $cleaned;
    }

    /**
     * CSRF token oluştur ve doğrula
     */
    public static function csrfToken($action = 'default') {
        if (empty($_SESSION['csrf_tokens'][$action])) {
            $_SESSION['csrf_tokens'][$action] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_tokens'][$action];
    }

    public static function verifyCsrfToken($token, $action = 'default') {
        if (!isset($_SESSION['csrf_tokens'][$action]) || !hash_equals($_SESSION['csrf_tokens'][$action], $token)) {
            throw new \RuntimeException("Geçersiz CSRF token");
        }
        unset($_SESSION['csrf_tokens'][$action]);
        return true;
    }

    /**
     * Şifre hashleme ve doğrulama
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
            'salt' => random_bytes(16) // PHP 7.0+ için ek güvenlik
        ]);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Rate limiting (hız sınırlama)
     */
    public static function rateLimit($key, $limit = 60, $timeout = 60) {
        $redis = new \Redis();
        $redis->connect(env('REDIS_HOST'), env('REDIS_PORT'));
        
        $current = $redis->get($key);
        
        if ($current && $current >= $limit) {
            throw new \RuntimeException("Çok fazla istek gönderdiniz. Lütfen $timeout saniye sonra tekrar deneyin.");
        }
        
        $redis->multi();
        $redis->incr($key);
        $redis->expire($key, $timeout);
        $redis->exec();
    }

    /**
     * Oturum güvenliği
     */
    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_lifetime', 0);
        
        session_start();
        
        // Oturum fixation koruması
        if (empty($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
            $_SESSION['ip_address'] = self::getClientIp();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } else {
            if ($_SESSION['ip_address'] !== self::getClientIp() || 
                $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                self::destroySession();
                throw new \RuntimeException("Oturum bilgilerinizde tutarsızlık tespit edildi");
            }
        }
    }

    public static function destroySession() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Güvenlik başlıkları
     */
    public static function setSecurityHeaders() {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // CSP (Content Security Policy)
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "img-src 'self' data: https:",
            "font-src 'self' https://cdn.jsdelivr.net",
            "connect-src 'self' https://api.stripe.com",
            "frame-src https://js.stripe.com",
            "form-action 'self'"
        ];
        
        header("Content-Security-Policy: " . implode("; ", $csp));
    }

    /**
     * İki faktörlü kimlik doğrulama
     */
    public static function generateTwoFactorCode($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * IP adresi doğrulama
     */
    public static function getClientIp() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Şifre karmaşıklık kontrolü
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Şifre en az 8 karakter olmalıdır";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Şifre en az bir büyük harf içermelidir";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Şifre en az bir küçük harf içermelidir";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Şifre en az bir rakam içermelidir";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Şifre en az bir özel karakter içermelidir";
        }
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode("\n", $errors));
        }
        
        return true;
    }

    /**
     * Dosya yükleme güvenliği
     */
    public static function secureFileUpload($file, $allowedTypes = [], $maxSize = 2097152) {
        // Dosya hata kontrolü
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Dosya yükleme hatası: " . $file['error']);
        }
        
        // Dosya boyutu kontrolü
        if ($file['size'] > $maxSize) {
            throw new \RuntimeException("Dosya boyutu çok büyük. Maksimum " . ($maxSize / 1024 / 1024) . " MB olmalıdır");
        }
        
        // Dosya tipi kontrolü
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($file['tmp_name']);
        
        if (!empty($allowedTypes) && !in_array($mime, $allowedTypes)) {
            throw new \RuntimeException("Geçersiz dosya türü");
        }
        
        // Dosya adı güvenliği
        $fileName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($file['name']));
        
        return [
            'tmp_name' => $file['tmp_name'],
            'name' => $fileName,
            'type' => $mime,
            'size' => $file['size']
        ];
    }
}