CREATE DATABASE evaluaciones_db;
USE evaluaciones_db;

CREATE TABLE estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT,
    puntaje INT NOT NULL,
    fecha DATETIME NOT NULL,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id)
);

CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resultado_id INT,
    pregunta VARCHAR(255),
    respuesta VARCHAR(255),
    es_correcta TINYINT(1),
    FOREIGN KEY (resultado_id) REFERENCES resultados(id)
);
