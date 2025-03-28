<?php ob_start() ?>
<div class="hero-section bg-light rounded-3 p-5 mb-4">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold">Uzun URL'lerinizi Kısaltın</h1>
            <p class="lead">Profesyonel URL kısaltma hizmeti ile bağlantılarınızı yönetin, takip edin ve optimize edin.</p>
            <div class="d-flex gap-2 mb-3">
                <a href="/register" class="btn btn-primary btn-lg px-4">Hemen Başla</a>
                <a href="/features" class="btn btn-outline-secondary btn-lg px-4">Özellikleri Keşfet</a>
            </div>
            <div class="d-flex align-items-center text-muted">
                <i class="bi bi-people-fill me-2"></i>
                <span>10.000+ mutlu kullanıcı</span>
            </div>
        </div>
        <div class="col-lg-6 d-none d-lg-block">
            <img src="/assets/images/url-shortener.svg" alt="URL Kısaltma" class="img-fluid">
        </div>
    </div>
</div>

<div class="url-shortener-card card shadow-sm mb-5">
    <div class="card-body p-4">
        <h2 class="h4 mb-4">URL Kısalt</h2>
        <form id="urlForm" class="needs-validation" novalidate>
            <div class="row g-2">
                <div class="col-md-9">
                    <input type="url" class="form-control form-control-lg" id="originalUrl" 
                           placeholder="https://ornek.com/uzun-bir-url-adresi" required>
                    <div class="invalid-feedback">Lütfen geçerli bir URL girin</div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-scissors"></i> Kısalt
                    </button>
                </div>
            </div>
            
            <div class="advanced-options mt-3 collapse" id="advancedOptions">
                <div class="card card-body bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customAlias" class="form-label">Özel Kısa Ad</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $_ENV['APP_URL'] ?>/</span>
                                <input type="text" class="form-control" id="customAlias" 
                                       placeholder="ornek" pattern="[a-zA-Z0-9\-_]+">
                                <div class="invalid-feedback">Sadece harf, rakam, - ve _ kullanabilirsiniz</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="expiryDate" class="form-label">Son Kullanma Tarihi</label>
                            <input type="date" class="form-control" id="expiryDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">İstatistik Takibi</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableStats" checked>
                                <label class="form-check-label" for="enableStats">Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reklam Gösterimi</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableAds">
                                <label class="form-check-label" for="enableAds">Pasif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" 
                        data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                    <i class="bi bi-gear"></i> Gelişmiş Ayarlar
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="qrCodeBtn">
                    <i class="bi bi-qr-code"></i> QR Kod Oluştur
                </button>
            </div>
        </form>
        
        <div id="resultContainer" class="mt-4 d-none">
            <div class="alert alert-success">
                <h3 class="h5">URL'niz başarıyla kısaltıldı!</h3>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="shortUrl" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copyBtn">
                        <i class="bi bi-clipboard"></i> Kopyala
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-sm btn-outline-primary" id="visitBtn">
                        <i class="bi bi-box-arrow-up-right"></i> Ziyaret Et
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-info" id="statsBtn">
                        <i class="bi bi-graph-up"></i> İstatistikler
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-success" id="shareBtn">
                        <i class="bi bi-share"></i> Paylaş
                    </a>
                </div>
            </div>
            
            <div class="card mb-3 d-none" id="qrCodeContainer">
                <div class="card-body text-center">
                    <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">
                    <div>
                        <a href="#" class="btn btn-sm btn-outline-primary" id="downloadQrBtn">
                            <i class="bi bi-download"></i> İndir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="features-section mb-5">
    <h2 class="text-center mb-4">Neden Bizim Servisimizi Kullanmalısınız?</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto">
                        <i class="bi bi-lightning-charge-fill fs-2"></i>
                    </div>
                    <h3 class="h5">Hızlı ve Güvenilir</h3>
                    <p class="text-muted">99.9% uptime garantisi ile kesintisiz hizmet sunuyoruz.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-success bg-opacity-10 text-success rounded-circle p-3 mb-3 mx-auto">
                        <i class="bi bi-bar-chart-line-fill fs-2"></i>
                    </div>
                    <h3 class="h5">Detaylı Analitik</h3>
                    <p class="text-muted">Tıklama istatistikleri ve kullanıcı demografik bilgileri.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="feature-icon bg-info bg-opacity-10 text-info rounded-circle p-3 mb-3 mx-auto">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h3 class="h5">Gelişmiş Güvenlik</h3>
                    <p class="text-muted">SSL şifreleme ve kötü amaçlı URL koruması.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cta-section bg-primary text-white rounded-3 p-5 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="fw-bold">Hemen Ücretsiz Kaydolun</h2>
            <p>Profesyonel özelliklerin kilidini açın ve bağlantılarınızın performansını artırın.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="/register" class="btn btn-light btn-lg px-4">Ücretsiz Hesap Aç</a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
$scripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    // URL kısaltma formu
    const urlForm = document.getElementById('urlForm');
    const resultContainer = document.getElementById('resultContainer');
    const shortUrlInput = document.getElementById('shortUrl');
    const copyBtn = document.getElementById('copyBtn');
    
    urlForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!urlForm.checkValidity()) {
            e.stopPropagation();
            urlForm.classList.add('was-validated');
            return;
        }
        
        // Burada AJAX çağrısı yapılacak
        const originalUrl = document.getElementById('originalUrl').value;
        const customAlias = document.getElementById('customAlias').value;
        
        // Demo amaçlı
        setTimeout(() => {
            const demoShortUrl = window.location.origin + '/' + (customAlias || 'abc123');
            shortUrlInput.value = demoShortUrl;
            resultContainer.classList.remove('d-none');
            
            // QR kod butonunu aktif et
            document.getElementById('qrCodeBtn').onclick = function() {
                document.getElementById('qrCodeContainer').classList.remove('d-none');
                document.getElementById('qrCodeImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(demoShortUrl);
            };
            
            // Diğer butonları ayarla
            document.getElementById('visitBtn').href = demoShortUrl;
            document.getElementById('statsBtn').href = '/stats/abc123';
            document.getElementById('shareBtn').onclick = function() {
                navigator.share({
                    title: 'Kısaltılmış URL',
                    text: 'Bu kısaltılmış URL\'yi paylaşın',
                    url: demoShortUrl
                }).catch(console.error);
            };
            
            document.getElementById('downloadQrBtn').onclick = function(e) {
                e.preventDefault();
                const link = document.createElement('a');
                link.href = document.getElementById('qrCodeImage').src;
                link.download = 'qr-code.png';
                link.click();
            };
        }, 1000);
    });
    
    // Kopyala butonu
    copyBtn.addEventListener('click', function() {
        shortUrlInput.select();
        document.execCommand('copy');
        
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="bi bi-check"></i> Kopyalandı!';
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
        }, 2000);
    });
});
</script>
JS;

include __DIR__ . '/layouts/app.php';
?>