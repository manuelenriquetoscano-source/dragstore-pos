SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND index_name = 'idx_productos_nombre'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_productos_nombre ON productos(nombre)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND index_name = 'idx_productos_stock'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_productos_stock ON productos(stock)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND index_name = 'idx_ventas_fecha'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_ventas_fecha ON ventas(fecha)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'detalle_ventas'
      AND index_name = 'idx_detalle_venta_id'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_detalle_venta_id ON detalle_ventas(venta_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
