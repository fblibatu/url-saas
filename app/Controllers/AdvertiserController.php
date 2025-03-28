<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Ad;
use App\Models\Payment;
use App\Services\AdNetworkService;
use App\Validators\UserValidator;

class AdvertiserController extends Controller {
    public function dashboard() {
        if (!auth()->check() || auth()->user()->role !== 'advertiser') {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $analytics = new AnalyticsService();
        
        return view('advertiser/dashboard', [
            'activeAds' => Ad::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'totalSpent' => Payment::where('user_id', $user->id)
                ->where('type', 'ad_spend')
                ->sum('amount'),
            'monthlyStats' => $analytics->getAdvertiserMonthlyStats($user->id)
        ]);
    }
    
    public function createAd() {
        if (!auth()->check() || auth()->user()->role !== 'advertiser') {
            return redirect('/login');
        }
        
        return view('advertiser/ad_create');
    }
    
    public function storeAd($request) {
        if (!auth()->check() || auth()->user()->role !== 'advertiser') {
            return redirect('/login');
        }
        
        $data = $request->getParsedBody();
        $validator = new UserValidator();
        $errors = $validator->validateAdCreation($data);
        
        if (!empty($errors)) {
            return back()->with('errors', $errors);
        }
        
        $adService = new AdNetworkService();
        
        try {
            $adService->createOrUpdateAd($data, auth()->id());
            return redirect('/advertiser/ads')->with('success', 'Reklam başarıyla oluşturuldu');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function adStats($request, $id) {
        if (!auth()->check() || auth()->user()->role !== 'advertiser') {
            return redirect('/login');
        }
        
        $ad = Ad::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$ad) {
            return redirect('/advertiser/ads')->with('error', 'Reklam bulunamadı');
        }
        
        $analytics = new AnalyticsService();
        $stats = $analytics->getAdStats($ad->id);
        
        return view('advertiser/ad_stats', [
            'ad' => $ad,
            'stats' => $stats
        ]);
    }
    
    public function billing() {
        if (!auth()->check() || auth()->user()->role !== 'advertiser') {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $paymentService = new PaymentService();
        
        return view('advertiser/billing', [
            'balance' => $user->balance,
            'paymentMethods' => $paymentService->getAvailablePaymentMethods('deposit'),
            'transactions' => Payment::where('user_id', $user->id)
                ->whereIn('type', ['deposit', 'ad_spend'])
                ->orderBy('created_at', 'DESC')
                ->paginate(10)
        ]);
    }
}