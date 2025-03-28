<?php ob_start() ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="/assets/images/logo.png" alt="Logo" height="50" class="mb-3">
                    <h2>Yeni Hesap Oluşturun</h2>
                    <p class="text-muted">Sadece birkaç adımda ücretsiz hesap açabilirsiniz</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="/register" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Security::csrfToken('register') ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-posta Adresi</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Şifre</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">En az 8 karakter uzunluğunda olmalıdır</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Şifre Tekrar</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            <a href="/terms">Kullanım Şartları</a> ve <a href="/privacy">Gizlilik Politikasını</a> okudum ve kabul ediyorum
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Hesap Oluştur</button>
                    
                    <div class="text-center">
                        <span class="text-muted">Zaten hesabınız var mı?</span>
                        <a href="/login" class="ms-1">Giriş Yapın</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
$scripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const passwordConfirmation = document.getElementById('password_confirmation');
    
    function validatePassword() {
        if (password.value !== passwordConfirmation.value) {
            passwordConfirmation.setCustomValidity('Şifreler eşleşmiyor');
        } else {
            passwordConfirmation.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validatePassword);
    passwordConfirmation.addEventListener('input', validatePassword);
});
</script>
JS;

include __DIR__ . '/layouts/app.php';
?>