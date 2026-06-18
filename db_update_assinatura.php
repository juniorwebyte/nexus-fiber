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
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `prazo_limite` TIMESTAMP NULL");
        $db->exec("ALTER TABLE `work_orders` ADD COLUMN `assinatura_url` VARCHAR(255) NULL");
        echo "Colunas de Assinatura e SLA adicionadas com sucesso!\n";
    } catch (Exception $e) {
        echo "Aviso (pode ser que já existam): " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
