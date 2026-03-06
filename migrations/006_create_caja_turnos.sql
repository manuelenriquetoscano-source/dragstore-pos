CREATE TABLE IF NOT EXISTS caja_turnos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0,
    monto_final_declarado DECIMAL(10,2) NULL DEFAULT NULL,
    total_ventas DECIMAL(10,2) NOT NULL DEFAULT 0,
    cantidad_ventas INT NOT NULL DEFAULT 0,
    diferencia DECIMAL(10,2) NULL DEFAULT NULL,
    estado ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto',
    observaciones VARCHAR(255) NULL,
    CONSTRAINT fk_caja_turnos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'caja_turnos'
      AND index_name = 'idx_caja_turnos_usuario_estado'
);
SET @idx_sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_caja_turnos_usuario_estado ON caja_turnos(usuario_id, estado)',
    'SELECT 1'
);
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'caja_turnos'
      AND index_name = 'idx_caja_turnos_opened_at'
);
SET @idx_sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_caja_turnos_opened_at ON caja_turnos(opened_at)',
    'SELECT 1'
);
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'ventas'
      AND column_name = 'usuario_id'
);
SET @col_sql := IF(
    @col_exists = 0,
    'ALTER TABLE ventas ADD COLUMN usuario_id INT NULL AFTER total',
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
      AND column_name = 'turno_id'
);
SET @col_sql := IF(
    @col_exists = 0,
    'ALTER TABLE ventas ADD COLUMN turno_id BIGINT NULL AFTER usuario_id',
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
      AND index_name = 'idx_ventas_turno_id'
);
SET @idx_sql := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_ventas_turno_id ON ventas(turno_id)',
    'SELECT 1'
);
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
