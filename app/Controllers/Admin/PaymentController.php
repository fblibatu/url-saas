<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Payment;
use App\Services\PaymentService;

class PaymentController extends Controller {
    public function deposits() {
        $deposits = Payment::with(['user', 'paymentMethod'])
            ->where('type', 'deposit')
            ->orderBy('created_at', 'DESC')
            ->paginate(15);
            
        return view('admin/payments/deposits', [
            'deposits' => $deposits
        ]);
    }
    
    public function withdrawals() {
        $withdrawals = Payment::with(['user', 'paymentMethod'])
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'DESC')
            ->paginate(15);
            
        return view('admin/payments/withdrawals', [
            'withdrawals' => $withdrawals
        ]);
    }
    
    public function approvePayment($request, $id) {
        $payment = Payment::find($id);
        
        if (!$payment) {
            return back()->with('error', 'Ödeme bulunamadı');
        }
        
        $paymentService = new PaymentService();
        
        try {
            $transactionId = $request->getParsedBody()['transaction_id'] ?? null;
            $paymentService->approvePayment($payment->id, $transactionId);
            
            return back()->with('success', 'Ödeme başarıyla onaylandı');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function rejectPayment($request, $id) {
        $payment = Payment::find($id);
        
        if (!$payment) {
            return back()->with('error', 'Ödeme bulunamadı');
        }
        
        $paymentService = new PaymentService();
        
        try {
            $reason = $request->getParsedBody()['reason'] ?? 'Belirtilmedi';
            $paymentService->rejectPayment($payment->id, $reason);
            
            return back()->with('success', 'Ödeme başarıyla reddedildi');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function stats() {
        $totalDeposits = Payment::where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalWithdrawals = Payment::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalAdSpend = Payment::where('type', 'ad_spend')
            ->sum('amount');
            
        $totalUrlEarnings = Payment::where('type', 'url_earnings')
            ->sum('amount');
            
        $recentDeposits = Payment::with('user')
            ->where('type', 'deposit')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();
            
        $recentWithdrawals = Payment::with('user')
            ->where('type', 'withdrawal')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();
            
        return view('admin/payments/stats', [
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'totalAdSpend' => $totalAdSpend,
            'totalUrlEarnings' => $totalUrlEarnings,
            'recentDeposits' => $recentDeposits,
            'recentWithdrawals' => $recentWithdrawals
        ]);
    }
}