<?php

$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// 1. Roteamento de API (Backend)
if (strpos($requestUri, '/api/') !== false) {
    require_once __DIR__ . '/backend/public/index.php';
    exit;
}

// Definições de caminhos fundamentais para o ecossistema Drop
$configFile = __DIR__ . '/backend/config/config.php';
$lockFile = __DIR__ . '/backend/storage/installed.lock';

// Helper para detectar o caminho base do ecossistema
function detect_base_path_root() {
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = preg_replace('#/index\\.php$#i', '', $script);
    return is_string($basePath) ? rtrim($basePath, '/') : '';
}

// Check de Modo de Manutenção
if (file_exists(__DIR__ . '/.maintenance')) {
    http_response_code(503);
    echo "<div style='background:#030406; color:#fff; height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif; position:relative; overflow:hidden;'>";
    echo "<div style='position:absolute; inset:0; background:radial-gradient(circle at 50% 50%, rgba(139,92,246,0.1), transparent 70%); filter:blur(100px); z-index:0;'></div>";
    echo "<div style='width:60px; height:60px; border:4px solid #8b5cf6; border-top-color:#06b6d4; border-radius:50%; animation: spin 1s linear infinite; position:relative; z-index:1; box-shadow: 0 0 20px rgba(139,92,246,0.3);'></div>";
    echo "<h1 style='margin-top:30px; letter-spacing:-1px; font-weight:900; position:relative; z-index:1;'>SINFONIA EM CURSO</h1>";
    echo "<p style='color:#94a3b8; position:relative; z-index:1; font-size:14px; text-transform:uppercase; letter-spacing:1px;'>Sincronizando núcleos do Ecossistema DROP</p>";
    echo "<style>@keyframes spin { to { transform: rotate(360deg); } }</style>";
    echo "</div>";
    exit;
}

// Se já estiver instalado, serve o Backend MVC em PHP
if (file_exists($lockFile)) {
    $basePath = detect_base_path_root();
    $backendUrl = ($basePath !== '' ? $basePath : '') . '/backend/';
    header("Location: " . $backendUrl);
    exit;
}

// Se não estiver instalado, mostra a tela de instalação
$basePath = detect_base_path_root();
$installUrl = ($basePath !== '' ? $basePath : '') . '/install/';

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-br">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bem-vindo • Instalador</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@1.3.0/dist/fonts/geist.css">
<style>
    :root {
    --background: #030406;
    --foreground: #f8fafc;
    --primary: #8b5cf6;
    --accent: #06b6d4;
    --card: rgba(255,255,255,0.02);
    --border: rgba(139,92,246,0.2);
    }

    * {
    box-sizing: border-box
    }

    html,
    body {
    height: 100%
    }

    body {
    margin: 0;
    background: var(--background);
    color: var(--foreground);
    font-family: 'Geist Sans', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    overflow-x: hidden;
    }

    code {
    font-family: 'Geist Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace
    }

    .bgGlow {
    position: fixed;
    inset: -20%;
    background:
        radial-gradient(60% 60% at 20% 20%, color-mix(in oklch, var(--primary) 45%, transparent) 0%, transparent 60%),
        radial-gradient(60% 60% at 80% 30%, color-mix(in oklch, var(--accent) 35%, transparent) 0%, transparent 65%);
    filter: blur(40px);
    opacity: .9;
    pointer-events: none;
    }

    #particleCanvas {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    opacity: .55;
    }

    .wrap {
    position: relative;
    min-height: 100%;
    display: grid;
    place-items: center;
    padding: 28px 18px;
    }

    .card {
    width: min(980px, 100%);
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 22px;
    padding: 22px;
    backdrop-filter: blur(14px);
    box-shadow: 0 18px 55px rgba(0, 0, 0, .45);
    }

    .top {
    display: flex;
    gap: 14px;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 14px;
    }

    .brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--foreground);
    font-weight: 700;
    margin-bottom: 10px;
    }

    .brand img {
    height: 42px;
    width: auto;
    object-fit: contain;
    border-radius: 8px;
    filter: drop-shadow(0 0 11px rgba(68, 246, 179, .4));
    transition: transform .3s ease;
    }

    .brand:hover img {
    transform: scale(1.08);
    }

    .badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: color-mix(in oklch, var(--card) 80%, transparent);
    backdrop-filter: blur(10px);
    font-size: 12px;
    opacity: .95;
    }

    .dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: var(--primary);
    box-shadow: 0 0 0 4px color-mix(in oklch, var(--primary) 20%, transparent);
    animation: pulse 1.6s ease-in-out infinite;
    }

    @keyframes pulse {

    0%,
    100% {
        transform: scale(1);
        opacity: .9
    }

    50% {
        transform: scale(1.15);
        opacity: 1
    }
    }

    h1 {
    margin: 0;
    font-size: clamp(22px, 3.6vw, 34px);
    letter-spacing: -.02em
    }

    p {
    margin: 10px 0 0 0;
    color: color-mix(in oklch, var(--foreground) 78%, transparent);
    line-height: 1.55
    }

    .grid {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 14px;
    margin-top: 16px;
    }

    @media (max-width: 860px) {
    .grid {
        grid-template-columns: 1fr
    }
    }

    .panel {
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 16px;
    background: color-mix(in oklch, var(--card) 65%, transparent);
    }

    .btnRow {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px
    }

    .btn {
    appearance: none;
    border: 1px solid color-mix(in oklch, var(--primary) 55%, var(--border));
    background: color-mix(in oklch, var(--primary) 65%, transparent);
    color: var(--primary-foreground);
    padding: 12px 16px;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform .18s ease, filter .18s ease, background .18s ease;
    will-change: transform;
    }

    .btn:hover {
    transform: translateY(-1px) scale(1.02);
    filter: brightness(1.05)
    }

    .btn:active {
    transform: scale(.98)
    }

    .btnSecondary {
    border: 1px solid var(--border);
    background: color-mix(in oklch, var(--card) 75%, transparent);
    color: var(--foreground);
    font-weight: 600;
    }

    .hint {
    margin-top: 10px;
    font-size: 12px;
    color: color-mix(in oklch, var(--foreground) 65%, transparent);
    }

    .reveal {
    opacity: 0;
    transform: translateY(18px);
    transition: opacity .7s ease, transform .7s ease
    }

    .reveal.on {
    opacity: 1;
    transform: translateY(0)
    }
</style>
</head>

<body>
<div class="bgGlow"></div>
<canvas id="particleCanvas" aria-hidden="true"></canvas>
<div class="wrap">
    <div class="card">
    <div class="top reveal">
        <div class="brand">
        <img src="img/logo.png" alt="WebyteHub" loading="lazy">
        <div>
            <strong>WebyteHub</strong>
            <div style="font-size:12px;opacity:.75">Instalador automático</div>
        </div>
        </div>
        <div class="badge"><span class="dot"></span> Primeira execução detectada</div>
        <div class="badge">Instalação guiada em <code>/install</code></div>
    </div>
    <div class="reveal">
        <h1>Bem-vindo. Vamos instalar seu app em poucos passos.</h1>
        <p>Para começar com segurança (banco, URL base, admin e migrações), use o instalador automático. Ele funciona tanto em VPS quanto em subpasta no XAMPP.</p>
    </div>

    <div class="grid">
        <div class="panel reveal">
            <strong>Próximo passo</strong>
            <p style="margin-top:8px">Clique no botão abaixo para abrir o instalador.</p>
            <div class="btnRow">
            <a class="btn" href="<?= htmlspecialchars($installUrl, ENT_QUOTES, 'UTF-8') ?>">Ir para o instalador</a>
            </div>
            <div class="hint">Dica: depois de instalar, a interface real da plataforma será exibida.</div>
        </div>
        <div class="panel reveal">
            <strong>Onde estou instalando?</strong>
            <p style="margin-top:8px">Base detectada:
            <code><?= htmlspecialchars(($basePath !== '' ? $basePath : '/'), ENT_QUOTES, 'UTF-8') ?></code>
            </p>
            <p class="hint">Se estiver em VPS, prefira a raiz do domínio. Em XAMPP, subpasta é normal.</p>
        </div>
    </div>
    </div>
</div>
<script>
    // Reveal
    document.addEventListener("DOMContentLoaded", () => {
    const els = document.querySelectorAll('.reveal');
    els.forEach((el, i) => setTimeout(() => el.classList.add('on'), 80 + i * 90));

    // Particle background (desktop only)
    const isMobile = matchMedia('(max-width: 768px)').matches;
    const canvas = document.getElementById('particleCanvas');
    if (isMobile || !canvas) return;

    const ctx = canvas.getContext('2d');
    const DPR = Math.min(2, window.devicePixelRatio || 1);
    let w = 0, h = 0, particles = [];
    const PCOUNT = 52;
    const MAXD = 140;

    function resize() {
        w = canvas.clientWidth; h = canvas.clientHeight;
        canvas.width = Math.floor(w * DPR);
        canvas.height = Math.floor(h * DPR);
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    }
    function rnd(min, max) { return Math.random() * (max - min) + min; }
    function init() {
        particles = Array.from({ length: PCOUNT }).map(() => ({
        x: rnd(0, w), y: rnd(0, h),
        vx: rnd(-0.35, 0.35), vy: rnd(-0.35, 0.35),
        r: rnd(1.2, 2.2)
        }));
    }
    function step() {
        ctx.clearRect(0, 0, w, h);
        // draw links
        for (let i = 0; i < particles.length; i++) {
        const a = particles[i];
        a.x += a.vx; a.y += a.vy;
        if (a.x < 0 || a.x > w) a.vx *= -1;
        if (a.y < 0 || a.y > h) a.vy *= -1;
        for (let j = i + 1; j < particles.length; j++) {
            const b = particles[j];
            const dx = a.x - b.x, dy = a.y - b.y;
            const d = Math.hypot(dx, dy);
            if (d < MAXD) {
            const alpha = (1 - d / MAXD) * 0.35;
            ctx.strokeStyle = `rgba(80,170,255,${alpha})`;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.lineTo(b.x, b.y);
            ctx.stroke();
            }
        }
        }
        // draw dots
        for (const p of particles) {
        ctx.fillStyle = 'rgba(130, 210, 255, .85)';
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
        }
        requestAnimationFrame(step);
    }
    window.addEventListener('resize', () => { resize(); init(); }, { passive: true });
    resize(); init(); step();
    });
</script>
</body>
</html>