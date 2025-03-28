<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Setting;

class SettingsController extends Controller {
    public function index() {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin/settings/index', ['settings' => $settings]);
    }
    
    public function update($request) {
        $data = $request->getParsedBody();
        
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
        
        return back()->with('success', 'Ayarlar başarıyla güncellendi');
    }
    
    public function updateAdRates($request) {
        $data = $request->getParsedBody();
        
        Setting::updateOrCreate(
            ['key' => 'ad_network_cpc_min'],
            ['value' => $data['cpc_min']]
        );
        
        Setting::updateOrCreate(
            ['key' => 'ad_network_cpc_max'],
            ['value' => $data['cpc_max']]
        );
        
        Setting::updateOrCreate(
            ['key' => 'ad_network_earnings_rate'],
            ['value' => $data['earnings_rate']]
        );
        
        return back()->with('success', 'Reklam oranları güncellendi');
    }
    
    public function updatePaymentSettings($request) {
        $data = $request->getParsedBody();
        
        Setting::updateOrCreate(
            ['key' => 'payment_min_deposit'],
            ['value' => $data['min_deposit']]
        );
        
        Setting::updateOrCreate(
            ['key' => 'payment_min_withdrawal'],
            ['value' => $data['min_withdrawal']]
        );
        
        Setting::updateOrCreate(
            ['key' => 'payment_fee_percent'],
            ['value' => $data['fee_percent']]
        );
        
        return back()->with('success', 'Ödeme ayarları güncellendi');
    }
}