<?php
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';

// Parámetros de consulta GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tema = isset($_GET['tema']) && $_GET['tema'] === 'oscuro' ? 'oscuro' : 'claro';

// Conectar a la base de datos
$pdo = getConnection();

// Obtener item
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    header('HTTP/1.0 404 Not Found');
    echo 'Ítem no encontrado.';
    exit;
}

// Obtener reviews del juego
$stmtReviews = $pdo->prepare("SELECT * FROM reviews WHERE game_id = :game_id ORDER BY fecha DESC");
$stmtReviews->execute([':game_id' => $id]);
$gameReviews = $stmtReviews->fetchAll();

// Calcular promedio de valoraciones
$totalRating = 0;
$countRating = count($gameReviews);

foreach ($gameReviews as $review) {
    $totalRating += $review['rating'];
}

$averageRating = $countRating > 0 ? round($totalRating / $countRating, 1) : 0;

$mensaje = "";
$tipoMensaje = "";

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    
    if ($nombre && $rating >= 1 && $rating <= 5 && $comentario) {
        try {
            $stmtInsert = $pdo->prepare("INSERT INTO reviews (game_id, nombre, rating, comentario) VALUES (:game_id, :nombre, :rating, :comentario)");
            $stmtInsert->execute([
                ':game_id' => $id,
                ':nombre' => $nombre,
                ':rating' => $rating,
                ':comentario' => $comentario
            ]);
            
            $mensaje = "✅ ¡Gracias por tu valoración!";
            $tipoMensaje = "success";
            
            // Recargar reviews
            $stmtReviews = $pdo->prepare("SELECT * FROM reviews WHERE game_id = :game_id ORDER BY fecha DESC");
            $stmtReviews->execute([':game_id' => $id]);
            $gameReviews = $stmtReviews->fetchAll();
            
            // Recalcular promedio
            $totalRating = 0;
            $countRating = count($gameReviews);
            foreach ($gameReviews as $review) {
                $totalRating += $review['rating'];
            }
            $averageRating = $countRating > 0 ? round($totalRating / $countRating, 1) : 0;
        } catch (PDOException $e) {
            $mensaje = "❌ Error al guardar tu comentario.";
            $tipoMensaje = "error";
        }
    } else {
        $mensaje = "⚠️ Por favor completa todos los campos correctamente.";
        $tipoMensaje = "error";
    }
}

// Procesar eliminación de comentario
if (isset($_GET['delete_review'])) {
    $reviewId = intval($_GET['delete_review']);
    
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM reviews WHERE id = :id AND game_id = :game_id");
        $stmtDelete->execute([
            ':id' => $reviewId,
            ':game_id' => $id
        ]);
        
        // Redirigir para evitar reenvío del formulario
        header("Location: item.php?id=$id&tema=$tema");
        exit;
    } catch (PDOException $e) {
        $mensaje = "❌ Error al eliminar el comentario.";
        $tipoMensaje = "error";
    }
}

?><!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($item['titulo']); ?> - GOTY 2025</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/item.css">
</head>

<body class="<?php echo $tema === 'oscuro' ? 'tema-oscuro' : ''; ?>">
    <div class="container">
        <div class="header">
            <h1><?php echo e($item['titulo']); ?></h1>
            <div>
                <a href="item.php?id=<?php echo $id; ?>&tema=claro">Tema claro</a> | 
                <a href="item.php?id=<?php echo $id; ?>&tema=oscuro">Tema oscuro</a>
            </div>
        </div>

        <a href="index.php?tema=<?php echo $tema; ?>" class="back-link">← Volver al listado</a>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipoMensaje; ?>">
                <?php echo e($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="game-detail">
            <div class="game-header">
                <div class="game-image">
                    <img src="<?php echo e($item['imagen']); ?>" alt="<?php echo e($item['titulo']); ?>">
                </div>
                <div class="game-info">
                    <h2 class="game-title-detail"><?php echo e($item['titulo']); ?></h2>
                    <div class="cat"><?php echo e($item['categoria']); ?></div>
                    
                    <div class="rating-display">
                        <div class="stars-display">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $averageRating ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo $averageRating; ?>/5 
                            <span class="rating-count">(<?php echo $countRating; ?> valoraciones)</span>
                        </span>
                    </div>
                    
                    <p class="game-description"><?php echo e($item['descripcion']); ?></p>
                </div>
            </div>

            <!-- Formulario de valoración -->
            <div class="review-form-container">
                <h2>📝 Deja tu valoración</h2>
                <form method="POST" class="review-form">
                    <div class="form-group">
                        <label for="nombre">Tu nombre:</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan Pérez">
                    </div>

                    <div class="form-group">
                        <label>Tu puntuación:</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5" title="5 estrellas">★</label>
                            
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4" title="4 estrellas">★</label>
                            
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3" title="3 estrellas">★</label>
                            
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2" title="2 estrellas">★</label>
                            
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1" title="1 estrella">★</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comentario">Tu comentario:</label>
                        <textarea id="comentario" name="comentario" rows="4" required placeholder="Cuéntanos qué te pareció el juego..."></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="btn-primary">Publicar valoración</button>
                </form>
            </div>

            <!-- Lista de comentarios -->
            <div class="reviews-list">
                <h2>💬 Valoraciones (<?php echo count($gameReviews); ?>)</h2>
                
                <?php if (empty($gameReviews)): ?>
                    <p class="no-reviews">Aún no hay valoraciones. ¡Sé el primero en comentar!</p>
                <?php else: ?>
                    <?php foreach ($gameReviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-author">
                                    <div class="author-avatar"><?php echo strtoupper(substr($review['nombre'], 0, 1)); ?></div>
                                    <div>
                                        <strong><?php echo e($review['nombre']); ?></strong>
                                        <div class="review-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-meta">
                                    <span class="review-date"><?php echo date('d/m/Y', strtotime($review['fecha'])); ?></span>
                                    <button class="btn-delete-review" onclick="confirmarEliminacion(<?php echo $review['id']; ?>)">🗑️</button>
                                </div>
                            </div>
                            <p class="review-text"><?php echo nl2br(e($review['comentario'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="modal-confirmacion" class="modal">
        <div class="modal-content">
            <h2>⚠️ Confirmar eliminación</h2>
            <p>¿Estás seguro de que deseas eliminar este comentario?</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                <a id="btn-confirmar" href="#" class="btn-confirm">Eliminar</a>
            </div>
        </div>
    </div>

    <script>
        // Modal de confirmación
        const modal = document.getElementById('modal-confirmacion');
        const btnConfirmar = document.getElementById('btn-confirmar');

        function confirmarEliminacion(reviewId) {
            btnConfirmar.href = `item.php?id=<?php echo $id; ?>&tema=<?php echo $tema; ?>&delete_review=${reviewId}`;
            modal.style.display = 'flex';
        }

        function cerrarModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                cerrarModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>
</body>

</html>