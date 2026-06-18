<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dropfiber');

require_once __DIR__ . '/backend/app/Core/Database.php';

try {
    $db = \App\Core\Database::connect();
    
    // Add columns if they don't exist
    try {
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `croqui_porta_cto` INT NULL");
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `croqui_sinal_dbm` DECIMAL(5,2) NULL");
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `croqui_mac` VARCHAR(50) NULL");
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `croqui_obs` TEXT NULL");
        echo "Colunas de Croqui adicionadas com sucesso!\n";
    } catch (Exception $e) {
        echo "Aviso (pode ser que já existam): " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
