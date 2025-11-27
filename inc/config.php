<?php
// Configuración de la base de datos usando SQLite (archivo local)
define('DB_FILE', __DIR__ . '/../data/database.db');

// Función para obtener la conexión a la base de datos SQLite
function getConnection() {
    try {
        // Crear directorio data si no existe
        $dataDir = dirname(DB_FILE);
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        // Verificar si es la primera vez (archivo no existe)
        $isFirstTime = !file_exists(DB_FILE);
        
        // Conectar a SQLite
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Si es primera vez, crear tablas e insertar datos
        if ($isFirstTime) {
            createTables($pdo);
            insertInitialData($pdo);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función para crear las tablas
function createTables($pdo) {
    // Tabla items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            categoria TEXT NOT NULL,
            descripcion TEXT NOT NULL,
            imagen TEXT DEFAULT 'assets/images/placeholder.jpg',
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Tabla reviews
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            nombre TEXT NOT NULL,
            rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comentario TEXT NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (game_id) REFERENCES items(id) ON DELETE CASCADE
        )
    ");
}

// Función para insertar datos iniciales
function insertInitialData($pdo) {
    // Insertar juegos
    $pdo->exec("
        INSERT INTO items (id, titulo, categoria, descripcion, imagen) VALUES
        (1, 'Astro Bot', 'Plataformas', 'Un encantador juego de plataformas en 3D con creatividad y humor.', 'assets/images/astro.jpg'),
        (2, 'Elden Ring: Shadow of the Erdtree', 'Acción/RPG', 'Expansión del aclamado Elden Ring que amplía la historia y los desafíos.', 'assets/images/eldenring.jpg'),
        (3, 'Metaphor: ReFantazio', 'RPG', 'Nuevo JRPG de los creadores de Persona, con un mundo de fantasía único.', 'assets/images/metaphor.jpg'),
        (4, 'Stellar Blade', 'Acción/Aventura', 'Juego de acción futurista con combates estilizados y narrativa intensa.', 'assets/images/stellarblade.jpg'),
        (5, 'Like a Dragon: Infinite Wealth', 'RPG', 'Nueva entrega de la saga Yakuza con historia cargada de humor y drama.', 'assets/images/likeadragon.jpg'),
        (6, 'Black Myth: Wukong', 'RPG', 'Un RPG de acción ambientado en el mundo de la mitología china, basado en la novela clásica Viaje al Oeste.', 'assets/images/wukong.jpg'),
        (7, 'Final Fantasy VII Rebirth', 'Acción/RPG', 'Es el segundo juego de la trilogía de remake de Final Fantasy VII, que continúa la historia de Cloud y sus compañeros.', 'assets/images/fantasy.jpg'),
        (8, 'Balatro', 'Estrategia', 'Es un videojuego de construcción de mazos roguelike inspirado en el póquer.', 'assets/images/balatro.jpg')
    ");
    
    // Insertar reviews de ejemplo
    $pdo->exec("
        INSERT INTO reviews (game_id, nombre, rating, comentario, fecha) VALUES
        (1, 'nico', 2, 'no me gusto', '2025-10-04 03:45:55'),
        (4, 'Lucas Pérez', 5, 'JUEGAZOOOOOOOOOOOOO', '2025-10-04 05:43:09'),
        (4, 'hater', 1, 'no me gusta', '2025-10-04 05:43:24')
    ");
}
?>