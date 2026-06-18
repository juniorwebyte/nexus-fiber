<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dropfiber');

require_once __DIR__ . '/backend/app/Core/Database.php';

try {
    $db = \App\Core\Database::connect();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `apr_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tipo_apr` VARCHAR(50) NOT NULL,
        `grupo_altura` VARCHAR(50) NULL,
        `caracteristica_atividade` VARCHAR(100) NULL,
        `tipo_supervisao` VARCHAR(50) NULL,
        `travessia` VARCHAR(10) NULL,
        `re_colaborador` VARCHAR(50) NULL,
        `nome_colaborador` VARCHAR(150) NULL,
        `gerente_corporativo` VARCHAR(150) NULL,
        `gerente_regional` VARCHAR(150) NULL,
        `coordenador` VARCHAR(150) NULL,
        `supervisor` VARCHAR(150) NULL,
        `funcao` VARCHAR(100) NULL,
        `base` VARCHAR(100) NULL,
        `operadora_contrato` VARCHAR(100) NULL,
        `setor` VARCHAR(100) NULL,
        `data_inicio` DATETIME NULL,
        `data_fim` DATETIME NULL,
        `endereco` TEXT NULL,
        `os_id` INT NULL,
        `etapa1_status` VARCHAR(20) NULL,
        `etapa1_obs` TEXT NULL,
        `etapa2_status` VARCHAR(20) NULL,
        `etapa2_obs` TEXT NULL,
        `etapa3_status` VARCHAR(20) NULL,
        `etapa3_obs` TEXT NULL,
        `etapa4_status` VARCHAR(20) NULL,
        `etapa4_obs` TEXT NULL,
        `etapa5_status` VARCHAR(20) NULL,
        `etapa5_obs` TEXT NULL,
        `etapa6_status` VARCHAR(20) NULL,
        `etapa6_obs` TEXT NULL,
        `relato_perigo` BOOLEAN DEFAULT FALSE,
        `relato_perigo_desc` TEXT NULL,
        `foto_url` VARCHAR(255) NULL,
        `latitude` VARCHAR(50) NULL,
        `longitude` VARCHAR(50) NULL,
        `criado_por` INT NULL,
        `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`criado_por`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`os_id`) REFERENCES `work_orders`(`id`) ON DELETE SET NULL
    );
    ";

    $db->exec($sql);
    echo "Tabela apr_records criada com sucesso!\n";

} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
