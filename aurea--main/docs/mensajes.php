<?php
header('Location: ../../admin/mensajes.php', true, 303);
exit();

// Crear tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    asunto VARCHAR(200),
    mensaje TEXT NOT NULL,
    estado ENUM('leido', 'no_leido') DEFAULT 'no_leido',
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Marcar como leído
if(isset($_GET['leer']) && is_numeric($_GET['leer'])) {
    $id = (int)$_GET['leer'];
    $conn->query("UPDATE mensajes_contacto SET estado = 'leido' WHERE id_mensaje = $id");
}

// Eliminar mensaje
if(isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $conn->query("DELETE FROM mensajes_contacto WHERE id_mensaje = $id");
}

$total_mensajes = $conn->query("SELECT COUNT(*) as total FROM mensajes_contacto")->fetch_assoc()['total'];
$total_no_leidos = $conn->query("SELECT COUNT(*) as total FROM mensajes_contacto WHERE estado = 'no_leido'")->fetch_assoc()['total'];
$resultado = $conn->query("SELECT * FROM mensajes_contacto ORDER BY fecha_envio DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>AureaMed | Mensajes de Contacto</title>

    <link rel="stylesheet" href="../css/normalize.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/bootstrap-material-design.min.css">
    <link rel="stylesheet" href="../css/all.css">
    <link rel="stylesheet" href="../css/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/jquery.mCustomScrollbar.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .nav-lateral {
            background: linear-gradient(180deg, #003366 0%, #0056b3 100%);
        }
        .nav-lateral-menu ul li a.active {
            background: rgba(255,255,255,0.3);
            border-left: 4px solid #ffc107;
        }
        .table-dark {
            background-color: #003366;
        }
        .page-header h3 {
            color: #003366;
        }
        .btn-warning {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-warning:hover {
            background-color: #c82333;
        }
        .badge-no-leido {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
        .badge-leido {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    
    <main class="full-box main-container">
        <!-- Sidebar igual que en panel.php -->
        <section class="full-box nav-lateral">
            <div class="full-box nav-lateral-bg show-nav-lateral"></div>
            <div class="full-box nav-lateral-content">
                <figure class="full-box nav-lateral-avatar">
                    <i class="far fa-times-circle show-nav-lateral"></i>
                    <img src="../assets/avatar/Avatar.png" class="img-fluid" alt="Avatar">
                    <figcaption class="roboto-medium text-center">
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?><br>
                        <small class="roboto-condensed-light">Centro Auditivo AureaMed</small>
                    </figcaption>
                </figure>
                <div class="full-box nav-lateral-bar"></div>
                <nav class="full-box nav-lateral-menu">
                    <ul>
                        <li><a href="panel.php"><i class="fab fa-dashcube fa-fw"></i> Dashboard</a></li>
                        <li><a href="#" class="nav-btn-submenu"><i class="fas fa-users fa-fw"></i> Pacientes <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="paciente_nuevo.php"><i class="fas fa-plus fa-fw"></i> Registrar Paciente</a></li>
                                <li><a href="pacientes.php"><i class="fas fa-clipboard-list fa-fw"></i> Lista de Pacientes</a></li>
                            </ul>
                        </li>
                        <li><a href="#" class="nav-btn-submenu"><i class="fas fa-stethoscope fa-fw"></i> Servicios <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="servicio_nuevo.php"><i class="fas fa-plus fa-fw"></i> Registrar Servicio</a></li>
                                <li><a href="servicios.php"><i class="fas fa-clipboard-list fa-fw"></i> Lista de Servicios</a></li>
                            </ul>
                        </li>
                        <li><a href="#" class="nav-btn-submenu"><i class="fas fa-calendar-alt fa-fw"></i> Reservas <i class="fas fa-chevron-down"></i></a>
                            <ul>
                                <li><a href="reserva_nueva.php"><i class="fas fa-plus fa-fw"></i> Nueva Reserva</a></li>
                                <li><a href="reservas.php"><i class="fas fa-clipboard-list fa-fw"></i> Lista de Reservas</a></li>
                                <li><a href="reserva_pendientes.php"><i class="fas fa-clock fa-fw"></i> Reservas Pendientes</a></li>
                            </ul>
                        </li>
                        <li><a href="mensajes.php" class="active"><i class="fas fa-envelope fa-fw"></i> Mensajes</a></li>
                        <li><a href="empresa.php"><i class="fas fa-building fa-fw"></i> Centro Auditivo</a></li>
                    </ul>
                </nav>
            </div>
        </section>

        <section class="full-box page-content">
            <nav class="full-box navbar-info">
                <a href="#" class="float-left show-nav-lateral"><i class="fas fa-exchange-alt"></i></a>
                <a href="perfil.php"><i class="fas fa-user-cog"></i></a>
                <a href="logout.php" class="btn-exit-system"><i class="fas fa-power-off"></i></a>
            </nav>

            <div class="full-box page-header">
                <h3 class="text-left">
                    <i class="fas fa-envelope fa-fw"></i> MENSAJES DE CONTACTO
                </h3>
                <p class="text-justify">
                    Gestione los mensajes enviados por los clientes desde el formulario de contacto.
                    Total de mensajes: <strong><?php echo $total_mensajes; ?></strong> | No leídos: <strong><?php echo $total_no_leidos; ?></strong>
                </p>
            </div>

            <div class="container-fluid">
                <ul class="full-box list-unstyled page-nav-tabs">
                    <li><a class="active" href="mensajes.php"><i class="fas fa-envelope fa-fw"></i> MENSAJES RECIBIDOS</a></li>
                </ul>    
            </div>
            
            <div class="container-fluid">
                <div class="table-responsive">
                    <table class="table table-dark table-sm">
                        <thead>
                            <tr class="text-center roboto-medium">
                                <th>#</th>
                                <th>FECHA</th>
                                <th>NOMBRE</th>
                                <th>EMAIL</th>
                                <th>TELÉFONO</th>
                                <th>ASUNTO</th>
                                <th>ESTADO</th>
                                <th>VER</th>
                                <th>LEER</th>
                                <th>ELIMINAR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($resultado && $resultado->num_rows > 0): ?>
                                <?php $contador = 1; while($row = $resultado->fetch_assoc()): ?>
                                    <tr class="text-center">
                                        <td><?php echo $contador++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_envio'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefono']) ?: '-'; ?></td>
                                        <td><?php echo htmlspecialchars($row['asunto']) ?: '-'; ?></td>
                                        <td>
                                            <?php if($row['estado'] == 'no_leido'): ?>
                                                <span class="badge-no-leido">📩 No leído</span>
                                            <?php else: ?>
                                                <span class="badge-leido">✅ Leído</span>
                                            <?php endif; ?>
                                         </div>
                                         </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm btn-ver" 
                                                    data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                    data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                                    data-asunto="<?php echo htmlspecialchars($row['asunto']); ?>"
                                                    data-mensaje="<?php echo htmlspecialchars($row['mensaje']); ?>"
                                                    data-fecha="<?php echo date('d/m/Y H:i', strtotime($row['fecha_envio'])); ?>"
                                                    title="Ver mensaje">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                         </div>
                                         </div>
                                        </td>
                                        <td>
                                            <?php if($row['estado'] == 'no_leido'): ?>
                                                <a href="?leer=<?php echo $row['id_mensaje']; ?>" class="btn btn-success btn-sm" title="Marcar como leído">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                         </div>
                                         </div>
                                        </td>
                                        <td>
                                            <a href="?eliminar=<?php echo $row['id_mensaje']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este mensaje?')" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                         </div>
                                         </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr class="text-center">
                                    <td colspan="10">No hay mensajes de contacto</td>
                                </table>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
                    </ul>
                </nav>
            </div>
        </section>
    </main>
    
    <!-- Modal para ver mensaje -->
    <div class="modal fade" id="modalMensaje" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #003366, #0056b3); color: white;">
                    <h5 class="modal-title"><i class="fas fa-envelope"></i> Detalle del Mensaje</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>📅 Fecha:</strong> <span id="modalFecha"></span></p>
                    <p><strong>👤 Nombre:</strong> <span id="modalNombre"></span></p>
                    <p><strong>📧 Email:</strong> <span id="modalEmail"></span></p>
                    <p><strong>📞 Teléfono:</strong> <span id="modalTelefono"></span></p>
                    <p><strong>📌 Asunto:</strong> <span id="modalAsunto"></span></p>
                    <hr>
                    <p><strong>💬 Mensaje:</strong></p>
                    <div id="modalMensajeText" style="background: #f8f9fa; padding: 15px; border-radius: 12px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="../js/bootstrap-material-design.min.js"></script>
    <script src="../js/sweetalert2.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('body').bootstrapMaterialDesign();
            
            $('.btn-ver').click(function() {
                $('#modalFecha').text($(this).data('fecha'));
                $('#modalNombre').text($(this).data('nombre'));
                $('#modalEmail').text($(this).data('email'));
                $('#modalTelefono').text($(this).data('telefono') || '-');
                $('#modalAsunto').text($(this).data('asunto') || '-');
                $('#modalMensajeText').text($(this).data('mensaje'));
                $('#modalMensaje').modal('show');
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>