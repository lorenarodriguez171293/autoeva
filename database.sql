-- database.sql
CREATE DATABASE IF NOT EXISTS evaluaciones_db CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE evaluaciones_db;

CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    puntaje INT NOT NULL,
    fecha DATETIME NOT NULL,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resultado_id INT NOT NULL,
    pregunta VARCHAR(255) NOT NULL,
    respuesta VARCHAR(255) NOT NULL,
    es_correcta TINYINT(1) NOT NULL,
    FOREIGN KEY (resultado_id) REFERENCES resultados(id) ON DELETE CASCADE
);
