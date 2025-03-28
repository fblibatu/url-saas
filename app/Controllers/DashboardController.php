<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payment;
use App\Models\Url;
use App\Models\Ad;
use App\Services\AnalyticsService;

class DashboardController extends Controller {
    public function index() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $analytics = new AnalyticsService();
        
        $data = [
            'totalUrls' => Url::where('user_id', $user->id)->count(),
            'totalClicks' => $analytics->getUserTotalClicks($user->id),
            'totalEarnings' => $analytics->getUserTotalEarnings($user->id),
            'recentUrls' => Url::where('user_id', $user->id)
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get(),
            'recentPayments' => Payment::where('user_id', $user->id)
                ->whereIn('type', ['url_earnings', 'withdrawal'])
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get(),
            'activeAds' => Ad::where('user_id', $user->id)
                ->where('status', 'active')
                ->count()
        ];
        
        return view('dashboard/index', $data);
    }
    
    public function urls() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $urls = Url::where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);
            
        return view('dashboard/urls', ['urls' => $urls]);
    }
    
    public function createUrl() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        return view('dashboard/url_create');
    }
    
    public function storeUrl($request) {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $data = $request->getParsedBody();
        $urlShortener = new UrlShortener();
        
        try {
            $result = $urlShortener->shortenUrl(
                $data['original_url'],
                auth()->id(),
                $data['custom_code'] ?? null,
                [
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'password' => $data['password'] ?? null,
                    'expires_at' => $data['expires_at'] ?? null,
                    'monetization' => isset($data['monetization']),
                    'utm_source' => $data['utm_source'] ?? null,
                    'utm_medium' => $data['utm_medium'] ?? null,
                    'utm_campaign' => $data['utm_campaign'] ?? null,
                    'generate_qr' => isset($data['generate_qr'])
                ]
            );
            
            return redirect('/dashboard/urls')
                ->with('success', 'URL başarıyla kısaltıldı: ' . $result['short_url']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    // Diğer dashboard metodları...
}