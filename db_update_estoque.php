<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dropfiber');

require_once __DIR__ . '/backend/app/Core/Database.php';

try {
    $db = \App\Core\Database::connect();
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS `materials` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(255) NOT NULL,
            `unidade` VARCHAR(50) DEFAULT 'un'
        )
    ");
    
    // Seed basic materials if empty
    $stmt = $db->query("SELECT COUNT(*) FROM materials");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO materials (nome, unidade) VALUES ('Bobina Cabo Drop', 'metros'), ('Roteador ONU Wi-Fi 6', 'un'), ('Conector Fast', 'un'), ('Caixa CTO', 'un')");
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS `user_stock` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `material_id` INT NOT NULL,
            `quantidade` DECIMAL(10,2) DEFAULT 0,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `stock_movements` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `material_id` INT NOT NULL,
            `quantidade` DECIMAL(10,2) NOT NULL,
            `tipo` ENUM('entrada', 'saida') NOT NULL,
            `descricao` VARCHAR(255),
            `data_movimento` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE
        )
    ");
    echo "Tabelas de estoque criadas com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
