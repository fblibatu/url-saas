<?php
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\AdvertiserMiddleware;

$router = new Router();

// Genel Rotalar
$router->get('/', 'HomeController@index');
$router->get('/{code}', 'UrlController@redirect');

// Auth Rotaları
$router->group('/auth', function($router) {
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
    $router->get('/logout', 'AuthController@logout');
    $router->get('/verify-email', 'AuthController@verifyEmail');
    $router->get('/forgot-password', 'AuthController@showForgotPassword');
    $router->post('/forgot-password', 'AuthController@forgotPassword');
    $router->get('/reset-password', 'AuthController@showResetPassword');
    $router->post('/reset-password', 'AuthController@resetPassword');
});

// Kullanıcı Rotaları
$router->group('', function($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/dashboard/urls', 'DashboardController@urls');
    $router->get('/dashboard/url/create', 'DashboardController@createUrl');
    $router->post('/dashboard/url/store', 'DashboardController@storeUrl');
    $router->get('/dashboard/url/{code}/stats', 'DashboardController@urlStats');
    $router->delete('/dashboard/url/{code}', 'DashboardController@deleteUrl');
    
    $router->get('/profile', 'UserController@profile');
    $router->post('/profile/update', 'UserController@updateProfile');
    $router->get('/payment-history', 'UserController@paymentHistory');
    $router->get('/verify-email/send', 'UserController@requestVerificationEmail');
}, [AuthMiddleware::class]);

// Reklamveren Rotaları
$router->group('/advertiser', function($router) {
    $router->get('/dashboard', 'AdvertiserController@dashboard');
    $router->get('/ads', 'AdvertiserController@ads');
    $router->get('/ad/create', 'AdvertiserController@createAd');
    $router->post('/ad/store', 'AdvertiserController@storeAd');
    $router->get('/ad/{id}/stats', 'AdvertiserController@adStats');
    $router->post('/ad/{id}/toggle', 'AdvertiserController@toggleAdStatus');
    $router->get('/billing', 'AdvertiserController@billing');
    $router->post('/deposit', 'AdvertiserController@deposit');
}, [AuthMiddleware::class, AdvertiserMiddleware::class]);

// Admin Rotaları
$router->group('/admin', function($router) {
    $router->get('/dashboard', 'AdminController@dashboard');
    
    // Kullanıcı Yönetimi
    $router->get('/users', 'Admin\UserController@index');
    $router->get('/user/{id}/edit', 'Admin\UserController@edit');
    $router->post('/user/{id}/update', 'Admin\UserController@update');
    $router->post('/user/{id}/toggle', 'Admin\UserController@toggleStatus');
    
    // Ödeme Yönetimi
    $router->get('/payment-methods', 'Admin\PaymentMethodController@index');
    $router->get('/payment-method/create', 'Admin\PaymentMethodController@create');
    $router->post('/payment-method/store', 'Admin\PaymentMethodController@store');
    $router->get('/payment-method/{id}/edit', 'Admin\PaymentMethodController@edit');
    $router->post('/payment-method/{id}/update', 'Admin\PaymentMethodController@update');
    $router->post('/payment-method/{id}/toggle', 'Admin\PaymentMethodController@toggleStatus');
    
    // Raporlar
    $router->get('/reports', 'Admin\ReportController@index');
    $router->post('/reports/generate', 'Admin\ReportController@generate');
    $router->get('/reports/export', 'Admin\ReportController@export');
    
    // Ayarlar
    $router->get('/settings', 'Admin\SettingsController@index');
    $router->post('/settings/update', 'Admin\SettingsController@update');
    $router->post('/settings/ad-rates', 'Admin\SettingsController@updateAdRates');
    $router->post('/settings/payment', 'Admin\SettingsController@updatePaymentSettings');
}, [AuthMiddleware::class, AdminMiddleware::class]);

// API Rotaları
$router->group('/api/v1', function($router) {
    $router->post('/auth/register', 'Api\AuthController@register');
    $router->post('/auth/login', 'Api\AuthController@login');
    
    $router->group('', function($router) {
        $router->post('/url/shorten', 'Api\UrlController@shorten');
        $router->get('/url/{code}/stats', 'Api\UrlController@getUrlStats');
        $router->delete('/url/{code}', 'Api\UrlController@deleteUrl');
        
        $router->get('/user', 'Api\UserController@profile');
        $router->put('/user', 'Api\UserController@updateProfile');
        
        $router->get('/ads', 'Api\AdController@index');
        $router->post('/ads', 'Api\AdController@create');
        $router->get('/ads/{id}', 'Api\AdController@show');
        $router->put('/ads/{id}', 'Api\AdController@update');
        $router->post('/ads/{id}/toggle', 'Api\AdController@toggleStatus');
    }, [ApiAuthMiddleware::class, RateLimitMiddleware::class]);
});

return $router;