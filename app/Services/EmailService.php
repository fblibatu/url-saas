<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\User;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // SMTP ayarları
        $this->mailer->isSMTP();
        $this->mailer->Host = env('MAIL_HOST');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = env('MAIL_USERNAME');
        $this->mailer->Password = env('MAIL_PASSWORD');
        $this->mailer->SMTPSecure = env('MAIL_ENCRYPTION');
        $this->mailer->Port = env('MAIL_PORT');
        
        // Gönderen bilgileri
        $this->mailer->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $this->mailer->isHTML(true);
    }
    
    public function sendVerificationEmail(User $user) {
        try {
            // Doğrulama tokenı oluştur
            $token = bin2hex(random_bytes(32));
            $user->remember_token = $token;
            $user->save();
            
            // E-posta içeriği
            $subject = 'E-posta Doğrulama';
            $body = view('emails/verify', [
                'user' => $user,
                'verificationUrl' => url("/verify-email?token=$token")
            ]);
            
            // E-posta ayarları
            $this->mailer->addAddress($user->email, $user->username);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            // Gönder
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("E-posta gönderim hatası: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    public function sendPasswordResetEmail(User $user) {
        try {
            // Reset tokenı oluştur
            $token = bin2hex(random_bytes(32));
            $user->remember_token = $token;
            $user->save();
            
            // E-posta içeriği
            $subject = 'Şifre Sıfırlama Talebi';
            $body = view('emails/reset', [
                'user' => $user,
                'resetUrl' => url("/reset-password?token=$token")
            ]);
            
            // E-posta ayarları
            $this->mailer->addAddress($user->email, $user->username);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            // Gönder
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("E-posta gönderim hatası: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    public function sendPaymentReceipt(User $user, $payment) {
        try {
            // E-posta içeriği
            $subject = 'Ödeme Makbuzu - ' . env('APP_NAME');
            $body = view('emails/payment', [
                'user' => $user,
                'payment' => $payment,
                'invoiceUrl' => url("/invoice/{$payment->id}")
            ]);
            
            // E-posta ayarları
            $this->mailer->addAddress($user->email, $user->username);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            // Gönder
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("E-posta gönderim hatası: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    public function sendMonthlyReport(User $user, $stats) {
        try {
            // E-posta içeriği
            $subject = 'Aylık Rapor - ' . env('APP_NAME');
            $body = view('emails/monthly_report', [
                'user' => $user,
                'stats' => $stats,
                'dashboardUrl' => url('/dashboard')
            ]);
            
            // E-posta ayarları
            $this->mailer->addAddress($user->email, $user->username);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            // Gönder
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("E-posta gönderim hatası: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}