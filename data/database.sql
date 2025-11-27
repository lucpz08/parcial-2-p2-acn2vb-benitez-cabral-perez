-- Base de datos para GOTY 2025
CREATE DATABASE IF NOT EXISTS goty_2025;
USE goty_2025;

-- Tabla de juegos
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    imagen VARCHAR(500) DEFAULT 'assets/images/placeholder.jpg',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comentario TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos iniciales
INSERT INTO items (id, titulo, categoria, descripcion, imagen) VALUES
(1, 'Astro Bot', 'Plataformas', 'Un encantador juego de plataformas en 3D con creatividad y humor.', 'assets/images/astro.jpg'),
(4, 'Elden Ring: Shadow of the Erdtree', 'Acción/RPG', 'Expansión del aclamado Elden Ring que amplía la historia y los desafíos.', 'assets/images/eldenring.jpg'),
(6, 'Metaphor: ReFantazio', 'RPG', 'Nuevo JRPG de los creadores de Persona, con un mundo de fantasía único.', 'assets/images/metaphor.jpg'),
(7, 'Stellar Blade', 'Acción/Aventura', 'Juego de acción futurista con combates estilizados y narrativa intensa.', 'assets/images/stellarblade.jpg'),
(8, 'Like a Dragon: Infinite Wealth', 'RPG', 'Nueva entrega de la saga Yakuza con historia cargada de humor y drama.', 'assets/images/likeadragon.jpg'),
(12, 'Black Myth: Wukong', 'RPG', 'Un RPG de acción ambientado en el mundo de la mitología china, basado en la novela clásica Viaje al Oeste. El juego te pone en la piel del "Predestinado", un monje que emprende un viaje lleno de peligros para descubrir la verdad detrás de una antigua leyenda.', 'assets/images/uploads/game_68fc2fc5e5b812.88811489.jpg'),
(13, 'Final fantasy vii Rebirth', 'Acción/RPG', 'Es el segundo juego de la trilogía de remake de Final Fantasy VII, que continúa la historia de Cloud y sus compañeros tras escapar de Midgar en busca de Sefirot.', 'assets/images/uploads/game_68fc3013026022.26682839.jpg'),
(14, 'Balatro', 'Estrategia', 'Es un videojuego de construcción de mazos roguelike inspirado en el póquer, donde los jugadores deben crear manos de póquer combinándolas con comodines y efectos para ganar puntuaciones.', 'assets/images/uploads/balatro.jpg');

-- Insertar reviews iniciales
INSERT INTO reviews (game_id, nombre, rating, comentario, fecha) VALUES
(1, 'nico', 2, 'no me gusto', '2025-10-04 03:45:55'),
(4, 'Lucas Pérez', 5, 'JUEGAZOOOOOOOOOOOOO', '2025-10-04 05:43:09'),
(4, 'hater', 1, 'no me gusta', '2025-10-04 05:43:24');