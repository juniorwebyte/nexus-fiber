<?php
namespace App\Controllers;

use App\Controllers\AuthController;

class MapController
{
    private $allowedRoles = [
        'Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 
        'Coordenador', 'Supervisor', 'Fiscal', 'Técnico'
    ];

    public function __construct()
    {
        AuthController::requireLogin();
        
        if (!in_array($_SESSION['cargo'], $this->allowedRoles)) {
            echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'><h2>Acesso Negado</h2><p>Você precisa ser no mínimo <b>Técnico</b> para visualizar os projetos executivos de rede.</p><a href='".BASE_URL."/'>Voltar</a></div>";
            exit;
        }
    }

    public function upload()
    {
        $activeMenu = 'mapa';
        $viewPath = __DIR__ . '/../Views/map/upload.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mubi_file'])) {
            $file = $_FILES['mubi_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, ['kml', 'kmz', 'dwg'])) {
                // Sobe a raiz do backend/uploads
                $uploadDir = __DIR__ . '/../../../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $filename = uniqid('projeto_') . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    
                    // Se for DWG, roda o "Motor Nexus MUBI" para converter
                    if ($ext === 'dwg') {
                        $this->simulateDwgToGeojsonConversion($uploadDir, $filename);
                        // O nome do arquivo a ser lido no mapa passa a ser o JSON convertido
                        $filename = $filename . '.geojson';
                    }

                    header("Location: " . BASE_URL . "/mapa/view?file=" . $filename);
                    exit;
                } else {
                    $error = "Erro ao salvar arquivo MUBI.";
                }
            } else {
                $error = "Formato inválido. Apenas arquivos .dwg, .kml e .kmz (MUBI) são permitidos.";
            }
        }

        require_once __DIR__ . '/../Views/layout.php';
    }

    public function view()
    {
        $activeMenu = 'mapa';
        $viewPath = __DIR__ . '/../Views/map/view.php';
        $file = $_GET['file'] ?? '';
        
        if (!$file) {
            header("Location: " . BASE_URL . "/mapa/upload");
            exit;
        }
        
        // Passamos o URL do arquivo gerado/convertido para o frontend ler
        $fileUrl = BASE_URL . '/../uploads/' . htmlspecialchars($file);
        $isGeojson = strpos(strtolower($file), '.geojson') !== false;
        
        require_once __DIR__ . '/../Views/layout.php';
    }

    /**
     * Simula o Motor de Conversão DWG (GDAL/Autodesk).
     * Cria um arquivo .geojson falso simulando a rota de cabos extraída do AutoCAD.
     */
    private function simulateDwgToGeojsonConversion($path, $dwgFilename)
    {
        $geojson = '{
            "type": "FeatureCollection",
            "features": [
                {
                    "type": "Feature",
                    "properties": { "tipo": "Cabo Óptico", "capacidade": "36FO" },
                    "geometry": {
                        "type": "LineString",
                        "coordinates": [
                            [-46.633308, -23.550520],
                            [-46.634000, -23.551000],
                            [-46.635500, -23.550100],
                            [-46.637000, -23.548000]
                        ]
                    }
                },
                {
                    "type": "Feature",
                    "properties": { "tipo": "Caixa de Emenda (CTO)", "nome": "CTO-01" },
                    "geometry": { "type": "Point", "coordinates": [-46.633308, -23.550520] }
                },
                {
                    "type": "Feature",
                    "properties": { "tipo": "Caixa de Emenda (CTO)", "nome": "CTO-02" },
                    "geometry": { "type": "Point", "coordinates": [-46.637000, -23.548000] }
                }
            ]
        }';
        
        file_put_contents($path . $dwgFilename . '.geojson', $geojson);
    }
}
