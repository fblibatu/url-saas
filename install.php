<?php
require_once __DIR__ . '/app/Core/bootstrap.php';

// Kurulum kontrolü
if (file_exists(__DIR__ . '/.env')) {
    header('Location: /');
    exit;
}

// Varsayılan ödeme yöntemleri
$defaultPaymentMethods = require __DIR__ . '/config/payment.php';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Veritabanı bağlantısını test et
        $dsn = "mysql:host={$_POST['db_host']};port={$_POST['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Veritabanı oluştur
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_POST['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$_POST['db_name']}`");
        
        // Tabloları oluştur
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($sql);
        
        // Varsayılan ödeme yöntemlerini ekle
        $stmt = $pdo->prepare("
            INSERT INTO payment_methods 
            (name, code, status, min_deposit, min_withdrawal, fee_percent, config, created_at, updated_at) 
            VALUES 
            (:name, :code, :status, :min_deposit, :min_withdrawal, :fee_percent, :config, NOW(), NOW())
        ");
        
        foreach ($defaultPaymentMethods['default_methods'] as $method) {
            $stmt->execute([
                ':name' => $method['name'],
                ':code' => $method['code'],
                ':status' => $method['status'] ? 1 : 0,
                ':min_deposit' => $method['min_deposit'],
                ':min_withdrawal' => $method['min_withdrawal'],
                ':fee_percent' => $method['fee_percent'],
                ':config' => json_encode($method['config'])
            ]);
        }
        
        // Yönetici hesabı oluştur
        $hashedPassword = password_hash($_POST['admin_pass'], PASSWORD_BCRYPT);
        
        $pdo->prepare("
            INSERT INTO users 
            (username, email, password, role, balance, email_verified_at, created_at, updated_at) 
            VALUES 
            (?, ?, ?, 'admin', 0.00, NOW(), NOW(), NOW())
        ")->execute([
            $_POST['admin_user'],
            $_POST['admin_email'],
            $hashedPassword
        ]);
        
        // .env dosyasını oluştur
        $envContent = <<<ENV
APP_NAME="{$_POST['site_name']}"
APP_ENV=production
APP_KEY=base64:{$this->generateRandomKey()}
APP_DEBUG=false
APP_URL={$_POST['site_url']}
APP_TIMEZONE=Europe/Istanbul

DB_CONNECTION=mysql
DB_HOST={$_POST['db_host']}
DB_PORT={$_POST['db_port']}
DB_DATABASE={$_POST['db_name']}
DB_USERNAME={$_POST['db_user']}
DB_PASSWORD={$_POST['db_pass']}

AD_NETWORK_CPC=0.0100
AD_NETWORK_EARNINGS_RATE=0.7000
ENV;
        
        file_put_contents(__DIR__ . '/.env', $envContent);
        
        // Kurulum tamamlandı
        header('Location: /install?success=1');
        exit;
    } catch (PDOException $e) {
        $error = "Veritabanı hatası: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Kurulum arayüzünü göster
require_once __DIR__ . '/resources/views/install.php';