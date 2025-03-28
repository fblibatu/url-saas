<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGateway;

class PayoutController extends Controller {
    public function index() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $minPayout = config('payment.min_payout', 20.00);
        
        $pendingPayouts = Payment::where('user_id', $user->id)
            ->where('type', 'payout')
            ->orderBy('created_at', 'DESC')
            ->get();
            
        $revenues = Payment::where('user_id', $user->id)
            ->where('type', 'click_revenue')
            ->where('status', 'completed')
            ->sum('amount');
            
        $paidOut = Payment::where('user_id', $user->id)
            ->where('type', 'payout')
            ->where('status', 'completed')
            ->sum('amount');
            
        $availableBalance = $revenues - $paidOut;
        
        return view('payouts/index', [
            'pendingPayouts' => $pendingPayouts,
            'availableBalance' => $availableBalance,
            'minPayout' => $minPayout,
            'user' => $user
        ]);
    }
    
    public function requestPayout($request) {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $minPayout = config('payment.min_payout', 20.00);
        
        // Kullanılabilir bakiyeyi hesapla
        $revenues = Payment::where('user_id', $user->id)
            ->where('type', 'click_revenue')
            ->where('status', 'completed')
            ->sum('amount');
            
        $paidOut = Payment::where('user_id', $user->id)
            ->where('type', 'payout')
            ->where('status', 'completed')
            ->sum('amount');
            
        $availableBalance = $revenues - $paidOut;
        
        // Minimum çekilebilir tutar kontrolü
        if ($availableBalance < $minPayout) {
            return back()->with('error', "Minimum çekilebilir tutar $" . number_format($minPayout, 2) . " olarak belirlenmiştir");
        }
        
        // Ödeme yöntemi kontrolü
        $paymentMethod = $request->getParsedBody()['payment_method'] ?? null;
        $paymentDetails = $request->getParsedBody()['payment_details'] ?? null;
        
        if (empty($paymentMethod) || empty($paymentDetails)) {
            return back()->with('error', "Ödeme yöntemi ve detayları gereklidir");
        }
        
        // Ödeme kaydı oluştur
        $payout = new Payment();
        $payout->user_id = $user->id;
        $payout->amount = $availableBalance;
        $payout->payment_method = $paymentMethod;
        $payout->type = 'payout';
        $payout->status = 'pending';
        $payout->metadata = json_encode([
            'payment_details' => $paymentDetails,
            'requested_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($payout->save()) {
            // Kullanıcıyı bilgilendir
            $emailService = new EmailService();
            $emailService->sendPayoutRequestConfirmation($user, $payout);
            
            return back()->with('success', "Ödeme talebiniz alındı. İşlem 3-5 iş günü içinde tamamlanacaktır.");
        }
        
        return back()->with('error', "Ödeme talebi oluşturulamadı. Lütfen daha sonra tekrar deneyin.");
    }
    
    public function adminPayouts($request) {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        
        $status = $request->getQueryParam('status', 'pending');
        $page = $request->getQueryParam('page', 1);
        
        $query = Payment::with('user')
            ->where('type', 'payout');
            
        if (in_array($status, ['pending', 'completed', 'failed'])) {
            $query->where('status', $status);
        }
        
        $payouts = $query->orderBy('created_at', 'DESC')
            ->paginate(15, $page);
            
        return view('admin/payouts', [
            'payouts' => $payouts,
            'status' => $status
        ]);
    }
    
    public function processPayout($request, $id) {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        
        $payout = Payment::find($id);
        
        if (!$payout || $payout->type !== 'payout') {
            return back()->with('error', "Geçersiz ödeme talebi");
        }
        
        $action = $request->getParsedBody()['action'] ?? null;
        
        if ($action === 'approve') {
            // Ödeme ağ geçidi ile ödeme yap
            $paymentGateway = new PaymentGateway();
            
            try {
                $metadata = json_decode($payout->metadata, true);
                
                $result = $paymentGateway->sendPayout(
                    $payout->user->email,
                    $payout->amount,
                    $metadata['payment_details']
                );
                
                // Ödeme durumunu güncelle
                $payout->status = 'completed';
                $payout->transaction_id = $result->id;
                $payout->completed_at = date('Y-m-d H:i:s');
                $payout->save();
                
                // Kullanıcıya e-posta gönder
                $emailService = new EmailService();
                $emailService->sendPayoutCompleted($payout->user, $payout);
                
                return back()->with('success', "Ödeme başarıyla tamamlandı");
            } catch (\Exception $e) {
                $payout->status = 'failed';
                $payout->error_message = $e->getMessage();
                $payout->save();
                
                return back()->with('error', "Ödeme işlemi başarısız: " . $e->getMessage());
            }
        } elseif ($action === 'reject') {
            // Ödeme talebini reddet
            $payout->status = 'failed';
            $payout->error_message = $request->getParsedBody()['reason'] ?? 'Belirtilmedi';
            $payout->save();
            
            // Kullanıcıya e-posta gönder
            $emailService = new EmailService();
            $emailService->sendPayoutRejected($payout->user, $payout);
            
            return back()->with('success', "Ödeme talebi reddedildi");
        }
        
        return back()->with('error', "Geçersiz işlem");
    }
}