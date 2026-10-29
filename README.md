# 🏥 AUREAMED - BASE DE DATOS Y MENÚ ARREGLADOS

## 📋 RESUMEN EJECUTIVO

Se ha completado satisfactoriamente:
- ✅ **Script SQL completo** para la base de datos `aureamed`
- ✅ **Menú principal arreglado** con todos los enlaces funcionando
- ✅ **Documentación completa** para importar y usar

---

## 📁 ARCHIVOS ENTREGADOS

| Archivo | Descripción |
|---------|-------------|
| `aureamed_database.sql` | Script SQL con todas las tablas y datos iniciales |
| `INSTRUCCIONES_BASE_DATOS.md` | Guía paso a paso para importar en XAMPP |
| `test_base_datos.sql` | Script de prueba y verificación |
| `CAMBIOS_MENU.md` | Detalle de todos los cambios realizados |
| `README.md` | Este archivo |

---

## 🚀 EMPEZAR RÁPIDO (3 PASOS)

### 1️⃣ Importar la Base de Datos
- Abre: `http://localhost/phpmyadmin/`
- Ve a **Importar**
- Selecciona: `aureamed_database.sql`
- Haz clic en **Importar**

### 2️⃣ Verificar que funciona
- Abre: `http://localhost/phpmyadmin/`
- En el lado izquierdo, deberías ver `aureamed`
- Haz clic en ella y verás las tablas de la aplicación, incluidas las de audiometrías

### 3️⃣ Acceder a la aplicación
- Abre: `http://localhost/web2026/admin/index.php`
- Usuario: `admin`
- Contraseña: `admin`

---

## 📊 BASE DE DATOS - DETALLES

### Tablas del script SQL (9):

```
usuarios ..................... Cuentas de administradores, recepcionistas, audiólogos
pacientes .................... Datos de los pacientes
servicios .................... Servicios disponibles (audiología, etc.)
reservas ..................... Citas/reservas de pacientes
empresa ...................... Información de la clínica
mensajes_contacto ............ Mensajes del formulario web
reservas_rapidas ............. Reservas rápidas desde sitio web
audiometrias ................. Evaluaciones y recomendaciones por paciente
audiometria_imagenes ......... Fotos asociadas a cada audiometría
```

### Características:

- ✅ **Codificación UTF-8** - Soporta acentos y caracteres especiales
- ✅ **Relaciones** - Foreign Keys para integridad de datos
- ✅ **Índices** - Búsquedas rápidas
- ✅ **Datos iniciales** - Usuario admin, empresa, servicios
- ✅ **Timestamps automáticos** - Fechas de creación/modificación

### Usuario Admin:
```
Usuario: admin
Contraseña: admin
Rol: Administrador
```

---

## 🔧 MENÚ - CORRECCIONES REALIZADAS

### Cambios (En archivo: `index.php`):

1. ✅ **Logo** - Ahora enlaza a la página de inicio
2. ✅ **Rutas relativas** - Funcionan desde cualquier carpeta
3. ✅ **Enlaces consistentes** - Todos con formato `./ruta/archivo.html`
4. ✅ **Administrador** - Enlace correcto a `../../admin/index.php`
5. ✅ **Nombres claros** - "Pérdida Auditiva" no "Perdida"
6. ✅ **Estilos uniformes** - Todos los menús con clases iguales

---

## 📚 DOCUMENTACIÓN COMPLETA

Para importar la base de datos:
→ **Lee:** `INSTRUCCIONES_BASE_DATOS.md`

Para ver todos los cambios del menú:
→ **Lee:** `CAMBIOS_MENU.md`

Para verificar la BD está correcta:
→ **Ejecuta:** `test_base_datos.sql` en phpMyAdmin

---

## ⚙️ CONFIGURACIÓN DE LA APLICACIÓN

### Sitio público de demostración en GitHub Pages

El sitio informativo se publica como una versión estática en `https://ae105455.github.io/presentar/`. GitHub Pages no ejecuta PHP ni incluye el panel administrativo. La reserva de citas se reemplaza por un mensaje de demostración con contacto por WhatsApp; el administrador y las operaciones con base de datos requieren un hosting PHP/MySQL.

### URL de los códigos QR de pacientes

Al generar un QR, la aplicación usa automáticamente el dominio y la ruta con que se abrió. Para un servidor con una dirección pública distinta (por ejemplo, detrás de un proxy), se puede definir la variable de entorno `AUREAMED_QR_BASE_URL` con la URL completa de la carpeta `admin`, sin `/` al final:

```text
AUREAMED_QR_BASE_URL=https://dominio-del-sitio/presentar/admin
```

Si no se define, no se usa un dominio fijo: la URL se detecta desde la solicitud actual. En XAMPP, `localhost` solo funciona en la misma computadora; para escanear el QR desde un teléfono se necesita acceder al sitio por la IP de la computadora en la red local o por el dominio del servidor publicado. GitHub almacena el código, pero no ejecuta esta aplicación PHP.

### Conexión de la base de datos en el hosting

Las páginas administrativas comparten la conexión definida en `admin/db_connection.php`. En XAMPP se usa `admin/db_config.example.php`. En el hosting, crea `admin/db_config.local.php` a partir del ejemplo y coloca allí el hostname, usuario, contraseña, nombre de base de datos y puerto asignados por el proveedor. Ese archivo está excluido de Git para no publicar credenciales.

### Conexión a BD:
```php
$host = 'localhost';      // Servidor XAMPP
$user = 'root';           // Usuario MySQL default
$password = '';           // Sin contraseña (default XAMPP)
$database = 'aureamed';   // Base de datos
```

### Archivos de conexión:
- `admin/index.php`
- `admin/login.php`
- `admin/panel.php`
- Todos los archivos PHP en `admin/`
- Archivos en `aurea--main/docs/`

---

## ✅ CHECKLIST DE VERIFICACIÓN

Después de importar, verifica que:

- [ ] La BD `aureamed` existe en phpMyAdmin
- [ ] Están creadas las tablas de pacientes y audiometrías
- [ ] Tabla `usuarios` tiene 1 registro (admin)
- [ ] Tabla `servicios` tiene 5 registros
- [ ] Tabla `empresa` tiene 1 registro
- [ ] Puedo hacer login con admin/admin
- [ ] El menú tiene todos los enlaces activos
- [ ] Los links del menú van a las páginas correctas

---

## 🎯 ESTRUCTURA DEL PROYECTO

```
c:\xampp\htdocs\web2026\
│
├── 📄 aureamed_database.sql ................. Script SQL ⭐
├── 📄 INSTRUCCIONES_BASE_DATOS.md ........... Guía de importación
├── 📄 test_base_datos.sql .................. Script de pruebas
├── 📄 CAMBIOS_MENU.md ....................... Detalle de cambios
├── 📄 README.md ............................ Este archivo
│
├── admin/ .................................. Aplicación administrador
│   ├── index.php ........................... Login
│   ├── panel.php ........................... Dashboard
│   ├── usuarios.php ........................ Gestión de usuarios
│   ├── pacientes.php ....................... Gestión de pacientes
│   ├── servicios.php ....................... Gestión de servicios
│   ├── reservas.php ........................ Gestión de reservas
│   └── ... (más archivos PHP)
│
└── aurea--main/docs/ ....................... Sitio web principal
    ├── index.php ........................... Página de inicio (MENÚ ARREGLADO)
    ├── nosotros/ ........................... Página "Nosotros"
    ├── servicios/ .......................... Página de servicios
    ├── productos/ .......................... Página de productos
    └── ... (más páginas)
```

---

## 🔐 SEGURIDAD - RECOMENDACIONES

Para usar en producción:

1. **Cambiar contraseña del admin**
   - No usar la contraseña por defecto "admin"
   - Ir a: Panel → Perfil → Cambiar contraseña

2. **Cambiar credenciales de BD**
   - Crear usuario MySQL específico para esta aplicación
   - No usar "root" directamente

3. **Usar HTTPS**
   - Implementar certificado SSL en producción

4. **Validación de entrada**
   - El código actual usa algunos prepared statements
   - Revisar y mejorar todas las validaciones

5. **Copias de seguridad**
   - Hacer backup regular de la base de datos

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### "No puedo conectar a la BD"
- Verifica que MySQL está corriendo (verde en XAMPP)
- Verifica las credenciales en el código PHP

### "Error al importar SQL"
- Usa codificación **utf8mb4**
- Verifica que no hay líneas cortadas

### "Los acentos se ven mal"
- La codificación debe ser **utf8mb4_unicode_ci**

### "Los enlaces del menú no funcionan"
- Verifica que estés en la carpeta correcta
- Las rutas son relativas, deben funcionar desde cualquier lugar

---

## 📞 INFORMACIÓN DE CONTACTO

Datos de la empresa (en la BD):
- **Nombre:** Centro Auditivo AureaMed
- **Email:** info@aureamed.com
- **Teléfono:** +591-7-1234567
- **Dirección:** Av. 16 de Julio, Edificio Central, La Paz - Bolivia
- **Horario:** Lunes a Viernes: 08:00 - 18:00 | Sábados: 09:00 - 13:00

*Puedes editar estos datos desde el panel admin*

---

## 📋 PRÓXIMAS ACCIONES

1. ✅ **Completado:** Importar base de datos
2. ✅ **Completado:** Arreglar menú
3. 🔄 **Siguiente:** Crear más usuarios (recepcionista, audiólogo)
4. 🔄 **Siguiente:** Agregar pacientes
5. 🔄 **Siguiente:** Crear reservas de prueba
6. 🔄 **Siguiente:** Personalizar información de empresa

---

## 📌 NOTAS IMPORTANTES

- El script SQL está optimizado para XAMPP
- Todas las tablas usan InnoDB (recomendado)
- Los índices mejoran el rendimiento de búsquedas
- Las Foreign Keys garantizan integridad de datos
- Los Timestamps son automáticos

---

## 🎉 ¡LISTO PARA USAR!

Todo está preparado. Solo falta:

1. Abre XAMPP → Start Apache y MySQL
2. Abre phpMyAdmin → Importa `aureamed_database.sql`
3. Abre `http://localhost/web2026/admin/index.php`
4. Usa credenciales: admin / admin
5. ¡Empieza a usar la aplicación!

---

**Fecha:** 2026-06-18  
**Sistema:** AureaMed v1.0  
**Estado:** ✅ Listo para producción (después de cambiar contraseñas)

---

## 📖 LECTURA RECOMENDADA

Para más información, lee en este orden:

1. **INSTRUCCIONES_BASE_DATOS.md** - Cómo importar
2. **CAMBIOS_MENU.md** - Qué cambió en el menú
3. **test_base_datos.sql** - Cómo verificar

---

¿Preguntas? Revisa la documentación incluida.

**¡Que disfrutes usando AureaMed!** 🏥
