<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dropfiber');

require_once __DIR__ . '/backend/app/Core/Database.php';

try {
    $db = \App\Core\Database::connect();
    $db->exec("
        CREATE TABLE IF NOT EXISTS `work_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `titulo` VARCHAR(255) NOT NULL,
            `descricao` TEXT,
            `status` ENUM('pendente', 'concluida') DEFAULT 'pendente',
            `tecnico_id` INT NULL,
            `foto_url` VARCHAR(255) NULL,
            `pontos_recompensa` INT DEFAULT 50,
            `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `concluido_em` TIMESTAMP NULL,
            FOREIGN KEY (`tecnico_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )
    ");
    echo "Tabela work_orders criada com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
