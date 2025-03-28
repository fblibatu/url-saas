<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Url;
use App\Models\Statistic;
use App\Models\AdImpression;

class AnalyticsService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getTotalClicks() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM statistics");
        return $stmt->fetch()['total'];
    }
    
    public function getTotalRevenue() {
        $stmt = $this->db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
        return $stmt->fetch()['total'] ?? 0;
    }
    
    public function getClickStatsLast30Days() {
        $stmt = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM statistics 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        $results = $stmt->fetchAll();
        
        // Eksik günleri tamamla
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = 0;
        }
        
        foreach ($results as $row) {
            $data[$row['date']] = (int)$row['count'];
        }
        
        return $data;
    }
    
    public function getRevenueStatsLast30Days() {
        $stmt = $this->db->query("
            SELECT DATE(created_at) as date, SUM(amount) as total 
            FROM payments 
            WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        $results = $stmt->fetchAll();
        
        // Eksik günleri tamamla
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = 0.00;
        }
        
        foreach ($results as $row) {
            $data[$row['date']] = (float)$row['total'];
        }
        
        return $data;
    }
    
    public function getUrlStats($urlId, $period = '30d') {
        // Period: 7d, 30d, 90d, all
        $interval = $this->getIntervalFromPeriod($period);
        
        $query = "
            SELECT 
                DATE(s.created_at) as date,
                COUNT(s.id) as clicks,
                SUM(CASE WHEN ai.id IS NOT NULL THEN 1 ELSE 0 END) as ad_impressions,
                SUM(CASE WHEN p.id IS NOT NULL THEN p.amount ELSE 0 END) as revenue
            FROM statistics s
            LEFT JOIN ad_impressions ai ON ai.url_id = s.url_id AND DATE(ai.created_at) = DATE(s.created_at)
            LEFT JOIN payments p ON p.metadata->>'$.url_id' = s.url_id AND DATE(p.created_at) = DATE(s.created_at)
            WHERE s.url_id = :url_id
        ";
        
        if ($interval) {
            $query .= " AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL $interval)";
        }
        
        $query .= " GROUP BY DATE(s.created_at) ORDER BY date ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':url_id', $urlId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getUserStats($userId, $period = '30d') {
        $interval = $this->getIntervalFromPeriod($period);
        
        $query = "
            SELECT 
                u.short_code,
                u.original_url,
                COUNT(s.id) as total_clicks,
                COUNT(ai.id) as total_impressions,
                SUM(CASE WHEN p.id IS NOT NULL THEN p.amount ELSE 0 END) as total_revenue
            FROM urls u
            LEFT JOIN statistics s ON s.url_id = u.id
            LEFT JOIN ad_impressions ai ON ai.url_id = u.id
            LEFT JOIN payments p ON p.metadata->>'$.url_id' = u.id
            WHERE u.user_id = :user_id
        ";
        
        if ($interval) {
            $query .= " AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL $interval)";
        }
        
        $query .= " GROUP BY u.id ORDER BY total_clicks DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    private function getIntervalFromPeriod($period) {
        switch ($period) {
            case '7d': return '7 DAY';
            case '30d': return '30 DAY';
            case '90d': return '90 DAY';
            case 'all': return null;
            default: return '30 DAY';
        }
    }
    
    public function getTopCountries($urlId = null, $limit = 5) {
        $query = "
            SELECT 
                country_code,
                COUNT(*) as clicks
            FROM statistics
            WHERE country_code IS NOT NULL
        ";
        
        if ($urlId) {
            $query .= " AND url_id = :url_id";
        }
        
        $query .= " GROUP BY country_code ORDER BY clicks DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        
        if ($urlId) {
            $stmt->bindParam(':url_id', $urlId);
        }
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getDeviceStats($urlId = null) {
        $query = "
            SELECT 
                device_type,
                COUNT(*) as clicks
            FROM statistics
            WHERE device_type IS NOT NULL
        ";
        
        if ($urlId) {
            $query .= " AND url_id = :url_id";
        }
        
        $query .= " GROUP BY device_type ORDER BY clicks DESC";
        
        $stmt = $this->db->prepare($query);
        
        if ($urlId) {
            $stmt->bindParam(':url_id', $urlId);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}