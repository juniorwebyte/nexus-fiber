<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__, 2);
$lockFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'installed.lock';
$configFile = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.php';
$rootHtaccess = $root . DIRECTORY_SEPARATOR . '.htaccess';
$installHtacc = __DIR__ . DIRECTORY_SEPARATOR . '.htaccess';
$logFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'php-error.log';

function h(string $v): string
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function detect_base_url(bool $isHttps): string
{
  $scheme = $isHttps ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = preg_replace('#/(drop/)?install(?:/post-check\\.php)?$#i', '', $script);
  $basePath = is_string($basePath) ? rtrim($basePath, '/') : '';
  return $scheme . $host . ($basePath !== '' ? $basePath : '') . '/';
}

function read_local_config(string $path): array
{
  if (!is_file($path))
    return [];
  $loaded = require $path;
  return is_array($loaded) ? $loaded : [];
}

function add_check(array &$checks, string $name, bool $ok, string $details = '', string $fix = ''): void
{
  $checks[] = ['name' => $name, 'ok' => $ok, 'details' => $details, 'fix' => $fix];
}

$checks = [];
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
  || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$detectedBaseUrl = detect_base_url($isHttps);
$cfg = read_local_config($configFile);

add_check(
  $checks,
  'Instalação detectada (installed.lock)',
  is_file($lockFile),
  is_file($lockFile) ? 'OK' : 'Não encontrado',
  'Finalize a instalação em /install/index.php'
);

add_check(
  $checks,
  'config/local.php existe',
  is_file($configFile),
  is_file($configFile) ? 'OK' : 'Não encontrado',
  'Reinstale ou gere config/local.php pelo instalador.'
);

add_check(
  $checks,
  'Escrita em /storage',
  is_dir($root . '/storage') && is_writable($root . '/storage'),
  $root . '/storage',
  'Ajuste permissões da pasta (chmod 755 ou via painel de hospedagem).'
);

add_check(
  $checks,
  'Escrita em /storage/logs',
  is_dir($root . '/storage/logs') ? is_writable($root . '/storage/logs') : (bool) @mkdir($root . '/storage/logs', 0755, true),
  $root . '/storage/logs',
  'Ajuste permissões da pasta.'
);

add_check($checks, '.htaccess na raiz existe', is_file($rootHtaccess), $rootHtaccess, 'Faça upload do arquivo .htaccess para a raiz do projeto.');
add_check($checks, '.htaccess em /install existe', is_file($installHtacc), $installHtacc, 'O install/.htaccess bloqueia acesso após a instalação.');

$apacheRewrite = null;
if (function_exists('apache_get_modules')) {
  $mods = apache_get_modules();
  $apacheRewrite = is_array($mods) ? in_array('mod_rewrite', $mods, true) : null;
}
add_check(
  $checks,
  'mod_rewrite ativo (Apache)',
  $apacheRewrite === null ? true : $apacheRewrite,
  $apacheRewrite === null
  ? 'Não detectável automaticamente (normal em Nginx ou Apache sem apache_get_modules).'
  : ($apacheRewrite ? 'Ativo' : 'Desativado'),
  'No XAMPP: habilite mod_rewrite e AllowOverride All no httpd.conf.'
);

$baseUrlSaved = (string) ($cfg['APP_BASE_URL'] ?? '');
if ($baseUrlSaved !== '') {
  $okBase = rtrim($baseUrlSaved, '/') . '/' === $detectedBaseUrl;
  add_check(
    $checks,
    'BASE_URL consistente com a pasta',
    $okBase,
    'Detectado: ' . $detectedBaseUrl . ' | Configurado: ' . rtrim($baseUrlSaved, '/') . '/',
    'Reinstale ou edite APP_BASE_URL em config/local.php.'
  );
}

/* DB checks */
$dbOk = false;
$tablesOk = false;
$dbDetails = '';
$tablesDetails = '';
$requiredTables = ['system_info', 'users', 'product_list', 'order_list', 'customer_list', 'order_number_list'];
$orderNumberUniqueOk = false;
$orderNumberUniqueDetails = '';
$orderNumberStatusOk = false;
$orderNumberStatusDetails = '';

$dbHost = (string) ($cfg['DB_SERVER'] ?? getenv('DB_SERVER') ?: '');
$dbUser = (string) ($cfg['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: '');
$dbPass = (string) ($cfg['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '');
$dbName = (string) ($cfg['DB_NAME'] ?? getenv('DB_NAME') ?: '');

if ($dbHost && $dbUser && $dbName) {
  try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if (!$conn->connect_error) {
      $dbOk = true;
      $conn->set_charset('utf8mb4');
      $missing = [];
      foreach ($requiredTables as $t) {
        $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($t) . "'");
        if (!$res || $res->num_rows === 0)
          $missing[] = $t;
      }
      $tablesOk = count($missing) === 0;
      $tablesDetails = $tablesOk ? 'Todas as tabelas-chave foram encontradas.' : ('Faltando: ' . implode(', ', $missing));

      if ($tablesOk) {
        $idxRes = $conn->query("SHOW INDEX FROM `order_number_list` WHERE Key_name = 'uniq_product_lucky_number'");
        if ($idxRes && $idxRes->num_rows >= 2) {
          $hasProduct = false;
          $hasLucky = false;
          while ($idxRow = $idxRes->fetch_assoc()) {
            $col = (string)($idxRow['Column_name'] ?? '');
            if ($col === 'product_id')
              $hasProduct = true;
            if ($col === 'lucky_number')
              $hasLucky = true;
          }
          $orderNumberUniqueOk = $hasProduct && $hasLucky;
        }
        $orderNumberUniqueDetails = $orderNumberUniqueOk
          ? 'Índice único uniq_product_lucky_number ativo.'
          : 'Índice único ausente/incompleto em order_number_list.';

        // Valida coluna status (o PHP e sincronização dependem disso)
        $colRes = $conn->query("SHOW COLUMNS FROM `order_number_list` LIKE 'status'");
        if ($colRes && $colRes->num_rows >= 1) {
          $orderNumberStatusOk = true;
          $orderNumberStatusDetails = 'Coluna status encontrada em order_number_list.';
        } else {
          $orderNumberStatusOk = false;
          $orderNumberStatusDetails = 'Coluna status ausente em order_number_list (inconsistência com migrations/bug de runtime).';
        }
      }
      $conn->close();
    } else {
      $dbDetails = 'Falha de conexão (' . $conn->connect_error . ').';
    }
  } catch (Throwable $e) {
    $dbDetails = 'Erro ao testar BD: ' . $e->getMessage();
  }
} else {
  $dbDetails = 'Credenciais de BD não encontradas em config/local.php.';
}

add_check(
  $checks,
  'Conexão com banco de dados',
  $dbOk,
  $dbOk ? 'OK' : $dbDetails,
  'Verifique DB_* no instalador e em config/local.php.'
);

add_check(
  $checks,
  'Tabelas-chave presentes',
  $dbOk ? $tablesOk : false,
  $dbOk ? $tablesDetails : 'BD não conectado.',
  'Reinstale em um banco vazio e rode o SQL de schema.'
);

add_check(
  $checks,
  'Unicidade de cotas (order_number_list)',
  ($dbOk && $tablesOk) ? $orderNumberUniqueOk : false,
  ($dbOk && $tablesOk) ? $orderNumberUniqueDetails : 'Dependente de conexão/tabelas.',
  'Aplique a migração 2026_04_06_phase2_normalization_order_numbers.sql.'
);

add_check(
  $checks,
  'Coluna status em order_number_list',
  ($dbOk && $tablesOk) ? $orderNumberStatusOk : false,
  ($dbOk && $tablesOk) ? $orderNumberStatusDetails : 'Dependente de conexão/tabelas.',
  'Ajuste a migração/hardening para criar order_number_list.status (ex.: 2026_03_30_hardening.sql).'
);

$allOk = array_reduce($checks, fn($c, $r) => $c && $r['ok'], true);

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

$logoRelPath = '../img/logo.png';
$logoExists = is_file(dirname(__DIR__) . '/img/logo.png');
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Diagnóstico Pós-Instalação – WebyteHub</title>
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
      max-width: 820px;
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

    .header-meta {
      flex: 1;
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
      margin-bottom: 14px;
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

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 18px;
    }

    .info-item {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 9px;
      padding: 10px 14px;
    }

    .info-item label {
      font-size: 10px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .04em;
      display: block;
      margin-bottom: 4px;
    }

    .info-item code {
      font-size: 12px;
      word-break: break-all;
    }

    .check-table {
      width: 100%;
      border-collapse: collapse;
    }

    .check-table th,
    .check-table td {
      padding: 9px 10px;
      text-align: left;
      font-size: 13px;
      border-bottom: 1px solid var(--border);
      vertical-align: top;
    }

    .check-table th {
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

    a {
      color: #a5b4fc;
    }

    .recs {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 9px;
      padding: 14px 16px;
      margin-top: 18px;
    }

    .recs h3 {
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .recs ul {
      padding-left: 16px;
    }

    .recs li {
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 6px;
      line-height: 1.5;
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
      .info-grid {
        grid-template-columns: 1fr;
      }

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
          <h1>Diagnóstico Pós-Instalação</h1>
          <p>Verifique a integridade do ambiente após a instalação</p>
        </div>
      </div>

      <div class="card-body">
        <div class="sec-title">
          <div class="sec-icon">🩺</div> Status do Ambiente
        </div>

        <div class="alert <?= $allOk ? 'alert-success' : 'alert-danger' ?>">
          <span><?= $allOk ? '✅' : '⚠️' ?></span>
          <div>
            <?= $allOk ? 'Ambiente validado. Pronto para operação.' : 'Foram encontrados pontos de atenção. Corrija os itens em vermelho.' ?>
          </div>
        </div>

        <!-- Info cards -->
        <div class="info-grid">
          <div class="info-item">
            <label>URL Detectada</label>
            <code><?= h($detectedBaseUrl) ?></code>
          </div>
          <div class="info-item">
            <label>URL Configurada</label>
            <code><?= $baseUrlSaved !== '' ? h($baseUrlSaved) : '—' ?></code>
          </div>
          <div class="info-item">
            <label>Acesso Admin</label>
            <code><?= h(parse_url($detectedBaseUrl, PHP_URL_PATH) ?: '/') ?>admin/login.php</code>
          </div>
          <div class="info-item">
            <label>Log de Erros PHP</label>
            <code style="font-size:11px;word-break:break-all"><?= h($logFile) ?></code>
          </div>
        </div>

        <table class="check-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Status</th>
              <th>Detalhes</th>
              <th>Como corrigir</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($checks as $c): ?>
              <tr>
                <td><?= h($c['name']) ?></td>
                <td class="<?= $c['ok'] ? 'ok' : 'fail' ?>"><?= $c['ok'] ? '✓ OK' : '✗ FALHA' ?></td>
                <td style="font-size:12px;color:var(--muted)"><?= h($c['details']) ?></td>
                <td style="font-size:12px;color:var(--muted)"><?= h($c['fix']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="recs">
          <h3>📋 Recomendações Finais</h3>
          <ul>
            <li>Remova a pasta <code>/install</code> após concluir a instalação.</li>
            <li>Ative HTTPS em produção e certifique-se que <code>APP_BASE_URL</code> corresponde ao domínio.</li>
            <li>Se ocorrer erro de pagamento/webhook, verifique <code>admin/?page=logs</code> e
              <code>storage/logs/php-error.log</code>.
            </li>
            <li>Faça backup periódico do banco de dados e dos arquivos <code>config/local.php</code> e
              <code>storage/</code>.
            </li>
            <li>Em ambiente VPS, defina permissões <code>644</code> para arquivos e <code>755</code> para diretórios.
            </li>
          </ul>
        </div>

        <div class="btn-row">
          <a class="btn btn-primary" href="index.php">← Voltar ao Instalador</a>
          <a class="btn btn-ghost" href="health-check.php">Health Check</a>
        </div>
      </div>
    </div>

    <p style="text-align:center;margin-top:16px;font-size:11px;color:var(--muted)">
      WebyteHub — remova a pasta <code>/install</code> após a instalação por segurança.
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


