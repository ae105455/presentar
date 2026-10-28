# 📚 INSTRUCCIONES PARA IMPORTAR LA BASE DE DATOS - XAMPP

## 📋 Resumen
Este documento contiene las instrucciones paso a paso para importar la base de datos `aureamed` en XAMPP.

---

## 🔧 OPCIÓN 1: Importar desde phpMyAdmin (RECOMENDADO)

### Paso 1: Iniciar XAMPP
1. Abre XAMPP Control Panel
2. Haz clic en **"Start"** para Apache y MySQL
3. Espera a que cambien el color de rojo a verde (activos)

### Paso 2: Abrir phpMyAdmin
1. Abre tu navegador (Chrome, Firefox, Edge)
2. Ve a: `http://localhost/phpmyadmin/`
3. Deberías ver la interfaz de phpMyAdmin

### Paso 3: Importar la Base de Datos
1. En phpMyAdmin, en el lado izquierdo, haz clic en **"Nueva"** o busca el botón de importar
2. En la sección **"Importar"**, haz clic en **"Seleccionar archivo"**
3. Busca y selecciona el archivo: `aureamed_database.sql`
4. Haz clic en **"Abrir"**
5. Verifica que en la parte inferior esté seleccionado:
   - Formato: `SQL`
   - Codificación: `utf8mb4`
6. Haz clic en el botón **"Importar"** (usualmente azul, abajo)

### Paso 4: Verificar Importación
- Deberías ver un mensaje verde: ✅ "Importación realizada exitosamente"
- En el lado izquierdo, deberías ver la base de datos **"aureamed"** en la lista
- Haz clic en **"aureamed"** y verás todas las tablas creadas

---

## 🔧 OPCIÓN 2: Importar desde Línea de Comandos (CMD)

### Paso 1: Abre CMD (Símbolo del Sistema)
1. Presiona `Windows + R`
2. Escribe `cmd` y presiona Enter

### Paso 2: Navega a la carpeta de XAMPP MySQL
```bash
cd C:\xampp\mysql\bin
```

### Paso 3: Ejecuta el comando de importación
Reemplaza la ruta según donde tengas el archivo SQL:

```bash
mysql -u root -p aureamed < "C:\xampp\htdocs\web2026\aureamed_database.sql"
```

**Nota:** Cuando te pida contraseña, presiona Enter (por defecto XAMPP no tiene contraseña)

### Paso 4: Verifica que se completó sin errores

---

## 📝 INFORMACIÓN DE LA BASE DE DATOS

### **Nombre de BD:** `aureamed`

### **Tablas Creadas:**
1. **usuarios** - Administradores, recepcionistas, audiólogos
2. **pacientes** - Información de los pacientes
3. **servicios** - Servicios disponibles
4. **reservas** - Reservas de pacientes
5. **empresa** - Información de la clínica
6. **mensajes_contacto** - Mensajes del formulario de contacto
7. **reservas_rapidas** - Reservas rápidas desde el sitio web

### **Usuario por Defecto (Admin):**
- **Usuario:** `admin`
- **Contraseña:** `admin`
- **Rol:** Administrador

### **Credenciales XAMPP:**
- **Host:** `localhost`
- **Usuario:** `root`
- **Contraseña:** (vacío/ninguna)
- **Base de Datos:** `aureamed`

---

## ✅ VERIFICACIÓN: Comprobar que todo está correcto

### Desde phpMyAdmin:
1. Abre phpMyAdmin: `http://localhost/phpmyadmin/`
2. Haz clic en **"aureamed"** en el lado izquierdo
3. Deberías ver 9 tablas creadas por el script:
   - ✓ usuarios
   - ✓ pacientes
   - ✓ servicios
   - ✓ reservas
   - ✓ empresa
   - ✓ mensajes_contacto
   - ✓ reservas_rapidas
   - ✓ audiometrias
   - ✓ audiometria_imagenes

### Desde el navegador (Prueba la aplicación):
1. Abre: `http://localhost/web2026/admin/index.php`
2. Intenta login con:
   - Usuario: `admin`
   - Contraseña: `admin`
3. Si entras al panel, ¡todo está funcionando! ✅

---

## 🚨 SOLUCIÓN DE PROBLEMAS

### ❌ "Error: Base de datos no existe"
- Asegúrate de que MySQL está corriendo (verde en XAMPP)
- Intenta crear la base de datos manualmente:
  ```sql
  CREATE DATABASE aureamed;
  ```

### ❌ "Error de conexión en la aplicación"
- Verifica que en los archivos PHP esté:
  ```php
  $conn = new mysqli('localhost', 'root', '', 'aureamed');
  ```

### ❌ "Caracteres acentos mojados (ñ, á, é, etc.)"
- Asegúrate de importar con codificación **utf8mb4**
- O ejecuta en phpMyAdmin:
  ```sql
  ALTER DATABASE aureamed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

### ❌ "No puedo acceder a localhost/phpmyadmin"
- Verifica que Apache Y MySQL estén en verde
- Intenta: `http://127.0.0.1/phpmyadmin/`
- O reinicia XAMPP

---

## 📌 ARCHIVOS IMPORTANTES

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| `aureamed_database.sql` | `c:\xampp\htdocs\web2026\` | Script SQL de la BD |
| `index.php` | `c:\xampp\htdocs\web2026\admin\` | Panel de login |
| `panel.php` | `c:\xampp\htdocs\web2026\admin\` | Dashboard principal |

---

## 🎯 PRÓXIMOS PASOS

1. ✅ Importar la base de datos (HECHO)
2. ✅ Verificar que funciona
3. 📝 Crear más usuarios si lo necesitas
4. 📋 Agregar pacientes y servicios
5. 🗓️ Empezar a hacer reservas

---

## 📞 NOTAS ADICIONALES

- La contraseña del usuario admin está en MD5: `21232f297a57a5a743894a0e4a801fc3`
- Puedes cambiarla desde el panel (Perfil)
- Las tablas tienen índices para mejor rendimiento
- Se usan Foreign Keys para integridad referencial

---

**Última actualización:** 2026-06-18  
**Autor:** Sistema AureaMed
