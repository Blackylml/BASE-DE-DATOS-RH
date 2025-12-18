# Sistema DBRH - Base de Datos de Recursos Humanos

Un sistema web completo para gestión de empleados con campos dinámicos, desarrollado con PHP, MySQL y Tailwind CSS.

## Características Principales

### 🔍 Búsqueda Avanzada
- Búsqueda por número de ficha
- Búsqueda por nombre
- Filtrado por departamento
- Resultados en tiempo real con AJAX

### 👥 Gestión de Empleados
- Registro completo de información personal y laboral
- 30+ campos estándar (ficha, nombre, CURP, RFC, salarios, etc.)
- Soporte para fotos de empleados
- Visualización detallada de información

### ⚙️ Campos Adicionales Dinámicos
- Creación de campos personalizados por el administrador
- Tipos de campo soportados:
  - Texto simple
  - Números
  - Fechas
  - Área de texto
  - Lista desplegable
  - Múltiples valores
  - Archivos adjuntos
- Activación/desactivación de campos
- Opciones configurables para listas

### 📊 Importación desde Excel
- Importación masiva desde archivos .xls y .xlsx
- Mapeo automático de columnas
- Validación de datos
- Reporte detallado de errores
- Prevención de duplicados por ficha

### 🎨 Interfaz Moderna
- Diseño empresarial con Tailwind CSS
- Totalmente responsive
- Iconos Font Awesome
- Animaciones suaves con Alpine.js

## Instalación

### Requisitos Previos
- XAMPP, WAMP o servidor web con PHP 7.4+
- MySQL 5.7+ o MariaDB
- Navegador web moderno

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   cd DBRH
   ```

2. **Configurar la base de datos**
   - Abrir phpMyAdmin o cliente MySQL
   - Ejecutar el script `database.sql` para crear la estructura
   - Modificar `config/database.php` si es necesario:
     ```php
     private $host = "localhost";
     private $database_name = "dbrh_empleados";
     private $username = "root";
     private $password = "";
     ```

3. **Configurar permisos de directorio**
   - Asegurar que `uploads/` tenga permisos de escritura
   - En Linux/Mac: `chmod 777 uploads/`

4. **Acceder al sistema**
   - Navegar a `http://localhost/ruta_del_proyecto/`
   - Para administración: `http://localhost/ruta_del_proyecto/admin.php`

## Estructura del Proyecto

```
DBRH/
├── api/
│   ├── empleados.php     # API para gestión de empleados
│   ├── campos.php        # API para campos adicionales
│   ├── upload.php        # API para subida de archivos
│   └── import.php        # API para importación Excel
├── config/
│   ├── database.php      # Configuración de BD
│   └── config.php        # Configuraciones generales
├── uploads/
│   ├── fotos/           # Fotos de empleados
│   └── archivos/        # Archivos adjuntos
├── index.php            # Página principal de búsqueda
├── admin.php            # Panel de administración
├── database.sql         # Script de creación de BD
└── README.md
```

## Uso del Sistema

### Búsqueda de Empleados
1. Acceder a la página principal (`index.php`)
2. Ingresar criterios de búsqueda:
   - Número de ficha
   - Nombre del empleado
   - Departamento
3. Los resultados aparecen automáticamente
4. Hacer clic en "Ver más detalles" para información completa

### Administración de Empleados
1. Ir a `admin.php`
2. Pestaña "Empleados":
   - **Agregar**: Botón "Agregar Empleado"
   - **Editar**: Icono de lápiz en la tabla
   - **Eliminar**: Icono de basura
3. Completar formulario con información requerida
4. Los campos adicionales aparecen automáticamente

### Gestión de Campos Adicionales
1. En `admin.php`, pestaña "Campos Adicionales"
2. **Crear nuevo campo**:
   - Hacer clic en "Agregar Campo"
   - Especificar nombre y tipo
   - Para listas, agregar opciones
3. **Gestionar campos existentes**:
   - Editar: Modificar configuración
   - Pausar/Activar: Controlar visibilidad
   - Eliminar: Solo si no está en uso

### Importación desde Excel
1. Preparar archivo Excel con las siguientes columnas:
   ```
   FICHA | NOMBRE | ESCOLARIDAD | SEXO | DIRECCION | AÑO NACIMIENTO
   EDAD | CUMPLEAÑOS | AREA | DEPARTAMENTO | PUESTO | MENSUAL CON BD
   MENSUAL SIN BD | SALARIO REAL EXCEL | BD | SUELDO REAL MAS BD
   CATEGORIAS | SUELDO MICROSIP | S.D. | SDI | JEFE DIRECTO | DIA
   MES | AÑO | NOMINA | CURP | RFC | IMSS | NUMERO | CONTACTO
   REGISTRO PATRONAL
   ```
   
   **Nota importante:** Las columnas DIA, MES y AÑO se combinan automáticamente para formar la fecha de ingreso.

2. En la página principal, hacer clic en "Importar Excel"
3. Seleccionar archivo y hacer clic en "Importar"
4. Revisar reporte de importación

## Tipos de Campos Adicionales

### Texto Simple
Para información textual corta (nombres, códigos, etc.)

### Número
Para valores numéricos (cantidades, scores, etc.)

### Fecha
Para fechas específicas con selector de calendario

### Área de Texto
Para información extensa (comentarios, observaciones)

### Lista Desplegable
Para selección única entre opciones predefinidas
- Ejemplo: Estado civil, tipo de sangre

### Múltiples Valores
Para campos que pueden tener varios valores
- Ejemplo: Hijos del trabajador, certificaciones

### Archivo
Para adjuntar documentos (PDFs, imágenes, etc.)
- Ejemplo: Recibos de nómina, contratos

## Base de Datos

### Tablas Principales

**empleados**
- Información básica del empleado
- Campos estándar de RH
- Referencia a foto

**tipos_campo**
- Definición de campos adicionales
- Configuración y opciones

**empleado_campos_adicionales**
- Valores de campos adicionales por empleado
- Relación many-to-many

**archivos_empleados**
- Metadatos de archivos subidos
- Referencia a archivos físicos

## Personalización

### Agregar Nuevos Campos Base
1. Modificar tabla `empleados` en `database.sql`
2. Actualizar APIs en `api/empleados.php`
3. Agregar campos al formulario en `admin.php`

### Modificar Diseño
- Los estilos usan Tailwind CSS
- Modificar clases CSS en los archivos HTML
- Personalizar colores y tipografía según marca

### Configurar Validaciones
- Modificar validaciones en `config/config.php`
- Ajustar tipos de archivo permitidos
- Cambiar tamaños máximos de archivos

## Seguridad

### Medidas Implementadas
- Validación de tipos de archivo
- Sanitización de inputs
- Prepared statements para SQL
- Prevención de XSS
- Límites de tamaño de archivo

### Recomendaciones Adicionales
- Implementar autenticación de usuarios
- Configurar HTTPS en producción
- Realizar backups regulares
- Monitorear logs de acceso

## Solución de Problemas

### Error de Conexión a BD
- Verificar credenciales en `config/database.php`
- Confirmar que el servidor MySQL esté corriendo
- Revisar permisos de usuario de BD

### Archivos No Se Suben
- Verificar permisos de directorio `uploads/`
- Revisar configuración PHP (`upload_max_filesize`, `post_max_size`)
- Confirmar que el directorio existe

### Importación Excel Falla
- Verificar formato de archivo (.xls o .xlsx)
- Confirmar que las columnas coincidan exactamente
- Revisar que no haya fichas duplicadas

## Licencia

Este proyecto es de código abierto. Puedes modificarlo y distribuirlo libremente.

## Soporte

Para reportar bugs o solicitar características:
1. Revisar la documentación
2. Verificar logs de error en el servidor
3. Crear un issue con detalles específicos

---

Desarrollado con ❤️ para mejorar la gestión de recursos humanos.