-- Base de datos para sistema de empleados DBRH
CREATE DATABASE IF NOT EXISTS dbrh_empleados;
USE dbrh_empleados;

-- Tabla principal de empleados
CREATE TABLE empleados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ficha VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    escolaridad VARCHAR(100),
    sexo ENUM('M', 'F', 'Otro'),
    direccion TEXT,
    ano_nacimiento YEAR,
    edad INT,
    cumpleanos DATE,
    area VARCHAR(100),
    departamento VARCHAR(100),
    puesto VARCHAR(100),
    mensual_con_bd DECIMAL(10,2),
    mensual_sin_bd DECIMAL(10,2),
    salario_real_excel DECIMAL(10,2),
    bd DECIMAL(10,2),
    sueldo_real_mas_bd DECIMAL(10,2),
    categorias VARCHAR(100),
    sueldo_microsip DECIMAL(10,2),
    sd DECIMAL(10,2),
    sdi DECIMAL(10,2),
    jefe_directo VARCHAR(255),
    dia_ingreso INT,
    mes_ingreso INT,
    ano_ingreso YEAR,
    fecha_ingreso DATE,
    nomina VARCHAR(50),
    curp VARCHAR(18),
    rfc VARCHAR(13),
    imss VARCHAR(20),
    numero VARCHAR(20),
    contacto VARCHAR(20),
    registro_patronal VARCHAR(50),
    foto VARCHAR(255) DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla para tipos de campos adicionales
CREATE TABLE tipos_campo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('texto', 'numero', 'fecha', 'archivo', 'select', 'textarea', 'multiple') NOT NULL,
    opciones JSON DEFAULT NULL, -- Para campos select y multiple
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para valores de campos adicionales
CREATE TABLE empleado_campos_adicionales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id INT NOT NULL,
    tipo_campo_id INT NOT NULL,
    valor TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_campo_id) REFERENCES tipos_campo(id) ON DELETE CASCADE,
    INDEX idx_empleado_campo (empleado_id, tipo_campo_id)
);

-- Tabla para archivos adjuntos
CREATE TABLE archivos_empleados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id INT NOT NULL,
    tipo_campo_id INT DEFAULT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo_mime VARCHAR(100),
    tamano INT,
    ruta VARCHAR(500) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_campo_id) REFERENCES tipos_campo(id) ON DELETE SET NULL
);

-- Tabla para usuarios administradores
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(255),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario admin por defecto (password: admin123)
INSERT INTO usuarios (usuario, password, nombre_completo) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema');

-- Insertar algunos tipos de campo por defecto
INSERT INTO tipos_campo (nombre, tipo, opciones) VALUES 
('Hijos del Trabajador', 'multiple', '[]'),
('Recibo de Nómina', 'archivo', NULL),
('Comentarios Adicionales', 'textarea', NULL),
('Estado Civil', 'select', '["Soltero", "Casado", "Divorciado", "Viudo", "Unión Libre"]'),
('Tipo Sangre', 'select', '["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"]');