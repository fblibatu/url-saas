<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\EmailService;

class UserController extends Controller {
    public function profile() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $paymentService = new PaymentService();
        
        return view('user/profile', [
            'user' => $user,
            'paymentMethods' => $paymentService->getAvailablePaymentMethods('withdrawal')
        ]);
    }
    
    public function updateProfile($request) {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $data = $request->getParsedBody();
        
        // Temel bilgileri güncelle
        if (!empty($data['username'])) {
            $user->username = $data['username'];
        }
        
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            // E-posta değişikliği için doğrulama süreci
            $user->email = $data['email'];
            $user->email_verified_at = null;
            
            $emailService = new EmailService();
            $emailService->sendVerificationEmail($user);
        }
        
        // Şifre değişikliği
        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            if (!Security::verifyPassword($data['current_password'], $user->password)) {
                return back()->with('error', 'Mevcut şifre yanlış');
            }
            
            $user->password = Security::hashPassword($data['new_password']);
        }
        
        if ($user->save()) {
            return back()->with('success', 'Profil başarıyla güncellendi');
        }
        
        return back()->with('error', 'Profil güncellenirken hata oluştu');
    }
    
    public function paymentHistory() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        $payments = Payment::where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->paginate(15);
            
        return view('user/payment_history', [
            'payments' => $payments
        ]);
    }
    
    public function requestVerificationEmail() {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        $user = auth()->user();
        
        if ($user->email_verified_at) {
            return back()->with('info', 'E-posta adresiniz zaten doğrulanmış');
        }
        
        $emailService = new EmailService();
        $emailService->sendVerificationEmail($user);
        
        return back()->with('success', 'Doğrulama e-postası gönderildi');
    }
}