<?php
require __DIR__ . '/../app/Core/bootstrap.php';

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGateway;

// Bekleyen ödemeleri işle
$pendingPayouts = Payment::where('type', 'payout')
    ->where('status', 'pending')
    ->get();

$paymentGateway = new PaymentGateway();

foreach ($pendingPayouts as $payout) {
    try {
        // Ödeme işlemini gerçekleştir (Stripe, PayPal vb.)
        $result = $paymentGateway->processPayout(
            $payout->user->email,
            $payout->amount,
            $payout->metadata
        );
        
        // Ödeme durumunu güncelle
        $payout->status = 'completed';
        $payout->transaction_id = $result->id;
        $payout->completed_at = date('Y-m-d H:i:s');
        $payout->save();
        
        // Kullanıcı bakiyesini güncelle
        $payout->user->balance -= $payout->amount;
        $payout->user->save();
        
        log_message("Payout processed: " . $payout->id);
    } catch (\Exception $e) {
        $payout->status = 'failed';
        $payout->error_message = $e->getMessage();
        $payout->save();
        
        log_message("Payout failed: " . $payout->id . " - " . $e->getMessage(), 'error');
    }
}

// Kullanıcı tıklama gelirlerini işle
$pendingRevenues = Payment::where('type', 'click_revenue')
    ->where('status', 'pending')
    ->get();

foreach ($pendingRevenues as $revenue) {
    try {
        // Kullanıcı bakiyesine ekle
        $revenue->user->balance += $revenue->amount;
        $revenue->user->save();
        
        $revenue->status = 'completed';
        $revenue->save();
        
        log_message("Revenue credited: " . $revenue->id);
    } catch (\Exception $e) {
        log_message("Revenue credit failed: " . $revenue->id . " - " . $e->getMessage(), 'error');
    }
}

log_message("Cron job completed: process_payments");