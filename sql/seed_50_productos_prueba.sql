-- Seed masivo: 50 productos de prueba para Drugstore POS
-- Compatible con MySQL 8.x (Workbench)
-- Idempotente: si existe el codigo_barras, actualiza el producto.
-- Incluye lotes FEFO si existe la tabla producto_lotes.

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_seed_productos;
CREATE TEMPORARY TABLE tmp_seed_productos (
    seq INT PRIMARY KEY,
    codigo_barras VARCHAR(120) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    stock_minimo INT NOT NULL,
    numero_lote VARCHAR(120) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    costo_unitario DECIMAL(10,2) NULL
);

INSERT INTO tmp_seed_productos
    (seq, codigo_barras, nombre, precio, stock, stock_minimo, numero_lote, fecha_vencimiento, costo_unitario)
WITH RECURSIVE serie AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM serie WHERE n < 50
)
SELECT
    n AS seq,
    CONCAT('779990', LPAD(n, 6, '0')) AS codigo_barras,
    CONCAT(
        ELT(((n - 1) % 10) + 1,
            'Shampoo',
            'Acondicionador',
            'Jabon Liquido',
            'Pasta Dental',
            'Desodorante',
            'Crema Corporal',
            'Toalla Humeda',
            'Vitamina C',
            'Analgesico',
            'Alcohol Gel'
        ),
        ' ',
        ELT(((n - 1) % 5) + 1, 'Fresh', 'Care', 'Plus', 'Active', 'Family'),
        ' ',
        ELT(((n - 1) % 4) + 1, '250ml', '400ml', '500ml', '750ml'),
        ' #',
        LPAD(n, 2, '0')
    ) AS nombre,
    ROUND(450 + (n * 23.75), 2) AS precio,
    8 + (n % 25) AS stock,
    3 + (n % 6) AS stock_minimo,
    CONCAT('L-TEST-', DATE_FORMAT(CURDATE(), '%y%m'), '-', LPAD(n, 4, '0')) AS numero_lote,
    DATE_ADD(CURDATE(), INTERVAL (20 + n) DAY) AS fecha_vencimiento,
    ROUND((450 + (n * 23.75)) * 0.62, 2) AS costo_unitario
FROM serie;

-- Detecta si productos.stock_minimo existe
SET @has_stock_minimo := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND column_name = 'stock_minimo'
);

SET @sql_insert_productos := IF(
    @has_stock_minimo > 0,
    "INSERT INTO productos (codigo_barras, nombre, precio, stock, stock_minimo)
     SELECT codigo_barras, nombre, precio, stock, stock_minimo
     FROM tmp_seed_productos
     ON DUPLICATE KEY UPDATE
        nombre = VALUES(nombre),
        precio = VALUES(precio),
        stock = VALUES(stock),
        stock_minimo = VALUES(stock_minimo)",
    "INSERT INTO productos (codigo_barras, nombre, precio, stock)
     SELECT codigo_barras, nombre, precio, stock
     FROM tmp_seed_productos
     ON DUPLICATE KEY UPDATE
        nombre = VALUES(nombre),
        precio = VALUES(precio),
        stock = VALUES(stock)"
);

PREPARE stmt_insert_productos FROM @sql_insert_productos;
EXECUTE stmt_insert_productos;
DEALLOCATE PREPARE stmt_insert_productos;

-- Si existe producto_lotes, crea 1 lote por producto seed (sin duplicar numero_lote por producto)
SET @has_lotes := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'producto_lotes'
);

SET @sql_insert_lotes := IF(
    @has_lotes > 0,
    "INSERT INTO producto_lotes
        (producto_id, numero_lote, fecha_vencimiento, cantidad_inicial, cantidad_disponible, costo_unitario, estado)
     SELECT
        p.id,
        t.numero_lote,
        t.fecha_vencimiento,
        t.stock,
        t.stock,
        t.costo_unitario,
        'activo'
     FROM tmp_seed_productos t
     INNER JOIN productos p ON p.codigo_barras = t.codigo_barras
     LEFT JOIN producto_lotes l
            ON l.producto_id = p.id
           AND l.numero_lote = t.numero_lote
     WHERE l.id IS NULL",
    "SELECT 1"
);

PREPARE stmt_insert_lotes FROM @sql_insert_lotes;
EXECUTE stmt_insert_lotes;
DEALLOCATE PREPARE stmt_insert_lotes;

-- Reajusta stock en productos segun el seed (consistencia visual)
UPDATE productos p
INNER JOIN tmp_seed_productos t ON t.codigo_barras = p.codigo_barras
SET p.stock = t.stock;

COMMIT;

SELECT
    COUNT(*) AS productos_seed_cargados
FROM productos
WHERE codigo_barras LIKE '779990%';

SELECT
    p.codigo_barras,
    p.nombre,
    p.precio,
    p.stock
FROM productos p
WHERE p.codigo_barras LIKE '779990%'
ORDER BY p.codigo_barras
LIMIT 10;
