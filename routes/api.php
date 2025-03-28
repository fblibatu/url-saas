<?php
use App\Core\Router;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\RateLimitMiddleware;

$router = new Router();

// V1 API Routes
$router->group('/api/v1', function($router) {
    // Auth endpoints
    $router->post('/auth/register', 'Api\AuthController@register');
    $router->post('/auth/login', 'Api\AuthController@login');
    $router->post('/auth/refresh', 'Api\AuthController@refresh');
    
    // Authenticated routes
    $router->group('', function($router) {
        // URL endpoints
        $router->post('/urls', 'Api\UrlController@create');
        $router->get('/urls', 'Api\UrlController@list');
        $router->get('/urls/{id}', 'Api\UrlController@get');
        $router->put('/urls/{id}', 'Api\UrlController@update');
        $router->delete('/urls/{id}', 'Api\UrlController@delete');
        
        // Stats endpoints
        $router->get('/stats/urls/{id}', 'Api\StatController@urlStats');
        $router->get('/stats/clicks', 'Api\StatController@clickStats');
        $router->get('/stats/revenue', 'Api\StatController@revenueStats');
        
        // User endpoints
        $router->get('/user', 'Api\UserController@profile');
        $router->put('/user', 'Api\UserController@updateProfile');
        $router->get('/user/balance', 'Api\UserController@balance');
        $router->post('/user/payout', 'Api\UserController@requestPayout');
    }, [ApiAuthMiddleware::class]);
}, [RateLimitMiddleware::class]);

return $router;