<?php
namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Core\Database;
use PDO;

class PaymentService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Kullanılabilir ödeme yöntemlerini listeler
     */
    public function getAvailablePaymentMethods($type = 'deposit') {
        $query = "SELECT * FROM payment_methods WHERE status = 1";
        
        if ($type === 'deposit') {
            $query .= " AND min_deposit > 0";
        } elseif ($type === 'withdrawal') {
            $query .= " AND min_withdrawal > 0";
        }
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_CLASS, PaymentMethod::class);
    }
    
    /**
     * Para yatırma talebi oluşturur
     */
    public function createDepositRequest(User $user, $paymentMethodId, $amount) {
        $paymentMethod = PaymentMethod::find($paymentMethodId);
        
        if (!$paymentMethod || !$paymentMethod->status) {
            throw new \Exception("Geçersiz ödeme yöntemi");
        }
        
        if ($amount < $paymentMethod->min_deposit) {
            throw new \Exception("Minimum yatırma tutarı: " . $paymentMethod->min_deposit);
        }
        
        $fee = $amount * ($paymentMethod->fee_percent / 100);
        $netAmount = $amount - $fee;
        
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->payment_method_id = $paymentMethod->id;
        $payment->amount = $amount;
        $payment->fee = $fee;
        $payment->net_amount = $netAmount;
        $payment->type = 'deposit';
        $payment->status = 'pending';
        
        if ($payment->save()) {
            return $payment;
        }
        
        throw new \Exception("Ödeme talebi oluşturulamadı");
    }
    
    /**
     * Para çekme talebi oluşturur
     */
    public function createWithdrawalRequest(User $user, $paymentMethodId, $amount, $details) {
        $paymentMethod = PaymentMethod::find($paymentMethodId);
        
        if (!$paymentMethod || !$paymentMethod->status) {
            throw new \Exception("Geçersiz ödeme yöntemi");
        }
        
        if ($amount < $paymentMethod->min_withdrawal) {
            throw new \Exception("Minimum çekim tutarı: " . $paymentMethod->min_withdrawal);
        }
        
        if ($user->balance < $amount) {
            throw new \Exception("Yetersiz bakiye");
        }
        
        $fee = $amount * ($paymentMethod->fee_percent / 100);
        $netAmount = $amount - $fee;
        
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->payment_method_id = $paymentMethod->id;
        $payment->amount = $amount;
        $payment->fee = $fee;
        $payment->net_amount = $netAmount;
        $payment->type = 'withdrawal';
        $payment->status = 'pending';
        $payment->metadata = json_encode([
            'account_details' => $details,
            'requested_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($payment->save()) {
            // Kullanıcı bakiyesini bloke et
            $user->balance -= $amount;
            $user->save();
            
            return $payment;
        }
        
        throw new \Exception("Çekim talebi oluşturulamadı");
    }
    
    /**
     * Ödemeyi onaylar (admin)
     */
    public function approvePayment($paymentId, $transactionId = null) {
        $payment = Payment::find($paymentId);
        
        if (!$payment) {
            throw new \Exception("Ödeme bulunamadı");
        }
        
        if ($payment->status !== 'pending') {
            throw new \Exception("Sadece bekleyen ödemeler onaylanabilir");
        }
        
        $user = User::find($payment->user_id);
        
        if ($payment->type === 'deposit') {
            // Bakiyeyi artır
            $user->balance += $payment->net_amount;
            $user->save();
            
            $payment->status = 'completed';
            $payment->transaction_id = $transactionId;
            $payment->save();
            
            return true;
        } elseif ($payment->type === 'withdrawal') {
            // Ödeme yapıldıktan sonra
            $payment->status = 'completed';
            $payment->transaction_id = $transactionId;
            $payment->save();
            
            return true;
        }
        
        throw new \Exception("Geçersiz ödeme türü");
    }
    
    /**
     * Ödemeyi reddeder (admin)
     */
    public function rejectPayment($paymentId, $reason) {
        $payment = Payment::find($paymentId);
        
        if (!$payment) {
            throw new \Exception("Ödeme bulunamadı");
        }
        
        if ($payment->status !== 'pending') {
            throw new \Exception("Sadece bekleyen ödemeler reddedilebilir");
        }
        
        $user = User::find($payment->user_id);
        
        if ($payment->type === 'withdrawal') {
            // Bloke edilen bakiyeyi geri ver
            $user->balance += $payment->amount;
            $user->save();
        }
        
        $payment->status = 'rejected';
        $payment->admin_notes = $reason;
        $payment->save();
        
        return true;
    }
    
    /**
     * Reklam harcamasını kaydeder
     */
    public function recordAdSpend($userId, $amount, $adId) {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \Exception("Kullanıcı bulunamadı");
        }
        
        if ($user->balance < $amount) {
            throw new \Exception("Yetersiz bakiye");
        }
        
        // Kullanıcı bakiyesini güncelle
        $user->balance -= $amount;
        $user->save();
        
        // Ödeme kaydı oluştur
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->payment_method_id = 0; // Sistem içi transfer
        $payment->amount = $amount;
        $payment->fee = 0;
        $payment->net_amount = $amount;
        $payment->type = 'ad_spend';
        $payment->status = 'completed';
        $payment->metadata = json_encode([
            'ad_id' => $adId,
            'processed_at' => date('Y-m-d H:i:s')
        ]);
        
        return $payment->save();
    }
    
    /**
     * URL tıklama kazancını kaydeder
     */
    public function recordUrlEarnings($userId, $amount, $urlId) {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \Exception("Kullanıcı bulunamadı");
        }
        
        // Kullanıcı bakiyesini güncelle
        $user->balance += $amount;
        $user->save();
        
        // Ödeme kaydı oluştur
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->payment_method_id = 0; // Sistem içi transfer
        $payment->amount = $amount;
        $payment->fee = 0;
        $payment->net_amount = $amount;
        $payment->type = 'url_earnings';
        $payment->status = 'completed';
        $payment->metadata = json_encode([
            'url_id' => $urlId,
            'processed_at' => date('Y-m-d H:i:s')
        ]);
        
        return $payment->save();
    }
}