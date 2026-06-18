<?php
namespace App\Controllers;

use App\Core\Database;

class ReportController
{
    public function __construct()
    {
        AuthController::requireLogin();
        
        $cargo = $_SESSION['cargo'] ?? '';
        $isManager = in_array($cargo, ['Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 'Supervisor']);
        if (!$isManager) {
            die("Acesso Negado: Apenas gestores podem emitir relatórios.");
        }
    }

    public function exportOs()
    {
        $db = Database::connect();
        $stmt = $db->query("
            SELECT w.id, w.titulo, w.status, w.criado_em, w.concluido_em, 
                   u.nome as tecnico, 
                   w.croqui_porta_cto, w.croqui_sinal_dbm, w.croqui_mac, w.croqui_obs 
            FROM work_orders w 
            LEFT JOIN users u ON w.tecnico_id = u.id 
            ORDER BY w.criado_em DESC
        ");
        $orders = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_os_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Título', 'Status', 'Técnico', 'Criado Em', 'Concluído Em', 'Porta CTO', 'Sinal (dBm)', 'MAC Address', 'Observações']);

        foreach ($orders as $row) {
            fputcsv($output, [
                $row['id'],
                $row['titulo'],
                $row['status'],
                $row['tecnico'],
                $row['criado_em'],
                $row['concluido_em'],
                $row['croqui_porta_cto'],
                $row['croqui_sinal_dbm'],
                $row['croqui_mac'],
                $row['croqui_obs']
            ]);
        }
        fclose($output);
        exit;
    }

    public function importOs()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $db = Database::connect();
                $header = fgetcsv($handle, 1000, ","); // Skip header
                
                $count = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Planilha esperada: Titulo, Descricao, Pontos Recompensa, Tecnico_ID
                    $titulo = $data[0] ?? '';
                    $descricao = $data[1] ?? '';
                    $pontos = $data[2] ?? 50;
                    $tecnico_id = $data[3] ?? null;

                    if ($titulo) {
                        $stmt = $db->prepare("INSERT INTO work_orders (titulo, descricao, pontos_recompensa, tecnico_id) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$titulo, $descricao, $pontos, $tecnico_id]);
                        $count++;
                    }
                }
                fclose($handle);
                header("Location: " . BASE_URL . "/os?imported=" . $count);
                exit;
            }
        }
        header("Location: " . BASE_URL . "/os?error=import");
        exit;
    }
}
