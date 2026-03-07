<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin(['admin']);
require_once __DIR__ . '/../../controllers/PromocionController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new PromocionController($db);
$tableOk = $controller->tableExists();

$flash = '';
$flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableOk) {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'save') {
        $result = $controller->guardar($_POST);
        $flash = (string)$result['message'];
        $flashType = !empty($result['ok']) ? 'ok' : 'err';
    } elseif ($action === 'toggle') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $activo = isset($_POST['activo']) && (string)$_POST['activo'] === '1';
        $ok = $controller->setActivo($id, !$activo);
        $flash = $ok ? 'Estado actualizado.' : 'No se pudo actualizar estado.';
        $flashType = $ok ? 'ok' : 'err';
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $ok = $controller->eliminar($id);
        $flash = $ok ? 'Promocion eliminada.' : 'No se pudo eliminar.';
        $flashType = $ok ? 'ok' : 'err';
    }
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editPromo = ($tableOk && $editId > 0) ? $controller->obtenerPorId($editId) : null;
$promos = $tableOk ? $controller->listarTodas() : [];

function promoField($promo, string $key, $default = '')
{
    if (!$promo || !array_key_exists($key, $promo)) {
        return $default;
    }
    return $promo[$key];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Promociones - Drugstore POS</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #eef5fb; color: #1f2d3d; padding: 14px; }
        .wrap { max-width: 1220px; margin: 0 auto; background: rgba(255,255,255,0.84); border: 1px solid rgba(255,255,255,0.62); border-radius: 14px; padding: 14px; box-shadow: 0 24px 44px -30px rgba(44, 62, 80, 0.5); }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        h1 { margin: 0; font-size: 24px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { border: 1px solid rgba(255,255,255,0.45); background: rgba(44, 62, 80, 0.92); color: #fff; text-decoration: none; border-radius: 10px; padding: 9px 13px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .flash { padding: 10px; border-radius: 8px; margin-bottom: 10px; font-size: 13px; font-weight: 700; }
        .flash.ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .flash.err { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .card { background: rgba(255,255,255,0.58); border: 1px solid rgba(148,163,184,0.25); border-radius: 10px; padding: 10px; margin-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 8px; }
        .form-grid label { font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 3px; display: block; }
        .form-grid input, .form-grid select, .form-grid textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 10px; font-size: 13px; background: rgba(255,255,255,0.88); font-family: inherit; }
        .help { font-size: 11px; color: #64748b; }
        .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid rgba(148,163,184,0.35); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid rgba(148,163,184,0.25); text-align: left; font-size: 13px; white-space: nowrap; vertical-align: top; }
        th { background: rgba(51, 65, 85, 0.92); color: #fff; }
        .badge { display: inline-block; border-radius: 999px; padding: 3px 8px; font-size: 11px; font-weight: 800; }
        .b-on { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .b-off { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .row-actions { display: flex; gap: 6px; }
        .mini { font-size: 11px; padding: 6px 8px; border-radius: 7px; }
        @media (min-width: 980px) {
            body { padding: 24px; }
            .wrap { padding: 18px; }
            .form-grid { grid-template-columns: 1fr 1fr 1fr; }
            .span2 { grid-column: span 2; }
            .span3 { grid-column: span 3; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>Administracion de Promociones</h1>
        <div class="actions">
            <a class="btn" href="/dragstore-pos/views/ventas/caja.php">Caja</a>
            <a class="btn" href="/dragstore-pos/index.php">Menu</a>
        </div>
    </div>

    <?php if (!$tableOk): ?>
        <div class="flash err">Debe ejecutar migraciones para habilitar promociones en BD (`php migrate.php`).</div>
    <?php endif; ?>
    <?php if ($flash !== ''): ?>
        <div class="flash <?php echo $flashType === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card">
        <strong><?php echo $editPromo ? 'Editar promocion #' . (int)$editPromo['id'] : 'Nueva promocion'; ?></strong>
        <form method="POST" action="promociones.php<?php echo $editPromo ? ('?edit=' . (int)$editPromo['id']) : ''; ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo (int)promoField($editPromo, 'id', 0); ?>">
            <div class="form-grid" style="margin-top:8px;">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="nombre" required value="<?php echo htmlspecialchars((string)promoField($editPromo, 'nombre', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Tipo</label>
                    <select name="tipo" required>
                        <?php $tipo = (string)promoField($editPromo, 'tipo', '2x1'); ?>
                        <option value="2x1" <?php echo $tipo === '2x1' ? 'selected' : ''; ?>>2x1</option>
                        <option value="percent" <?php echo $tipo === 'percent' ? 'selected' : ''; ?>>Descuento %</option>
                        <option value="combo" <?php echo $tipo === 'combo' ? 'selected' : ''; ?>>Combo</option>
                    </select>
                </div>
                <div>
                    <label>Prioridad</label>
                    <input type="number" name="prioridad" value="<?php echo (int)promoField($editPromo, 'prioridad', 100); ?>">
                </div>
                <div>
                    <label>Percent (solo tipo percent)</label>
                    <input type="number" step="0.01" min="0" max="100" name="percent_value" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'percent_value', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Min qty (solo tipo percent)</label>
                    <input type="number" min="1" name="min_qty" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'min_qty', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Combo price (solo tipo combo)</label>
                    <input type="number" step="0.01" min="0" name="combo_price" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'combo_price', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="span3">
                    <label>product_ids (JSON o lista: 1,2,3)</label>
                    <textarea name="product_ids" rows="2"><?php echo htmlspecialchars(json_encode((array)promoField($editPromo, 'product_ids', []), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="span3">
                    <label>codigos_barras (JSON o lista: 779...,779...)</label>
                    <textarea name="codigos_barras" rows="2"><?php echo htmlspecialchars(json_encode((array)promoField($editPromo, 'codigos_barras', []), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="span3">
                    <label>required_items (JSON solo combo)</label>
                    <textarea name="required_items" rows="3"><?php echo htmlspecialchars(json_encode((array)promoField($editPromo, 'required_items', []), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div class="help">Ej: [{"codigo_barras":"779991000004","qty":1},{"codigo_barras":"779991000005","qty":1}]</div>
                </div>

                <div>
                    <label>Dias semana (JSON: [0..6], 0=domingo)</label>
                    <input type="text" name="dias_semana" value="<?php echo htmlspecialchars(json_encode((array)promoField($editPromo, 'dias_semana', []), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Hora desde (HH:MM)</label>
                    <input type="time" name="hora_desde" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'hora_desde', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Hora hasta (HH:MM)</label>
                    <input type="time" name="hora_hasta" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'hora_hasta', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Vigencia desde</label>
                    <input type="date" name="vigencia_desde" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'vigencia_desde', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Vigencia hasta</label>
                    <input type="date" name="vigencia_hasta" value="<?php echo htmlspecialchars((string)promoField($editPromo, 'vigencia_hasta', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Activo</label>
                    <input type="checkbox" name="activo" value="1" <?php echo promoField($editPromo, 'activo', true) ? 'checked' : ''; ?>>
                </div>
            </div>
            <div class="actions" style="margin-top:8px;">
                <button class="btn" type="submit">Guardar</button>
                <a class="btn" href="promociones.php">Nueva</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Prioridad</th>
                <th>Horario/Vigencia</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($promos)): ?>
                <tr><td colspan="7">Sin promociones registradas.</td></tr>
            <?php else: ?>
                <?php foreach ($promos as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$p['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$p['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$p['prioridad']; ?></td>
                        <td>
                            Dias: <?php echo htmlspecialchars(json_encode((array)$p['dias_semana']), ENT_QUOTES, 'UTF-8'); ?><br>
                            Hora: <?php echo htmlspecialchars((string)($p['hora_desde'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string)($p['hora_hasta'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?><br>
                            Vig: <?php echo htmlspecialchars((string)($p['vigencia_desde'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string)($p['vigencia_hasta'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><span class="badge <?php echo !empty($p['activo']) ? 'b-on' : 'b-off'; ?>"><?php echo !empty($p['activo']) ? 'Activa' : 'Inactiva'; ?></span></td>
                        <td>
                            <div class="row-actions">
                                <a class="btn mini" href="promociones.php?edit=<?php echo (int)$p['id']; ?>">Editar</a>
                                <form method="POST" action="promociones.php" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="activo" value="<?php echo !empty($p['activo']) ? '1' : '0'; ?>">
                                    <button class="btn mini" type="submit"><?php echo !empty($p['activo']) ? 'Desactivar' : 'Activar'; ?></button>
                                </form>
                                <form method="POST" action="promociones.php" style="margin:0;" onsubmit="return confirm('Eliminar promocion?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <button class="btn mini" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
