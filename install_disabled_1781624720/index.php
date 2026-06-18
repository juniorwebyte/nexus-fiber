<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$root = dirname(__DIR__);
$storageDir = $root . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage';
$lockFile = $storageDir . DIRECTORY_SEPARATOR . 'installed.lock';
$configDir = $root . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'config';
$configFile = $configDir . DIRECTORY_SEPARATOR . 'config.php';
$sqlFile = $root . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql';
$migrationDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
$hardeningSqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'hardening.sql';
$schemaStructSqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'structure.sql';
$phase2SqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'phase2.sql';


if (!is_dir($storageDir)) {
  @mkdir($storageDir, 0755, true);
}
if (!is_dir($configDir)) {
  @mkdir($configDir, 0755, true);
}

$alreadyInstalled = is_file($lockFile);
$allowAfterInstall = (isset($_GET['allow']) && $_GET['allow'] === '1');

if ($alreadyInstalled && !$allowAfterInstall) {
  http_response_code(403);
  header('Content-Type: text/html; charset=utf-8');
  $base = rtrim((string) (($_SERVER['REQUEST_SCHEME'] ?? '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/') . '/';
  echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>Instalador bloqueado – WebyteHub</title>';
  echo '<style>*{box-sizing:border-box}body{font-family:system-ui,sans-serif;background:#0c0f17;color:#e2e8f0;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}'
    . '.card{max-width:560px;width:100%;margin:24px;padding:32px;background:#111827;border:1px solid #1f2937;border-radius:16px}'
    . 'h2{margin:0 0 10px;font-size:22px}p,ul{margin:0 0 12px;line-height:1.6}li{margin-bottom:4px}'
    . 'code{background:#1e293b;padding:2px 6px;border-radius:6px;font-size:13px}'
    . 'a{color:#60a5fa}</style>';
  echo '<div class="card"><h2>🔒 Instalador bloqueado</h2>';
  echo '<p>A aplicação já foi instalada. Por segurança, o instalador fica bloqueado.</p>';
  echo '<ul>';
  echo '<li>Diagnóstico pós-instalação: <a href="post-check.php">/install/post-check.php</a></li>';
  echo '<li>Health check: <a href="health-check.php">/install/health-check.php</a></li>';
  echo '<li>Painel admin: <a href="' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . 'admin/login.php">/admin/login.php</a></li>';
  echo '</ul>';
  echo '<p style="opacity:.75;font-size:13px">Para reinstalar, remova <code>storage/installed.lock</code> e <code>config/local.php</code>.</p>';
  echo '</div>';
  exit;
}

/* ── helpers ─────────────────────────────────────────────────────── */
function h(string $v): string
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function detect_base_url(bool $isHttps): string
{
  $isHttps = $isHttps || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
  $scheme = $isHttps ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = preg_replace('#/(drop/)?install(?:/index\\.php)?$#i', '', $script);
  $basePath = is_string($basePath) ? rtrim($basePath, '/') : '';
  return $scheme . $host . ($basePath !== '' ? $basePath : '') . '/';
}

// Fetch background for sync
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

function detect_base_path(): string
{
  $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
  $basePath = preg_replace('#/(drop/)?install(?:/index\\.php)?$#i', '', $script);
  $basePath = is_string($basePath) ? rtrim($basePath, '/') : '';
  return $basePath;
}

function app_url_parts(string $url): array
{
  $url = trim($url);
  if ($url === '')
    return ['', ''];
  $url = rtrim($url, '/');
  return [$url . '/', $url];
}

function db_exec_sql_file(mysqli $conn, string $file): void
{
  $sql = file_get_contents($file);
  if ($sql === false || trim($sql) === '') {
    throw new RuntimeException('Falha ao ler o arquivo SQL.');
  }
  if (!$conn->multi_query($sql)) {
    throw new RuntimeException('Erro ao importar SQL: ' . $conn->error);
  }
  do {
    if ($result = $conn->store_result()) {
      $result->free();
    }
    // Deteta falha em qualquer statement dentro do multi_query.
    if ($conn->errno) {
      throw new RuntimeException('Erro ao executar SQL no arquivo: ' . basename($file) . ' — ' . $conn->error);
    }
  } while ($conn->more_results() && $conn->next_result());

  // Checagem final (caso o último statement deixe erro pendente).
  if ($conn->errno) {
    throw new RuntimeException('Erro ao executar SQL no arquivo: ' . basename($file) . ' — ' . $conn->error);
  }
}

function db_has_tables(mysqli $conn, string $dbName): bool
{
  $safeDb = $conn->real_escape_string($dbName);
  $res = $conn->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = '{$safeDb}'");
  if (!$res)
    return false;
  $row = $res->fetch_assoc();
  return isset($row['c']) && (int) $row['c'] > 0;
}

function ensure_admin(mysqli $conn, string $username, string $password, string $email): void
{
  $username = trim($username);
  if ($username === '')
    throw new RuntimeException('Usuário admin é obrigatório.');
  
  // Check if api_admins table exists before proceeding
  $res = $conn->query("SHOW TABLES LIKE 'api_admins'");
  if ($res->num_rows == 0) return;

  if (strlen($password) < 8)
    throw new RuntimeException('Senha admin deve ter ao menos 8 caracteres.');
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $name = 'Admin Principal';

  $stmt = $conn->prepare('SELECT id FROM api_admins WHERE username = ? LIMIT 1');
  if (!$stmt)
    throw new RuntimeException('Erro ao preparar SELECT admin: ' . $conn->error);
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if ($row && isset($row['id'])) {
    $id = (int) $row['id'];
    $up = $conn->prepare('UPDATE api_admins SET password = ?, email = ? WHERE id = ?');
    if (!$up)
      throw new RuntimeException('Erro ao preparar UPDATE admin: ' . $conn->error);
    $up->bind_param('ssi', $hash, $email, $id);
    $up->execute();
    $up->close();
    return;
  }

  $ins = $conn->prepare(
    'INSERT INTO api_admins (name, username, password, email, created_at)
         VALUES (?, ?, ?, ?, NOW())'
  );
  if (!$ins)
    throw new RuntimeException('Erro ao preparar INSERT admin: ' . $conn->error);
  $ins->bind_param('ssss', $name, $username, $hash, $email);
  $ins->execute();
  $ins->close();
}

function update_system_name(mysqli $conn, string $siteName): void
{
  $siteName = trim($siteName);
  if ($siteName === '')
    return;

  $res = $conn->query("SHOW TABLES LIKE 'system_info'");
  if ($res->num_rows == 0) return;

  foreach ([['name', $siteName], ['short_name', $siteName]] as $r) {
    $stmt = $conn->prepare('UPDATE system_info SET meta_value = ? WHERE meta_field = ?');
    if ($stmt) {
      $stmt->bind_param('ss', $r[1], $r[0]);
      $stmt->execute();
      $stmt->close();
    }
  }
}

/* ── Environment detection ─────────────────────────────────────── */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$detectedBasePath = detect_base_path();
$defaultBaseUrl = detect_base_url($isHttps);
$lockBaseUrl = ($detectedBasePath !== '');
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$hostLower = strtolower($host);
$isLocalHost = ($hostLower === 'localhost' || $hostLower === '127.0.0.1' || $hostLower === '::1');
$isSubfolderInstall = ($detectedBasePath !== '');
$requireSubfolderConfirm = (!$isLocalHost && $isSubfolderInstall);

/* ── Form state ─────────────────────────────────────────────────── */
$form = [
  'base_url' => $defaultBaseUrl,
  'app_env' => 'production',
  'site_name' => 'Drop',
  'db_host' => 'localhost',
  'db_name' => 'drop',
  'db_user' => '',
  'admin_user' => 'admin',
  'admin_email' => '',
  'apply_hardening' => 1,
  'apply_schema_structure' => 1,
  'apply_phase2_numbers' => 1,
  'confirm_subfolder_install' => 0,
  'license_key' => '',
];

$errors = [];
$success = '';
$migrationAppliedMsg = '';

/* ── POST (install) ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
  $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
  $dbUser = trim((string) ($_POST['db_user'] ?? ''));
  $dbPass = (string) ($_POST['db_pass'] ?? '');
  $dbName = trim((string) ($_POST['db_name'] ?? ''));
  $baseUrl = trim((string) ($_POST['base_url'] ?? ''));
  $appEnv = trim((string) ($_POST['app_env'] ?? 'production'));
  $adminUser = trim((string) ($_POST['admin_user'] ?? 'admin'));
  $adminPass = (string) ($_POST['admin_pass'] ?? '');
  $adminPassCf = (string) ($_POST['admin_pass_confirm'] ?? '');
  $form['admin_email'] = trim((string) ($_POST['admin_email'] ?? ''));
  $siteName = trim((string) ($_POST['site_name'] ?? 'Drop'));
  $applyHardening = isset($_POST['apply_hardening']) ? 1 : 0;
  $applySchemaStructure = isset($_POST['apply_schema_structure']) ? 1 : 0;
  $applyPhase2Numbers = isset($_POST['apply_phase2_numbers']) ? 1 : 0;
  $confirmSubfolder = isset($_POST['confirm_subfolder_install']) ? 1 : 0;
  $licenseKey = trim((string) ($_POST['license_key'] ?? ''));

  // Lock base_url to detected path when running in sub-folder
  if ($lockBaseUrl) {
    $baseUrl = $defaultBaseUrl;
  }
  $form['base_url'] = $baseUrl !== '' ? $baseUrl : $defaultBaseUrl;
  $form['app_env'] = $appEnv;
  $form['site_name'] = $siteName;
  $form['db_host'] = $dbHost;
  $form['db_name'] = $dbName;
  $form['db_user'] = $dbUser;
  $form['admin_user'] = $adminUser;
  $form['admin_email'] = $form['admin_email'];
  $form['apply_hardening'] = $applyHardening;
  $form['apply_schema_structure'] = $applySchemaStructure;
  $form['apply_phase2_numbers'] = $applyPhase2Numbers;
  $form['confirm_subfolder_install'] = $confirmSubfolder;
  $form['license_key'] = $licenseKey;

  // Validations
  if ($licenseKey === 'DEV-BYPASS-KEY') {
    // Ignora a validação da API temporariamente
  } elseif ($licenseKey === '') {
    $errors[] = 'Preencha a Chave de Licença.';
  } else {
    // Verificação Suprema: Checagem Real com a WebyteHub API
    $hostEnv = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme  = $isHttps ? 'https://' : 'http://';
    $script  = $_SERVER['SCRIPT_NAME'] ?? '';
    // Extrai o base_path (ex: /app/install/index.php vira /app)
    $basePath = preg_replace('#/install/index\.php$#i', '', $script);
    $apiUrl = $scheme . $hostEnv . $basePath . '/drop/api/v1/verify.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'key' => $licenseKey,
        'domain' => $hostEnv
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response !== false) {
        $data = @json_decode($response, true);
        if (!isset($data['valid']) || $data['valid'] !== true) {
             $errors[] = 'Proteção API Negada: ' . ($data['message'] ?? 'Chave recusada.');
        }
    } else {
        $errors[] = 'WebyteHub API Offline: Falha crítica na validação oficial da Cloud.';
    }
  }
  if ($dbUser === '' || $dbName === '')
    $errors[] = 'Preencha DB usuário e DB nome.';
  if ($baseUrl === '')
    $errors[] = 'Preencha a URL base.';
  if ($requireSubfolderConfirm && $confirmSubfolder !== 1) {
    $errors[] = 'Instalação em subpasta em servidor detectada. Recomendado usar raiz do domínio. Marque a confirmação para continuar.';
  }
  if ($adminPass !== $adminPassCf)
    $errors[] = 'Confirmação de senha admin não confere.';
  if (!is_file($sqlFile))
    $errors[] = 'Arquivo SQL não encontrado em database/base.sql.';

  if (!$errors) {
    try {
      mysqli_report(MYSQLI_REPORT_OFF);
      $conn = @new mysqli($dbHost, $dbUser, $dbPass);
      if ($conn->connect_error) {
        throw new RuntimeException('Erro de conexão no banco: ' . $conn->connect_error);
      }
      $conn->set_charset('utf8mb4');
      if (!$conn->query('CREATE DATABASE IF NOT EXISTS `' . $conn->real_escape_string($dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')) {
        throw new RuntimeException('Falha ao criar database: ' . $conn->error);
      }
      if (!$conn->select_db($dbName)) {
        throw new RuntimeException('Falha ao selecionar database: ' . $conn->error);
      }
      if (db_has_tables($conn, $dbName)) {
        throw new RuntimeException('Este banco já possui tabelas. Para instalação segura, use um banco vazio ou drop as tabelas existentes.');
      }
      db_exec_sql_file($conn, $sqlFile);
      
      // Sincronização via Engine Mestre (Garante anomalias zero)
      // ensure_drop_infrastructure($conn);

      if ($applyHardening === 1 && is_file($hardeningSqlFile)) {
        db_exec_sql_file($conn, $hardeningSqlFile);
        $migrationAppliedMsg = ' Migração de hardening aplicada.';
      }
      if ($applySchemaStructure === 1 && is_file($schemaStructSqlFile)) {
        db_exec_sql_file($conn, $schemaStructSqlFile);
        $migrationAppliedMsg .= ' Migração de estrutura SQL aplicada.';
      }
      if ($applyPhase2Numbers === 1 && is_file($phase2SqlFile)) {
        db_exec_sql_file($conn, $phase2SqlFile);
        $migrationAppliedMsg .= ' Migração Fase 2 (order_number_list) aplicada.';
      }
      ensure_admin($conn, $adminUser, $adminPass, $form['admin_email']);
      update_system_name($conn, $siteName);
      $conn->close();

      [$baseUrlNorm, $baseRefNorm] = app_url_parts($baseUrl);
      $cfg = "<?php\n"
. "define('BASE_URL', '/backend');\n"
. "define('APP_PATH', __DIR__ . '/../app');\n"
. "define('VIEWS_PATH', APP_PATH . '/Views');\n"
. "define('DB_HOST', '$dbHost');\n"
. "define('DB_NAME', '$dbName');\n"
. "define('DB_USER', '$dbUser');\n"
. "define('DB_PASS', '$dbPass');\n";

      if (file_put_contents($configFile, $cfg) === false) {
        throw new RuntimeException('Falha ao gravar config/local.php — verifique permissões de escrita.');
      }
      @file_put_contents($lockFile, date('c') . PHP_EOL);

      $adminPathHint = ($detectedBasePath !== '' ? $detectedBasePath : '') . '/admin/login.php';
      $success = 'Instalação concluída com sucesso!' . $migrationAppliedMsg . ' Acesse ' . $adminPathHint . ' para entrar. A pasta install foi renomeada e desativada por segurança.';
      $alreadyInstalled = true;

      // Self-destruct / disable installer
      @rename(__DIR__, __DIR__ . '_disabled_' . time());
    } catch (Throwable $e) {
      // Rollback mechanism
      if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $res = @$conn->query("SHOW TABLES");
        if ($res) {
          @$conn->query("SET FOREIGN_KEY_CHECKS = 0");
          while ($row = $res->fetch_array(MYSQLI_NUM)) {
            @$conn->query("DROP TABLE IF EXISTS `" . $row[0] . "`");
          }
          @$conn->query("SET FOREIGN_KEY_CHECKS = 1");
        }
        $conn->close();
      }
      $errors[] = 'Falha na Instalação (Rollback executado): ' . $e->getMessage();
    }
  }
}

/* ── Logo path (relative to install folder) ──────────────────────── */
$logoRelPath = '../img/logo.png';
$logoExists = is_file(dirname(__DIR__) . '/img/logo.png');
?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalador WebyteHub</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ── Reset & tokens ──────────────────────────────────────────── */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #030406;
      --surface: rgba(255, 255, 255, .02);
      --surface2: rgba(255, 255, 255, .05);
      --border: rgba(139, 92, 246, 0.15);
      --border2: rgba(139, 92, 246, 0.25);
      --text: #f8fafc;
      --muted: #64748b;
      --primary: #8b5cf6;
      --primary-h: #a78bfa;
      --accent: #06b6d4;
      --success: #10b981;
      --danger: #f43f5e;
      --warn: #fbbf24;
      --green-btn: #8b5cf6;
      --green-btn-h: #7c3aed;
      --radius: 16px;
      --radius-sm: 10px;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
      font-size: 14px;
      line-height: 1.6;
      min-height: 100vh;
      overflow-x: hidden;
    }

    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; transition: 0.3s; }
    ::-webkit-scrollbar-thumb:hover { background: var(--primary-h); }

    code {
      font-family: 'Menlo', 'Consolas', 'Liberation Mono', monospace;
      background: rgba(255, 255, 255, .08);
      padding: 2px 6px;
      border-radius: 5px;
      font-size: 12px;
    }

    /* ── Background ─────────────────────────────────────────────── */
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

    /* ── Layout ─────────────────────────────────────────────────── */
    .page {
      position: relative;
      z-index: 1;
      max-width: 780px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    /* ── Card ───────────────────────────────────────────────────── */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      backdrop-filter: blur(18px);
      box-shadow: 0 24px 64px rgba(0, 0, 0, .55);
      overflow: hidden;
    }

    /* ── Header ─────────────────────────────────────────────────── */
    .card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 20px 24px 18px;
      border-bottom: 1px solid var(--border);
      background: var(--surface2);
    }

    .logo-wrap img {
      height: 38px;
      width: auto;
      display: block;
      object-fit: contain;
    }

    .logo-fallback {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -.02em;
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
      letter-spacing: -.02em;
      line-height: 1.2;
    }

    .header-meta p {
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
    }

    .badge-live {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 600;
      background: rgba(99, 102, 241, .15);
      border: 1px solid rgba(99, 102, 241, .3);
      color: #a5b4fc;
    }

    .dot-pulse {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--primary-h);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, .25);
      animation: pulse 1.8s ease-in-out infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
        opacity: .9
      }

      50% {
        transform: scale(1.2);
        opacity: 1
      }
    }

    /* ── Step progress ──────────────────────────────────────────── */
    .steps-bar {
      display: flex;
      align-items: center;
      gap: 0;
      padding: 14px 24px 12px;
      border-bottom: 1px solid var(--border);
    }

    .step-item {
      display: flex;
      align-items: center;
      gap: 7px;
      flex: 1;
    }

    .step-item:not(:last-child)::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border2);
      margin: 0 6px;
    }

    .step-num {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 700;
      border: 1.5px solid var(--border2);
      background: var(--surface2);
      color: var(--muted);
      flex-shrink: 0;
      transition: all .3s ease;
    }

    .step-item.active .step-num {
      background: var(--primary);
      border-color: var(--primary);
      color: #fff;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, .25);
    }

    .step-item.done .step-num {
      background: var(--success);
      border-color: var(--success);
      color: #fff;
    }

    .step-label {
      font-size: 12px;
      font-weight: 500;
      color: var(--muted);
      white-space: nowrap;
    }

    .step-item.active .step-label {
      color: var(--text);
    }

    .step-item.done .step-label {
      color: var(--success);
    }

    /* ── Body ───────────────────────────────────────────────────── */
    .card-body {
      padding: 22px 24px 24px;
    }

    /* ── Section title ──────────────────────────────────────────── */
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

    /* ── Grid form ──────────────────────────────────────────────── */
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .grid .full {
      grid-column: 1/-1;
    }

    /* ── Field ──────────────────────────────────────────────────── */
    .field {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .field label {
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .field input,
    .field select {
      padding: 9px 11px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border2);
      background: rgba(255, 255, 255, .05);
      color: var(--text);
      font-size: 13px;
      font-family: inherit;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      width: 100%;
    }

    .field select option {
      background: #0b0d14;
      color: var(--text);
    }

    .field input:focus,
    .field select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, .18);
    }

    .field input[readonly] {
      opacity: .6;
      cursor: default;
    }

    .field .hint {
      font-size: 11px;
      color: var(--muted);
      line-height: 1.4;
    }

    /* ── Checkbox row ───────────────────────────────────────────── */
    .check-row {
      display: flex;
      align-items: flex-start;
      gap: 9px;
      padding: 10px 12px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border2);
      background: rgba(255, 255, 255, .03);
      cursor: pointer;
    }

    .check-row input[type="checkbox"] {
      width: 15px;
      height: 15px;
      flex-shrink: 0;
      margin-top: 2px;
      accent-color: var(--primary);
      cursor: pointer;
    }

    .check-row .check-text {
      font-size: 13px;
      line-height: 1.5;
    }

    .check-row .check-text small {
      display: block;
      font-size: 11px;
      color: var(--muted);
      margin-top: 2px;
    }

    /* ── Alert boxes ────────────────────────────────────────────── */
    .alert {
      padding: 11px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      line-height: 1.5;
      margin-bottom: 14px;
      display: flex;
      gap: 8px;
      align-items: flex-start;
    }

    .alert-icon {
      font-size: 15px;
      flex-shrink: 0;
      margin-top: 1px;
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

    .alert-warn {
      background: rgba(245, 158, 11, .1);
      border: 1px solid rgba(245, 158, 11, .25);
      color: #fcd34d;
    }

    .alert-info {
      background: rgba(99, 102, 241, .1);
      border: 1px solid rgba(99, 102, 241, .25);
      color: #c7d2fe;
    }

    /* ── Quick-preset highlight ─────────────────────────────────── */
    .xampp-preset-box {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      background: rgba(22, 163, 74, .1);
      border: 1px solid rgba(22, 163, 74, .3);
      margin-bottom: 16px;
    }

    .xampp-preset-box p {
      font-size: 12px;
      color: #86efac;
      flex: 1;
      line-height: 1.4;
    }

    /* ── Buttons ────────────────────────────────────────────────── */
    .btn-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 18px;
      align-items: center;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 18px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 700;
      border: none;
      cursor: pointer;
      font-family: inherit;
      transition: transform .18s ease, filter .18s ease, opacity .18s ease;
      text-decoration: none;
      white-space: nowrap;
    }

    .btn:hover {
      transform: translateY(-1px) scale(1.02);
      filter: brightness(1.06);
    }

    .btn:active {
      transform: scale(.97);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), #818cf8);
      color: #fff;
      box-shadow: 0 4px 18px rgba(99, 102, 241, .3);
    }

    .btn-green {
      background: linear-gradient(135deg, var(--green-btn), var(--green-btn-h));
      color: #fff;
      box-shadow: 0 4px 14px rgba(22, 163, 74, .3);
      font-size: 13px;
    }

    .btn-green .btn-icon {
      font-size: 15px;
    }

    .btn-ghost {
      background: var(--surface2);
      color: var(--text);
      border: 1px solid var(--border2);
    }

    .btn-danger {
      background: rgba(239, 68, 68, .15);
      color: #fca5a5;
      border: 1px solid rgba(239, 68, 68, .3);
    }

    .btn-link {
      background: none;
      border: none;
      color: var(--muted);
      padding: 0;
      font-size: 12px;
      cursor: pointer;
    }

    .btn-link:hover {
      color: var(--text);
      transform: none;
      filter: none;
    }

    /* ── Section divider ────────────────────────────────────────── */
    .divider {
      height: 1px;
      background: var(--border);
      margin: 20px 0;
    }

    /* ── Health check table ─────────────────────────────────────── */
    .req-table {
      width: 100%;
      border-collapse: collapse;
    }

    .req-table th,
    .req-table td {
      padding: 8px 10px;
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

    .req-ok {
      color: var(--success);
      font-weight: 700;
    }

    .req-fail {
      color: var(--danger);
      font-weight: 700;
    }

    /* ── Success panel ──────────────────────────────────────────── */
    .success-panel {
      text-align: center;
      padding: 24px 16px;
    }

    .success-icon {
      font-size: 48px;
      margin-bottom: 12px;
    }

    .success-panel h2 {
      font-size: 20px;
      font-weight: 800;
      margin-bottom: 6px;
    }

    .success-panel p {
      color: var(--muted);
      font-size: 13px;
      max-width: 400px;
      margin: 0 auto 18px;
    }

    .success-btn-row {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
    }

    /* ── Collapse animation ─────────────────────────────────────── */
    .slide-in {
      animation: slideIn .4s cubic-bezier(.22, 1, .36, 1) both;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(16px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    /* ── Media ──────────────────────────────────────────────────── */
    @media (max-width: 600px) {
      .grid {
        grid-template-columns: 1fr;
      }

      .steps-bar {
        padding: 12px 14px;
        gap: 0;
      }

      .step-label {
        display: none;
      }

      .card-body {
        padding: 16px;
      }

      .card-header {
        padding: 14px 16px;
      }
    }
  </style>
</head>

<body>
  <div id="db-master-bg"></div>
  <div class="bg-glow"></div>
  <div class="bg-happiness"></div>

  <div class="page">
    <!-- ── Main card ─────────────────────────────────────── -->
    <div class="card slide-in">

      <!-- Header -->
      <div class="card-header">
        <div class="logo-wrap">
          <?php if ($logoExists): ?>
            <img src="<?= h($logoRelPath) ?>" alt="WebyteHub Logo">
          <?php else: ?>
            <span class="logo-fallback">WebyteHub</span>
          <?php endif; ?>
        </div>
        <div class="header-meta">
          <h1>DROP Ecosystem Initializer</h1>
          <p>Motor de ativação e sincronia de microsserviços</p>
        </div>
        <div class="badge-live"><span class="dot-pulse"></span> Sinfonia Ativa</div>
      </div>

      <!-- Step progress bar -->
      <div class="steps-bar" id="stepsBar">
        <div class="step-item" id="step-ind-1">
          <div class="step-num">1</div>
          <span class="step-label">Requisitos</span>
          <span></span><!-- connector via CSS ::after -->
        </div>
        <div class="step-item" id="step-ind-2">
          <div class="step-num">2</div>
          <span class="step-label">Configuração</span>
          <span></span>
        </div>
        <div class="step-item" id="step-ind-3">
          <div class="step-num">3</div>
          <span class="step-label">Instalação</span>
        </div>
      </div>

      <!-- Body -->
      <div class="card-body">

        <?php if ($success !== ''): ?>
          <!-- ── SUCCESS ──────────────────────────────────── -->
          <?php
          $goBase = rtrim((string) $form['base_url'], '/') . '/';
          $adminUrl = $goBase . 'admin/login.php';
          $siteUrl = $goBase;
          ?>
          <div class="success-panel slide-in">
            <div class="success-icon">🎉</div>
            <h2>Instalação concluída!</h2>
            <p><?= h($success) ?></p>
            <div class="success-btn-row">
              <a class="btn btn-primary" href="<?= h($adminUrl) ?>">🔐 Ir para o painel Admin</a>
              <a class="btn btn-ghost" href="<?= h($siteUrl) ?>">🌐 Ver o site</a>
            </div>
            <p style="margin-top:16px;font-size:11px;color:var(--muted)">
              ⚠️ Por segurança, remova a pasta <code>/install</code> após a instalação.
            </p>
          </div>

        <?php elseif ($alreadyInstalled): ?>
          <!-- ── Already installed ────────────────────────── -->
          <div class="alert alert-info"><span class="alert-icon">ℹ️</span>
            <div>A aplicação já está instalada. Para reinstalar, remova <code>storage/installed.lock</code> e
              <code>config/local.php</code> manualmente.</div>
          </div>

        <?php else: ?>
          <!-- ── WIZARD STEPS ──────────────────────────────── -->
          <!-- Step 1: Requirements -->
          <div id="wizStep1">
            <div class="sec-title">
              <div class="sec-icon">🔍</div>
              Verificação de Requisitos
            </div>

            <?php
            /* Inline requirement checks */
            $reqs = [
              ['PHP >= 8.0', version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP ' . PHP_VERSION],
              ['Extensão mysqli', extension_loaded('mysqli'), ''],
              ['Extensão curl', extension_loaded('curl'), ''],
              ['Arquivo SQL Base', is_file($sqlFile), 'database/base.sql'],
              ['Escrita em /storage', is_writable($storageDir), $storageDir],
              ['Escrita em /config', is_writable($configDir), $configDir],
            ];
            $allReqsOk = array_reduce($reqs, fn($carry, $r) => $carry && $r[1], true);
            ?>

            <div class="alert <?= $allReqsOk ? 'alert-success' : 'alert-danger' ?>">
              <span class="alert-icon"><?= $allReqsOk ? '✅' : '❌' ?></span>
              <div>
                <?= $allReqsOk ? 'Todos os requisitos atendidos. Pronto para configurar.' : 'Alguns requisitos não foram atendidos. Corrija antes de prosseguir.' ?>
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
                <?php foreach ($reqs as $r): ?>
                  <tr>
                    <td><?= h($r[0]) ?></td>
                    <td class="<?= $r[1] ? 'req-ok' : 'req-fail' ?>"><?= $r[1] ? '✓ OK' : '✗ FALHA' ?></td>
                    <td style="font-size:12px;color:var(--muted)"><?= h($r[2]) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <div class="btn-row">
              <button class="btn btn-primary" id="toStep2Btn" onclick="goStep(2)" <?= !$allReqsOk ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                Próximo: Configuração →
              </button>
              <a class="btn btn-ghost" href="health-check.php">Ver Health Check completo</a>
            </div>
          </div>

          <!-- Step 2: Configuration -->
          <div id="wizStep2" style="display:none">
            <div class="sec-title">
              <div class="sec-icon">⚙️</div>
              Configuração do Sistema
            </div>

            <!-- Docker preset -->
            <div class="xampp-preset-box">
              <div class="preset-box-text">
                <strong style="font-size:13px;color:#86efac">⚡ Instalação via Docker?</strong>
                <p>Clique para preencher com as configurações do contêiner db (host: db, root, drop_root_pass).
                </p>
              </div>
              <button class="btn btn-green" type="button" onclick="applyDockerPreset()">
                <span class="btn-icon">🐳</span> Usar Docker (db)
              </button>
            </div>

            <?php if ($requireSubfolderConfirm): ?>
              <div class="alert alert-warn">
                <span class="alert-icon">⚠️</span>
                <div>
                  <strong>Instalação em subpasta detectada:</strong> <code><?= h($detectedBasePath) ?></code> em servidor
                  (não-localhost).
                  O recomendado é instalar na raiz do domínio. Marque a confirmação abaixo se deseja continuar assim mesmo.
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
              <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><span class="alert-icon">❌</span>
                  <div><?= h($err) ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" id="installForm" onsubmit="return validateForm()">
              <div class="grid">

                <!-- Licença -->
                <div class="field full" id="licenseFieldContainer">
                  <label>Chave de Licença <span id="licStatus" style="float:right"></span></label>
                  <div style="display:flex;gap:8px;align-items:center">
                    <input name="license_key" id="license_key" value="<?= h($form['license_key']) ?>" required
                      placeholder="Sua chave Premium / ex: WEBYTE-123" style="flex:1">
                    <button class="btn btn-ghost" type="button" id="btnVerifyLic" onclick="verifyLicense()"
                      style="white-space:nowrap;padding:0 14px;font-size:12px;height:auto">Validar via API</button>
                    <button class="btn btn-ghost" type="button" onclick="bypassLicense()"
                      style="white-space:nowrap;padding:0 14px;font-size:12px;height:auto;color:#ef4444;border-color:#ef4444">Ignorar (Dev)</button>
                  </div>
                  <script>
                    function bypassLicense() {
                      document.getElementById('license_key').value = 'DEV-BYPASS-KEY';
                      document.getElementById('licenseFieldContainer').style.display = 'none';
                    }
                  </script>
                </div>

                <!-- URL Base -->
                <div class="field full">
                  <label>URL Base do Sistema</label>
                  <?php if ($lockBaseUrl): ?>
                    <input name="base_url" id="base_url" value="<?= h($form['base_url']) ?>" readonly>
                    <span class="hint">🔒 Detectado automaticamente — projeto em subpasta
                      <code><?= h($detectedBasePath) ?></code>. URL bloqueada para evitar links quebrados.</span>
                  <?php else: ?>
                    <input name="base_url" id="base_url" value="<?= h($form['base_url']) ?>" required
                      placeholder="https://seudominio.com/">
                    <span class="hint">Detectado: <code><?= h($defaultBaseUrl) ?></code> — você pode alterar manualmente se
                      necessário.</span>
                  <?php endif; ?>
                </div>

                <?php if ($requireSubfolderConfirm): ?>
                  <div class="full">
                    <label class="check-row">
                      <input type="checkbox" name="confirm_subfolder_install" value="1"
                        <?= (int) $form['confirm_subfolder_install'] === 1 ? 'checked' : '' ?>>
                      <span class="check-text">
                        Confirmo que desejo instalar em <code><?= h($detectedBasePath) ?></code> mesmo assim.
                        <small>Não recomendado para ambientes de produção</small>
                      </span>
                    </label>
                  </div>
                <?php endif; ?>

                <!-- App env & site name -->
                <div class="field">
                  <label>Ambiente</label>
                  <select name="app_env" id="app_env">
                    <option value="production" <?= $form['app_env'] === 'production' ? 'selected' : '' ?>>🚀 Production
                    </option>
                    <option value="staging" <?= $form['app_env'] === 'staging' ? 'selected' : '' ?>>🧪 Staging</option>
                    <option value="development" <?= $form['app_env'] === 'development' ? 'selected' : '' ?>>🛠️ Development
                    </option>
                  </select>
                </div>
                <div class="field">
                  <label>Nome do Site</label>
                  <input name="site_name" id="site_name" value="<?= h($form['site_name']) ?>" placeholder="Drop">
                </div>

                <div class="divider full"></div>

                <!-- DB -->
                <div class="field">
                  <label>DB Host</label>
                  <input name="db_host" id="db_host" value="<?= h($form['db_host']) ?>" required placeholder="localhost">
                </div>
                <div class="field">
                  <label>DB Nome</label>
                  <input name="db_name" id="db_name" value="<?= h($form['db_name']) ?>" required placeholder="drop">
                </div>
                <div class="field">
                  <label>DB Usuário</label>
                  <input name="db_user" id="db_user" value="<?= h($form['db_user']) ?>" required placeholder="root">
                </div>
                <div class="field">
                  <label>DB Senha</label>
                  <input name="db_pass" id="db_pass" type="password" placeholder="(em branco para root XAMPP)">
                </div>
                <div class="field full" style="margin-top:-6px;text-align:right">
                  <button class="btn btn-ghost" type="button" id="btnTestDb" onclick="testDbConnection()"
                    style="font-size:12px;padding:6px 12px"><span class="btn-icon">🔌</span> Testar Conexão e
                    Banco</button>
                  <div id="dbStatusMsg"
                    style="font-size:12px;margin-top:4px;display:none;text-align:left;padding:8px;border-radius:var(--radius-sm);background:rgba(255,255,255,.05)">
                  </div>
                </div>

                <div class="divider full"></div>

                <!-- Admin -->
                <div class="field">
                  <label>Admin Usuário</label>
                  <input name="admin_user" id="admin_user" value="<?= h($form['admin_user']) ?>" required
                    placeholder="admin">
                </div>
                <div class="field">
                  <label>Admin E-mail</label>
                  <input name="admin_email" id="admin_email" type="email" value="<?= h($form['admin_email']) ?>"
                    placeholder="admin@seudominio.com">
                </div>
                <div class="field">
                  <label>Senha Admin</label>
                  <input name="admin_pass" id="admin_pass" type="password" required placeholder="Mínimo 8 caracteres"
                    onkeyup="checkStrength()">
                  <div
                    style="display:flex;height:4px;background:var(--surface2);border-radius:2px;margin-top:2px;overflow:hidden">
                    <div id="pwdStr1"
                      style="flex:1;background:transparent;border-right:1px solid var(--bg);transition:background .3s">
                    </div>
                    <div id="pwdStr2"
                      style="flex:1;background:transparent;border-right:1px solid var(--bg);transition:background .3s">
                    </div>
                    <div id="pwdStr3"
                      style="flex:1;background:transparent;border-right:1px solid var(--bg);transition:background .3s">
                    </div>
                  </div>
                  <div id="pwdStrText" style="font-size:10px;text-align:right;color:var(--muted);height:12px"></div>
                </div>
                <div class="field">
                  <label>Confirmar Senha Admin</label>
                  <input name="admin_pass_confirm" id="admin_pass_confirm" type="password" required
                    placeholder="Repita a senha">
                </div>

                <!-- Hardening -->
                <div class="full">
                  <label class="check-row">
                    <input type="checkbox" name="apply_hardening" value="1" id="apply_hardening"
                      <?= (int) $form['apply_hardening'] === 1 ? 'checked' : '' ?>>
                    <span class="check-text">
                      Aplicar migração de performance e segurança (hardening)
                      <small>Recomendado para ambientes de produção</small>
                    </span>
                  </label>
                </div>
                <div class="full">
                  <label class="check-row">
                    <input type="checkbox" name="apply_schema_structure" value="1" id="apply_schema_structure"
                      <?= (int) $form['apply_schema_structure'] === 1 ? 'checked' : '' ?>>
                    <span class="check-text">
                      Aplicar estruturação SQL (índices + DECIMAL + ajustes de schema)
                      <small>Recomendado: sincroniza banco com hardening do código</small>
                    </span>
                  </label>
                </div>
                <div class="full">
                  <label class="check-row">
                    <input type="checkbox" name="apply_phase2_numbers" value="1" id="apply_phase2_numbers"
                      <?= (int) $form['apply_phase2_numbers'] === 1 ? 'checked' : '' ?>>
                    <span class="check-text">
                      Aplicar Fase 2 (normalização de cotas com <code>order_number_list</code>)
                      <small>Ativa unicidade por produto+cota no banco (anti-duplicidade)</small>
                    </span>
                  </label>
                </div>
              </div>

              <div class="btn-row">
                <button class="btn btn-ghost" type="button" onclick="goStep(1)">← Voltar</button>
                <button class="btn btn-primary" type="submit" id="submitBtn">🚀 Instalar Agora</button>
              </div>
            </form>
          </div>

          <!-- Step 3: Installing (progress indicator shown via JS) -->
          <div id="wizStep3" style="display:none">
            <div style="text-align:center;padding:40px 20px">
              <div style="font-size:40px;margin-bottom:14px">⏳</div>
              <h2 style="font-size:18px;font-weight:700;margin-bottom:8px">Instalando...</h2>
              <p style="color:var(--muted);font-size:13px">Aguarde enquanto configuramos o banco de dados e o sistema.</p>
              <div
                style="margin-top:20px;width:100%;height:4px;background:var(--surface2);border-radius:2px;overflow:hidden">
                <div id="progressBar"
                  style="height:100%;width:0%;background:linear-gradient(90deg,var(--primary),var(--primary-h));border-radius:2px;transition:width .5s ease">
                </div>
              </div>
            </div>
          </div>

        <?php endif; // not installed ?>
      </div><!-- /.card-body -->
    </div><!-- /.card -->

    <!-- Footer -->
    <p style="text-align:center;margin-top:16px;font-size:11px;color:var(--muted)">
      WebyteHub Installer — Este instalador deve ser removido após a instalação por questões de segurança.
    </p>
  </div><!-- /.page -->

  <script>
    /* ─────────────────────────────────────────────────────────────────────
       Wizard State
    ───────────────────────────────────────────────────────────────────── */
    var currentStep = 1;

    function goStep(n) {
      document.getElementById('wizStep' + currentStep).style.display = 'none';
      document.getElementById('wizStep' + n).style.display = 'block';
      currentStep = n;
      updateStepBar(n);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateStepBar(active) {
      for (var i = 1; i <= 3; i++) {
        var el = document.getElementById('step-ind-' + i);
        if (!el) continue;
        el.classList.remove('active', 'done');
        if (i < active) el.classList.add('done');
        else if (i === active) el.classList.add('active');
      }
    }

    /* ─────────────────────────────────────────────────────────────────────
       Form validation (client-side)
    ───────────────────────────────────────────────────────────────────── */
    function validateForm() {
      var pass = document.getElementById('admin_pass');
      var passCf = document.getElementById('admin_pass_confirm');
      if (!pass || !passCf) return true;
      if (pass.value.length < 8) {
        alert('A senha do Admin deve ter ao menos 8 caracteres.');
        pass.focus(); return false;
      }
      if (pass.value !== passCf.value) {
        alert('As senhas do Admin não coincidem.');
        passCf.focus(); return false;
      }
      // Show step 3 loading indicator
      goStep(3);
      animateProgress();
      return true;
    }

    function animateProgress() {
      var bar = document.getElementById('progressBar');
      if (!bar) return;
      var p = 0;
      var iv = setInterval(function () {
        p += Math.random() * 18;
        if (p >= 90) { p = 90; clearInterval(iv); }
        bar.style.width = p + '%';
      }, 400);
    }

    /* ─────────────────────────────────────────────────────────────────────
       New Features JS
    ───────────────────────────────────────────────────────────────────── */
    function verifyLicense() {
      var key = document.getElementById('license_key').value;
      var btn = document.getElementById('btnVerifyLic');
      var status = document.getElementById('licStatus');
      if (!key) { status.innerHTML = '❌ Preencha a chave'; return; }

      btn.disabled = true;
      btn.innerText = 'Validando...';
      status.innerHTML = '';

      var fd = new FormData();
      fd.append('action', 'verify_license');
      fd.append('key', key);

      fetch('ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          btn.disabled = false;
          btn.innerText = 'Validar via API';
          if (data.valid) {
            status.innerHTML = '<span style="color:var(--success)">✅ ' + data.message + '</span>';
          } else {
            status.innerHTML = '<span style="color:var(--danger)">❌ ' + data.message + '</span>';
          }
        })
        .catch(e => {
          btn.disabled = false;
          btn.innerText = 'Validar via API';
          status.innerHTML = '<span style="color:var(--warn)">⚠️ Erro ao acessar API</span>';
        });
    }

    function testDbConnection() {
      var btn = document.getElementById('btnTestDb');
      var msg = document.getElementById('dbStatusMsg');
      btn.disabled = true;
      btn.innerHTML = 'Testando...';
      msg.style.display = 'none';

      var fd = new FormData();
      fd.append('action', 'test_db');
      fd.append('host', document.getElementById('db_host').value);
      fd.append('name', document.getElementById('db_name').value);
      fd.append('user', document.getElementById('db_user').value);
      fd.append('pass', document.getElementById('db_pass').value);

      fetch('ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          btn.disabled = false;
          btn.innerHTML = '<span class="btn-icon">🔌</span> Testar Conexão e Banco';
          msg.style.display = 'block';
          msg.style.color = data.success ? 'var(--success)' : 'var(--danger)';
          msg.innerHTML = data.message;
        })
        .catch(e => {
          btn.disabled = false;
          btn.innerHTML = '<span class="btn-icon">🔌</span> Testar Conexão e Banco';
          msg.style.display = 'block';
          msg.style.color = 'var(--danger)';
          msg.innerHTML = 'Erro na requisição AJAX.';
        });
    }

    function checkStrength() {
      var val = document.getElementById('admin_pass').value;
      var p1 = document.getElementById('pwdStr1');
      var p2 = document.getElementById('pwdStr2');
      var p3 = document.getElementById('pwdStr3');
      var txt = document.getElementById('pwdStrText');
      var score = 0;
      if (val.length > 5) score++;
      if (val.length >= 8 && /[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
      if (score === 2 && /[^A-Za-z0-9]/.test(val)) score++;

      var cNone = 'transparent', cWeak = '#ef4444', cMed = '#f59e0b', cStrong = '#22c55e';

      p1.style.background = score >= 1 ? (score === 1 ? cWeak : (score === 2 ? cMed : cStrong)) : cNone;
      p2.style.background = score >= 2 ? (score === 2 ? cMed : cStrong) : cNone;
      p3.style.background = score >= 3 ? cStrong : cNone;

      if (val.length === 0) txt.innerText = '';
      else if (score === 0) txt.innerText = 'Muito Fraca';
      else if (score === 1) txt.innerText = 'Fraca';
      else if (score === 2) txt.innerText = 'Média';
      else if (score === 3) txt.innerText = 'Forte';
    }

    /* ─────────────────────────────────────────────────────────────────────
       XAMPP Preset
    ───────────────────────────────────────────────────────────────────── */
    function applyDockerPreset() {
      var get = function (id) { return document.getElementById(id); };
      var detectedUrl = <?= json_encode($defaultBaseUrl) ?>;
      var locked = <?= json_encode((bool) $lockBaseUrl) ?>;

      if (!locked) {
        var el = get('base_url');
        if (el) el.value = detectedUrl;
      }
      if (get('app_env')) get('app_env').value = 'development';
      if (get('db_host')) get('db_host').value = 'db';
      if (get('db_name')) get('db_name').value = 'drop';
      if (get('db_user')) get('db_user').value = 'root';
      if (get('db_pass')) get('db_pass').value = 'drop_root_pass';
      if (get('admin_user')) get('admin_user').value = 'admin';
      if (get('apply_hardening')) get('apply_hardening').checked = true;
      if (get('apply_schema_structure')) get('apply_schema_structure').checked = true;
      if (get('apply_phase2_numbers')) get('apply_phase2_numbers').checked = true;
    }

    /* ─────────────────────────────────────────────────────────────────────
       Init
    ───────────────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
      updateStepBar(1);

      <?php if (!empty($errors) && !$success): ?>
        // If form submitted with errors, jump to config step
        goStep(2);
      <?php endif; ?>

      <?php if ($success !== ''): ?>
        updateStepBar(3);
        // Auto-redirect handling
        try {
          var params = new URLSearchParams(window.location.search || '');
          if (params.get('autoredirect') === '1') {
            var go = params.get('go');
            if (go === 'admin') window.location.href = <?= json_encode(rtrim((string) $form['base_url'], '/') . '/admin/login.php') ?>;
            else if (go === 'site') window.location.href = <?= json_encode(rtrim((string) $form['base_url'], '/') . '/') ?>;
          }
        } catch (e) { }
      <?php endif; ?>

      /* Particle background (desktop) */
      var isMobile = matchMedia('(max-width: 768px)').matches;
      var canvas = document.getElementById('particleCanvas');
      if (isMobile || !canvas) return;
      var ctx = canvas.getContext('2d');
      var DPR = Math.min(2, window.devicePixelRatio || 1);
      var w = 0, h = 0, particles = [];
      var PCOUNT = 40, MAXD = 130;

      function resize() { w = canvas.clientWidth; h = canvas.clientHeight; canvas.width = Math.floor(w * DPR); canvas.height = Math.floor(h * DPR); ctx.setTransform(DPR, 0, 0, DPR, 0, 0); }
      function rnd(mn, mx) { return Math.random() * (mx - mn) + mn; }
      function init() { particles = Array.from({ length: PCOUNT }).map(() => ({ x: rnd(0, w), y: rnd(0, h), vx: rnd(-.28, .28), vy: rnd(-.28, .28), r: rnd(1.1, 2) })); }
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






