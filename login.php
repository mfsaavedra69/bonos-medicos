<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/db.php';

// Autor: Marcelo Saavedra  
// Fecha: 2025-04-23    
// Descripción: Página principal del sistema de inventario. Muestra estadísticas y un menú de navegación.
// Versión: 1.0
//*******************************
// Si ya está logueado, redirigir al index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($usuario) || empty($password)) {
            $error = 'Por favor ingrese usuario y contraseña';
        } else {
            // Verificar conexión a la base de datos
            if ($conn->connect_error) {
                throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error);
            }

            $sql = "SELECT id, nombre, usuario, password, primer_ingreso FROM usuarios WHERE usuario = ? AND activo = TRUE LIMIT 1";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conn->error);
            }

            $stmt->bind_param("s", $usuario);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                $stored = $user['password'];
                $authenticated = false;

                // Verificación normal con hash (password_hash)
                if (password_verify($password, $stored)) {
                    $authenticated = true;
                } else {
                    // Detectar si la contraseña almacenada NO parece ser un hash bcrypt
                    // (bcrypt típico: $2y$ + 56 chars). Esto cubre la migración desde contraseñas en claro.
                    if (!preg_match('/^\\$2[ayb]\\$.{56}$/', $stored)) {
                        // Comparar en claro de forma segura
                        if (function_exists('hash_equals') && hash_equals($stored, $password) || $stored === $password) {
                            $authenticated = true;
                            // Re-hash y actualizar la base de datos para migrar a contraseñas seguras
                            try {
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                $upd = $conn->prepare('UPDATE usuarios SET password = ?, updated_at = NOW() WHERE id = ?');
                                if ($upd) {
                                    $upd->bind_param('si', $newHash, $user['id']);
                                    $upd->execute();
                                    error_log("Usuario id={$user['id']} - contraseña migrada a hash");
                                }
                            } catch (Exception $e) {
                                error_log('Error al migrar password: ' . $e->getMessage());
                            }
                        }
                    }
                }

                if ($authenticated) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['primer_ingreso'] = $user['primer_ingreso'];

                    // Si es primer ingreso, redirigir a cambiar contraseña
                    if ($user['primer_ingreso']) {
                        header('Location: cambiar_password.php');
                        exit;
                    }

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Bonos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        .alert-danger {
            background-color: transparent !important;
            border: 1px solid #00AEEF; /* Borde celeste */
            color: red;
            padding: 10px;
        }       
        body {
            background: linear-gradient(45deg, #1a237e, #0d47a1);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(13, 71, 161, 0.95);
            border-radius: 20px;
            overflow: hidden;
            width: 80%;
            max-width: 1000px;
            min-height: 500px;
            display: flex;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .login-form-section {
            width: 40%;
            padding: 40px;
            background: rgba(25, 118, 210, 0.98);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .welcome-section {
            width: 60%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 800 800"><path d="M0,400Q200,600,400,400Q600,200,800,400" fill="none" stroke="%231976d2" stroke-width="2"><animate attributeName="d" dur="5s" repeatCount="indefinite" values="M0,400Q200,600,400,400Q600,200,800,400;M0,400Q200,200,400,400Q600,600,800,400;M0,400Q200,600,400,400Q600,200,800,400"/></path></svg>') center/cover;
            opacity: 0.1;
            animation: wave 15s linear infinite;
        }
        .logo-container {
            width: 120px;
            height: 120px;
            margin-bottom: 30px;
            border-radius: 50%;
            padding: 10px;
            background: transparent;
        }
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .form-control {
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 25px;
            color: white;
            padding: 12px 25px;
            margin-bottom: 15px;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.15);
            color: white;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.2);
        }
        .form-control::placeholder {
            color: rgba(255,255,255,0.6);
        }
        .btn-login {
            background: linear-gradient(45deg, #00b0ff, #0091ea);
            border: none;
            border-radius: 25px;
            padding: 12px 35px;
            color: white;
            font-weight: bold;
            width: 100%;
            margin-top: 10px;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(45deg, #0091ea, #00b0ff);
        }
        .welcome-text {
            color: white;
            position: relative;
            z-index: 1;
        }
        .welcome-text h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        .welcome-text p {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            line-height: 1.6;
        }
        @keyframes wave {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .alert {
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 20px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form-section">
            <div class="logo-container">
                <img src="img/atencion-medica.png" alt="UOM Logo">
            </div>
            <form method="POST" class="w-100">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <input type="text" class="form-control" name="usuario" placeholder="Usuario" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn btn-login">Ingresar</button>
            </form>
        </div>
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Bienvenido.</h1>
                <p>Sistema de Gestión de Bonos<br>Uso Exclusivo de MarceLito®</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 