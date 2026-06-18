<?php
$scrollDir = dirname(dirname(dirname(__DIR__))) . '/img/scroll/';
$scrollImages = [];
if (is_dir($scrollDir)) {
    $files = scandir($scrollDir);
    foreach ($files as $f) {
        if (preg_match('/\.(png|jpe?g|webp)$/i', $f)) {
            $scrollImages[] = '/img/scroll/' . $f;
        }
    }
}
if (empty($scrollImages)) $scrollImages = ['/img/logo.png'];
$defaultBg = $scrollImages[array_rand($scrollImages)];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Drop Nexus Fiber</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@1.3.0/dist/fonts/geist.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#030406">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #030406;
            color: #f8fafc;
        }

        /* Background effects from installer */
        #db-master-bg {
            position: fixed;
            top: -15vh; left: -15vw; right: -15vw; bottom: -15vh;
            z-index: -10;
            background: linear-gradient(rgba(3, 4, 6, 0.8), rgba(3, 4, 6, 0.9)), 
                        url('<?= $defaultBg ?>') center/cover no-repeat;
            filter: contrast(1.1) brightness(0.7);
            transition: opacity 1.5s ease;
            pointer-events: none;
        }
        .bg-glow { 
            position: fixed; 
            top: -30%; left: -30%; right: -30%; bottom: -30%;
            background: radial-gradient(circle at 10% 20%, rgba(139, 92, 246, 0.15), transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.1), transparent 50%); 
            filter: blur(100px); z-index: -9; pointer-events: none;
        }
        .bg-happiness { 
            position: fixed; bottom: -10%; right: -10%; width: 50%; height: 50%; 
            background: radial-gradient(circle, rgba(139, 92, 246, 0.03), transparent 70%); 
            filter: blur(100px); z-index: -8; pointer-events: none; animation: breatheJoy 12s infinite alternate; 
        }

        #particleCanvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: .4;
            z-index: 0;
        }

        @keyframes breatheJoy {
            0% { transform: scale(1) translate(0, 0); opacity: 0.8; }
            100% { transform: scale(1.1) translate(-2%, -2%); opacity: 1; }
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            z-index: 1;
            background: rgba(255, 255, 255, .02);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 20px;
            backdrop-filter: blur(18px);
            box-shadow: 0 24px 64px rgba(0, 0, 0, .55);
        }
    </style>
</head>

<body>
    <div id="db-master-bg"></div>
    <div class="bg-glow"></div>
    <div class="bg-happiness"></div>
    <canvas id="particleCanvas"></canvas>

    <div class="login-box">
        <div style="text-align: center; margin-bottom: 30px;">
            <img src="/img/logo.png" alt="Nexus Fiber" style="height: 60px;">
            <h2 style="margin-top: 10px; color: var(--primary);">Acesso Operacional</h2>
        </div>
        <?php require $viewPath; ?>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js').then(function(registration) {
                    console.log('ServiceWorker registrado: ', registration.scope);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Particle logic
            const canvas = document.getElementById('particleCanvas');
            const ctx = canvas.getContext('2d');
            let w, h;
            const particles = [];
            function resize() {
                w = canvas.width = window.innerWidth;
                h = canvas.height = window.innerHeight;
            }
            function init() {
                particles.length = 0;
                for (let i = 0; i < 40; i++) {
                    particles.push({
                        x: Math.random() * w, y: Math.random() * h,
                        r: Math.random() * 2 + 0.5,
                        dx: (Math.random() - 0.5) * 0.4, dy: (Math.random() - 0.5) * 0.4,
                        o: Math.random() * 0.5 + 0.1
                    });
                }
            }
            function step() {
                ctx.clearRect(0, 0, w, h);
                for (let p of particles) {
                    p.x += p.dx; p.y += p.dy;
                    if (p.x < 0 || p.x > w) p.dx *= -1;
                    if (p.y < 0 || p.y > h) p.dy *= -1;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(139, 92, 246, ${p.o})`;
                    ctx.fill();
                }
                requestAnimationFrame(step);
            }
            window.addEventListener('resize', function () { resize(); init(); }, { passive: true });
            resize(); init(); step();

            /* Background Image Rotation */
            const images = <?= json_encode($scrollImages) ?>;
            const bgMaster = document.getElementById('db-master-bg');
            let current = 0;

            async function nextSlide() {
                if (!images || images.length === 0) return;
                current = (current + 1) % images.length;
                const nextImg = images[current];
                
                bgMaster.style.opacity = '0';
                await new Promise(r => setTimeout(r, 1500));
                
                const full = `linear-gradient(rgba(3, 4, 6, 0.8), rgba(3, 4, 6, 0.9)), url('${nextImg}') center/cover no-repeat`;
                bgMaster.style.background = full;
                bgMaster.style.opacity = '1';
            }

            if (images && images.length > 1) {
                setInterval(nextSlide, 8000);
            }
        });
    </script>
</body>

</html>