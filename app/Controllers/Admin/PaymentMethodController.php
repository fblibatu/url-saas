<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller {
    public function index() {
        $paymentMethods = PaymentMethod::all();
        return view('admin/payment_methods/index', [
            'paymentMethods' => $paymentMethods
        ]);
    }
    
    public function create() {
        return view('admin/payment_methods/create');
    }
    
    public function store($request) {
        $data = $request->getParsedBody();
        
        $paymentMethod = new PaymentMethod();
        $paymentMethod->name = $data['name'];
        $paymentMethod->code = $data['code'];
        $paymentMethod->status = isset($data['status']) ? 1 : 0;
        $paymentMethod->min_deposit = $data['min_deposit'] ?? 0;
        $paymentMethod->min_withdrawal = $data['min_withdrawal'] ?? 0;
        $paymentMethod->fee_percent = $data['fee_percent'] ?? 0;
        $paymentMethod->config = json_encode($data['config'] ?? []);
        
        if ($paymentMethod->save()) {
            return redirect('/admin/payment-methods')
                ->with('success', 'Ödeme yöntemi başarıyla eklendi');
        }
        
        return back()->with('error', 'Ödeme yöntemi eklenirken bir hata oluştu');
    }
    
    public function edit($request, $id) {
        $paymentMethod = PaymentMethod::find($id);
        
        if (!$paymentMethod) {
            return redirect('/admin/payment-methods')
                ->with('error', 'Ödeme yöntemi bulunamadı');
        }
        
        return view('admin/payment_methods/edit', [
            'paymentMethod' => $paymentMethod
        ]);
    }
    
    public function update($request, $id) {
        $paymentMethod = PaymentMethod::find($id);
        
        if (!$paymentMethod) {
            return redirect('/admin/payment-methods')
                ->with('error', 'Ödeme yöntemi bulunamadı');
        }
        
        $data = $request->getParsedBody();
        
        $paymentMethod->name = $data['name'];
        $paymentMethod->code = $data['code'];
        $paymentMethod->status = isset($data['status']) ? 1 : 0;
        $paymentMethod->min_deposit = $data['min_deposit'] ?? 0;
        $paymentMethod->min_withdrawal = $data['min_withdrawal'] ?? 0;
        $paymentMethod->fee_percent = $data['fee_percent'] ?? 0;
        $paymentMethod->config = json_encode($data['config'] ?? []);
        
        if ($paymentMethod->save()) {
            return redirect('/admin/payment-methods')
                ->with('success', 'Ödeme yöntemi başarıyla güncellendi');
        }
        
        return back()->with('error', 'Ödeme yöntemi güncellenirken bir hata oluştu');
    }
    
    public function toggleStatus($request, $id) {
        $paymentMethod = PaymentMethod::find($id);
        
        if (!$paymentMethod) {
            return json(['success' => false, 'message' => 'Ödeme yöntemi bulunamadı']);
        }
        
        $paymentMethod->status = $paymentMethod->status ? 0 : 1;
        
        if ($paymentMethod->save()) {
            return json([
                'success' => true,
                'message' => 'Durum güncellendi',
                'newStatus' => $paymentMethod->status
            ]);
        }
        
        return json(['success' => false, 'message' => 'Durum güncellenirken bir hata oluştu']);
    }
}