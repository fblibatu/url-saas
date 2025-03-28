<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Url {
    public $id;
    public $user_id;
    public $original_url;
    public $short_code;
    public $is_custom;
    public $meta_title;
    public $meta_description;
    public $expires_at;
    public $password;
    public $utm_source;
    public $utm_medium;
    public $utm_campaign;
    public $monetization;
    public $created_at;
    public $updated_at;
    
    public static function find($code) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM urls WHERE short_code = :code");
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        return $stmt->fetchObject(__CLASS__);
    }
    
    public function save() {
        $db = Database::getInstance()->getConnection();
        
        if ($this->id) {
            // Güncelleme
            $stmt = $db->prepare("UPDATE urls SET 
                user_id = :user_id,
                original_url = :original_url,
                is_custom = :is_custom,
                meta_title = :meta_title,
                meta_description = :meta_description,
                expires_at = :expires_at,
                password = :password,
                utm_source = :utm_source,
                utm_medium = :utm_medium,
                utm_campaign = :utm_campaign,
                monetization = :monetization,
                updated_at = NOW()
                WHERE id = :id");
                
            $stmt->bindParam(':id', $this->id);
        } else {
            // Yeni kayıt
            $stmt = $db->prepare("INSERT INTO urls (
                user_id,
                original_url,
                short_code,
                is_custom,
                meta_title,
                meta_description,
                expires_at,
                password,
                utm_source,
                utm_medium,
                utm_campaign,
                monetization,
                created_at,
                updated_at
            ) VALUES (
                :user_id,
                :original_url,
                :short_code,
                :is_custom,
                :meta_title,
                :meta_description,
                :expires_at,
                :password,
                :utm_source,
                :utm_medium,
                :utm_campaign,
                :monetization,
                NOW(),
                NOW()
            )");
        }
        
        // Parametreleri bağla
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':original_url', $this->original_url);
        $stmt->bindParam(':short_code', $this->short_code);
        $stmt->bindParam(':is_custom', $this->is_custom, PDO::PARAM_BOOL);
        $stmt->bindParam(':meta_title', $this->meta_title);
        $stmt->bindParam(':meta_description', $this->meta_description);
        $stmt->bindParam(':expires_at', $this->expires_at);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':utm_source', $this->utm_source);
        $stmt->bindParam(':utm_medium', $this->utm_medium);
        $stmt->bindParam(':utm_campaign', $this->utm_campaign);
        $stmt->bindParam(':monetization', $this->monetization, PDO::PARAM_BOOL);
        
        $result = $stmt->execute();
        
        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }
        
        return $result;
    }
    
    // Diğer model metodları...
}