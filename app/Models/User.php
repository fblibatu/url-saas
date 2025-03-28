<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    public $id;
    public $username;
    public $email;
    public $password;
    public $avatar;
    public $plan_id;
    public $plan_expires_at;
    public $balance;
    public $role;
    public $email_verified_at;
    public $remember_token;
    public $last_login_at;
    public $last_login_ip;
    public $created_at;
    public $updated_at;
    
    public static function find($id) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetchObject(__CLASS__);
    }
    
    public static function findByEmail($email) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetchObject(__CLASS__);
    }
    
    public function save() {
        $db = Database::getInstance()->getConnection();
        
        if ($this->id) {
            // Güncelleme
            $stmt = $db->prepare("UPDATE users SET 
                username = :username,
                email = :email,
                password = :password,
                avatar = :avatar,
                plan_id = :plan_id,
                plan_expires_at = :plan_expires_at,
                balance = :balance,
                role = :role,
                email_verified_at = :email_verified_at,
                remember_token = :remember_token,
                last_login_at = :last_login_at,
                last_login_ip = :last_login_ip,
                updated_at = NOW()
                WHERE id = :id");
                
            $stmt->bindParam(':id', $this->id);
        } else {
            // Yeni kayıt
            $stmt = $db->prepare("INSERT INTO users (
                username,
                email,
                password,
                avatar,
                plan_id,
                plan_expires_at,
                balance,
                role,
                email_verified_at,
                remember_token,
                last_login_at,
                last_login_ip,
                created_at,
                updated_at
            ) VALUES (
                :username,
                :email,
                :password,
                :avatar,
                :plan_id,
                :plan_expires_at,
                :balance,
                :role,
                :email_verified_at,
                :remember_token,
                :last_login_at,
                :last_login_ip,
                NOW(),
                NOW()
            )");
        }
        
        // Parametreleri bağla
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':avatar', $this->avatar);
        $stmt->bindParam(':plan_id', $this->plan_id);
        $stmt->bindParam(':plan_expires_at', $this->plan_expires_at);
        $stmt->bindParam(':balance', $this->balance);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':email_verified_at', $this->email_verified_at);
        $stmt->bindParam(':remember_token', $this->remember_token);
        $stmt->bindParam(':last_login_at', $this->last_login_at);
        $stmt->bindParam(':last_login_ip', $this->last_login_ip);
        
        $result = $stmt->execute();
        
        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }
        
        return $result;
    }
    
    public function getPlan() {
        if (!$this->plan_id) return null;
        return Plan::find($this->plan_id);
    }
    
    public function hasActivePlan() {
        if (!$this->plan_id) return false;
        return strtotime($this->plan_expires_at) > time();
    }
    
    public function canCreateUrl() {
        if ($this->role === 'admin') return true;
        
        $plan = $this->getPlan();
        if (!$plan) return false;
        
        // Aylık URL limit kontrolü
        $monthlyCount = Url::where('user_id', $this->id)
            ->where('created_at', '>=', date('Y-m-01 00:00:00'))
            ->count();
            
        return $monthlyCount < $plan->monthly_limit;
    }
    
    public function recordLogin() {
        $this->last_login_at = date('Y-m-d H:i:s');
        $this->last_login_ip = $_SERVER['REMOTE_ADDR'];
        $this->save();
    }
}