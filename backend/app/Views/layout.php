<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Drop Nexus Fiber - App</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#030406">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>

    <header class="app-header">
        <img src="<?= BASE_URL ?>/img/logo-nexus.png" alt="Nexus Fiber" class="brand-logo">
        <div style="font-weight: bold; color: var(--primary);">Técnico</div>
    </header>

    <div class="app-container">
        <?php include $viewPath; ?>
    </div>

    <nav class="bottom-nav">
        <a href="<?= BASE_URL ?>/" class="nav-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <span>🏠</span>
            Início
        </a>
        <a href="<?= BASE_URL ?>/simulador" class="nav-item <?= $activeMenu === 'simulador' ? 'active' : '' ?>">
            <span>📡</span>
            Simulador
        </a>
        <a href="<?= BASE_URL ?>/calculadora" class="nav-item <?= $activeMenu === 'calculadora' ? 'active' : '' ?>">
            <span>🧮</span>
            Cálculo
        </a>
        <a href="#" class="nav-item">
            <span>📚</span>
            Academia
        </a>
    </nav>

    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js').then(function(registration) {
                    console.log('ServiceWorker registrado com sucesso no escopo: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker falhou no registro: ', err);
                });
            });
        }
    </script>
</body>
</html>
