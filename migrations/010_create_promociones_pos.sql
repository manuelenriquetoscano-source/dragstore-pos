CREATE TABLE IF NOT EXISTS promociones_pos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(180) NOT NULL,
    tipo ENUM('2x1', 'percent', 'combo') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    prioridad INT NOT NULL DEFAULT 100,
    percent_value DECIMAL(6,2) NULL,
    min_qty INT NULL,
    combo_price DECIMAL(10,2) NULL,
    product_ids_json JSON NULL,
    codigos_barras_json JSON NULL,
    required_items_json JSON NULL,
    dias_semana_json JSON NULL,
    hora_desde TIME NULL,
    hora_hasta TIME NULL,
    vigencia_desde DATE NULL,
    vigencia_hasta DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'promociones_pos'
      AND index_name = 'idx_promos_activo_prioridad'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_promos_activo_prioridad ON promociones_pos(activo, prioridad)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'promociones_pos'
      AND index_name = 'idx_promos_vigencia'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_promos_vigencia ON promociones_pos(vigencia_desde, vigencia_hasta)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
