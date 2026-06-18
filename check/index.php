<?php
declare(strict_types=1);

/**
 * WebyteHub - System Check & Auto-Prepare v2.0
 * Analisa o projeto raiz e prepara o motor Drop
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$projectName = basename($root);
$dropPath = dirname(__DIR__);
// require_once $dropPath . '/api/libs/infrastructure.php';

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
    if ($conn->errno) {
      throw new RuntimeException('Erro ao executar SQL no arquivo: ' . basename($file) . ' — ' . $conn->error);
    }
  } while ($conn->more_results() && $conn->next_result());
}

function db_has_tables(mysqli $conn, string $dbName): bool
{
  $safeDb = $conn->real_escape_string($dbName);
  $res = $conn->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = '{$safeDb}'");
  if (!$res) return false;
  $row = $res->fetch_assoc();
  return isset($row['c']) && (int) $row['c'] > 0;
}

// Fetch scroll backgrounds from API folder
$scrollDir = $dropPath . '/img/scroll/';
$scrollImages = [];
if (is_dir($scrollDir)) {
    $files = scandir($scrollDir);
    foreach ($files as $f) {
        if (preg_match('/\.(png|jpe?g|webp)$/i', $f)) {
            $scrollImages[] = '../img/scroll/' . $f;
        }
    }
}
if (empty($scrollImages)) $scrollImages = ['../img/logo.png'];
$defaultBg = $scrollImages[array_rand($scrollImages)];

// Auditoria de Rotas (.htaccess)
$routeLog = [];
$htaccessFile = $root . '/.htaccess';
if (is_file($htaccessFile)) {
    $content = file_get_contents($htaccessFile);
    if (strpos($content, 'drop/') === false) {
        $routeLog[] = "[CRITICAL] Rotas do motor 'drop' não detectadas no .htaccess da raiz.";
    } else {
        $routeLog[] = "[OK] Rotas do motor 'drop' configuradas no .htaccess.";
    }
} else {
    $routeLog[] = "[WARNING] Arquivo .htaccess não encontrado na raiz.";
}

// Auditoria de Gateway (index.php)
$indexFile = $root . '/index.php';
if (is_file($indexFile)) {
    $content = file_get_contents($indexFile);
    if (strpos($content, 'backend/public/index.php') === false) {
        $routeLog[] = "[CRITICAL] O index.php da raiz não aponta para o motor drop.";
    } else {
        $routeLog[] = "[OK] Gateway index.php configurado corretamente.";
    }
}

// Resultados da auditoria
$results = [
    'project' => [
        'name' => $projectName,
        'path' => $root,
        'detected' => true
    ],
    'environment' => [
        'php_version' => PHP_VERSION,
        'php_ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'extensions' => [
            'mysqli' => extension_loaded('mysqli'),
            'curl' => extension_loaded('curl'),
            'openssl' => extension_loaded('openssl'),
            'gd' => extension_loaded('gd'),
        ],
    ],
    'permissions' => [
        'storage' => is_writable($root . '/storage'),
        'config' => is_writable($root . '/config'),
    ],
    'structure' => [
        'has_config' => is_file($root . '/config/local.php'),
    ]
];

$allOk = $results['environment']['php_ok'] && 
         $results['environment']['extensions']['mysqli'] && 
         $results['permissions']['storage'];

$prepareMsg = '';
if (isset($_POST['action']) && $_POST['action'] === 'prepare') {
    try {
        if (!is_dir($root . '/storage')) mkdir($root . '/storage', 0755, true);
        if (!is_dir($root . '/config')) mkdir($root . '/config', 0755, true);
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $baseUrl .= str_replace('/check/index.php', '', $_SERVER['SCRIPT_NAME']);
        // Remove /drop do final se ele existir no script name detectado pela subpasta
        $baseUrl = preg_replace('#/drop$#i', '', rtrim($baseUrl, '/')) . '/';

        if (!is_file($root . '/config/local.php')) {
            $dbName = strtolower($projectName);
            $configContent = "<?php\nreturn [\n    'APP_NAME' => '" . $projectName . "',\n    'APP_BASE_URL' => '" . $baseUrl . "',\n    'DB_SERVER' => 'localhost',\n    'DB_USERNAME' => 'root',\n    'DB_PASSWORD' => '',\n    'DB_NAME' => '" . $dbName . "',\n];\n";
            file_put_contents($root . '/config/local.php', $configContent);
            $prepareMsg = 'Configurações geradas.';

            // Tentar Auto-Migração (Localhost Only)
            if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
                try {
                    $conn = @new mysqli('localhost', 'root', '');
                    if (!$conn->connect_error) {
                        $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $conn->select_db($dbName);
                        if ($conn->select_db($dbName)) {
                            // Garante a infraestrutura unificada
                            ensure_drop_infrastructure($conn);
                            $prepareMsg .= ' Banco de dados sincronizado via Master Engine.';
                        }
                        $conn->close();
                    }
                } catch (Exception $dbE) {
                    $prepareMsg .= ' (Aviso: Banco de dados não pôde ser auto-configurado: ' . $dbE->getMessage() . ')';
                }
            }
        }

        // Configuração Automática do .htaccess
        $htaccessPath = $root . '/.htaccess';
        $rules = "\n# --- WEBYTEHUB DROP SYSTEM ROUTES ---\nRewriteEngine On\nRewriteRule ^api(/.*)?$ drop/api$1 [L]\nRewriteRule ^install(/.*)?$ drop/install$1 [L]\nRewriteRule ^update(/.*)?$ drop/update$1 [L]\nRewriteRule ^check(/.*)?$ drop/check$1 [L]\nRewriteRule ^img/(.*)$ drop/img/$1 [L]\n# --- END DROP ROUTES ---\n";
        
        if (is_file($htaccessPath)) {
            $current = file_get_contents($htaccessPath);
            if (strpos($current, 'drop/') === false) {
                file_put_contents($htaccessPath, $current . $rules);
                $prepareMsg .= ' .htaccess atualizado.';
            }
        } else {
            file_put_contents($htaccessPath, "DirectoryIndex index.php index.html\n<IfModule mod_rewrite.c>\n" . $rules . "\n</IfModule>");
            $prepareMsg .= ' .htaccess criado.';
        }

        // Configuração Automática do Gateway index.php na raiz
        $rootIndex = $root . '/index.php';
        $gatewayCode = "<?php\n/**\n * WEBYTEHUB DROP - Gateway de Entrada\n */\nrequire_once __DIR__ . '/backend/public/index.php';\n";
        
        if (is_file($rootIndex)) {
            $currentI = file_get_contents($rootIndex);
            if (strpos($currentI, 'backend/public/index.php') === false) {
                // Faz backup do original se não for o padrão do XAMPP
                if (strpos($currentI, 'XAMPP') === false) {
                    copy($rootIndex, $root . '/index.php.bak');
                }
                file_put_contents($rootIndex, $gatewayCode);
                $prepareMsg .= ' Gateway index.php da raiz atualizado.';
            }
        } else {
            file_put_contents($rootIndex, $gatewayCode);
            $prepareMsg .= ' Gateway index.php criado na raiz.';
        }
        
        if (!$prepareMsg) $prepareMsg = 'O sistema já está configurado.';
    } catch (Exception $e) { $prepareMsg = 'Erro: ' . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check & Prepare | WebyteHub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@1.3.0/dist/fonts/geist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #030406;
            --surface: rgba(8, 10, 18, 0.7);
            --primary: #8b5cf6;
            --accent: #06b6d4;
            --success: #10b981;
            --text: #f8fafc;
            --border: rgba(139, 92, 246, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Geist Sans', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Cinematic Background */
        #db-master-bg { position: fixed; top: -15vh; left: -15vw; right: -15vw; bottom: -15vh; z-index: -10; background: linear-gradient(rgba(3, 4, 6, 0.8), rgba(3, 4, 6, 0.9)), url('<?= $defaultBg ?>') center/cover no-repeat; filter: contrast(1.1) brightness(0.7); transition: opacity 1.5s ease; pointer-events: none; }

        .main-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            width: 500px;
            backdrop-filter: blur(30px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        header { text-align: center; margin-bottom: 1.5rem; }
        header img { height: 32px; margin-bottom: 0.5rem; }
        header h1 { font-size: 1.25rem; font-weight: 800; }

        .log-panel {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            font-family: 'Geist Mono', monospace;
            font-size: 11px;
            height: 120px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }
        .log-entry { margin-bottom: 4px; }
        .log-entry[data-type="OK"] { color: var(--success); }
        .log-entry[data-type="CRITICAL"] { color: #ef4444; }

        .check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .mini-card { background: rgba(255,255,255,0.03); padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border); }
        .mini-card h4 { font-size: 10px; text-transform: uppercase; color: var(--primary); margin-bottom: 4px; }
        .mini-card p { font-size: 12px; display: flex; justify-content: space-between; }

        .actions { display: flex; gap: 0.75rem; }
        .btn { flex: 1; padding: 0.75rem; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 13px; text-align: center; transition: 0.3s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-secondary { background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border); }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .smoke-wrap {
            position: fixed; inset: 0; z-index: -5; pointer-events: none; opacity: 0;
            transition: opacity 2s ease; background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
        }
    </style>
</head>
<body>
    <div id="db-master-bg"></div>
    <div class="smoke-wrap" id="smoke"></div>

    <div class="main-card">
        <header>
            <img src="../img/logo.png" alt="Logo">
            <h1>DROP Health Audit</h1>
            <p style="font-size: 11px; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Análise de Ecossistema & Rotas</p>
        </header>

        <div class="log-panel">
            <?php foreach($routeLog as $log): 
                $type = strpos($log, '[OK]') !== false ? 'OK' : (strpos($log, '[CRITICAL]') !== false ? 'CRITICAL' : 'WARN');
            ?>
            <div class="log-entry" data-type="<?= $type ?>"><?= htmlspecialchars($log) ?></div>
            <?php endforeach; ?>
            <div class="log-entry">> Escaneando diretórios...</div>
            <div class="log-entry">> Projeto detectado: <?= $projectName ?></div>
        </div>

        <div class="check-grid">
            <div class="mini-card">
                <h4>Ambiente</h4>
                <p><span>PHP</span> <span><?= PHP_VERSION ?></span></p>
                <p><span>MySQLi</span> <span><?= extension_loaded('mysqli') ? '✓' : '✗' ?></span></p>
            </div>
            <div class="mini-card">
                <h4>Permissões</h4>
                <p><span>Storage</span> <span><?= $results['permissions']['storage'] ? '✓' : '✗' ?></span></p>
                <p><span>Config</span> <span><?= $results['permissions']['config'] ? '✓' : '✗' ?></span></p>
            </div>
        </div>

        <?php if($prepareMsg): ?>
            <p style="text-align: center; color: var(--success); font-size: 12px; margin-bottom: 1rem;"><?= $prepareMsg ?></p>
        <?php endif; ?>

        <div class="actions">
            <?php if(!$results['structure']['has_config']): ?>
            <form method="POST" style="flex: 1;">
                <input type="hidden" name="action" value="prepare">
                <button type="submit" class="btn btn-success">Auto-Configurar</button>
            </form>
            <?php endif; ?>
            <a href="../install/index.php" class="btn btn-primary">Instalador</a>
            <a href="../index.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>





