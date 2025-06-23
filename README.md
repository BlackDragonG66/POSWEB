# POS Web PHP/MySQL

Sistema de Punto de Venta (POS) Web desarrollado en PHP y MySQL, ideal para pequeños negocios, tiendas y comercios. Permite gestionar productos, ventas, inventario, usuarios y reportes, con soporte para modo oscuro y exportación/importación de datos.

## Características principales

- **Gestión de usuarios:** Alta, baja, cambio de contraseña, activar/desactivar usuarios.
- **Gestión de productos:** Registro, edición, carga de imagen, código de barras.
- **Ventas:** Carrito, control de stock, cobro, cálculo de cambio, validaciones.
- **Inventario:** Listado editable, exportación e importación CSV, impresión PDF, actualización de imágenes.
- **Reportes:** Filtros por fecha y usuario, reporte diario, exportación a PDF y CSV.
- **Control de acceso:** Solo usuarios activos pueden iniciar sesión.
- **Modo oscuro global:** Botón destacado, persistencia de preferencia.
- **Interfaz moderna:** Bootstrap 5, tablas y tarjetas responsivas.

## Instalación

1. **Clona el repositorio:**
   ```
   git clone https://github.com/BlackDragonG66/POSWEB.git
   ```
2. **Configura la base de datos:**
   - Crea una base de datos MySQL llamada `posweb`.
   - Importa el archivo `basedatos.sql` incluido en el proyecto.
3. **Configura la conexión:**
   - Edita `conexion.php` si tu usuario, contraseña o puerto de MySQL son diferentes.
4. **Coloca el proyecto en tu servidor web local (ej: XAMPP en `htdocs`).**
5. **Accede desde tu navegador:**
   - Si es la primera vez, el sistema te guiará para crear el usuario administrador.

## Primer acceso
- Usuario: `admin`
- Contraseña: `eldenring`

## Importar y exportar inventario
- Puedes descargar el inventario en CSV, editarlo y volver a cargarlo.
- Es obligatorio que el campo "Código de Barras" esté completo para cada producto.
- Al cargar, los productos existentes se actualizan por código de barras y los nuevos se agregan.

## Actualización de imágenes
- Desde el inventario, haz clic en "Actualizar Imagen" para abrir un modal, ver la imagen actual y subir una nueva.

## Seguridad
- Las contraseñas se almacenan con MD5 (recomendado cambiar a un hash más seguro en producción).
- Todas las acciones importantes requieren sesión activa.

## Dependencias
- PHP >= 7.4
- MySQL
- Bootstrap 5 (CDN)
- FPDF (incluido en `/fpdf`)

## Notas
- Desarrollado por diversion.
- Inspirado en perder unas partidas de Elden Ring (Nightreign).
- Si te funcionó, puedes regalarme una estrella aquí o, si quieres, invítame un café: https://paypal.me/elblackyg66

---
¡Disfruta tu sistema POS Web! Si tienes dudas o sugerencias, abre un issue o un pull request.