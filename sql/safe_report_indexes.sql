-- Script seguro/idempotente para optimizar reportes en dragstore_db
-- Compatible con MySQL 8.x
-- Evita crear índices duplicados por nombre o por columna.

USE `dragstore_db`;

DROP PROCEDURE IF EXISTS add_index_if_missing;
DELIMITER //
CREATE PROCEDURE add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_create_sql TEXT
)
BEGIN
    DECLARE v_exists_name INT DEFAULT 0;
    DECLARE v_exists_column INT DEFAULT 0;

    SELECT COUNT(*)
      INTO v_exists_name
      FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = p_table_name
       AND index_name = p_index_name;

    -- Si ya existe cualquier índice para esa columna, no crear otro para evitar duplicados.
    SELECT COUNT(*)
      INTO v_exists_column
      FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = p_table_name
       AND column_name = p_column_name;

    IF v_exists_name = 0 AND v_exists_column = 0 THEN
        SET @sql_stmt = p_create_sql;
        PREPARE stmt FROM @sql_stmt;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Reportes por fecha (ventas diarias)
CALL add_index_if_missing(
    'ventas',
    'idx_ventas_fecha',
    'fecha',
    'CREATE INDEX idx_ventas_fecha ON ventas(fecha)'
);

-- Búsqueda/listado por nombre de producto (inventario)
CALL add_index_if_missing(
    'productos',
    'idx_productos_nombre',
    'nombre',
    'CREATE INDEX idx_productos_nombre ON productos(nombre)'
);

-- Filtro por bajo stock
CALL add_index_if_missing(
    'productos',
    'idx_productos_stock',
    'stock',
    'CREATE INDEX idx_productos_stock ON productos(stock)'
);

-- Join de detalle -> venta (en tu dump ya existe KEY `venta_id`; se mantiene sin duplicar)
CALL add_index_if_missing(
    'detalle_ventas',
    'idx_detalle_venta_id',
    'venta_id',
    'CREATE INDEX idx_detalle_venta_id ON detalle_ventas(venta_id)'
);

DROP PROCEDURE IF EXISTS add_index_if_missing;

