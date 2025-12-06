<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

// Permitir CORS para desarrollo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'get_items':
            // Obtener todos los items con filtros opcionales
            $search = $_GET['q'] ?? '';
            $categoria = $_GET['categoria'] ?? '';
            
            $sql = "SELECT * FROM items WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND titulo LIKE :search";
                $params[':search'] = "%$search%";
            }
            
            if ($categoria) {
                $sql .= " AND categoria = :categoria";
                $params[':categoria'] = $categoria;
            }
            
            $sql .= " ORDER BY id DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $items,
                'count' => count($items)
            ]);
            break;
            
        case 'get_item':
            // Obtener un item específico
            $id = intval($_GET['id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch();
            
            if ($item) {
                echo json_encode([
                    'success' => true,
                    'data' => $item
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Item no encontrado'
                ]);
            }
            break;
            
        case 'get_categories':
            // Obtener todas las categorías
            $stmt = $pdo->query("SELECT DISTINCT categoria FROM items ORDER BY categoria");
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'data' => $categorias
            ]);
            break;
            
        case 'add_item':
            // Agregar nuevo item (POST)
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $titulo = trim($data['titulo'] ?? '');
            $categoria = trim($data['categoria'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $imagen = trim($data['imagen'] ?? 'assets/images/placeholder.jpg');
            
            if (empty($titulo) || empty($categoria) || empty($descripcion)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Todos los campos son obligatorios'
                ]);
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO items (titulo, categoria, descripcion, imagen) VALUES (:titulo, :categoria, :descripcion, :imagen)");
            $stmt->execute([
                ':titulo' => $titulo,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':imagen' => $imagen
            ]);
            
            $newId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Juego agregado con éxito',
                'id' => $newId
            ]);
            break;
            
        case 'delete_item':
            // Eliminar item
            if ($method !== 'POST' && $method !== 'DELETE') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                break;
            }
            
            // Obtener imagen para eliminarla
            $stmt = $pdo->prepare("SELECT imagen FROM items WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch();
            
            if ($item) {
                // Eliminar de la base de datos
                $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
                $stmt->execute([':id' => $id]);
                
                // Eliminar imagen si no es placeholder
                if ($item['imagen'] !== 'assets/images/placeholder.jpg' && file_exists(__DIR__ . '/' . $item['imagen'])) {
                    unlink(__DIR__ . '/' . $item['imagen']);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Juego eliminado correctamente'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Item no encontrado'
                ]);
            }
            break;
            
        case 'get_reviews':
            // Obtener reviews de un juego
            $gameId = intval($_GET['game_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE game_id = :game_id ORDER BY fecha DESC");
            $stmt->execute([':game_id' => $gameId]);
            $reviews = $stmt->fetchAll();
            
            // Calcular promedio
            $totalRating = 0;
            $countRating = count($reviews);
            
            foreach ($reviews as $review) {
                $totalRating += $review['rating'];
            }
            
            $averageRating = $countRating > 0 ? round($totalRating / $countRating, 1) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => $reviews,
                'count' => $countRating,
                'average' => $averageRating
            ]);
            break;
            
        case 'add_review':
            // Agregar nueva review
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $gameId = intval($data['game_id'] ?? 0);
            $nombre = trim($data['nombre'] ?? '');
            $rating = intval($data['rating'] ?? 0);
            $comentario = trim($data['comentario'] ?? '');
            
            if ($gameId <= 0 || empty($nombre) || $rating < 1 || $rating > 5 || empty($comentario)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos inválidos'
                ]);
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO reviews (game_id, nombre, rating, comentario) VALUES (:game_id, :nombre, :rating, :comentario)");
            $stmt->execute([
                ':game_id' => $gameId,
                ':nombre' => $nombre,
                ':rating' => $rating,
                ':comentario' => $comentario
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => '¡Gracias por tu valoración!'
            ]);
            break;
            
        case 'delete_review':
            // Eliminar review
            if ($method !== 'POST' && $method !== 'DELETE') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                break;
            }
            
            $reviewId = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
            $stmt->execute([':id' => $reviewId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Review eliminada'
            ]);
            break;

        case 'get_gameplay':
            $youtubeApiKey = getenv('YOUTUBE_API_KEY');
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT titulo FROM items WHERE id = :id");
            $stmt ->execute ([':id' => $id]);
            $item = $stmt->fetch();

            if (!$item || !$youtubeApiKey) {
                http_response_code(404);
                echo json_encode ([
                    'success' => false,
                    'message' => 'Error al recolectar datos de Youtube.'
                ]);
                break;
            }

            $tituloJuego = $item ['titulo'];
            $query = urlencode($tituloJuego . 'gameplay trailer');
            $youtubeApiUrl = 'https://www.googleapis.com/youtube/v3/search';

            $params = [
                'part'          => 'snippet',
                'q'             => $query,
                'key'           => $youtubeApiKey,
                'type'          => 'video',
                'maxResults'    => 1,
            ];

            $apiUrl = $youtubeApiUrl . '?' . http_build_query($params);
            $response = @file_get_contents($apiUrl);

            if ($response === false) {
                http_response_code(503);
                echo json_encode (['succes' => false, 'message' => 'Error al conectar con Youtube'.]);
                break;
            }

            $data = json_decode($response, true);
            $videoId = $data ['items'] [0] ['id'] ['videoId'] ?? null;
            $videoTitle = $data ['items'] [0] ['snippet'] ['title'] ?? 'Video no encontrado';

            if ($videoId) {
                echo json_encode ([
                    'success'    => true,
                    'videoId'    => $videoId,
                    'videoTitle' => $videoTitle
                ]);
            } else {
                echo json_encode ([
                    'success' => false,
                    'message' => 'No se encontro video para ' . $tituloJuego
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>