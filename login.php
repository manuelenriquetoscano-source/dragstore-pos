<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: /dragstore-pos/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (attemptLogin($username, $password)) {
        header('Location: /dragstore-pos/index.php');
        exit;
    }
    $error = 'Usuario o contraseña inválidos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Drugstore POS</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(145deg, #eaf2fb 0%, #f7fbff 45%, #edf6f1 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #1f2d3d;
            padding: 16px;
            box-sizing: border-box;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 14px;
            box-shadow: 0 24px 40px -28px rgba(44, 62, 80, 0.5);
            padding: 22px;
        }
        h1 {
            margin: 0 0 18px;
            font-size: 24px;
        }
        .field {
            margin-bottom: 14px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            padding: 12px;
            cursor: pointer;
        }
        .error {
            margin-bottom: 12px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }
        .help {
            margin-top: 12px;
            font-size: 12px;
            color: #475569;
        }
    </style>
</head>
<body>
    <form class="card" method="POST" action="login.php">
        <h1>Acceso al sistema</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <div class="field">
            <label for="username">Usuario</label>
            <input id="username" type="text" name="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>
        </div>
        <button class="btn" type="submit">Ingresar</button>
        <div class="help">Configura credenciales en el archivo <code>.env</code>.</div>
    </form>
</body>
</html>
