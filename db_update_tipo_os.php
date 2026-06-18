<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dropfiber');

require_once __DIR__ . '/backend/app/Core/Database.php';

try {
    $db = \App\Core\Database::connect();
    
    try {
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `tipo_os` ENUM('instalacao', 'manutencao') DEFAULT 'instalacao'");
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `rede_causa_rompimento` VARCHAR(255) NULL");
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `rede_atenuacao_fusao` DECIMAL(5,2) NULL");
        echo "Colunas de Tipagem e Manutenção adicionadas com sucesso!\n";
    } catch (Exception $e) {
        echo "Aviso (pode ser que já existam): " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
