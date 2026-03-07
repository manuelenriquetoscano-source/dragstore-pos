SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND column_name = 'estado'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada' AFTER total",
    'SELECT 1'
);
PREPARE col_stmt FROM @col_sql;
EXECUTE col_stmt;
DEALLOCATE PREPARE col_stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND column_name = 'motivo_anulacion'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN motivo_anulacion VARCHAR(255) NULL AFTER estado",
    'SELECT 1'
);
PREPARE col_stmt FROM @col_sql;
EXECUTE col_stmt;
DEALLOCATE PREPARE col_stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND column_name = 'anulada_at'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN anulada_at TIMESTAMP NULL DEFAULT NULL AFTER motivo_anulacion",
    'SELECT 1'
);
PREPARE col_stmt FROM @col_sql;
EXECUTE col_stmt;
DEALLOCATE PREPARE col_stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND column_name = 'anulada_por_user_id'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN anulada_por_user_id INT NULL AFTER anulada_at",
    'SELECT 1'
);
PREPARE col_stmt FROM @col_sql;
EXECUTE col_stmt;
DEALLOCATE PREPARE col_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND index_name = 'idx_ventas_estado'
);
SET @idx_sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_ventas_estado ON ventas(estado)',
    'SELECT 1'
);
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
