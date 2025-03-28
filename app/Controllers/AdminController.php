<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Url;
use App\Models\Payment;
use App\Services\AnalyticsService;

class AdminController extends Controller {
    /**
     * Admin dashboard
     */
    public function dashboard() {
        $analytics = new AnalyticsService();
        
        $data = [
            'totalUsers' => User::count(),
            'totalUrls' => Url::count(),
            'totalClicks' => $analytics->getTotalClicks(),
            'totalRevenue' => $analytics->getTotalRevenue(),
            'recentUsers' => User::orderBy('created_at', 'DESC')->limit(5)->get(),
            'recentPayments' => Payment::with('user')->orderBy('created_at', 'DESC')->limit(5)->get(),
            'clickStats' => $analytics->getClickStatsLast30Days(),
            'revenueStats' => $analytics->getRevenueStatsLast30Days()
        ];
        
        return view('admin/dashboard', $data);
    }
    
    /**
     * Kullanıcı yönetimi
     */
    public function users($request) {
        $search = $request->getQueryParam('search');
        $page = $request->getQueryParam('page', 1);
        
        $query = User::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('username', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%");
            });
        }
        
        $users = $query->orderBy('created_at', 'DESC')->paginate(15, $page);
        
        return view('admin/users', [
            'users' => $users,
            'search' => $search
        ]);
    }
    
    /**
     * URL yönetimi
     */
    public function urls($request) {
        $search = $request->getQueryParam('search');
        $page = $request->getQueryParam('page', 1);
        
        $query = Url::with('user');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('original_url', 'LIKE', "%$search%")
                  ->orWhere('short_code', 'LIKE', "%$search%");
            });
        }
        
        $urls = $query->orderBy('created_at', 'DESC')->paginate(15, $page);
        
        return view('admin/urls', [
            'urls' => $urls,
            'search' => $search
        ]);
    }
    
    // Diğer admin metodları...
}