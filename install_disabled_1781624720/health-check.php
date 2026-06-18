<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__, 2);
$lockFile = $root . '/storage/installed.lock';
$isInstalled = is_file($lockFile);
$checks = [];

$scrollDir = dirname(__DIR__) . '/img/scroll/';
$scrollImages = [];
if (is_dir($scrollDir)) {
    $files = scandir($scrollDir);
    foreach ($files as $f) {
        if (preg_match('/\.(png|jpe?g|webp)$/i', $f)) $scrollImages[] = '../img/scroll/' . $f;
    }
}
if (empty($scrollImages)) $scrollImages = ['../img/logo.png'];
$defaultBg = $scrollImages[array_rand($scrollImages)];

function add_check(array &$checks, string $name, bool $ok, string $details = ''): void
{
  $checks[] = ['name' => $name, 'ok' => $ok, 'details' => $details];
}

add_check($checks, 'PHP >= 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), 'PHP ' . PHP_VERSION);
add_check($checks, 'Extensão mysqli', extension_loaded('mysqli'));
add_check($checks, 'Extensão curl', extension_loaded('curl'));
add_check($checks, 'Extensão openssl', extension_loaded('openssl'));
add_check($checks, 'Extensão mbstring', extension_loaded('mbstring'));
add_check($checks, 'Extensão gd (Imagens)', extension_loaded('gd'));
add_check($checks, 'Extensão bcmath (Financeiro)', extension_loaded('bcmath'));
add_check($checks, 'Extensão zip', extension_loaded('zip'), extension_loaded('zip') ? 'OK' : 'Opcional mas recomendada');
add_check($checks, 'Arquivo SQL', is_file($root . '/database/weby_play.sql'), 'database/weby_play.sql');
add_check(
  $checks,
  'Migração estrutura SQL',
  is_file($root . '/database/migrations/2026_04_06_schema_structure.sql'),
  'database/migrations/2026_04_06_schema_structure.sql'
);
add_check(
  $checks,
  'Migração Fase 2 (order_number_list)',
  is_file($root . '/database/migrations/2026_04_06_phase2_normalization_order_numbers.sql'),
  'database/migrations/2026_04_06_phase2_normalization_order_numbers.sql'
);

if (!is_dir($root . '/storage'))
  @mkdir($root . '/storage', 0755, true);
if (!is_dir($root . '/config'))
  @mkdir($root . '/config', 0755, true);
add_check($checks, 'Escrita em /storage', is_writable($root . '/storage'), $root . '/storage');
add_check($checks, 'Escrita em /config', is_writable($root . '/config'), $root . '/config');

$allOk = array_reduce($checks, fn($c, $r) => $c && $r['ok'], true);

function h(string $v): string
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$logoRelPath = '../img/logo.png';
$logoExists = is_file(dirname(__DIR__) . '/img/logo.png');
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Health Check – WebyteHub</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #0b0d14;
      --surface: rgba(255, 255, 255, .04);
      --surface2: rgba(255, 255, 255, .07);
      --border: rgba(255, 255, 255, .09);
      --border2: rgba(255, 255, 255, .14);
      --text: #e8ecf4;
      --muted: #7a869a;
      --primary: #6366f1;
      --primary-h: #818cf8;
      --success: #22c55e;
      --danger: #ef4444;
    }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      font-size: 14px;
      min-height: 100vh;
    }

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
      z-index: -7;
    }

    .page {
      position: relative;
      z-index: 1;
      max-width: 780px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      backdrop-filter: blur(18px);
      box-shadow: 0 24px 64px rgba(0, 0, 0, .55);
      overflow: hidden;
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
      background: var(--surface2);
    }

    .logo-wrap img {
      height: 36px;
      width: auto;
      object-fit: contain;
      display: block;
    }

    .logo-fallback {
      font-size: 18px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary-h), #a5b4fc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header-meta h1 {
      font-size: 17px;
      font-weight: 700;
    }

    .header-meta p {
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
    }

    .card-body {
      padding: 22px 24px 24px;
    }

    .sec-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 14px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border);
    }

    .sec-icon {
      width: 30px;
      height: 30px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      background: rgba(99, 102, 241, .15);
      border: 1px solid rgba(99, 102, 241, .25);
    }

    .alert {
      padding: 11px 14px;
      border-radius: 9px;
      font-size: 13px;
      line-height: 1.5;
      margin-bottom: 16px;
      display: flex;
      gap: 8px;
      align-items: flex-start;
    }

    .alert-success {
      background: rgba(34, 197, 94, .1);
      border: 1px solid rgba(34, 197, 94, .25);
      color: #86efac;
    }

    .alert-danger {
      background: rgba(239, 68, 68, .1);
      border: 1px solid rgba(239, 68, 68, .25);
      color: #fca5a5;
    }

    .alert-info {
      background: rgba(99, 102, 241, .1);
      border: 1px solid rgba(99, 102, 241, .25);
      color: #c7d2fe;
    }

    .req-table {
      width: 100%;
      border-collapse: collapse;
    }

    .req-table th,
    .req-table td {
      padding: 9px 10px;
      text-align: left;
      font-size: 13px;
      border-bottom: 1px solid var(--border);
    }

    .req-table th {
      font-size: 11px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .ok {
      color: var(--success);
      font-weight: 700;
    }

    .fail {
      color: var(--danger);
      font-weight: 700;
    }

    code {
      font-family: monospace;
      background: rgba(255, 255, 255, .08);
      padding: 2px 6px;
      border-radius: 5px;
      font-size: 12px;
    }

    .btn-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 18px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      border-radius: 9px;
      font-size: 13px;
      font-weight: 700;
      border: none;
      cursor: pointer;
      font-family: inherit;
      text-decoration: none;
      transition: transform .18s, filter .18s;
    }

    .btn:hover {
      transform: translateY(-1px) scale(1.02);
      filter: brightness(1.06);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), #818cf8);
      color: #fff;
      box-shadow: 0 4px 18px rgba(99, 102, 241, .3);
    }

    .btn-ghost {
      background: var(--surface2);
      color: var(--text);
      border: 1px solid var(--border2);
    }

    @media(max-width:600px) {
      .card-body {
        padding: 16px;
      }
    }
  </style>
</head>

<body>
    <div id="db-master-bg"></div>
  <div class="bg-glow"></div>
  <div class="bg-happiness"></div>
  <canvas id="particleCanvas"></canvas>
  <div class="page">
    <div class="card">
      <div class="card-header">
        <div class="logo-wrap">
          <?php if ($logoExists): ?>
            <img src="<?= h($logoRelPath) ?>" alt="WebyteHub Logo">
          <?php else: ?>
            <span class="logo-fallback">WebyteHub</span>
          <?php endif; ?>
        </div>
        <div class="header-meta">
          <h1>Health Check do Servidor</h1>
          <p>Verifique se o ambiente atende todos os requisitos</p>
        </div>
      </div>

      <div class="card-body">
        <div class="sec-title">
          <div class="sec-icon">🔍</div> Requisitos do Sistema
        </div>

        <?php if ($isInstalled): ?>
          <div class="alert alert-info">
            <span>ℹ️</span>
            <div>Instalação já concluída. Por segurança, use <a href="post-check.php"
                style="color:#a5b4fc">post-check.php</a> e remova a pasta <code>/install</code>.</div>
          </div>
        <?php endif; ?>

        <div class="alert <?= $allOk ? 'alert-success' : 'alert-danger' ?>">
          <span><?= $allOk ? '✅' : '❌' ?></span>
          <div>
            <?= $allOk ? 'Tudo pronto para instalar.' : 'Foram encontrados bloqueios. Corrija os itens em vermelho antes de instalar.' ?>
          </div>
        </div>

        <table class="req-table">
          <thead>
            <tr>
              <th>Requisito</th>
              <th>Status</th>
              <th>Detalhes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($checks as $c): ?>
              <tr>
                <td><?= h($c['name']) ?></td>
                <td class="<?= $c['ok'] ? 'ok' : 'fail' ?>"><?= $c['ok'] ? '✓ OK' : '✗ FALHA' ?></td>
                <td style="font-size:12px;color:var(--muted)"><?= h($c['details']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="btn-row">
          <a class="btn btn-primary" href="index.php">← Voltar ao Instalador</a>
          <a class="btn btn-ghost" href="post-check.php">Diagnóstico pós-instalação</a>
        </div>
      </div>
    </div>

    <p style="text-align:center;margin-top:16px;font-size:11px;color:var(--muted)">
      WebyteHub — remova a pasta <code>/install</code> após a instalação.
    </p>
  </div>
<script>
    // Particulas
    document.addEventListener("DOMContentLoaded", function () {
      const canvas = document.getElementById('particleCanvas');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      let w, h, particles = [];
      const MAXD = 120;
      function resize() { w = canvas.width = window.innerWidth; h = canvas.height = window.innerHeight; }
      function init() {
        particles = [];
        for (let i = 0; i < (window.innerWidth < 768 ? 30 : 60); i++) {
          particles.push({
            x: Math.random() * w, y: Math.random() * h,
            vx: (Math.random() - .5) * .6, vy: (Math.random() - .5) * .6,
            r: Math.random() * 2 + 1
          });
        }
      }
      function step() {
        ctx.clearRect(0, 0, w, h);
        particles.forEach(function (a, i) {
          a.x += a.vx; a.y += a.vy;
          if (a.x < 0 || a.x > w) a.vx *= -1;
          if (a.y < 0 || a.y > h) a.vy *= -1;
          for (var j = i + 1; j < particles.length; j++) {
            var b = particles[j], dx = a.x - b.x, dy = a.y - b.y, d = Math.hypot(dx, dy);
            if (d < MAXD) {
              ctx.strokeStyle = 'rgba(99,102,241,' + (((1 - d / MAXD) * .3)) + ')';
              ctx.lineWidth = .8;
              ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
            }
          }
        });
        particles.forEach(function (p) { ctx.fillStyle = 'rgba(165,180,252,.8)'; ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fill(); });
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
          
          const full = "linear-gradient(rgba(3, 4, 6, 0.8), rgba(3, 4, 6, 0.9)), url('" + nextImg + "') center/cover no-repeat";
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


