SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND column_name = 'metodo_pago'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'mixto') NOT NULL DEFAULT 'efectivo' AFTER total",
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
      AND column_name = 'monto_recibido'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN monto_recibido DECIMAL(10,2) NULL AFTER metodo_pago",
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
      AND column_name = 'vuelto'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN vuelto DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER monto_recibido",
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
      AND column_name = 'monto_efectivo'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN monto_efectivo DECIMAL(10,2) NULL AFTER vuelto",
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
      AND column_name = 'monto_digital'
);
SET @col_sql := IF(
    @col_exists = 0,
    "ALTER TABLE ventas ADD COLUMN monto_digital DECIMAL(10,2) NULL AFTER monto_efectivo",
    'SELECT 1'
);
PREPARE col_stmt FROM @col_sql;
EXECUTE col_stmt;
DEALLOCATE PREPARE col_stmt;
