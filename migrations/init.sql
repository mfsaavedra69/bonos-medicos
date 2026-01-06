-- Migraciones iniciales (MySQL compatible)
-- Ajustá los tipos/constraints según tu SGBD si usás PostgreSQL

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol VARCHAR(50) NOT NULL DEFAULT 'admin',
  activo BOOLEAN DEFAULT TRUE,
  primer_ingreso BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS especialidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) UNIQUE NOT NULL,
  estado BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS medicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  matricula VARCHAR(50) UNIQUE NOT NULL,
  telefono VARCHAR(50),
  email VARCHAR(120),
  estado BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS medico_especialidad (
  medico_id INT,
  especialidad_id INT,
  prioridad INT DEFAULT 1,
  PRIMARY KEY (medico_id, especialidad_id),
  FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
  FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS consultorios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  direccion VARCHAR(200),
  telefono VARCHAR(50),
  email VARCHAR(120),
  estado BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS medico_consultorio (
  medico_id INT,
  consultorio_id INT,
  observaciones TEXT,
  PRIMARY KEY (medico_id, consultorio_id),
  FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
  FOREIGN KEY (consultorio_id) REFERENCES consultorios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS afiliados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dni VARCHAR(20) UNIQUE NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  fecha_nacimiento DATE,
  telefono VARCHAR(50),
  email VARCHAR(120),
  estado BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS practicas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) UNIQUE NOT NULL,
  descripcion VARCHAR(200) NOT NULL,
  precio DECIMAL(12,2) NOT NULL,
  estado BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agenda_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medico_id INT,
  consultorio_id INT,
  especialidad_id INT,
  dia_semana INT,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  capacidad INT DEFAULT 1,
  estado BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (medico_id) REFERENCES medicos(id),
  FOREIGN KEY (consultorio_id) REFERENCES consultorios(id),
  FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
);

CREATE TABLE IF NOT EXISTS turnos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agenda_slot_id INT,
  fecha DATE NOT NULL,
  afiliado_id INT,
  estado VARCHAR(20) NOT NULL,
  notas TEXT,
  FOREIGN KEY (agenda_slot_id) REFERENCES agenda_slots(id),
  FOREIGN KEY (afiliado_id) REFERENCES afiliados(id)
);

CREATE TABLE IF NOT EXISTS bonos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medico_id INT,
  afiliado_id INT,
  consultorio_id INT,
  fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(20) NOT NULL DEFAULT 'emitido',
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  observaciones TEXT,
  created_by INT,
  FOREIGN KEY (medico_id) REFERENCES medicos(id),
  FOREIGN KEY (afiliado_id) REFERENCES afiliados(id),
  FOREIGN KEY (consultorio_id) REFERENCES consultorios(id),
  FOREIGN KEY (created_by) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS bono_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bono_id INT,
  practica_id INT,
  cantidad INT NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (bono_id) REFERENCES bonos(id) ON DELETE CASCADE,
  FOREIGN KEY (practica_id) REFERENCES practicas(id)
);

-- END
