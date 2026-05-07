<?php
/**
 * SISTEMA DE CONTROL DE CAJA DIARIA
 * Optimizado para Hosting compartido (Webempresa)
 */

// 1. CONFIGURACIÓN (Modificar estos datos en el hosting)
date_default_timezone_set('America/Argentina/Buenos_Aires');
define('DB_HOST', 'localhost');
define('DB_NAME', 'marketca_caja_diaria');
define('DB_USER', 'marketca_caja_diaria');
define('DB_PASS', 'aTBlg1vcpf!');
define('APP_USER', 'admin'); // Usuario para login
define('APP_PASS', 'aTBlg1vcpf!'); // Contraseña para login

session_start();

// 2. CONEXIÓN A BASE DE DATOS
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Sincronizar zona horaria con Argentina (-03:00)
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    $db_error = "Error de conexión: " . $e->getMessage();
}

// 3. LÓGICA DE AUTENTICACIÓN
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    if ($_POST['username'] === APP_USER && $_POST['password'] === APP_PASS) {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Usuario o contraseña incorrectos";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Bloqueo si no está logueado
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// 4. LÓGICA DE NEGOCIO (Solo si está logueado y no hay error de DB)
if ($is_logged_in && !isset($db_error)) {
    
    // a. Agregar movimiento
    if (isset($_POST['action']) && $_POST['action'] == 'add_movimiento') {
        $tipo = $_POST['tipo'];
        $concepto = htmlspecialchars($_POST['concepto']);
        $monto = floatval($_POST['monto']);
        $stmt = $pdo->prepare("INSERT INTO movimientos (tipo, concepto, monto) VALUES (?, ?, ?)");
        $stmt->execute([$tipo, $concepto, $monto]);
        header("Location: index.php?msg=added");
        exit;
    }

    // b. Eliminar movimiento
    if (isset($_GET['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM movimientos WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: index.php?msg=deleted");
        exit;
    }

    // c. Editar movimiento
    if (isset($_POST['action']) && $_POST['action'] == 'edit_movimiento') {
        $id = intval($_POST['id']);
        $tipo = $_POST['tipo'];
        $concepto = htmlspecialchars($_POST['concepto']);
        $monto = floatval($_POST['monto']);
        $fecha = $_POST['fecha']; // Formato datetime-local: YYYY-MM-DDTHH:MM
        
        $stmt = $pdo->prepare("UPDATE movimientos SET tipo = ?, concepto = ?, monto = ?, fecha = ? WHERE id = ?");
        $stmt->execute([$tipo, $concepto, $monto, str_replace('T', ' ', $fecha), $id]);
        header("Location: index.php?msg=updated");
        exit;
    }

    // b. Exportar a Excel (CSV)
    if (isset($_GET['export'])) {
        $filter_date = $_GET['filter_date'] ?? null;
        $where_sql = $filter_date ? "WHERE DATE(fecha) = :fdate" : "";
        $params = $filter_date ? [':fdate' => $filter_date] : [];

        // Calcular saldo inicial para el filtro
        $saldo_acumulado = 0;
        if ($filter_date) {
            $stmt_prev = $pdo->prepare("SELECT SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as previo FROM movimientos WHERE DATE(fecha) < :fdate");
            $stmt_prev->execute([':fdate' => $filter_date]);
            $res_prev = $stmt_prev->fetch();
            $saldo_acumulado = floatval($res_prev['previo'] ?? 0);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=caja_' . ($filter_date ?: date('Y-m-d')) . '.csv');
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Fecha', 'Concepto', 'Tipo', 'Monto', 'Saldo Acumulado']);
        
        // Exportamos en orden cronológico para que el saldo tenga sentido
        $stmt = $pdo->prepare("SELECT * FROM movimientos $where_sql ORDER BY fecha ASC");
        $stmt->execute($params);
        
        while ($row = $stmt->fetch()) {
            $monto_num = floatval($row['monto']);
            if ($row['tipo'] === 'ingreso') {
                $saldo_acumulado += $monto_num;
                $monto_str = "+" . number_format($monto_num, 2, ',', '.');
            } else {
                $saldo_acumulado -= $monto_num;
                $monto_str = "-" . number_format($monto_num, 2, ',', '.');
            }
            
            fputcsv($output, [
                $row['fecha'], 
                $row['concepto'], 
                ucfirst($row['tipo']), 
                $monto_str, 
                number_format($saldo_acumulado, 2, ',', '.')
            ]);
        }
        fclose($output);
        exit;
    }


    // e. Obtener historial y balance filtrado
    $filter_date = $_GET['filter_date'] ?? null;
    $where_sql = $filter_date ? "WHERE DATE(fecha) = :fdate" : "";
    $params = $filter_date ? [':fdate' => $filter_date] : [];

    // Dashboard con filtro
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as saldo_total
        FROM movimientos $where_sql");
    $stmt->execute($params);
    $stats = $stmt->fetch();
    $saldo_total = $stats['saldo_total'] ?? 0;

    // Historial con filtro
    $stmt = $pdo->prepare("SELECT * FROM movimientos $where_sql ORDER BY fecha DESC LIMIT 100");
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Diaria - Control Interno</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

    <?php if (!$is_logged_in): ?>
        <!-- LOGIN UI -->
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Caja Diaria</h1>
                    <p class="text-gray-500">Inicia sesión para continuar</p>
                </div>
                
                <?php if (isset($login_error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Usuario</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Contraseña</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition shadow-lg">
                        Entrar al Sistema
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- MAIN APP -->
        <nav class="bg-white shadow-sm border-b px-4 py-4 mb-8">
            <div class="max-w-4xl mx-auto flex justify-between items-center">
                <h1 class="text-xl font-bold text-blue-600">Caja Diaria</h1>
                <div class="flex items-center gap-4">
                    <a href="?logout=1" class="text-sm font-medium text-red-500 hover:text-red-700">Cerrar Sesión</a>
                </div>
            </div>
        </nav>

        <main class="max-w-4xl mx-auto px-4 pb-12">
            
            <?php if (isset($db_error)): ?>
                <div class="bg-red-500 text-white p-6 rounded-2xl shadow-lg mb-8">
                    <h2 class="font-bold text-lg mb-2">Error de Configuración</h2>
                    <p><?php echo $db_error; ?></p>
                    <p class="text-sm mt-4 opacity-75 italic text-white/80">Revisa los datos de conexión al principio de index.php</p>
                </div>
            <?php else: ?>

                <!-- DASHBOARD -->
                <div class="flex flex-col md:flex-row gap-4 mb-8">
                    <div class="flex-1 bg-white rounded-3xl shadow-sm border p-6 flex flex-col items-center justify-center text-center">
                        <span class="text-gray-500 uppercase tracking-wider text-xs font-bold mb-2">Saldo Total Actual</span>
                        <div class="text-5xl font-extrabold <?php echo $saldo_total >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            $<?php echo number_format($saldo_total, 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="flex items-center justify-center">
                        <button onclick="window.location.href='index.php'" class="w-full md:w-auto bg-blue-50 hover:bg-blue-100 text-blue-600 p-8 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-md flex flex-col items-center gap-2 group border border-blue-100/50">
                            <div class="bg-white p-3 rounded-2xl shadow-sm group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 group-active:rotate-[360deg] transition-transform duration-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold uppercase tracking-widest mt-1">Actualizar Datos</span>
                        </button>
                    </div>
                </div>

                <!-- FORMULARIO -->
                <div class="bg-white rounded-3xl shadow-sm border p-6 mb-8">
                    <h2 class="text-lg font-bold mb-4">Registrar Movimiento</h2>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="action" value="add_movimiento">
                        <div>
                            <select name="tipo" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                                <option value="ingreso">Ingreso (+)</option>
                                <option value="egreso">Egreso (-)</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <input type="text" name="concepto" placeholder="Concepto" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="monto" placeholder="Monto" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-700 transition">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TABLA HISTORIAL -->
                <div class="bg-white rounded-3xl shadow-sm border overflow-hidden">
                    <div class="p-6 border-b flex flex-col md:flex-row justify-between items-center gap-4">
                        <h2 class="text-lg font-bold text-gray-800">Operaciones</h2>
                        
                        <!-- FILTRO POR DÍA -->
                        <form method="GET" class="flex items-center gap-2">
                            <input type="date" name="filter_date" value="<?php echo $filter_date; ?>" 
                                   class="px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-gray-50">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition">
                                Filtrar
                            </button>
                            <?php if ($filter_date): ?>
                                <a href="index.php" class="text-xs text-red-500 font-bold hover:underline">Limpiar</a>
                            <?php endif; ?>
                            <a href="?export=1<?php echo $filter_date ? '&filter_date=' . $filter_date : ''; ?>" 
                               class="text-sm bg-green-100 text-green-700 px-4 py-2 rounded-xl font-bold hover:bg-green-200 transition">
                                Excel
                            </a>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 text-left">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Concepto</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Monto</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($movimientos)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-400">No hay movimientos registrados</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo date('d/m H:i', strtotime($mov['fecha'])); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-medium">
                                                <?php echo $mov['concepto']; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-right font-bold <?php echo $mov['tipo'] == 'ingreso' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo ($mov['tipo'] == 'ingreso' ? '+' : '-') . ' $' . number_format($mov['monto'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center">
                                                <div class="flex justify-center gap-2">
                                                    <button onclick='openEditModal(<?php echo json_encode($mov); ?>)' class="text-blue-500 hover:text-blue-700 p-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                    </button>
                                                    <button onclick="showConfirm('Eliminar Movimiento', '¿Estás seguro de que deseas borrar este registro de caja? Esta acción no se puede deshacer.', () => window.location.href='?delete=<?php echo $mov['id']; ?>')" class="text-red-500 hover:text-red-700 p-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        </main>
    <?php endif; ?>

    <!-- MODAL DE EDICIÓN -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl scale-up-animation">
            <h2 class="text-2xl font-bold mb-6">Editar Movimiento</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_movimiento">
                <input type="hidden" name="id" id="edit_id">
                <div>
                    <label class="block text-sm font-semibold mb-2">Fecha y Hora</label>
                    <input type="datetime-local" name="fecha" id="edit_fecha" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Tipo</label>
                    <select name="tipo" id="edit_tipo" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                        <option value="ingreso">Ingreso (+)</option>
                        <option value="egreso">Egreso (-)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Concepto</label>
                    <input type="text" name="concepto" id="edit_concepto" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Monto</label>
                    <input type="number" step="0.01" name="monto" id="edit_monto" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN -->
    <div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4 z-[60]">
        <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full shadow-2xl scale-up-animation border border-gray-100">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 id="confirm_title" class="text-xl font-bold mb-2 text-center text-gray-800">Confirmar Acción</h2>
            <p id="confirm_message" class="text-gray-500 text-center mb-8 text-sm leading-relaxed"></p>
            <div class="flex flex-col gap-2">
                <button onclick="executeConfirm()" class="w-full bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold hover:bg-blue-700 transition order-2 md:order-1">
                    Confirmar
                </button>
                <button onclick="closeConfirm()" class="w-full bg-gray-50 text-gray-500 px-6 py-3 rounded-2xl font-bold hover:bg-gray-100 transition order-1 md:order-2">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(mov) {
            document.getElementById('edit_id').value = mov.id;
            document.getElementById('edit_tipo').value = mov.tipo;
            document.getElementById('edit_concepto').value = mov.concepto;
            document.getElementById('edit_monto').value = mov.monto;
            
            // Formatear fecha de DB (YYYY-MM-DD HH:MM:SS) a datetime-local (YYYY-MM-DDTHH:MM)
            const date = new Date(mov.fecha.replace(' ', 'T'));
            const formattedDate = date.getFullYear() + '-' + 
                String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                String(date.getDate()).padStart(2, '0') + 'T' + 
                String(date.getHours()).padStart(2, '0') + ':' + 
                String(date.getMinutes()).padStart(2, '0');
            
            document.getElementById('edit_fecha').value = formattedDate;
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }


        // SISTEMA DE CONFIRMACIÓN PERSONALIZADO
        let confirmCallback = null;
        function showConfirm(title, message, onConfirm) {
            document.getElementById('confirm_title').innerText = title;
            document.getElementById('confirm_message').innerText = message;
            confirmCallback = onConfirm;
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmModal').classList.add('flex');
        }

        function closeConfirm() {
            document.getElementById('confirmModal').classList.add('hidden');
            document.getElementById('confirmModal').classList.remove('flex');
        }

        function executeConfirm() {
            if (confirmCallback) confirmCallback();
            closeConfirm();
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const confirmModal = document.getElementById('confirmModal');
            if (event.target == editModal) closeEditModal();
            if (event.target == confirmModal) closeConfirm();
        }
    </script>

    <style>
        @keyframes scaleUp {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .scale-up-animation {
            animation: scaleUp 0.15s ease-out;
        }
    </style>
</body>
</html>
