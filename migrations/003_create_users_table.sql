CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'caja') NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO usuarios (username, password_hash, role, display_name, activo)
VALUES
    ('admin', '$2y$10$/WVQ1JvRYVQ2Xh.KKts2re6i90lBEDZoBDkZxmBsBVWYBI28AJiMa', 'admin', 'Administrador', 1),
    ('caja', '$2y$10$bczBGbx7Ta.P3oyAymdHVuFP2/xsefcNFTJTJsLFUF2J5CWNS2cfu', 'caja', 'Caja', 1)
ON DUPLICATE KEY UPDATE
    updated_at = CURRENT_TIMESTAMP;

