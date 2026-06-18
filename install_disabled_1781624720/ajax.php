<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

if ($action === 'test_db') {
    $host = $_POST['host'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $name = $_POST['name'] ?? '';

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($host, $user, $pass);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]);
        exit;
    }

    if ($name !== '') {
        $db_selected = @$conn->select_db($name);
        if (!$db_selected) {
            echo json_encode(['success' => true, 'message' => 'Conexão OK. O banco de dados "' . $name . '" ainda não existe (será criado na instalação).']);
            $conn->close();
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Conexão com o banco de dados estabelecida com sucesso!']);
    $conn->close();
    exit;
}

if ($action === 'verify_license') {
    $key = $_POST['key'] ?? '';
    if (trim($key) === '') {
        echo json_encode(['valid' => false, 'message' => 'Chave de licença vazia.']);
        exit;
    }
    
    // Detect API URL
    $isHttps   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme    = $isHttps ? 'https://' : 'http://';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script    = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath  = preg_replace('#/install(?:/ajax\\.php)?$#i', '', $script);
    $basePath  = rtrim($basePath, '/');
    
    $apiUrl = $scheme . $host . $basePath . '/api/v1/verify.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'key' => $key,
        'domain' => $host
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data) {
            echo json_encode($data);
            exit;
        }
    }
    
    // Fallback or Test mock
    if (strpos(strtoupper($key), 'WEBYTE') === 0 || strtoupper($key) === 'TESTE') {
        echo json_encode(['valid' => true, 'message' => 'Licença válida (Modo Offline / Teste).']);
    } else {
        echo json_encode(['valid' => false, 'message' => 'Chave inválida. API inacessível no momento.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
