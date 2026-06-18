<?php
namespace App\Controllers;

use App\Core\Database;

class AprController
{
    public function __construct()
    {
        AuthController::requireLogin();
    }

    public function index()
    {
        $db = Database::connect();
        $isManager = in_array($_SESSION['user_role'], ['admin', 'manager']);

        // Filtros (Ex: por data)
        $where = "";
        $params = [];
        if (!$isManager) {
            $where = "WHERE criado_por = ?";
            $params[] = $_SESSION['user_id'];
        }

        $stmt = $db->prepare("SELECT * FROM apr_records $where ORDER BY id DESC");
        $stmt->execute($params);
        $aprs = $stmt->fetchAll();

        require_once __DIR__ . '/../Views/layout/header.php';
        require_once __DIR__ . '/../Views/apr/index.php';
        require_once __DIR__ . '/../Views/layout/footer.php';
    }

    public function create()
    {
        // Se a APR está sendo criada vinculada a uma OS
        $os_id = $_GET['os_id'] ?? null;
        
        require_once __DIR__ . '/../Views/layout/header.php';
        require_once __DIR__ . '/../Views/apr/create.php';
        require_once __DIR__ . '/../Views/layout/footer.php';
    }

    public function store()
    {
        $db = Database::connect();

        $tipo_apr = $_POST['tipo_apr'] ?? 'Outras atividades';
        $grupo_altura = $_POST['grupo_altura'] ?? null;
        $caracteristica_atividade = $_POST['caracteristica_atividade'] ?? null;
        $tipo_supervisao = $_POST['tipo_supervisao'] ?? null;
        $travessia = $_POST['travessia'] ?? null;
        $re_colaborador = $_POST['re_colaborador'] ?? null;
        $nome_colaborador = $_POST['nome_colaborador'] ?? null;
        $gerente_corporativo = $_POST['gerente_corporativo'] ?? null;
        $gerente_regional = $_POST['gerente_regional'] ?? null;
        $coordenador = $_POST['coordenador'] ?? null;
        $supervisor = $_POST['supervisor'] ?? null;
        $funcao = $_POST['funcao'] ?? null;
        $base = $_POST['base'] ?? null;
        $operadora_contrato = $_POST['operadora_contrato'] ?? null;
        $setor = $_POST['setor'] ?? null;
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $endereco = $_POST['endereco'] ?? null;
        $os_id = $_POST['os_id'] ?? null;
        
        $etapa1_status = $_POST['etapa1_status'] ?? null;
        $etapa1_obs = $_POST['etapa1_obs'] ?? null;
        $etapa2_status = $_POST['etapa2_status'] ?? null;
        $etapa2_obs = $_POST['etapa2_obs'] ?? null;
        $etapa3_status = $_POST['etapa3_status'] ?? null;
        $etapa3_obs = $_POST['etapa3_obs'] ?? null;
        $etapa4_status = $_POST['etapa4_status'] ?? null;
        $etapa4_obs = $_POST['etapa4_obs'] ?? null;
        $etapa5_status = $_POST['etapa5_status'] ?? null;
        $etapa5_obs = $_POST['etapa5_obs'] ?? null;
        $etapa6_status = $_POST['etapa6_status'] ?? null;
        $etapa6_obs = $_POST['etapa6_obs'] ?? null;

        $relato_perigo = isset($_POST['relato_perigo']) && $_POST['relato_perigo'] === 'sim' ? 1 : 0;
        $relato_perigo_desc = $_POST['relato_perigo_desc'] ?? null;
        
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        // Upload de foto da APR
        $foto_url = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid('apr_') . '_' . basename($_FILES['foto']['name']);
            move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename);
            $foto_url = $filename;
        }

        $stmt = $db->prepare("
            INSERT INTO apr_records (
                tipo_apr, grupo_altura, caracteristica_atividade, tipo_supervisao, travessia,
                re_colaborador, nome_colaborador, gerente_corporativo, gerente_regional,
                coordenador, supervisor, funcao, base, operadora_contrato, setor,
                data_inicio, data_fim, endereco, os_id,
                etapa1_status, etapa1_obs, etapa2_status, etapa2_obs,
                etapa3_status, etapa3_obs, etapa4_status, etapa4_obs,
                etapa5_status, etapa5_obs, etapa6_status, etapa6_obs,
                relato_perigo, relato_perigo_desc, foto_url, latitude, longitude, criado_por
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $tipo_apr, $grupo_altura, $caracteristica_atividade, $tipo_supervisao, $travessia,
            $re_colaborador, $nome_colaborador, $gerente_corporativo, $gerente_regional,
            $coordenador, $supervisor, $funcao, $base, $operadora_contrato, $setor,
            $data_inicio, $data_fim, $endereco, $os_id,
            $etapa1_status, $etapa1_obs, $etapa2_status, $etapa2_obs,
            $etapa3_status, $etapa3_obs, $etapa4_status, $etapa4_obs,
            $etapa5_status, $etapa5_obs, $etapa6_status, $etapa6_obs,
            $relato_perigo, $relato_perigo_desc, $foto_url, $latitude, $longitude, $_SESSION['user_id']
        ]);

        header("Location: " . BASE_URL . "/apr?success=1");
        exit;
    }

    public function view($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM apr_records WHERE id = ?");
        $stmt->execute([$id]);
        $apr = $stmt->fetch();

        require_once __DIR__ . '/../Views/layout/header.php';
        require_once __DIR__ . '/../Views/apr/view.php';
        require_once __DIR__ . '/../Views/layout/footer.php';
    }
}
