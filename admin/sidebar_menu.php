<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit();
}

$current_page = isset($_GET['page']) ? basename($_GET['page']) : 'panel.php';
$render_link = static function($href, $icon, $label, $active = false) {
    $class = $active ? ' class="active" aria-current="page"' : '';
    echo '<li><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $class . '><i class="fas ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' fa-fw"></i> &nbsp; ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></li>';
};
$render_group = static function($label, $icon, $items, $pages, $current_page) use ($render_link) {
    $active = in_array($current_page, $pages, true);
    echo '<li><a href="#" class="nav-btn-submenu' . ($active ? ' active' : '') . '" aria-expanded="' . ($active ? 'true' : 'false') . '"><i class="fas ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' fa-fw"></i> &nbsp; ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' <i class="fas fa-chevron-down' . ($active ? ' fa-rotate-180' : '') . '"></i></a>';
    echo '<ul' . ($active ? ' class="show-nav-lateral-submenu"' : '') . '>';
    foreach ($items as $item) {
        $render_link($item[0], $item[1], $item[2], $current_page === $item[0]);
    }
    echo '</ul></li>';
};
?>
<ul>
    <?php $render_link('panel.php', 'fa-th-large', 'Inicio', $current_page === 'panel.php'); ?>
    <?php $render_group('Pacientes', 'fa-users', [
        ['paciente_nuevo.php', 'fa-user-plus', 'Registrar Paciente'],
        ['pacientes.php', 'fa-clipboard-list', 'Lista de Pacientes'],
        ['paciente_buscar.php', 'fa-search', 'Buscar Paciente']
    ], ['paciente_nuevo.php', 'pacientes.php', 'paciente_buscar.php', 'paciente_editar.php'], $current_page); ?>
    <?php $render_group('Servicios', 'fa-stethoscope', [
        ['servicio_nuevo.php', 'fa-plus-circle', 'Registrar Servicio'],
        ['servicios.php', 'fa-clipboard-list', 'Catálogo de Servicios'],
        ['servicio_buscar.php', 'fa-search', 'Buscar Servicio']
    ], ['servicio_nuevo.php', 'servicios.php', 'servicio_buscar.php', 'servicio_editar.php'], $current_page); ?>
    <?php $render_group('Reservas / Citas', 'fa-calendar-alt', [
        ['reserva_nueva.php', 'fa-calendar-plus', 'Agendar Cita'],
        ['reservas.php', 'fa-calendar-check', 'Lista de Reservas'],
        ['reserva_pendientes.php', 'fa-clock', 'Citas Pendientes'],
        ['reserva_buscar.php', 'fa-search', 'Buscar Cita']
    ], ['reserva_nueva.php', 'reservas.php', 'reserva_pendientes.php', 'reserva_buscar.php', 'reserva_editar.php', 'reserva_confirmar.php', 'reserva_cancelar.php'], $current_page); ?>
    <?php $render_group('Facturación', 'fa-file-invoice-dollar', [
        ['facturas.php', 'fa-list-alt', 'Lista de Facturas'],
        ['pagos_servicio.php', 'fa-dollar-sign', 'Registrar Pago']
    ], ['facturas.php', 'factura_ver.php', 'pagos_servicio.php'], $current_page); ?>
    <?php $render_link('mensajes.php', 'fa-envelope-open-text', 'Citas Web / Mensajes', $current_page === 'mensajes.php'); ?>
    <?php $render_group('Reportes', 'fa-chart-bar', [
        ['reporte_citas.php', 'fa-calendar-alt', 'Reporte de Citas'],
        ['reporte_pacientes.php', 'fa-user-check', 'Pacientes Atendidos'],
        ['reporte_servicios.php', 'fa-chart-pie', 'Demanda de Servicios'],
        ['REPPDF/index.php', 'fa-file-pdf', 'Reporte PDF / XLS']
    ], ['reporte_citas.php', 'reporte_pacientes.php', 'reporte_servicios.php', 'REPPDF/index.php'], $current_page === 'index.php' ? 'REPPDF/index.php' : $current_page); ?>
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <?php $render_group('Usuarios', 'fa-user-shield', [
            ['usuario_nuevo.php', 'fa-user-plus', 'Nuevo Usuario'],
            ['usuarios.php', 'fa-users-cog', 'Lista de Usuarios'],
            ['usuario_buscar.php', 'fa-search', 'Buscar Usuario']
        ], ['usuario_nuevo.php', 'usuarios.php', 'usuario_buscar.php', 'usuario_editar.php'], $current_page); ?>
    <?php endif; ?>
    <?php $render_link('empresa.php', 'fa-hospital-alt', 'Centro Auditivo', $current_page === 'empresa.php'); ?>
    <?php $render_link('qr.php', 'fa-qrcode', 'Generar código QR', $current_page === 'qr.php'); ?>
    <li><a href="logout.php" class="btn-exit-system"><i class="fas fa-power-off fa-fw"></i> &nbsp; Salir</a></li>
</ul>