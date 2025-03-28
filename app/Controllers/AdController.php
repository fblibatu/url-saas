<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Ad;
use App\Services\AdNetworkService;

class AdController extends Controller {
    public function index() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $ads = Ad::where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);
            
        return view('ads/index', ['ads' => $ads]);
    }
    
    public function create() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        return view('ads/create');
    }
    
    public function store($request) {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $data = $request->getParsedBody();
        $adService = new AdNetworkService();
        
        try {
            $adService->createOrUpdateAd($data, auth()->id());
            return redirect('/ads')->with('success', 'Reklam başarıyla oluşturuldu');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function show($request, $id) {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $ad = Ad::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$ad) {
            return redirect('/ads')->with('error', 'Reklam bulunamadı');
        }
        
        $analytics = new AnalyticsService();
        $stats = $analytics->getAdStats($ad->id);
        
        return view('ads/show', [
            'ad' => $ad,
            'stats' => $stats
        ]);
    }
    
    public function toggleStatus($request, $id) {
        if (!auth()->check()) {
            return json(['success' => false, 'message' => 'Yetkisiz erişim']);
        }
        
        $ad = Ad::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$ad) {
            return json(['success' => false, 'message' => 'Reklam bulunamadı']);
        }
        
        $ad->status = $ad->status === 'active' ? 'paused' : 'active';
        
        if ($ad->save()) {
            return json([
                'success' => true,
                'message' => 'Reklam durumu güncellendi',
                'newStatus' => $ad->status
            ]);
        }
        
        return json(['success' => false, 'message' => 'Durum güncellenirken hata oluştu']);
    }
}