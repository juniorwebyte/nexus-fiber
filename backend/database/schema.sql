-- Drop Nexus Fiber - Schema Inicial (Fase 2)
-- Execute este script no banco de dados 'dropfiber'

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `re_matricula` VARCHAR(50) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `cargo` ENUM(
        'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 
        'Supervisor', 'Fiscal', 'Técnico', 'Cabista Especial', 
        'Cabista 4', 'Cabista 3', 'Cabista 2', 'Cabista 1', 
        'Backbone', 'Classe L', 'Classe F', 'Auxiliar', 'Admin'
    ) DEFAULT 'Auxiliar',
    `pontuacao` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `work_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(255) NOT NULL,
    `descricao` TEXT,
    `status` ENUM('pendente', 'concluida') DEFAULT 'pendente',
    `tecnico_id` INT NULL,
    `foto_url` VARCHAR(255) NULL,
    `pontos_recompensa` INT DEFAULT 50,
    `croqui_porta_cto` INT NULL,
    `croqui_sinal_dbm` DECIMAL(5,2) NULL,
    `croqui_mac` VARCHAR(50) NULL,
    `croqui_obs` TEXT NULL,
    `prazo_limite` TIMESTAMP NULL,
    `assinatura_url` VARCHAR(255) NULL,
    `tipo_os` ENUM('instalacao', 'manutencao') DEFAULT 'instalacao',
    `rede_causa_rompimento` VARCHAR(255) NULL,
    `rede_atenuacao_fusao` DECIMAL(5,2) NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `concluido_em` TIMESTAMP NULL,
    FOREIGN KEY (`tecnico_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `unidade` VARCHAR(50) DEFAULT 'un'
);

CREATE TABLE IF NOT EXISTS `user_stock` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `quantidade` DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE
);

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
);

CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cargo_alvo` ENUM('Auxiliar', 'Técnico') NOT NULL,
    `pergunta` TEXT NOT NULL,
    `opcao_a` VARCHAR(255) NOT NULL,
    `opcao_b` VARCHAR(255) NOT NULL,
    `opcao_c` VARCHAR(255) NOT NULL,
    `opcao_d` VARCHAR(255) NOT NULL,
    `resposta_correta` CHAR(1) NOT NULL,
    `pontos` INT DEFAULT 10
);

CREATE TABLE IF NOT EXISTS `user_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tipo_atividade` ENUM('video', 'quiz', 'missao') NOT NULL,
    `referencia_id` VARCHAR(100) NOT NULL, -- ID do vídeo ou quiz concluído
    `pontos_ganhos` INT DEFAULT 0,
    `data_conclusao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Inserindo um admin padrão (Senha: 123456)
-- O hash abaixo é um bcrypt para '123456'
INSERT IGNORE INTO `users` (`re_matricula`, `senha`, `nome`, `cargo`, `pontuacao`) 
VALUES ('ADMIN01', '$2y$10$wN9P.pZ4D8J5Z5tM6S0r2.15gE/hK8bQz9O9n5tF8eYJ9V3A1aC3S', 'Administrador Supremo', 'Admin', 9999);

-- Inserindo perguntas base
INSERT IGNORE INTO `quiz_questions` (`cargo_alvo`, `pergunta`, `opcao_a`, `opcao_b`, `opcao_c`, `opcao_d`, `resposta_correta`) VALUES 
('Auxiliar', 'Qual é a função da bobina de lançamento (500m) em testes com OTDR?', 'Esticar o cabo', 'Superar a zona morta do equipamento', 'Aumentar a potência do laser', 'Limpar o conector', 'b'),
('Auxiliar', 'Qual a atenuação teórica máxima de um Splitter 1:8?', '7.1 dB', '13.8 dB', '10.5 dB', '17.0 dB', 'c'),
('Técnico', 'Qual comprimento de onda (λ) é mais sensível a macrocurvaturas (dobras no cabo)?', '1310 nm', '1490 nm', '1550 nm', '850 nm', 'c');
