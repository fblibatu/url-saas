<?php ob_start() ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="/assets/images/logo.png" alt="Logo" height="50" class="mb-3">
                    <h2>Hesabınıza Giriş Yapın</h2>
                    <p class="text-muted">Devam etmek için lütfen giriş yapın</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form action="/login" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Security::csrfToken('login') ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="text-end mt-1">
                            <a href="/forgot-password" class="small text-muted">Şifremi Unuttum?</a>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Beni Hatırla</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Giriş Yap</button>
                    
                    <div class="text-center">
                        <span class="text-muted">Hesabınız yok mu?</span>
                        <a href="/register" class="ms-1">Kayıt Olun</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>