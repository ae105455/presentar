<?php
require_once __DIR__ . '/db_connection.php';

session_start();


function sanitizar($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

if(isset($_SESSION['usuario_id'])) {
    header("Location: panel.php");
    exit();
}

$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = isset($_POST['usuario']) ? sanitizar($_POST['usuario']) : '';
    $clave = isset($_POST['clave']) ? sanitizar($_POST['clave']) : '';
    
    if(empty($usuario) || empty($clave)) {
        $error = "Usuario y contraseña son obligatorios.";
    } else {
        $conn = aureamed_db_connect();
        if ($conn->connect_error) {
            $error = "Error de conexión con la base de datos.";
        } else {
            $stmt = $conn->prepare("SELECT id_usuario, usuario, nombre, password, rol FROM usuarios WHERE usuario = ? AND estado = 'activo'");
            if($stmt) {
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if($resultado->num_rows == 1) {
                    $usuario_data = $resultado->fetch_assoc();
                    if(md5($clave) == $usuario_data['password']) {
                        $_SESSION['usuario_id'] = $usuario_data['id_usuario'];
                        $_SESSION['usuario'] = $usuario_data['usuario'];
                        $_SESSION['nombre'] = $usuario_data['nombre'];
                        $_SESSION['rol'] = $usuario_data['rol'];
                        $stmt->close();
                        $conn->close();
                        header("Location: panel.php");
                        exit();
                    } else {
                        $error = "Contraseña incorrecta.";
                    }
                } else {
                    $error = "Usuario no encontrado o inactivo.";
                }
                $stmt->close();
            } else {
                $error = "Error al procesar la solicitud.";
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Iniciar Sesión</title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">

    <style>
        :root {
            --bg-soft: #efefef;
            --panel-bg: rgba(255, 255, 255, 0.7);
            --line: #d8d5d1;
            --text: #242424;
            --muted: #666666;
            --accent: #d46d42;
            --accent-dark: #b8582e;
            --black: #1d1d1d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--bg-soft);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "MED";
            position: absolute;
            right: 10%;
            bottom: 18%;
            font-size: clamp(65px, 9vw, 170px);
            font-weight: 800;
            letter-spacing: -0.06em;
            color: rgba(191, 181, 173, 0.22);
            pointer-events: none;
            user-select: none;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.52);
            border: 1px solid rgba(255,255,255,0.65);
            border-radius: 28px;
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 520px;
            padding: 24px 38px 30px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            margin: 0 0 18px;
            flex-wrap: nowrap;
            width: 100%;
        }

        .logo-box {
            position: relative;
            width: 150px;
            height: 150px;
            flex-shrink: 0;
            transform: rotate(-15deg);
            margin-left: -10px;
        }

        .logo-box::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #f9ac3d 0%, #f39b2d 100%);
            clip-path: polygon(50% 15%, 78% 22%, 100% 50%, 78% 78%, 62% 100%, 50% 100%, 38% 100%, 22% 78%, 0 50%, 22% 22%, 38% 0%, 50% 0%);
            border-radius: 8px;
        }

        .logo-box::after {
            content: "";
            position: absolute;
            inset: 24px 22px 22px 22px;
            background: rgba(255,255,255,0.82);
            clip-path: polygon(50% 20%, 75% 28%, 90% 50%, 75% 72%, 50% 80%, 25% 72%, 10% 50%, 25% 28%);
            transform: rotate(8deg);
        }

        .brand-name {
            font-size: clamp(3.5rem, 6vw, 7.2rem);
            line-height: 0.82;
            letter-spacing: -0.09em;
            color: #7d2a2a;
            font-weight: 800;
            display: inline-flex;
            align-items: flex-end;
            gap: 0.02em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            white-space: nowrap;
        }

        .brand-med {
            font-size: 0.36em;
            font-weight: 700;
            letter-spacing: -0.06em;
            margin-bottom: 0.18em;
            color: #7d2a2a;
        }

        h1 {
            display: none;
        }

        .subtitulo {
            font-size: 18px;
            color: #5a5a5a !important;
            margin-bottom: 22px;
            font-weight: 400;
        }

        form {
            margin-top: 12px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            font-size: 18px;
            color: #4a4a4a !important;
            margin-bottom: 12px;
            display: block;
            font-weight: 400;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrap i {
            position: absolute;
            left: 12px;
            color: #8d8d8d;
            font-size: 18px;
            z-index: 1;
        }

        .form-control {
            width: 100%;
            height: 48px;
            padding: 12px 14px 12px 40px;
            border: none;
            border-bottom: 2px solid var(--line);
            border-radius: 0;
            font-size: 16px;
            color: var(--text);
            background: transparent;
            transition: all 0.25s ease;
            box-shadow: none;
        }

        .form-control:focus {
            outline: none;
            border-bottom-color: #7b7b7b;
            box-shadow: none;
            background: transparent;
        }

        .form-control::placeholder {
            color: transparent;
        }

        .extra-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 6px 0 28px;
            gap: 12px;
            font-size: 16px;
            color: #4b4b4b;
        }

        .remember-box {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .remember-box input {
            width: 18px;
            height: 18px;
            accent-color: #222;
        }

        .forgot-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            color: var(--accent-dark);
            text-decoration: none;
        }

        .btn-login-submit {
            background: var(--black);
            color: #ffffff !important;
            width: 100%;
            padding: 18px 12px;
            border: none;
            border-radius: 14px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login-submit:hover {
            background: #000000;
            transform: translateY(-1px);
            color: #ffffff;
        }

        .register-text {
            margin-top: 22px;
            font-size: 16px;
            color: #4d4d4d;
            text-align: center;
        }

        .register-text a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .register-text a:hover {
            color: var(--accent-dark);
            text-decoration: none;
        }

        .alert-error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            color: #991b1b;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer {
            margin-top: 22px;
            font-size: 12px;
            color: rgba(90, 90, 90, 0.75) !important;
            border-top: 1px solid rgba(0,0,0,0.07);
            padding-top: 14px;
            display: none;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 24px 22px 28px;
            }

            .logo-box {
                width: 120px;
                height: 120px;
            }

            .extra-row {
                font-size: 14px;
            }

            .btn-login-submit {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-wrap">
        <div class="logo-box" aria-label="Logo AureaMed"></div>
        <div class="brand-name" aria-label="AUREA MED">
            <span>AUREA</span>
            <span class="brand-med">MED</span>
        </div>
    </div>

    <div class="subtitulo">Panel de Control</div>
    
    <?php if($error != ''): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="form-group">
            <label for="usuario">Usuario</label>
            <div class="input-wrap">
                <i class="fas fa-user"></i>
                <input type="text" id="usuario" class="form-control" name="usuario" placeholder="Usuario" required autocomplete="username" autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="clave">Contraseña</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" id="clave" class="form-control" name="clave" placeholder="Contraseña" required autocomplete="current-password">
            </div>
        </div>

        <div class="extra-row">
            <label class="remember-box">
                <input type="checkbox" name="remember">
                <span>Recordarme</span>
            </label>
            <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn-login-submit">
            Iniciar sesión
        </button>
    </form>

    <div class="register-text">
        ¿No tienes cuenta? <a href="../aurea--main/docs/index.php">Contáctanos</a>
    </div>

    <div class="login-footer">
        Centro Auditivo AureaMed &bull; Sistema Seguro
    </div>
</div>

<script src="./js/jquery-3.4.1.min.js"></script>
<script src="./js/popper.min.js"></script>
<script src="./js/bootstrap.min.js"></script>
<script src="./js/sweetalert2.min.js"></script>
<?php if($error != ''): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Acceso Denegado',
        text: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#0056b3'
    });
</script>
<?php endif; ?>
</body>
</html>