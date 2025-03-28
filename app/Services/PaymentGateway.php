<?php
namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Subscription;
use App\Models\User;
use App\Models\Plan;
use App\Models\Payment;

class PaymentGateway {
    private $stripeSecret;
    
    public function __construct() {
        $this->stripeSecret = env('STRIPE_SECRET');
        Stripe::setApiKey($this->stripeSecret);
    }
    
    /**
     * Yeni ödeme oturumu oluştur
     */
    public function createCheckoutSession($userId, $planId, $successUrl, $cancelUrl) {
        $user = User::find($userId);
        $plan = Plan::find($planId);
        
        if (!$user || !$plan) {
            throw new \InvalidArgumentException("Geçersiz kullanıcı veya plan");
        }
        
        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $plan->name,
                    ],
                    'unit_amount' => $plan->price * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'subscription_data' => [
                'trial_period_days' => $plan->trial_days,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id
                ]
            ],
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ]);
        
        // Ödeme oturumunu veritabanına kaydet
        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->plan_id = $plan->id;
        $payment->amount = $plan->price;
        $payment->session_id = $session->id;
        $payment->status = 'pending';
        $payment->save();
        
        return $session->id;
    }
    
    /**
     * Webhook işleyici
     */
    public function handleWebhook($payload, $signature) {
        $event = null;
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                env('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Webhook doğrulama hatası: " . $e->getMessage());
        }
        
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handlePaymentSuccess($session);
                break;
                
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $this->handleRecurringPayment($invoice);
                break;
                
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionCancel($subscription);
                break;
        }
        
        return true;
    }
    
    /**
     * Başarılı ödeme işlemi
     */
    private function handlePaymentSuccess($session) {
        $payment = Payment::where('session_id', $session->id)->first();
        
        if ($payment) {
            $payment->status = 'completed';
            $payment->transaction_id = $session->payment_intent;
            $payment->save();
            
            // Kullanıcı planını güncelle
            $user = User::find($payment->user_id);
            $user->plan_id = $payment->plan_id;
            $user->plan_expires_at = now()->addDays(Plan::find($payment->plan_id)->duration_days);
            $user->save();
            
            // Ödeme makbuzu gönder
            $this->sendPaymentReceipt($user, $payment);
        }
    }
    
    // Diğer ödeme metodları...
}