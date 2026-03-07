CREATE TABLE IF NOT EXISTS detalle_ventas_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detalle_venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    lote_id INT NOT NULL,
    cantidad INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detalle_ventas_lotes_detalle
        FOREIGN KEY (detalle_venta_id) REFERENCES detalle_ventas(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_ventas_lotes_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_detalle_ventas_lotes_lote
        FOREIGN KEY (lote_id) REFERENCES producto_lotes(id)
        ON DELETE RESTRICT
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'detalle_ventas_lotes'
      AND index_name = 'idx_dvl_detalle'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_dvl_detalle ON detalle_ventas_lotes(detalle_venta_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'detalle_ventas_lotes'
      AND index_name = 'idx_dvl_producto'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_dvl_producto ON detalle_ventas_lotes(producto_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'detalle_ventas_lotes'
      AND index_name = 'idx_dvl_lote'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_dvl_lote ON detalle_ventas_lotes(lote_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
