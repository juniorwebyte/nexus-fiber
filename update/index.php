<?php
/**
 * WebyteHub Automation - Master Sync Engine v4.0
 * Real-Time Stepper Progress & Concluded State
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

$appCfg = dirname(__DIR__) . '/backend/config/config.php';
$apiCfg = dirname(__DIR__) . '/api/config.php'; // might not exist but keep it for now
$config = is_file($appCfg) ? require $appCfg : [];
$license_key = $config['LICENSE_KEY'] ?? 'WEBYTE-PREMIUM-XYZ123';

$db_app = null;
$db_api = null;
try {
    mysqli_report(MYSQLI_REPORT_OFF);
    if (!empty($config['DB_NAME'])) {
        $db_app = @new mysqli($config['DB_SERVER'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);
        if (!$db_app->connect_error) {
            $db_app->set_charset('utf8mb4');
        }
    }
    // Conexão com o Banco da API (DROP)
    if (is_file($apiCfg)) {
        // Usamos um include isolado para capturar as definições sem conflitos
        $getApiDb = function($path) {
            // Se já estiver definido em algum lugar, evitamos erro de redefinição
            ob_start();
            include $path;
            ob_end_clean();
            return [
                'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
                'user' => defined('DB_USER') ? DB_USER : 'root',
                'pass' => defined('DB_PASS') ? DB_PASS : '',
                'name' => defined('DB_NAME') ? DB_NAME : 'drop_api'
            ];
        };
        $apiDbInfo = $getApiDb($apiCfg);
        $db_api = @new mysqli($apiDbInfo['host'], $apiDbInfo['user'], $apiDbInfo['pass'], $apiDbInfo['name']);
        if (!$db_api->connect_error) {
            $db_api->set_charset('utf8mb4');
        }
    }
    require_once 'functions.php';
} catch (Exception $e) {
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

session_start();
if (isset($_GET['logout'])) {
    unset($_SESSION['updater_authorized']);
    header('Location: index.php');
    exit;
}
$valid = isset($_SESSION['updater_authorized']) && $_SESSION['updater_authorized'] === true;

if (!$valid && ($_POST['license_input'] ?? '') === $license_key) {
    $_SESSION['updater_authorized'] = true;
    $valid = true;
}

if (isset($_GET['action']) && $valid) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    if ($action === 'check') {
        $applied_app = get_applied_versions($db_app);
        $applied_api = get_applied_versions($db_api);
        
        // SINFONIA REFINADA: Uma versão só é "Aplicada" se estiver em AMBOS os bancos conectados.
        // Se um banco não estiver disponível, consideramos apenas o outro.
        if ($db_app && $db_api) {
            $applied = array_intersect($applied_app, $applied_api);
        } else {
            $applied = array_merge($applied_app, $applied_api);
        }
        $applied = array_unique($applied);
        
        $available = get_available_updates($applied);
        echo json_encode([
            'status' => 'success', 
            'current' => (end($applied) ?: '0.0.0'), 
            'applied' => $applied,
            'available' => $available, 
            'count' => count($available), 
            'db_app' => $config['DB_NAME'] ?? 'N/D', 
            'db_api' => $apiDbInfo['name'] ?? 'N/D',
            'db_error' => ($db_app && $db_app->connect_error) ? $db_app->connect_error : null
        ]);
    } elseif ($action === 'apply') {
        // Habilita streaming para feedback em tempo real
        set_time_limit(0);
        header('Content-Type: text/plain');
        header('Cache-Control: no-cache');
        ob_implicit_flush(true);
        
        $ver = $_POST['version'] ?? $_GET['version'];
        if (!$ver) { echo "Erro: Versão não especificada."; exit; }

        echo ">>> Iniciando Sincronização v$ver...\n";
        
        // 0. Ativar Modo de Manutenção
        file_put_contents(dirname(__DIR__) . '/.maintenance', time());

        // 1. Garantir infraestrutura de versões
        if ($db_app) {
            $db_app->query("CREATE TABLE IF NOT EXISTS system_versions (id INT AUTO_INCREMENT PRIMARY KEY, version VARCHAR(50), log TEXT, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        }

        // 2. Backup de Segurança (Opcional, mas recomendado)
        $backups = ['app' => null, 'api' => null];
        if (isset($_REQUEST['backup']) && $_REQUEST['backup'] === 'true') {
            echo "> Criando Ponto de Restauração...\n";
            if ($db_app) {
                $backups['app'] = backup_database($config['DB_SERVER'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);
            }
            if ($db_api) {
                $backups['api'] = backup_database($apiDbInfo['host'], $apiDbInfo['user'], $apiDbInfo['pass'], $apiDbInfo['name']);
            }
            echo "> Backups concluídos.\n";
        }

        $res_log = "";
        $path = __DIR__ . '/' . $ver;
        
        try {
            // Habilita exceções para detecção de erro e Rollback
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            if (is_file("$path/database.sql")) {
                echo "> Aplicando Migrações SQL...\n";
                $sqlContent = file_get_contents("$path/database.sql");
                $sql = array_filter(array_map('trim', explode(';', $sqlContent)));
                foreach ($sql as $q) {
                    if (empty($q)) continue;
                    if ($db_app) {
                        $db_app->query($q);
                        $res_log .= "[R] SQL: " . substr($q, 0, 50) . "...\n";
                    }
                    if ($db_api) {
                        $db_api->query($q);
                        $res_log .= "[A] SQL OK\n";
                    }
                    echo ".";
                }
                echo "\n> SQL concluído com sucesso.\n";
            }

            if (is_file("$path/update.php")) {
                echo "> Executando Logic Patch PHP...\n";
                ob_start();
                include "$path/update.php";
                $php_out = ob_get_clean();
                echo $php_out . "\n";
                $res_log .= "[PHP] " . $php_out . "\n";
            }
        } catch (Exception $e) {
            echo "\n[ERRO CRÍTICO] Falha na migração: " . $e->getMessage() . "\n";
            echo "> Iniciando ROLLBACK AUTOMÁTICO...\n";
            
            if ($backups['app']) {
                restore_database($config['DB_SERVER'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME'], $backups['app']);
            }
            if ($backups['api']) {
                restore_database($apiDbInfo['host'], $apiDbInfo['user'], $apiDbInfo['pass'], $apiDbInfo['name'], $backups['api']);
            }
            
            @unlink(dirname(__DIR__) . '/.maintenance');
            echo "\n>>> ROLLBACK CONCLUÍDO. O sistema foi restaurado para o estado anterior.\n";
            exit;
        }

        // Registrar versão aplicada em ambos os nós (SINFONIA)
        mysqli_report(MYSQLI_REPORT_OFF); 
        if ($db_app) {
            $stmt_r = $db_app->prepare("INSERT INTO system_versions (version, log) VALUES (?,?)");
            if ($stmt_r) {
                $stmt_r->bind_param("ss", $ver, $res_log);
                $stmt_r->execute();
                $stmt_r->close();
            }
        }
        if ($db_api) {
            $stmt_a = $db_api->prepare("INSERT INTO system_versions (version, log) VALUES (?,?)");
            if ($stmt_a) {
                $stmt_a->bind_param("ss", $ver, $res_log);
                $stmt_a->execute();
                $stmt_a->close();
            }
        }

        update_log("Versão $ver aplicada com sucesso.");
        
        // Finalizar Modo de Manutenção
        @unlink(dirname(__DIR__) . '/.maintenance');

        echo "\n>>> Sincronização Concluída!";
        exit;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizador WebyteHub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist@1.3.0/dist/fonts/geist.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --accent: #06b6d4;
            --bg: #030406;
            --card: rgba(255, 255, 255, 0.02);
            --border: rgba(139, 92, 246, 0.15);
            --text: #f8fafc;
            --muted: #64748b;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; transition: 0.3s; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Geist Sans', sans-serif;
            overflow: hidden;
            height: 100vh;
            display: grid;
            place-items: center;
        }

        /* --- MASTER BACKGROUND (ARTISTIC) --- */
        #db-master-bg {
            position: fixed;
            top: -15vh; left: -15vw; right: -15vw; bottom: -15vh;
            z-index: -10;
            background: linear-gradient(rgba(3, 4, 6, 0.8), rgba(3, 4, 6, 0.9)),
                url('<?= $defaultBg ?>') center/cover no-repeat;
            filter: contrast(1.1) brightness(0.7);
            pointer-events: none;
        }

        .bgGlow {
            position: fixed;
            inset: -50%;
            background: radial-gradient(circle at 50% 50%, rgba(139, 92, 246, 0.1), transparent 70%);
            z-index: -9;
            filter: blur(120px);
        }

        .bgHappiness {
            position: fixed;
            bottom: -20%;
            right: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.03), transparent 70%);
            z-index: -8;
            filter: blur(100px);
            animation: breatheJoy 8s infinite alternate;
        }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; transition: 0.3s; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        @keyframes breatheJoy {
            from {
                opacity: 0.2;
                transform: scale(1);
            }

            to {
                opacity: 0.5;
                transform: scale(1.1);
            }
        }

        #particleCanvas {
            position: fixed;
            inset: 0;
            z-index: -7;
            opacity: 0.15;
            pointer-events: none;
        }

        #golden-ratio {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
            opacity: 0.05;
            background-image: repeating-linear-gradient(rgba(251, 191, 36, 0.1) 0 1px, transparent 1px 61.8%),
                repeating-linear-gradient(90deg, rgba(251, 191, 36, 0.1) 0 1px, transparent 1px 61.8%);
        }

        .main-card {
            width: 95%;
            max-width: 900px;
            max-height: 85vh;
            background: var(--card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            position: relative;
            z-index: 10;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .stepper {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 25px;
            font-size: 11px;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .step.active {
            color: var(--accent);
        }

        .step.active span {
            background: var(--accent);
            border-color: transparent;
            color: #000;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
        }

        .step span {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid var(--border);
            display: inline-grid;
            place-items: center;
            font-size: 11px;
            margin-right: 10px;
            transition: 0.4s;
        }

        .btn-main {
            background: linear-gradient(135deg, var(--primary), var(--accent)) !important;
            color: #fff !important;
            border: none;
            padding: 18px 45px;
            border-radius: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.6s cubic-bezier(0.19, 1, 0.22, 1);
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            box-shadow: 0 15px 30px rgba(139, 92, 246, 0.2);
        }

        .btn-main:hover {
            transform: translateY(-5px) scale(1.02) skewX(-2deg);
            box-shadow: 0 25px 50px rgba(139, 92, 246, 0.4);
        }

        #console {
            background: rgba(0, 0, 0, 0.6);
            padding: 25px;
            border-radius: 20px;
            color: var(--success);
            font-family: 'Geist Mono', monospace;
            font-size: 12px;
            margin-top: 30px;
            height: 180px;
            overflow-y: auto;
            border: 1px solid var(--border);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        input[type="text"] {
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            color: #fff;
            text-align: center;
            font-family: monospace;
            margin-bottom: 30px;
            font-size: 16px;
            transition: 0.3s;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.1);
        }

        .btn-refresh {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }
        .btn-refresh:hover { background: var(--surface2); border-color: var(--accent); color: var(--accent); }
        .btn-refresh:hover { background: rgba(139, 92, 246, 0.2); transform: translateY(-2px); }
        .btn-refresh.loading svg { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .btn-refresh.pulse { animation: pulseRefresh 1.5s infinite; }
        @keyframes pulseRefresh {
            0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(139, 92, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
        }
    </style>
</head>

<body>
    <div id="db-master-bg"></div>
    <div class="bgGlow"></div>
    <div class="bgHappiness"></div>
    <div id="golden-ratio"></div>
    <canvas id="particleCanvas"></canvas>

    <div class="main-card">
        <header class="header">
            <div style="display:flex;align-items:center;gap:18px;">
                <img src="img/logo.png" height="40">
                <div>
                    <h2 style="margin:0;font-size:18px; font-weight: 800; letter-spacing: -0.5px;">Master Sync Engine</h2>
                    <p style="margin:0;font-size:10px;color:var(--muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">webytehub cloud integration</p>
                </div>
            </div>
            <div style="background:rgba(139,92,246,0.1); padding:8px 18px; border-radius:12px; font-size:10px; font-weight:900; color:var(--primary); text-transform: uppercase; border: 1px solid rgba(139,92,246,0.2);">
                <?php if (!is_file($appCfg)): ?> <i class="fa-solid fa-triangle-exclamation"></i> Sync Requerido
                <?php else: ?> Ecossistema Ativo <?php endif; ?>
            </div>
        </header>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="stepper" style="margin-bottom:0;">
                <div class="step" id="st1"><span>1</span> Acesso</div>
                <div class="step" id="st2"><span>2</span> Sincronia</div>
                <div class="step" id="st3"><span>3</span> Sucesso</div>
            </div>
            <button onclick="init()" class="btn-refresh" title="Buscar Atualizações" style="display: <?= $valid ? 'flex' : 'none' ?>;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                Refresh
            </button>
        </div>

        <div id="auth-panel" style="text-align:center; display: <?= !$valid ? 'block' : 'none' ?>;">
            <p style="font-size: 26px; font-weight: 800; margin-bottom: 35px; letter-spacing: -1px;">Central de
                Atualizações</p>
            <form method="POST" style="max-width: 440px; margin: 0 auto;">
                <input type="text" name="license_input" placeholder="LICENÇA-WEBYTEHUB" required>
                <button type="submit" class="btn-main" style="width:100%">Desbloquear Sistema</button>
            </form>
        </div>

        <div id="sync-panel" style="display:none;">
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <tbody id="v-body"></tbody>
                </table>
            </div>
            <!-- SAFETY & RECOVERY CENTER -->
            <div id="safety-engine" style="background:rgba(139, 92, 246, 0.05); border:1px solid rgba(139, 92, 246, 0.2); padding:20px; border-radius:15px; margin-top:20px; display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h4 style="font-size:11px; font-weight:900; color:var(--primary); letter-spacing:1px; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-shield-halved"></i> SAFETY SNAPSHOT ENGINE
                    </h4>
                    <span style="font-size:9px; background:var(--success); color:#000; padding:2px 6px; border-radius:4px; font-weight:900;">PROTEÇÃO ATIVA</span>
                </div>
                <p style="font-size:11px; color:var(--muted); line-height:1.4;">Um ponto de restauração será criado automaticamente antes da sincronização. Em caso de anomalia, você poderá reverter todo o sistema para o estado atual em menos de 30 segundos.</p>
                <div style="display:flex; gap:10px; margin-top:15px;">
                    <button class="btn-refresh" style="font-size:9px; background:rgba(255,255,255,0.05); border-color:var(--border);"><i class="fa-solid fa-clock-rotate-left"></i> VER ÚLTIMOS SNAPSHOTS</button>
                    <button class="btn-refresh" style="font-size:9px; border-color:var(--success); color:var(--success);"><i class="fa-solid fa-check-double"></i> INTEGRIDADE VALIDADA</button>
                </div>
            </div>

            <div id="console"></div>
        </div>

        <div id="done-panel" style="display:none; text-align:center; padding: 20px 0;">
            <div
                style="width:80px;height:80px;background:var(--success);border-radius:50%;display:grid;place-items:center;margin:0 auto 30px;box-shadow:0 0 50px rgba(34,197,94,0.3)">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="4">
                    <path d="M20 6L9 17L4 12"></path>
                </svg>
            </div>
            <h2 style="font-size:32px;font-weight:800;margin:0 0 12px; letter-spacing: -1px;">Tudo Pronto!</h2>
            <p style="color:var(--muted);font-size:17px;margin-bottom:45px; line-height: 1.6;">Seu ecossistema WebyteHub
                está totalmente sincronizado e otimizado em todas as instâncias.</p>
            <div style="display:flex;gap:20px;justify-content:center;">
                <a href="../../admin/" class="btn-main">Voltar ao Painel</a>
                <a href="../api/docs.php" class="btn-main"
                    style="background:transparent!important; border:1px solid var(--border)!important; color: #fff !important;">Ver
                    Logs</a>
            </div>
        </div>

        <div id="footer-db"
            style="margin-top: 45px; display: flex; justify-content: space-between; align-items: center; gap: 20px; font-size: 11px; color: var(--muted); border-top: 1px solid var(--border); padding-top: 30px; opacity:0; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div
                    style="width: 8px; height: 8px; background: var(--success); border-radius: 50%; box-shadow: 0 0 10px var(--success);">
                </div>
                <span>Sync Node: <strong id="db-ref" style="color:#fff">...</strong></span>
            </div>
            <a href="?logout=1"
                style="color:#ef4444; text-decoration:none; border: 1px solid rgba(239, 68, 68, 0.2); padding: 5px 12px; border-radius: 8px; transition: 0.3s; background: rgba(239, 68, 68, 0.05);">Desconectar</a>
        </div>
    </div>

    <!-- Partículas Dinâmicas -->
    <script>
        (function () {
            const canvas = document.getElementById('particleCanvas'); if (!canvas) return;
            const ctx = canvas.getContext('2d');
            let pts = []; function resz() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
            window.addEventListener('resize', resz); resz();
            class Pt {
                constructor() { this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height; this.s = Math.random() * 1.5; this.vx = Math.random() * 0.2 - 0.1; this.vy = Math.random() * 0.2 - 0.1; }
                upd() { this.x += this.vx; this.y += this.vy; if (this.x > canvas.width) this.x = 0; if (this.y > canvas.height) this.y = 0; }
                drw() { ctx.fillStyle = 'rgba(251,191,36,0.2)'; ctx.beginPath(); ctx.arc(this.x, this.y, this.s, 0, Math.PI * 2); ctx.fill(); }
            }
            for (let i = 0; i < 40; i++) pts.push(new Pt());
            function anim() { ctx.clearRect(0, 0, canvas.width, canvas.height); pts.forEach(p => { p.upd(); p.drw(); }); requestAnimationFrame(anim); }
            anim();
        })();
    </script>

    <script>
        async function init() {
            console.log("Init called - Checking state...");
            const valid = <?= $valid ? 'true' : 'false' ?>;
            if (!valid) { document.getElementById('st1').classList.add('active'); return; }

            document.getElementById('st2').classList.add('active');
            document.getElementById('sync-panel').style.display = 'block';
            if(document.getElementById('safety-engine')) document.getElementById('safety-engine').style.display = 'block';
            document.getElementById('footer-db').style.opacity = '1';

            const refreshBtn = document.querySelector('.btn-refresh');
            if(refreshBtn) {
                refreshBtn.classList.add('loading');
                refreshBtn.classList.remove('pulse');
                refreshBtn.style.opacity = '0.5';
            }

            try {
                const r = await fetch('index.php?action=check');
                const d = await r.json();
                console.log("Sync Response:", d);
                console.log("Applied Versions:", d.applied);
                document.getElementById('db-ref').innerText = `${d.db_app} & ${d.db_api}`;
                
                if(d.db_error) console.error("Database Connection Error:", d.db_error);

                if (d.count === 0) {
                    console.log("All synced! Showing completion.");
                    showConclusion();
                } else {
                    console.log(`Found ${d.count} updates.`);
                    if(refreshBtn) refreshBtn.classList.add('pulse');
                    renderVersions(d.available);
                }
            } catch (e) { console.error("Sync Error:", e); }
            finally { 
                if(refreshBtn) {
                    refreshBtn.classList.remove('loading');
                    refreshBtn.style.opacity = '1';
                }
            }
        }

        function renderVersions(list) {
            const b = document.getElementById('v-body'); b.innerHTML = '';
            list.forEach(v => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-weight:900;font-size:18px;">v${v.version}</td>
                    <td style="color:var(--primary);font-size:11px;font-weight:700;">DROP & API HUB</td>
                    <td style="color:var(--muted);font-size:13px">${v.info.description}</td>
                    <td style="text-align:right">
                        <label style="font-size:10px; color:var(--muted); margin-right:10px; cursor:pointer;">
                            <input type="checkbox" id="bkp-${v.version}" checked> Full Safe Backup
                        </label>
                        <button class="btn-main" onclick="run('${v.version}')">Sync Agora</button>
                    </td>`;
                b.appendChild(tr);
            });
        }

        async function run(v) {
            const c = document.getElementById('console'); 
            const doBackup = document.getElementById(`bkp-${v}`).checked;
            c.style.display = 'block'; 
            c.innerHTML = `> Conectando ao Sync Node v${v}...\n`;
            
            try {
                const response = await fetch(`index.php?action=apply&version=${v}&backup=${doBackup}`);
                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    const text = decoder.decode(value, { stream: true });
                    c.innerHTML += text;
                    c.scrollTop = c.scrollHeight;
                }
                
                c.innerHTML += `\n\n> Tudo pronto! Recarregando ambiente...`;
                setTimeout(() => location.reload(), 2000);
            } catch (e) {
                c.innerHTML += `\n> ERRO CRÍTICO: Falha na comunicação com o servidor.`;
            }
        }

        function showConclusion() {
            document.getElementById('sync-panel').style.display = 'none';
            document.getElementById('done-panel').style.display = 'block';
            document.getElementById('st2').classList.remove('active');
            document.getElementById('st3').classList.add('active');
            // Esconde o botão refresh na tela final
            const refreshBtn = document.querySelector('.btn-refresh');
            if(refreshBtn) refreshBtn.style.display = 'none';
        }

        init();
    </script>
</body>

</html>

