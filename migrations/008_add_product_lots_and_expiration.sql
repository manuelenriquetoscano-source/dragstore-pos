SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND column_name = 'stock_minimo'
);
SET @sql_col := IF(@col_exists = 0, 'ALTER TABLE productos ADD COLUMN stock_minimo INT NOT NULL DEFAULT 5 AFTER stock', 'SELECT 1');
PREPARE stmt_col FROM @sql_col;
EXECUTE stmt_col;
DEALLOCATE PREPARE stmt_col;

CREATE TABLE IF NOT EXISTS producto_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    numero_lote VARCHAR(120) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    cantidad_inicial INT NOT NULL DEFAULT 0,
    cantidad_disponible INT NOT NULL DEFAULT 0,
    costo_unitario DECIMAL(10,2) NULL,
    estado ENUM('activo', 'agotado', 'vencido') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_producto_lote_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON DELETE CASCADE
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'producto_lotes'
      AND index_name = 'idx_lotes_producto'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_lotes_producto ON producto_lotes(producto_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'producto_lotes'
      AND index_name = 'idx_lotes_vencimiento'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_lotes_vencimiento ON producto_lotes(fecha_vencimiento)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'producto_lotes'
      AND index_name = 'idx_lotes_estado'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_lotes_estado ON producto_lotes(estado)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
