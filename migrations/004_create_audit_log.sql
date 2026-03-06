CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT NULL,
    actor_username VARCHAR(120) NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT NULL,
    details_json JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_actor_user
        FOREIGN KEY (actor_user_id) REFERENCES usuarios(id)
        ON DELETE SET NULL
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'audit_log'
      AND index_name = 'idx_audit_created_at'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_audit_created_at ON audit_log(created_at)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'audit_log'
      AND index_name = 'idx_audit_actor_user_id'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_audit_actor_user_id ON audit_log(actor_user_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'audit_log'
      AND index_name = 'idx_audit_entity'
);
SET @idx_sql := IF(@idx_exists = 0, 'CREATE INDEX idx_audit_entity ON audit_log(entity_type, entity_id)', 'SELECT 1');
PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
