<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dropfiber');

require_once __DIR__ . '/backend/app/Core/Database.php';

try {
    $db = \App\Core\Database::connect();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `perigo_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `celular_corporativo` VARCHAR(50) NULL,
        `contrato` VARCHAR(100) NULL,
        `setor` VARCHAR(100) NULL,
        `data_relato` DATETIME NULL,
        `uf` VARCHAR(2) NULL,
        `cidade` VARCHAR(100) NULL,
        `endereco` TEXT NULL,
        `os_id` INT NULL,
        `descricao_ocorrido` TEXT NULL,
        `stop_work` BOOLEAN DEFAULT FALSE,
        `categoria_risco` VARCHAR(100) NULL,
        `anjo_da_guarda` VARCHAR(50) NULL,
        `acoes_equipe` TEXT NULL,
        `gestor_avisado` VARCHAR(50) NULL,
        `criado_por` INT NULL,
        `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`criado_por`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`os_id`) REFERENCES `work_orders`(`id`) ON DELETE SET NULL
    );
    ";

    $db->exec($sql);
    echo "Tabela perigo_records criada com sucesso!\n";

} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
