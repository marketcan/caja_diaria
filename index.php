<?php
// Seguridad de Cookies de Sesión
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();

// Inicializar Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. CARGAR CONFIGURACIÓN DESDE LA CARPETA DE CAJA DIARIA
$config_path = __DIR__ . '/caja/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("Error: No se encontró el archivo de configuración en caja/config.php.");
}

// 2. LÓGICA DE AUTENTICACIÓN
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $login_error = "Token de seguridad inválido. Recarga la página.";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === APP_USER && $password === APP_PASS) {
            session_regenerate_id(true); // Prevenir fijación de sesión
            $_SESSION['logged_in'] = true;
            header("Location: index.php");
            exit;
        } else {
            $login_error = "Usuario o contraseña incorrectos";
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>marketCan | Panel de Utilitarios</title>
    <meta name="description" content="Portal central de utilitarios para marketCan: Logística y Caja Diaria. Acceso rápido a herramientas administrativas.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐾</text></svg>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css?v=1.1">

    <?php if ($is_logged_in): ?>
    <style>
        /* Sidebar: menú scrollable con logout siempre visible */
        #sidebar-nav {
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            height: 100vh !important;
        }
        #sidebar-nav .logo-container {
            flex-shrink: 0;
        }
        #sidebar-nav .sidebar-nav-inner {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            min-height: 0 !important;
        }
        #sidebar-nav .nav-menu-main {
            flex: 1 !important;
            overflow-y: auto !important;
            min-height: 0 !important;
            padding: 1rem 1.25rem !important;
        }
        #sidebar-nav .nav-menu-logout {
            flex-shrink: 0 !important;
            padding: 0.75rem 1.25rem !important;
            border-top: 1px solid #e5e7eb !important;
        }
    </style>
    <?php endif; ?>
    <?php if (!$is_logged_in): ?>
    <style>
        /* Estilos Premium para la pantalla de Login */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #e0f2fe 0%, #f1f5f9 100%);
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            text-align: center;
            transition: all 0.3s ease;
        }
        .login-logo {
            margin-bottom: 2rem;
            display: inline-block;
            background: var(--primary-blue, #0071BC);
            padding: 1.5rem;
            border-radius: 20px;
            color: white;
            font-size: 2.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 113, 188, 0.3);
        }
        .login-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .form-group {
            text-align: left;
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 1rem;
            color: #1e293b;
            background-color: white;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            border-color: var(--primary-blue, #0071BC);
            box-shadow: 0 0 0 3px rgba(0, 113, 188, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary-blue, #0071BC);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 113, 188, 0.2);
            margin-top: 1rem;
        }
        .btn-submit:hover {
            background-color: var(--secondary-blue, #005a96);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 113, 188, 0.3);
        }
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-align: left;
        }
    </style>
    <?php endif; ?>
</head>
<body>

    <?php if (!$is_logged_in): ?>
        <!-- PANTALLA DE LOGIN -->
        <div class="login-card">
            <div class="login-logo">
                <i class="fas fa-paw"></i>
            </div>
            <div class="login-header">
                <h1>Panel de Utilitarios</h1>
                <p>Ingresa tus credenciales administrativas</p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($login_error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Iniciar Sesión</button>
            </form>
        </div>
    <?php else: ?>
        <!-- PANEL DE ACCESO AUTENTICADO -->
        <!-- Menú Lateral -->
        <aside id="sidebar-nav">
            <div class="logo-container">
                <img src="images/logo.jpg" alt="marketCan" class="logo-img">
            </div>

            <nav class="sidebar-nav-inner">
                <ul class="nav-menu nav-menu-main">
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link active" id="btn-inicio" onclick="showView('home')">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link" id="btn-logistica" onclick="showView('logistica/index.html')">
                            <i class="fas fa-shipping-fast"></i>
                            <span>Logística</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link" id="btn-caja" onclick="showView('caja/index.php')">
                            <i class="fas fa-cash-register"></i>
                            <span>Caja Diaria</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://drive.google.com/drive/folders/10XOKzdgHBIe1GMVEEBWk19gu1OgO9C7Y" class="nav-link" id="btn-drive" target="_blank">
                            <i class="fab fa-google-drive"></i>
                            <span>Google Drive</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://drive.google.com/drive/folders/1YGdvvmzlv4b7nR-pbfYh31b4zbq3YMD2" class="nav-link" id="btn-precios-m" target="_blank">
                            <i class="fas fa-list-alt"></i>
                            <span>Precios Mayorista</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://drive.google.com/drive/folders/1sQC7K2Fv86YNlfInp-7jfz2SdAGC0k_o" class="nav-link" id="btn-precios-p" target="_blank">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Precios Proveedores</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://drive.google.com/drive/folders/1c69iIpahP4JY8rIds9sdVk7_LXEC8Nwj" class="nav-link" id="btn-royal" target="_blank">
                            <i class="fas fa-dog"></i>
                            <span>Pedidos Royal Canin</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="https://animalshop.ennube.ar/lista/mayor/" class="nav-link" id="btn-animalshop" target="_blank">
                            <i class="fas fa-store"></i>
                            <span>Animalshop</span>
                        </a>
                    </li>
                </ul>

                <ul class="nav-menu nav-menu-logout">
                    <li class="nav-item" style="margin-bottom: 0; margin-top: 0.75rem;">
                        <a href="?logout=1" class="nav-link" style="color: #ef4444;" id="btn-logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Contenido Principal -->
        <main id="main-content">
            <!-- Vista Home -->
            <div id="view-home" class="view-container">
                <header>
                    <h1>Bienvenido</h1>
                    <p class="subtitle">Panel central de herramientas para el equipo de marketCan.</p>
                </header>

                <!-- Hero Section -->
                <section class="hero-card">
                    <img src="images/hero.jpg" alt="marketCan Hero" class="hero-image">
                    <div class="hero-overlay-minimal"></div>
                </section>
            </div>

            <!-- Vista App (Iframe) -->
            <div id="view-app" class="view-container" style="display: none;">
                <iframe id="main-frame" src="" frameborder="0"></iframe>
            </div>
        </main>

        <script>
            function showView(target) {
                const homeView = document.getElementById('view-home');
                const appView = document.getElementById('view-app');
                const mainFrame = document.getElementById('main-frame');
                const navLinks = document.querySelectorAll('.nav-menu .nav-link');

                // Reset active links
                navLinks.forEach(link => link.classList.remove('active'));

                if (target === 'home') {
                    homeView.style.display = 'block';
                    appView.style.display = 'none';
                    mainFrame.src = '';
                    document.getElementById('btn-inicio').classList.add('active');
                } else {
                    homeView.style.display = 'none';
                    appView.style.display = 'block';
                    mainFrame.src = target;
                    
                    // Find and highlight the clicked link based on its onclick attribute
                    navLinks.forEach(link => {
                        const clickAttr = link.getAttribute('onclick');
                        if (clickAttr && clickAttr.includes(target)) {
                            link.classList.add('active');
                        }
                    });
                }
            }
        </script>
    <?php endif; ?>

</body>
</html>
