<?php
require_once "conexion.php";

// Consulta para obtener resumen de estatus
$sqlEstatus = "SELECT c.nombre_estatus, COUNT(e.id_equipo) as total 
               FROM cat_estatus c 
               LEFT JOIN equipos e ON c.id_estatus = e.id_estatus 
               GROUP BY c.id_estatus, c.nombre_estatus";
$resEstatus = $conn->query($sqlEstatus);

// Consulta general de equipos (adaptada a los nuevos campos de la base de datos)
$sqlEquipos = "SELECT e.id_equipo, e.numero_serie, e.modelo, e.compania, e.fecha_equipos, e.revisado, s.nombre_estatus, e.observaciones_tecnicas, e.fecha_ultima_modificacion 
               FROM equipos e
               LEFT JOIN cat_estatus s ON e.id_estatus = s.id_estatus
               ORDER BY e.fecha_ultima_modificacion DESC";
$resEquipos = $conn->query($sqlEquipos);

// Consulta para obtener la Bitácora de Entradas y Salidas (Libreta Digital)
$sqlBitacora = "SELECT b.*, u.nombre as nombre_tecnico 
                 FROM bitacora_movimientos b
                 LEFT JOIN usuarios u ON b.id_usuario_tecnico = u.id_usuario
                 ORDER BY b.fecha_movimiento DESC LIMIT 15";
$resBitacora = $conn->query($sqlBitacora);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Montosa - Control de Taller</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Estilos Personalizados: Colores Corporativos Montosa (Rojo y Blanco) -->
    <style>
        :root {
            --montosa-rojo: #C8102E;
            --montosa-rojo-oscuro: #9B0920;
            --montosa-gris-fondo: #F8F9FA;
        }

        body { 
            background-color: var(--montosa-gris-fondo); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar Rojo Institucional */
        .navbar-montosa { 
            background-color: var(--montosa-rojo); 
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        /* Tarjetas de Resumen */
        .card-counter { 
            border-radius: 8px; 
            border: none; 
            border-top: 4px solid var(--montosa-rojo);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-counter:hover {
            transform: translateY(-3px);
        }

        /* Botones y Encabezados */
        .btn-montosa-danger {
            background-color: var(--montosa-rojo);
            color: white;
            border: none;
        }
        .btn-montosa-danger:hover {
            background-color: var(--montosa-rojo-oscuro);
            color: white;
        }

        .table-header-montosa {
            background-color: #212529;
            color: white;
        }

        .text-montosa {
            color: var(--montosa-rojo);
        }
    </style>
</head>
<body>

    <!-- Navegación Superior con Logo Oficial -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-montosa mb-4 px-4 py-2">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center bg-white px-3 py-1 rounded-3 shadow-sm" href="#">
                <img src="img/logo_montosa.png" alt="Montosa Logo" height="38" class="me-2" onerror="this.style.display='none'">
                <span class="text-dark fw-bold border-start border-2 border-danger ps-2 ms-1" style="font-size: 0.85rem;">
                    TALLER DE REFRIGERACIÓN
                </span>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3 d-none d-md-inline fw-semibold">
                    <i class="bi bi-person-badge-fill me-1"></i> Jefe Administrativo Servir
                </span>
                <a href="exportar_excel.php" class="btn btn-light btn-sm fw-bold text-danger border-0 shadow-sm px-3">
                    <i class="bi bi-file-earmark-excel-fill text-success me-1"></i> Exportar a Excel
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        
        <!-- Tarjetas de Resumen de Estatus -->
        <h5 class="mb-3 text-secondary fw-bold"><i class="bi bi-speedometer2 me-1"></i> RESUMEN OPERATIVO DEL TALLER</h5>
        <div class="row mb-4">
            <?php if($resEstatus && $resEstatus->num_rows > 0): ?>
                <?php while($row = $resEstatus->fetch_assoc()): ?>
                    <div class="col-md-2 col-sm-4 mb-3">
                        <div class="card card-counter bg-white p-3 text-center">
                            <small class="text-muted fw-bold d-block text-truncate"><?php echo htmlspecialchars(strtoupper($row['nombre_estatus'] ?? 'SIN ESTATUS')); ?></small>
                            <h2 class="fw-bold mb-0 text-montosa"><?php echo $row['total']; ?></h2>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Tabla Principal de Equipos -->
        <div class="card shadow-sm border-0 rounded-3 mb-5">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center border-bottom">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-journal-check text-montosa me-2"></i> Control de Equipos en Taller</h5>
                <div class="w-25">
                    <input type="text" id="buscador" class="form-control form-control-sm border-2" placeholder="🔍 Buscar por serie, compañía o modelo...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaEquipos">
                        <thead class="table-header-montosa">
                            <tr>
                                <th class="ps-3">FECHA EQUIPOS</th>
                                <th>COMPAÑÍA</th>
                                <th>MODELO</th>
                                <th>N° SERIE</th>
                                <th>ESTATUS ACTUAL</th>
                                <th class="text-center">REVISADO</th>
                                <th>OBSERVACIONES</th>
                                <th>ÚLTIMA ACTUALIZACIÓN</th>
                                <th class="text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($resEquipos && $resEquipos->num_rows > 0): ?>
                                <?php while($e = $resEquipos->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3"><small class="fw-semibold"><?php echo !empty($e['fecha_equipos']) ? date('d/m/Y', strtotime($e['fecha_equipos'])) : '-'; ?></small></td>
                                        <td><span class="badge bg-light text-dark border text-uppercase"><?php echo htmlspecialchars($e['compania'] ?? 'N/A'); ?></span></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($e['modelo']); ?></td>
                                        <td class="fw-bold text-montosa"><?php echo htmlspecialchars($e['numero_serie']); ?></td>
                                        <td>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
                                                <?php echo htmlspecialchars(strtoupper($e['nombre_estatus'] ?? 'PENDIENTE')); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($e['revisado'])): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-5" title="Revisado"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle text-muted fs-5" title="Pendiente de revisión"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($e['observaciones_tecnicas'] ?: 'Sin observaciones registradas'); ?></small></td>
                                        <td><small class="text-muted"><?php echo date('d/m/Y h:i A', strtotime($e['fecha_ultima_modificacion'])); ?></small></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-montosa-danger px-3"><i class="bi bi-eye"></i> Ver</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                        No hay refrigeradores registrados. Los datos aparecerán cuando el técnico sincronice desde la App Móvil.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Nueva Sección: Bitácora Digital de Entradas y Salidas -->
        <div class="card shadow-sm border-0 rounded-3 mb-5">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom border-danger border-2">
                <h5 class="m-0 fw-bold text-dark">
                    <i class="bi bi-journal-bookmark-fill text-montosa me-2"></i> Bitácora Digital (Entradas y Salidas del Taller)
                </h5>
                <span class="badge bg-dark">Historial de Movimientos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">MOVIMIENTO</th>
                                <th>FECHA / HORA</th>
                                <th>N° SERIE</th>
                                <th>MODELO</th>
                                <th>CLIENTE ID</th>
                                <th>ORIGEN / DESTINO</th>
                                <th>RESPONSABLE TRASLADO</th>
                                <th>OBSERVACIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($resBitacora && $resBitacora->num_rows > 0): ?>
                                <?php while($b = $resBitacora->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <?php if(strtoupper($b['tipo_movimiento']) == 'ENTRADA'): ?>
                                                <span class="badge bg-success px-2 py-1"><i class="bi bi-box-arrow-in-down me-1"></i> ENTRADA</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger px-2 py-1"><i class="bi bi-box-arrow-up-right me-1"></i> SALIDA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="fw-bold"><?php echo date('d/m/Y h:i A', strtotime($b['fecha_movimiento'])); ?></small></td>
                                        <td class="fw-bold text-montosa"><?php echo htmlspecialchars($b['numero_serie']); ?></td>
                                        <td><small class="fw-semibold"><?php echo htmlspecialchars($b['modelo']); ?></small></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($b['cliente_id']); ?></span></td>
                                        <td><small><?php echo htmlspecialchars($b['origen_destino']); ?></small></td>
                                        <td><small class="text-muted"><i class="bi bi-truck me-1"></i><?php echo htmlspecialchars($b['responsable_traslado']); ?></small></td>
                                        <td><small class="text-muted text-truncate d-block" style="max-width: 200px;"><?php echo htmlspecialchars($b['observaciones']); ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-journal-x fs-3 d-block text-secondary mb-1"></i>
                                        No hay registros en la bitácora todavía. Se registrarán cuando los técnicos guarden entradas o salidas desde la app.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Script de Filtro Rápido -->
    <script>
        document.getElementById('buscador').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#tablaEquipos tbody tr');
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Actualización automática en tiempo real cada 15 segundos
        setInterval(function() {
            const buscador = document.getElementById('buscador');
            if (document.activeElement !== buscador && buscador.value.trim() === '') {
                window.location.reload();
            }
        }, 15000);
    </script>
</body>
</html>
