-- Seed masivo: 200 productos realistas para Dragstore POS
-- MySQL 8.x / Workbench
-- Idempotente por codigo_barras (inserta o actualiza)
-- Si existe producto_lotes, crea lotes con vencimientos mixtos (vencido / por vencer / activo)

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_seed_200;
CREATE TEMPORARY TABLE tmp_seed_200 (
    seq INT PRIMARY KEY,
    codigo_barras VARCHAR(120) NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    marca VARCHAR(80) NOT NULL,
    presentacion VARCHAR(60) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    stock_minimo INT NOT NULL,
    numero_lote VARCHAR(120) NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    costo_unitario DECIMAL(10,2) NULL
);

INSERT INTO tmp_seed_200
    (seq, codigo_barras, categoria, marca, presentacion, nombre, precio, stock, stock_minimo, numero_lote, fecha_vencimiento, costo_unitario)
WITH RECURSIVE serie AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM serie WHERE n < 200
)
SELECT
    n AS seq,
    CONCAT('779991', LPAD(n, 6, '0')) AS codigo_barras,
    ELT(((n - 1) % 10) + 1,
        'Perfumeria',
        'Cuidado Personal',
        'OTC',
        'Bebidas',
        'Snacks',
        'Limpieza',
        'Bebe',
        'Cosmetica',
        'Botiquin',
        'Accesorios'
    ) AS categoria,
    ELT(((n - 1) % 12) + 1,
        'FreshCare',
        'NaturaPlus',
        'BioClean',
        'VitalMax',
        'PureSkin',
        'UrbanFit',
        'NutriLife',
        'AquaGo',
        'FamilyBox',
        'HealthOne',
        'SmartDose',
        'DailyBest'
    ) AS marca,
    ELT(((n - 1) % 8) + 1,
        '100ml',
        '200ml',
        '250ml',
        '400ml',
        '500ml',
        '750ml',
        '1L',
        'Pack x6'
    ) AS presentacion,
    CONCAT(
        ELT(((n - 1) % 10) + 1,
            'Shampoo',
            'Jabon Liquido',
            'Ibuprofeno',
            'Gaseosa',
            'Galletitas',
            'Detergente',
            'Toallitas',
            'Labial',
            'Alcohol',
            'Pilas'
        ),
        ' ',
        ELT(((n - 1) % 12) + 1,
            'FreshCare',
            'NaturaPlus',
            'BioClean',
            'VitalMax',
            'PureSkin',
            'UrbanFit',
            'NutriLife',
            'AquaGo',
            'FamilyBox',
            'HealthOne',
            'SmartDose',
            'DailyBest'
        ),
        ' ',
        ELT(((n - 1) % 8) + 1,
            '100ml',
            '200ml',
            '250ml',
            '400ml',
            '500ml',
            '750ml',
            '1L',
            'Pack x6'
        ),
        ' #',
        LPAD(n, 3, '0')
    ) AS nombre,
    ROUND(300 + (n * 17.35) + ((n % 7) * 11.2), 2) AS precio,
    5 + (n % 40) AS stock,
    3 + (n % 8) AS stock_minimo,
    CONCAT('L-DRG-', DATE_FORMAT(CURDATE(), '%y%m'), '-', LPAD(n, 5, '0')) AS numero_lote,
    CASE
        WHEN (n % 10) = 0 THEN DATE_SUB(CURDATE(), INTERVAL (5 + (n % 15)) DAY)   -- vencidos
        WHEN (n % 10) IN (1, 2, 3) THEN DATE_ADD(CURDATE(), INTERVAL (7 + (n % 23)) DAY) -- por vencer
        ELSE DATE_ADD(CURDATE(), INTERVAL (40 + n) DAY) -- activos
    END AS fecha_vencimiento,
    ROUND((300 + (n * 17.35) + ((n % 7) * 11.2)) * 0.58, 2) AS costo_unitario
FROM serie;

-- Detecta columnas opcionales en productos
SET @has_stock_minimo := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND column_name = 'stock_minimo'
);
SET @has_categoria := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND column_name = 'categoria'
);
SET @has_marca := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'productos'
      AND column_name = 'marca'
);

-- Construye INSERT dinamico segun esquema disponible
SET @sql_insert_productos :=
    CASE
        WHEN @has_stock_minimo > 0 AND @has_categoria > 0 AND @has_marca > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, stock_minimo, categoria, marca)
             SELECT codigo_barras, nombre, precio, stock, stock_minimo, categoria, marca
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                stock_minimo = VALUES(stock_minimo),
                categoria = VALUES(categoria),
                marca = VALUES(marca)"
        WHEN @has_stock_minimo > 0 AND @has_categoria > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, stock_minimo, categoria)
             SELECT codigo_barras, nombre, precio, stock, stock_minimo, categoria
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                stock_minimo = VALUES(stock_minimo),
                categoria = VALUES(categoria)"
        WHEN @has_stock_minimo > 0 AND @has_marca > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, stock_minimo, marca)
             SELECT codigo_barras, nombre, precio, stock, stock_minimo, marca
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                stock_minimo = VALUES(stock_minimo),
                marca = VALUES(marca)"
        WHEN @has_stock_minimo > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, stock_minimo)
             SELECT codigo_barras, nombre, precio, stock, stock_minimo
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                stock_minimo = VALUES(stock_minimo)"
        WHEN @has_categoria > 0 AND @has_marca > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, categoria, marca)
             SELECT codigo_barras, nombre, precio, stock, categoria, marca
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                categoria = VALUES(categoria),
                marca = VALUES(marca)"
        WHEN @has_categoria > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, categoria)
             SELECT codigo_barras, nombre, precio, stock, categoria
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                categoria = VALUES(categoria)"
        WHEN @has_marca > 0 THEN
            "INSERT INTO productos (codigo_barras, nombre, precio, stock, marca)
             SELECT codigo_barras, nombre, precio, stock, marca
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock),
                marca = VALUES(marca)"
        ELSE
            "INSERT INTO productos (codigo_barras, nombre, precio, stock)
             SELECT codigo_barras, nombre, precio, stock
             FROM tmp_seed_200
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                precio = VALUES(precio),
                stock = VALUES(stock)"
    END;

PREPARE stmt_insert_productos FROM @sql_insert_productos;
EXECUTE stmt_insert_productos;
DEALLOCATE PREPARE stmt_insert_productos;

-- Si existe tabla de lotes, inserta lote semilla por producto (sin duplicar)
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
        CASE
            WHEN t.fecha_vencimiento < CURDATE() THEN 'vencido'
            WHEN t.stock <= 0 THEN 'agotado'
            ELSE 'activo'
        END
     FROM tmp_seed_200 t
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

-- Sincroniza stock principal con seed
UPDATE productos p
INNER JOIN tmp_seed_200 t ON t.codigo_barras = p.codigo_barras
SET p.stock = t.stock;

COMMIT;

-- Resumen final
SELECT COUNT(*) AS productos_seed_200
FROM productos
WHERE codigo_barras LIKE '779991%';

SELECT
    p.codigo_barras,
    p.nombre,
    p.precio,
    p.stock
FROM productos p
WHERE p.codigo_barras LIKE '779991%'
ORDER BY p.codigo_barras
LIMIT 20;
