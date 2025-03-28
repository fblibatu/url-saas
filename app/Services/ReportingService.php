<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Url;
use App\Models\Ad;
use App\Models\Payment;

class ReportingService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateUserReport($userId, $startDate, $endDate) {
        $report = [
            'urls' => [],
            'ads' => [],
            'earnings' => 0,
            'spendings' => 0
        ];
        
        // URL istatistikleri
        $report['urls'] = Url::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->withCount(['statistics as clicks' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withSum(['payments as earnings' => function($query) use ($startDate, $endDate) {
                $query->where('type', 'url_earnings')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'net_amount')
            ->get()
            ->toArray();
        
        // Reklam istatistikleri
        $report['ads'] = Ad::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->withCount(['impressions as impressions' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withCount(['impressions as clicks' => function($query) use ($startDate, $endDate) {
                $query->where('clicked', 1)
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withSum(['payments as spendings' => function($query) use ($startDate, $endDate) {
                $query->where('type', 'ad_spend')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'net_amount')
            ->get()
            ->toArray();
        
        // Toplam kazanç ve harcamalar
        $report['earnings'] = Payment::where('user_id', $userId)
            ->where('type', 'url_earnings')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('net_amount');
            
        $report['spendings'] = Payment::where('user_id', $userId)
            ->where('type', 'ad_spend')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('net_amount');
        
        return $report;
    }
    
    public function generateAdminReport($startDate, $endDate) {
        $report = [
            'users' => [],
            'urls' => [],
            'ads' => [],
            'transactions' => []
        ];
        
        // Kullanıcı istatistikleri
        $report['users'] = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(CASE WHEN role = 'advertiser' THEN 1 ELSE 0 END) as advertisers
            FROM users
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate, $endDate])->fetchAll();
        
        // URL istatistikleri
        $report['urls'] = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(is_custom) as custom_urls,
                SUM(monetization) as monetized_urls
            FROM urls
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate, $endDate])->fetchAll();
        
        // Reklam istatistikleri
        $report['ads'] = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ads,
                SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused_ads
            FROM ads
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate, $endDate])->fetchAll();
        
        // İşlem istatistikleri
        $report['transactions'] = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposits,
                SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as withdrawals,
                SUM(CASE WHEN type = 'ad_spend' THEN amount ELSE 0 END) as ad_spend,
                SUM(CASE WHEN type = 'url_earnings' THEN amount ELSE 0 END) as url_earnings
            FROM payments
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$startDate, $endDate])->fetchAll();
        
        return $report;
    }
    
    public function exportToCsv($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Başlık satırı
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Veri satırları
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}