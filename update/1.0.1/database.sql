-- Update 1.0.1 - Generic System Patch
-- Garantindo que a infraestrutura base do DROP esteja otimizada
CREATE TABLE IF NOT EXISTS `system_meta` (
    `meta_key` VARCHAR(50) PRIMARY KEY,
    `meta_value` LONGTEXT
) CHARACTER SET utf8mb4;
