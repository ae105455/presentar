<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AUREA - Hipoacusia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="incio.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="global.css">
</head>
<body>


<!-- ================= NAVBAR - MENU PRINCIPAL ================= -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
       <a class="aurea2" href="./index.php" aria-label="Logo Aurea">
           <img src="./Asset%2010@4x.png" alt="Logo Aurea">
       </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown",
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link text-white fw-semibold dropdown-toggle" href="#nosotros" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Nosotros
                    </a>
                    <ul class="dropdown-menu">

                        <li><a class="dropdown-item" href="./nosotros/Nosotros.html">Conócenos</a></li>
                        <li><a class="dropdown-item" href="./perdidaauditiva/auditiva.html">Pérdida Auditiva</a></li>
                        <li><a class="dropdown-item" href="./encuesta/Encuesta.html">Mini Test Auditivo</a></li>

                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link text-white fw-semibold dropdown-toggle" href="#servicios" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Servicios
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="./audilogia/audiologia.html">Audiología</a></li>
                        <li><a class="dropdown-item" href="./prevenciones/cuidado.html">Servicio Técnico</a></li>
                        <li><a class="dropdown-item" href="./leyes/leyes.html">Laboratorio Auditivo</a></li>
                    </ul>
                </li>
                 <li class="nav-item dropdown">
                        <a class="nav-link text-white fw-semibold dropdown-toggle" href="#productos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Productos
                        </a>
                        <ul class="dropdown-menu">
                      <li><a class="dropdown-item" href="./estadistica/estadistica.html">Accesorios</a></li>
                      <li><a class="dropdown-item" href="./ventas/ventas.html">Audífonos</a></li>
                        </ul>
                    </li>
                <li class="nav-item">
                    <a class="nav-link text-white fw-semibold" href="../../admin/index.php" role="button">
                        Administrador
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link text-white fw-semibold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Ubicaciones
                    </a>
                    <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#reservar">Reservar cita</a></li>
    <li><a class="dropdown-item" href="./ubicacion/implante.html#la-paz">La Paz</a></li>
    
    <li><a class="dropdown-item" href="./ubicacion/implante.html#el-alto">El Alto</a></li>
    
    <li><a class="dropdown-item" href="./ubicacion/implante.html#oruro">Oruro</a></li>

    <li><a class="dropdown-item" href="./ubicacion/implante.html#tarija">Tarija</a></li>
</ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!--================ Botones Flotantes ================ -->
<!-- WhatsApp -->
<a href="https://wa.me/59177534284?text=Hola,%20quisiera%20recibir%20informaci%C3%B3n%20sobre%20los%20servicios%20de%20Aureamed%20y%20coordinar%20una%20cita.%20%C2%BFPodr%C3%ADan%20ayudarme?" 
   class="flotante whatsapp" 
   target="_blank" rel="noopener noreferrer" aria-label="Contactar a AureaMed por WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>



<!-- Facebook -->
<a href="https://www.facebook.com/AUREAEVOLUCIONAUDITIVA" class="flotante facebook" target="_blank" rel="noopener noreferrer" title="Síguenos en Facebook">
  <i class="icono fab fa-facebook-f"></i>
</a>

<!-- Acceso al formulario de reclamos -->
<a href="#reclamo" class="flotante reclamo" title="Enviar un reclamo" aria-label="Enviar un reclamo">
  <i class="fas fa-comment-medical" aria-hidden="true"></i>
</a>

<!-- Botón Scroll Top -->
<button class="acroll" id="btntop">↑</button>
<!-- ================= HERO CARRUSEL ================= -->
<section class="hero-cover">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false">
    <div class="carousel-inner">

      <div class="carousel-item active">
        <img src="m.png" class="d-block w-100" alt="Primera imagen">
        <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100">
          <h1>PARA UNA VIDA</h1>
          <p>CONECTADA</p>
        </div>
      </div>

      <div class="carousel-item">
        <img src="oido2.jpg" class="d-block w-100" alt="Segunda imagen">
        <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100">
          <h1>EXPERIENCIAS</h1>
          <p>QUE INSPIRAN</p>
        </div>
      </div>

      <div class="carousel-item">
        <img src="./imagenes copy/23.jpg" class="d-block w-100" alt="Tercera imagen">
        <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100">
          <h1>TECNOLOGÍA</h1>
          <p>A TU ALCANCE</p>
        </div>
      </div>

      <div class="carousel-item active">
        <img src="oido.jpg" class="d-block w-100" alt="Primera imagen">
        <div class="carousel-caption d-flex flex-column justify-content-center align-items-center h-100">
          <h1>PARA ESCUCHAR</h1>
          <p>COMO ANTES</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Sección de Estadísticas -->

 <!-- ================= TRES CUADROS ================= -->
    <section class="container mt-5">
        <h2 class="text-center mb-4"> PÉRDIDA AUDITIVA</h2>
        
        <div class="row g-4 text-center">
            
            <!-- Hipoacusia -->
            <div class="col-md-4">
                <div class="card-custom position-relative">
                    <img src="./hipo.jpg" alt="Hipoacusia" class="w-100">
                    <div class="card-overlay">
                          <h4 class="hipoacusia-contrast-2">Hipoacusia</h4>
                        <button type="button" class="btn btn-granate mt-2" data-bs-toggle="modal" data-bs-target="#modalHipoacusia">
                            Ver más
                        </button>
                    </div>
                </div>
            </div>

            <!-- Microtia -->
            <div class="col-md-4">
                <div class="card-custom position-relative">
                    <img src="./micr.jpg" alt="Microtia" class="w-100">
                    <div class="card-overlay">
                        <h4 class="hipoacusia-contrast-2">Microtia</h4>
                        <button type="button" class="btn btn-granate mt-2" data-bs-toggle="modal" data-bs-target="#modalMicrotia">
                            Ver más
                        </button>
                    </div>
                </div>
            </div>

            <!-- Anacusia -->
            <div class="col-md-4">
                <div class="card-custom position-relative">
                    <img src="viejo2.png" alt="">
                    <div class="card-overlay">
                        <h4 class="hipoacusia-contrast-2">Presbiacusia</h4>
                        <button type="button" class="btn btn-granate mt-2" data-bs-toggle="modal" data-bs-target="#modalAnacusia">
                            Ver más
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ventanas Emergentes (Modales) -->

    <!-- Modal Hipoacusia -->
    <div class="modal fade" id="modalHipoacusia" tabindex="-1" aria-labelledby="modalHipoacusiaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <img src="./hipo.jpg" alt="Hipoacusia" class="w-100">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalHipoacusiaLabel">Hipoacusia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                     <p>La hipoacusia es la pérdida parcial o total de la audición, de grado leve a profundo, y constituye un problema de salud pública mundial porque impacta la comunicación, el aprendizaje y la calidad de vida de millones de personas.</p>
                    <ul>
                        <li>Puede afectar a uno o ambos oídos y dificultar la capacidad de escuchar conversaciones cotidianas, sonidos ambientales o señales de alerta.</li>
                        <li>Hipoacusia: pérdida parcial de la audición, con posibilidad de mejorar mediante dispositivos o tratamientos.</li>
                        <li>Sordera: pérdida profunda o total de la audición, donde la persona no puede oír prácticamente nada.</li>
                    </ul>
              
               <hr>
               <h5 class="modal-title" id="modalHipoacusiaLabel">Prevención</h5>
               <ul>
                <li>Evitar exposición prolongada a ruidos fuertes.</li>
                <li>Promover prácticas seguras de audición (uso responsable de auriculares, protección auditiva en ambientes ruidosos).</li>
                <li>Vacunación y tratamiento de infecciones que pueden causar pérdida auditiva.</li>
                <li>Detección temprana</li>
                <li>Programas de tamizaje auditivo en recién nacidos, niños y adultos mayores.</li>
                <li>Evaluaciones periódicas en poblaciones de riesgo</li>
                <li>Intervenciones médicas y quirúrgicas</li>
                <li>Tratamiento de otitis crónicas, malformaciones o enfermedades del oído</li>
               </ul>
               <hr>
               <h5 class="modal-title" id="modalHipoacusiaLabel"> Dispositivos auditivos</h5>
               <ul>
                <li>Audífonos para hipoacusia leve a grave.</li>
                <li>Implantes cocleares en casos de hipoacusia profunda o sordera.</li>
                <li>Sistemas de asistencia auditiva (amplificadores, dispositivos de transmisión FM).</li>
               </ul>
               <hr>
                <h5 class="modal-title" id="modalHipoacusiaLabel">Rehabilitación auditiva</h5>
                <ul>
                    <li>Terapias de lenguaje y comunicación.</li>
                    <li>Apoyo psicosocial para reducir el aislamiento.</li>
                    <li>Capacitación en lectura labial o uso de lengua de señas cuando es necesario.</li>
                </ul>
                <hr>
                <p>La fuente oficial de la OMS sobre salud auditiva está disponible en su página dedicada a la sordera y pérdida de la audición.</p>
                <ul>
                    <li>Sordera y pérdida de la audición – OMS</li>
                    <li>Cuidado del oído y la audición – OMS</li>
                    <li>Salud auditiva – OPS/OMS</li>
                </ul>
            </div>

                <hr>
<h6><strong>Referencias</strong></h6>
<p class="small">
    La fuente oficial de la OMS sobre salud auditiva está disponible en su página dedicada
    a la sordera y pérdida de la audición.
</p>
<ul class="small">
    <li>Sordera y pérdida de la audición – Organización Mundial de la Salud (OMS).</li>
    <li>Cuidado del oído y la audición – Organización Mundial de la Salud (OMS).</li>
    <li>Salud auditiva – Organización Panamericana de la Salud / OMS (OPS/OMS).</li>
</ul>

                <div class="text-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Microtia -->
    <div class="modal fade" id="modalMicrotia" tabindex="-1" aria-labelledby="modalMicrotiaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <img src="./micr.jpg" alt="Microtia" class="w-100">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMicrotiaLabel">Microtia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Es una malformación congénita del pabellón auricular que va desde aurícula pequeña hasta ausencia total (anotia). A menudo se asocia a atresia del conducto auditivo externo y hipoacusia  Micro = Pequeño / Otia = Oreja</p>
                    <hr>
                    <img src="./micro.png" alt="Micro" class="w-100">
                    <p>La microtía consta de tres grados basándose en la forma del pabellón auricular y la formación de el oído interno.</p>
                   <hr>
                    <h5 class="modal-title" id="modalMicrotiaLabel">Tipo A (Estenosis) </h5>
                    <p>Conducto auditivo estrecho, membrana visible, y huesecillos poco móviles. Con una hipoacusia leve a moderada.</p>
                    <img src="./micros.png" alt="Micro" class="w-100">
                    <hr>
                    <h5 class="modal-title" id="modalMicrotiaLabel">Tipo B (Atrecia Parcial)  </h5>
                    <p>Conducto incompleto, membrana pequeña o ausente, huesecillos alterados. Con una hipoacusia Moderada a Severa</p>
                    
                     <img src="./microsiaa.png" alt="Micro" class="w-100">
                     <hr>
                     <h5 class="modal-title" id="modalMicrotiaLabel">Tipo C (Atresia Total) </h5>
                     <p>Conducto cerrado completamente, sin membrana ni estructura interna formada. Con una hipoacusia Severa.</p>
                       <img src="./punto.png" alt="Micro" class="w-100">
                       <p>El tratamiento suele ser el apoyo con prótesis auditivas, operación coclear.</p>
                       <p>Lo estético no es funcional.</p>
                       <p>Restaurar la forma externa del oído y la simetría facial. Es un procedimiento solo estético, no mejora la audición</p>
                       <hr>
                       <h5 class="modal-title" id="modalMicrotiaLabel">TIPOS DE RECONSTRUCCIÓN: </h5>
                       <ul>
                        <li> <strong>Autóloga:</strong> con cartílago costal del propio paciente (resultado natural, requiere cirugía).</li>
                        <li><strong>Sintética:</strong>  con implante (Medpor/Su-Por), menor invasión, aspecto simétrico.</li>
                        <li><strong>Protésica:</strong> oreja de silicona personalizada, sincirugía, con adhesivo o imán.</li>
                       </ul>
                      <!--  <hr>
                       <p>Es importante que la decisión sea de la persona que vive con esta esta condición y con el fin de sentirse mejor y pueda apoyar en su propia autoestima e identidad </p>
<img src="./tipo.png" alt="Micro" class="w-100"> -->
<hr>
<p>La microtía se puede diagnosticar a partir del quinto mes de gestación en la ecografía pero la malformación ocurre en los primeros tres meses de gestación.</p>
<hr>
<h6><strong>Referencias</strong></h6>
<p class="small">
    La fuente oficial de la OMS sobre salud auditiva está disponible en su página dedicada
    a la sordera y pérdida de la audición.
</p>
<ul class="small">
    <li>Sordera y pérdida de la audición – Organización Mundial de la Salud (OMS).</li>
    <li>Cuidado del oído y la audición – Organización Mundial de la Salud (OMS).</li>
    <li>Salud auditiva – Organización Panamericana de la Salud / OMS (OPS/OMS).</li>
</ul>

                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    
                    <hr>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Anacusia -->
    <div class="modal fade" id="modalAnacusia" tabindex="-1" aria-labelledby="modalAnacusiaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
          <img src="./viejo2.png" alt="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAnacusiaLabel">Presbiacusia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>La presbiacusia es una pérdida auditiva progresiva, bilateral y propia del envejecimiento, que afecta principalmente las frecuencias altas y dificulta entender el habla, sobre todo en ambientes con ruido.</p>
                     <h5 class="modal-title" id="modalAnacusiaLabel">¿Por qué ocurre?</h5>
                     <ul>
                        <li>Cambios naturales del envejecimiento en la cóclea y vías auditivas.</li>
                        <li>Inflamación, estrés oxidativo y alteración del metabolismo celular.</li>
                        <li>Factores que aceleran el daño: ruido, diabetes, hipertensión, fármacos ototóxicos y tabaco</li>
                     </ul>
                     <h5 class="modal-title" id="modalAnacusiaLabel">Impacto</h5>
                     <ul>
                        <li>Problemas de comunicación y comprensión del habla.</li>
                        <li>Mayor riesgo de deterioro cognitivo, aislamiento social y depresión.</li>
                        <li>Afecta actividad diaria, relaciones y calidad de vida.</li>
                     </ul>
                         <h5 class="modal-title" id="modalAnacusiaLabel">Tratamiento</h5>
                         <ul>
                            <li>Audífonos:opciones más efectivas hoy.</li>
                            <li>Se investigan nuevas terapias (fármacos, terapia génica y células madre).</li>
                            <li>El diagnóstico temprano mejora los resultados.</li>
                         </ul>
                         
        <hr>
        <p class="text-center mb-0">&copy;secretaria de salud2020 Guia clínica de presbiacusia rev.04.</p>
    
                </div>
                <hr>
<h6><strong>Referencias</strong></h6>
<p class="small">
    La fuente oficial de la OMS sobre salud auditiva está disponible en su página dedicada
    a la sordera y pérdida de la audición.
</p>
<ul class="small">
    <li>Sordera y pérdida de la audición – Organización Mundial de la Salud (OMS).</li>
    <li>Cuidado del oído y la audición – Organización Mundial de la Salud (OMS).</li>
    <li>Salud auditiva – Organización Panamericana de la Salud / OMS (OPS/OMS).</li>
</ul>

                <div class="text-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Tabla estaadistica -->
</section>
    <div class="abrir-espacio">
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">

                <div class="stat-item">
                    <div class="stat-icon">🏠</div>
                    <div class="stat-label">Atención a Domicillo</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number" data-count="2000 " data-plus="true">
                        200 +
                    </div>
                    <div class="stat-label">Audífonos Medicados Vendidos</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number" data-count="12" data-plus="true">
                        2000+
                    </div>
                    <div class="stat-label">Años de Experiencia</div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon">🤗</div>
                    <div class="stat-label">Calidez Humana</div>
                </div>

            </div>
        </div>
    </section>
</div>

    







<!-- ================= RESERVA DE CITAS ================= -->
<section id="reservar" class="reserva-section">

  <div class="reserva-bg">
    <div class="reserva-card">
      
      <!-- Cabecera decorativa -->
      <div class="reserva-header">
        <div class="reserva-icono">
          <i class="fas fa-head-side-medical"></i>
        </div>
        <h2>Reserva tu Cita</h2>
        <p>¡Cuida tu salud auditiva! Agenda una cita en segundos</p>
      </div>

      <form id="form-reserva" action="procesar_reserva.php" method="POST">
        
        <!-- Paso 1: Datos personales -->
        <div class="paso-card">
          <div class="paso-titulo">
            <span class="paso-numero">1</span>
            <h3>Información Personal</h3>
          </div>
          
          <div class="reserva-grid">
            <div class="input-grupo">
              <label class="campo-label" for="nombre">Nombre completo <span aria-hidden="true">*</span></label>
              <i class="fas fa-user"></i>
              <input type="text" id="nombre" name="nombre" maxlength="100" placeholder="Escribe tu nombre completo" autocomplete="name" aria-describedby="error-nombre" required>
              <small id="error-nombre" class="campo-error" role="alert"></small>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="cedula">Cédula de identidad <span aria-hidden="true">*</span></label>
              <i class="fas fa-id-card"></i>
              <input type="text" id="cedula" name="cedula" maxlength="20" autocomplete="off" placeholder="Escribe tu cédula" pattern="[A-Za-z0-9.\-]+" title="Usa letras, números, punto o guion" required>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="email">Correo electrónico <span aria-hidden="true">*</span></label>
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" maxlength="100" placeholder="Escribe tu correo electrónico" autocomplete="email" aria-describedby="error-email" required>
              <small id="error-email" class="campo-error" role="alert"></small>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="telefono">Teléfono / WhatsApp</label>
              <i class="fas fa-phone-alt"></i>
              <input type="tel" id="telefono" name="telefono" maxlength="20" placeholder="Escribe tu teléfono" aria-describedby="error-telefono">
              <small id="error-telefono" class="campo-error" role="alert"></small>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="edad">Edad</label>
              <i class="fas fa-birthday-cake"></i>
              <select id="edad" name="edad">
                <option value="">¿Cuál es tu edad?</option>
                <option value="menor">👶 Menor de 12 años</option>
                <option value="joven">🧑 Joven (13-25 años)</option>
                <option value="adulto">👨 Adulto (26-59 años)</option>
                <option value="mayor">👴 Adulto Mayor (60+ años)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Paso 2: Datos de la cita -->
        <div class="paso-card">
          <div class="paso-titulo">
            <span class="paso-numero">2</span>
            <h3>Datos de la Cita</h3>
          </div>
          
          <div class="reserva-grid">
            <div class="input-grupo">
              <label class="campo-label" for="servicio">Servicio que necesitas <span aria-hidden="true">*</span></label>
              <i class="fas fa-stethoscope"></i>
              <select id="servicio" name="servicio" aria-describedby="error-servicio" required>
                <option value="">¿Qué servicio necesitas?</option>
                <option value="audiometria">🦻 Audiometría (Prueba de audición)</option>
                <option value="limpieza">🧼 Limpieza de oídos</option>
                <option value="reparacion">⚙️ Reparación de audífonos</option>
                <option value="moldes">👂 Elaboración de moldes</option>
                <option value="consulta">💬 Consulta general</option>
              </select>
              <small id="error-servicio" class="campo-error" role="alert"></small>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="fecha">Fecha de la cita <span aria-hidden="true">*</span></label>
              <i class="fas fa-calendar-alt"></i>
              <input type="date" id="fecha" name="fecha" aria-label="Fecha de la cita" aria-describedby="error-fecha" required>
              <small id="error-fecha" class="campo-error" role="alert"></small>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="hora">Horario preferido</label>
              <i class="fas fa-clock"></i>
              <select id="hora" name="hora" required>
                <option value="">Selecciona un horario</option>
                <option value="09:00">🕘 09:00 - 10:00</option>
                <option value="10:00">🕙 10:00 - 11:00</option>
                <option value="11:00">🕚 11:00 - 12:00</option>
                <option value="15:00">🕒 15:00 - 16:00</option>
                <option value="16:00">🕓 16:00 - 17:00</option>
                <option value="17:00">🕔 17:00 - 18:00</option>
              </select>
            </div>
            <div class="input-grupo">
              <label class="campo-label" for="como_nos_conocio">¿Cómo nos conociste?</label>
              <i class="fas fa-globe"></i>
              <select id="como_nos_conocio" name="como_nos_conocio">
                <option value="">¿Cómo nos conociste?</option>
                <option value="google">🔍 Google / Internet</option>
                <option value="facebook">📘 Facebook</option>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="recomendacion">🗣️ Recomendación</option>
                <option value="otro">📢 Otro</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Mensaje adicional -->
        <div class="paso-card">
          <div class="input-grupo full-width">
            <label class="campo-label" for="mensaje">Mensaje adicional</label>
            <i class="fas fa-comment-dots"></i>
            <textarea id="mensaje" name="mensaje" rows="3" maxlength="5000" placeholder="¿Algo más que quieras decirnos? (Ej: Necesito atención especial, problemas de movilidad, etc.)"></textarea>
          </div>
        </div>

        <!-- Botones -->
        <div class="reserva-botones">
          <button type="submit" class="btn-reservar">
            <i class="fas fa-calendar-check"></i> Reservar Cita
          </button>
          <button type="reset" class="btn-limpiar">
            <i class="fas fa-broom"></i> Limpiar
          </button>
        </div>

      </form>
    </div>
  </div>
</section>
<section id="reclamo" class="reclamo-section">
  <div class="container">
    <div class="reclamo-card">
      <div class="reclamo-heading">
        <span class="reclamo-icon"><i class="fas fa-comment-medical" aria-hidden="true"></i></span>
        <div>
          <h2>¿Tuviste un problema?</h2>
          <p>Cuéntanos qué ocurrió. Tu reclamo llegará directamente a nuestro equipo.</p>
        </div>
      </div>
      <form id="form-reclamo" action="procesar_mensaje.php" method="post">
        <div class="reclamo-grid">
          <label for="reclamo-nombre">Tu nombre
            <input id="reclamo-nombre" type="text" name="nombre" maxlength="100" autocomplete="name" placeholder="Escribe tu nombre" required>
          </label>
          <label for="reclamo-email">Tu correo
            <input id="reclamo-email" type="email" name="email" maxlength="100" autocomplete="email" placeholder="nombre@correo.com" required>
          </label>
          <label for="reclamo-telefono">Teléfono o WhatsApp <span>(opcional)</span>
            <input id="reclamo-telefono" type="tel" name="telefono" maxlength="20" autocomplete="tel" placeholder="Tu número de contacto">
          </label>
          <label class="reclamo-full-width" for="reclamo-mensaje">Describe tu reclamo
            <textarea id="reclamo-mensaje" name="mensaje" rows="5" maxlength="5000" placeholder="Cuéntanos qué pasó y cómo podemos ayudarte." required></textarea>
          </label>
        </div>
        <input type="hidden" name="asunto" value="Reclamo">
        <button type="submit" class="btn-enviar-reclamo">
          <i class="fas fa-paper-plane" aria-hidden="true"></i> Enviar reclamo
        </button>
        <small class="reclamo-privacy-note">Recibiremos tu mensaje en la bandeja de reclamos del administrador.</small>
      </form>
    </div>
  </div>
</section>
<script>
  (function () {
    const form = document.getElementById('form-reserva');
    const nombre = form.querySelector('[name="nombre"]');
    const cedula = form.querySelector('[name="cedula"]');
    const email = form.querySelector('[name="email"]');
    const telefono = form.querySelector('[name="telefono"]');
    const servicio = form.querySelector('[name="servicio"]');
    const fecha = form.querySelector('[name="fecha"]');
    let formularioEnviado = false;

    // La fecha mínima se calcula con la hora local para no mostrar un día anterior.
    const hoy = new Date();
    const fechaHoy = [hoy.getFullYear(), String(hoy.getMonth() + 1).padStart(2, '0'), String(hoy.getDate()).padStart(2, '0')].join('-');
    fecha.min = fechaHoy;

    function validarCampo(campo) {
      campo.setCustomValidity('');
      const valor = campo.value.trim();
      const error = document.getElementById('error-' + campo.id);
      let mensaje = '';

      if (campo === nombre && !valor) {
        mensaje = 'Debes llenar tu nombre completo.';
      } else if (campo === nombre && !/^[A-Za-zÁÉÍÓÚáéíóúÜüÑñ]+(?:[ '\-][A-Za-zÁÉÍÓÚáéíóúÜüÑñ]+)*$/.test(valor)) {
        mensaje = 'El nombre está mal escrito. Usa solo letras y espacios.';
      } else if (campo === cedula && !/^[A-Za-z0-9.-]{1,20}$/.test(valor)) {
        mensaje = 'Escribe una cédula válida.';
      } else if (campo === email && !valor) {
        mensaje = 'Debes llenar tu correo electrónico.';
      } else if (campo === email && !email.validity.valid) {
        mensaje = 'El correo está mal escrito. Ejemplo: nombre@correo.com.';
      } else if (campo === telefono && valor && !/^\+?[0-9 ()-]{7,20}$/.test(valor)) {
        mensaje = 'El teléfono está mal escrito. Usa solo números.';
      } else if (campo === servicio && !servicio.value) {
        mensaje = 'Debes llenar el servicio que necesitas.';
      } else if (campo === fecha && !fecha.value) {
        mensaje = 'Debes llenar la fecha de tu cita.';
      } else if (campo === fecha && fecha.value < fecha.min) {
        mensaje = 'Selecciona una fecha desde hoy en adelante.';
      }

      campo.setCustomValidity(mensaje);
      if (error) {
        const mostrar = Boolean(mensaje) && (formularioEnviado || valor);
        error.textContent = mostrar ? mensaje : '';
        error.hidden = !mostrar;
      }
    }

    [nombre, cedula, email, telefono, servicio, fecha].forEach(function (campo) {
      campo.addEventListener('invalid', function () {
        formularioEnviado = true;
        validarCampo(campo);
      });
      campo.addEventListener('input', function () {
        validarCampo(campo);
      });
      campo.addEventListener('change', function () {
        validarCampo(campo);
      });
    });

    form.addEventListener('submit', function (event) {
      formularioEnviado = true;
      [nombre, cedula, email, telefono, servicio, fecha].forEach(validarCampo);

      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });

    form.addEventListener('reset', function () {
      setTimeout(function () {
        formularioEnviado = false;
        [nombre, cedula, email, telefono, servicio, fecha].forEach(validarCampo);
      }, 0);
    });
  }());
</script>
<style>
    /* ================= RESERVA DE CITAS ================= */
.reserva-section {
  padding: 60px 20px;
  background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
}

.reserva-bg {
  max-width: 1000px;
  margin: 0 auto;
}

.reserva-card {
  background: white;
  border-radius: 40px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  transition: transform 0.3s;
}

.reserva-card:hover {
  transform: translateY(-5px);
}

.reserva-header {
  background: linear-gradient(135deg, #eb6c05, #800020);
  padding: 40px 30px;
  text-align: center;
  color: white;
  position: relative;
  overflow: hidden;
}

.reserva-header::before {
  content: '🦻';
  position: absolute;
  font-size: 180px;
  opacity: 0.08;
  right: -30px;
  bottom: -50px;
  pointer-events: none;
}

.reserva-icono {
  background: rgba(255, 255, 255, 0.2);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 40px;
}

.reserva-header h2 {
  font-size: 32px;
  margin-bottom: 10px;
  font-weight: 700;
}

.reserva-header p {
  font-size: 16px;
  opacity: 0.9;
}

/* Paso a paso */
.paso-card {
  padding: 30px;
  border-bottom: 1px solid #eef2f7;
}

.paso-titulo {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
}

.paso-numero {
  background: linear-gradient(135deg, #800020, #eb6c05);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 18px;
}

.paso-titulo h3 {
  font-size: 22px;
  color: #eb6c05;
  margin: 0;
}

/* Grid de formulario */
.reserva-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.input-grupo {
  position: relative;
}

.campo-label {
  display: block;
  margin: 0 0 8px 4px;
  color: #800020;
  font-size: 14px;
  font-weight: 700;
}

.campo-label span {
  color: #eb6c05;
}

.campo-error {
  display: block;
  margin: 6px 4px 0;
  color: #b00020;
  font-size: 13px;
  font-weight: 600;
}

.campo-error[hidden] {
  display: none;
}

.input-grupo i {
  position: absolute;
  left: 18px;
  top: 49px;
  color: #eb6c05;
  font-size: 18px;
  z-index: 1;
}

.input-grupo input,
.input-grupo select,
.input-grupo textarea {
  width: 100%;
  padding: 15px 15px 15px 50px;
  border: 2px solid #e8e8e8;
  border-radius: 16px;
  font-size: 15px;
  font-family: inherit;
  transition: all 0.3s;
  background: white;
}

.input-grupo textarea {
  padding: 15px 15px 15px 50px;
  resize: vertical;
}

.input-grupo input:focus,
.input-grupo select:focus,
.input-grupo textarea:focus {
  outline: none;
  border-color: #eb6c05;
  box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.1);
}

.full-width {
  grid-column: span 2;
}

/* Botones */
.reserva-botones {
  padding: 20px 30px 40px;
  display: flex;
  gap: 20px;
  justify-content: center;
  background: #fafcfd;
}

.btn-reservar {
  background: linear-gradient(135deg, #800020, #eb6c05);
  color: white;
  padding: 16px 45px;
  border: none;
  border-radius: 50px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 12px;
}

.btn-reservar:hover {
  transform: scale(1.03);
  box-shadow: 0 10px 25px -5px rgba(0, 86, 179, 0.5);
}

.btn-limpiar {
  background: #f0f0f0;
  color: #555;
  padding: 16px 45px;
  border: none;
  border-radius: 50px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 12px;
}

.btn-limpiar:hover {
  background: #e5e5e5;
  transform: scale(1.02);
}

/* Responsive */
@media (max-width: 768px) {
  .reserva-card {
    margin: 0;
    border-radius: 30px;
  }
  
  .reserva-header {
    padding: 30px 20px;
  }
  
  .reserva-header h2 {
    font-size: 26px;
  }
  
  .paso-card {
    padding: 20px;
  }
  
  .reserva-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .full-width {
    grid-column: span 1;
  }
  
  .reserva-botones {
    flex-direction: column;
    padding: 20px;
  }
  
  .btn-reservar,
  .btn-limpiar {
    width: 100%;
    justify-content: center;
    padding: 14px;
    font-size: 16px;
  }
  
  .paso-titulo h3 {
    font-size: 18px;
  }
}
</style>

<!-- ================= FOOTER ================= -->
<footer>
    <div class="container">
        <div class="row text-center text-md-start">
            
            <div class="col-md-4">
                <h4>Áurea Centro Auditivo</h4>
                <p>Expertos en soluciones auditivas con más de 15 años de experiencia mejorando la calidad de vida de nuestros pacientes.</p>
            </div>

            <div class="col-md-4">
                <h4>Contacto</h4>
                <p>📍 Av.Arce Nro.2652 Edif. MILA Mezanine 1 Of. L21<br>(frente al Multicine La Paz, Bolivia)</p>
                <p>📞 <a href="tel:+59177534284">+591 77534284</a></p>
                <p>✉️ <a href="mailto:contacto@aureamed.com.bo">contacto@aureamed.com.bo</a></p>
            </div>

            <div class="col-md-4">
                <h4>Horario</h4>
                <p>Lunes y Miércoles: 15:00 - 19:00</p>
                <p>Martes y Jueves: 10:00 - 18:00</p>
                <p>Viernes: 14:00 - 18:00</p>
            </div>

        </div>

        <!-- BOTÓN MAPA -->
        <button type="button" class="btn btn-light btn-sm rounded-pill px-4 py-2 shadow btn-mapa-centered" 
                data-bs-toggle="modal" data-bs-target="#mapaModal">
            📍 Ver Mapa
        </button>

        <!-- MODAL MAPA -->
        <div class="modal fade" id="mapaModal" tabindex="-1" aria-labelledby="mapaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubicación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                  <div class="modal-body p-0">
    <div class="map-responsive">
        <iframe src="https://www.google.com/maps/embed?pb=!1m27!1m12!1m3!1d1116.1146343179062!2d-68.12314552347775!3d-16.5115717753784!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m12!3e6!4m4!1s0x915f2061d125ed5b%3A0x79c073ff64b8ac2b!3m2!1d-16.5115216!2d-68.1227171!4m5!1s0x915f2061d125ed5b%3A0x79c073ff64b8ac2b!2sAv.%20Arce%20Nro.%202652%20Edificio%20MILA%20Mezzanine%202%20Of.%202%2C%20La%20Paz!3m2!1d-16.5115216!2d-68.1227171!5e0!3m2!1ses-419!2sbo!4v1761098004108!5m2!1ses-419!2sbo" allowfullscreen="" loading="lazy"></iframe>
    </div>
</div>

                </div>
            </div>
        </div>

        <hr>
        <p class="text-center mb-0">&copy; 2025 Áurea Centro Auditivo. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="global.js"></script>
<script src="script.js"></script>
</body>
</html>
<script>
// SOLUCIÓN 100% FUNCIONAL - LOS NÚMEROS SE MUEVEN
(function() {
    console.log('=== INICIANDO ANIMACIÓN DE NÚMEROS ===');
    
    // Esperar a que todo cargue
    setTimeout(function() {
        const numbers = document.querySelectorAll('.stat-number');
        
        numbers.forEach((num, index) => {
            // Obtener valores
            const target = parseInt(num.getAttribute('data-count')) || 5000;
            const hasPlus = num.getAttribute('data-plus') === 'true';
            
            // Comenzar desde 0
            let current = 0;
            num.textContent = "0" + (hasPlus ? "+" : "");
            num.style.opacity = "1";
            num.style.transition = "all 0.3s ease";
            
            // Animación con setTimeout (más confiable)
            const steps = 60;
            const increment = target / steps;
            let step = 0;
            
            function animateStep() {
                current += increment;
                step++;
                
                // Efectos visuales
                const progress = step / steps;
                const scale = 0.7 + progress * 0.6;
                const bounce = Math.sin(progress * Math.PI) * 20;
                
                num.textContent = Math.floor(current) + (hasPlus ? "+" : "");
                num.style.transform = `scale(${scale}) translateY(${-bounce}px)`;
                num.style.color = `hsl(${progress * 120}, 80%, 50%)`;
                
                if (step < steps) {
                    setTimeout(animateStep, 30);
                } else {
                    // Efecto final
                    num.textContent = target + (hasPlus ? "+" : "");
                    num.style.transform = 'scale(1.3)';
                    num.style.color = '#ff0000';
                    
                    setTimeout(() => {
                        num.style.transform = 'scale(1)';
                        num.style.color = '#8B2C2C';
                        num.style.boxShadow = '0 0 20px gold';
                    }, 200);
                    
                    setTimeout(() => {
                        num.style.boxShadow = 'none';
                    }, 1000);
                    
                    console.log(` Número ${index + 1} animado: ${target}`);
                }
            }
            
            // Iniciar con retardo escalonado
            setTimeout(() => {
                animateStep();
                const status = document.getElementById('animation-status');
                if (status) {
                    status.style.display = 'block';
                }
            }, index * 800);
        });
    }, 1000);
})();
</script>
