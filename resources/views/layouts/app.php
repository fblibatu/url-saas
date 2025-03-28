<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'URL Kısaltma Servisi') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="/assets/css/main.css" rel="stylesheet">
    <link rel="icon" href="/assets/images/favicon.ico">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/assets/images/logo.png" alt="Logo" height="30" class="d-inline-block align-top">
                URL Kısalt
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/"><i class="bi bi-house-door"></i> Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/features"><i class="bi bi-stars"></i> Özellikler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pricing"><i class="bi bi-currency-dollar"></i> Fiyatlandırma</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (auth()->check()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= auth()->user()->username ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="/profile"><i class="bi bi-person"></i> Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout"><i class="bi bi-box-arrow-right"></i> Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login"><i class="bi bi-box-arrow-in-right"></i> Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register"><i class="bi bi-person-plus"></i> Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-4">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>URL Kısalt</h5>
                    <p>Uzun bağlantılarınızı kısa ve yönetilebilir hale getirin.</p>
                </div>
                <div class="col-md-2">
                    <h5>Bağlantılar</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Ana Sayfa</a></li>
                        <li><a href="/features" class="text-white">Özellikler</a></li>
                        <li><a href="/pricing" class="text-white">Fiyatlandırma</a></li>
                        <li><a href="/contact" class="text-white">İletişim</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Yardım</h5>
                    <ul class="list-unstyled">
                        <li><a href="/faq" class="text-white">SSS</a></li>
                        <li><a href="/docs" class="text-white">API Dökümantasyon</a></li>
                        <li><a href="/privacy" class="text-white">Gizlilik Politikası</a></li>
                        <li><a href="/terms" class="text-white">Kullanım Şartları</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Sosyal Medya</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-linkedin"></i></a>
                    </div>
                    <div class="mt-3">
                        <p>© <?= date('Y') ?> URL Kısalt. Tüm hakları saklıdır.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>