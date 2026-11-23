<?php
include '../conexion.php';

// Cabeceras para forzar la descarga en formato Excel (.xls)
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Reporte_Semanal_Montosa_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Mapeo de IDs de estatus a texto entendible
$catalogo_estatus = [
    1 => 'PENDIENTE / TALLER',
    2 => 'HABILITADO',
    3 => 'GARANTÍA',
    4 => 'COMODATADO',
    5 => 'BAJA'
];

// LÓGICA SQL: Filtra desde el viernes de la semana pasada hasta el viernes de la semana actual
$query = "SELECT numero_serie, compania, modelo, id_estatus, revisado, fecha_equipos, observaciones_tecnicas 
          FROM equipos 
          WHERE fecha_equipos BETWEEN 
                -- Viernes de la semana pasada
                DATE_SUB(DATE_ADD(CURDATE(), INTERVAL (5 - DAYOFWEEK(CURDATE())) DAY), INTERVAL 7 DAY) 
                AND 
                -- Viernes de la semana actual (o más reciente)
                DATE_ADD(CURDATE(), INTERVAL (5 - DAYOFWEEK(CURDATE())) DAY)
          ORDER BY fecha_equipos DESC";

$result = $conn->query($query);
?>
<!-- Estructura HTML para Excel -->
<meta charset="utf-8">
<table border="1">
    <thead>
        <tr style="background-color: #C8102E; color: white; font-weight: bold;">
            <th>N° SERIE</th>
            <th>COMPAÑÍA</th>
            <th>MODELO</th>
            <th>ESTATUS ACTUAL</th>
            <th>REVISADO</th>
            <th>FECHA INSPECCIÓN</th>
            <th>OBSERVACIONES TÉCNICAS</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['numero_serie']); ?></td>
                    <td><?php echo htmlspecialchars($row['compania'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['modelo'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($catalogo_estatus[$row['id_estatus']] ?? 'PENDIENTE'); ?></td>
                    <td><?php echo $row['revisado'] == 1 ? 'SÍ' : 'NO'; ?></td>
                    <td><?php echo htmlspecialchars($row['fecha_equipos'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['observaciones_tecnicas'] ?? ''); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; font-weight: bold;">
                    No hay registros de inspecciones realizados en el rango del viernes pasado a este viernes.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>