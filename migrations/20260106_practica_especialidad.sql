-- Migración: tabla para relacionar prácticas con especialidades
-- Ejecutar: mysql -u <user> -p bonos < migrations/20260106_practica_especialidad.sql

CREATE TABLE IF NOT EXISTS practica_especialidad (
  practica_id INT NOT NULL,
  especialidad_id INT NOT NULL,
  PRIMARY KEY (practica_id, especialidad_id),
  FOREIGN KEY (practica_id) REFERENCES practicas(id) ON DELETE CASCADE,
  FOREIGN KEY (especialidad_id) REFERENCES especialidades(id) ON DELETE CASCADE
);
