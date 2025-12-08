<?php
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';

//$tema = isset($_GET['tema']) && $_GET['tema'] === 'oscuro' ? 'oscuro' : 'claro';
$tema = obtenerTema();

// Obtener categorías de la base de datos
$pdo = getConnection();
$stmtCat = $pdo->query("SELECT DISTINCT categoria FROM items ORDER BY categoria");
$categorias = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

// Crear carpeta de uploads si no existe
$uploadDir = __DIR__ . '/assets/images/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Procesar formulario tradicional (con recarga) para compatibilidad
$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_traditional'])) {
    $titulo = trim($_POST["titulo"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    
    $imagenPath = 'assets/images/placeholder.jpg';

    if ($titulo === "" || $categoria === "" || $descripcion === "") {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
        $tipoMensaje = "error";
    } else {
        // Procesar imagen si se subió
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['imagen'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $extensionesPermitidas)) {
                if ($archivo['size'] <= 5 * 1024 * 1024) {
                    $nombreArchivo = uniqid('game_', true) . '.' . $extension;
                    $rutaDestino = $uploadDir . $nombreArchivo;
                    
                    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                        $imagenPath = 'assets/images/uploads/' . $nombreArchivo;
                    }
                }
            }
        }

        // Insertar en base de datos
        try {
            $stmt = $pdo->prepare("INSERT INTO items (titulo, categoria, descripcion, imagen) VALUES (:titulo, :categoria, :descripcion, :imagen)");
            $stmt->execute([
                ':titulo' => $titulo,
                ':categoria' => $categoria,
                ':descripcion' => $descripcion,
                ':imagen' => $imagenPath
            ]);
            
            $mensaje = "✅ Juego agregado con éxito: $titulo";
            $tipoMensaje = "success";
            
            // Recargar categorías
            $stmtCat = $pdo->query("SELECT DISTINCT categoria FROM items ORDER BY categoria");
            $categorias = $stmtCat->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $mensaje = "❌ Error al guardar el juego: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugerir Juego - GOTY 2025</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/sugerir.css">
</head>
<body class="<?php echo $tema === 'oscuro' ? 'tema-oscuro' : ''; ?>">
    <div class="container">
        <div class="header">
            <h1>Sugerir un nuevo juego</h1>
            <div>
                <a href="sugerir.php?tema=claro">Tema claro</a> | <a href="sugerir.php?tema=oscuro">Tema oscuro</a>
            </div>
        </div>

        <a href="index.php?tema=<?php echo $tema; ?>" class="back-link">← Volver al listado</a>

        <div id="mensaje-container">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipoMensaje; ?>">
                    <?php echo e($mensaje); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <form id="suggestion-form" method="POST" action="sugerir.php?tema=<?php echo $tema; ?>" class="suggestion-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titulo">Título del juego:</label>
                    <input type="text" id="titulo" name="titulo" required placeholder="Ej: The Last of Us Part III">
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">-- Selecciona una categoría --</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo e($cat); ?>"><?php echo e($cat); ?></option>
                        <?php endforeach; ?>
                        <option value="Aventura">Aventura</option>
                        <option value="Estrategia">Estrategia</option>
                        <option value="Deportes">Deportes</option>
                        <option value="Horror">Horror</option>
                        <option value="Simulación">Simulación</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" rows="4" required placeholder="Describe brevemente el juego..."></textarea>
                </div>

                <div class="form-group">
                    <label for="imagen">Imagen del juego:</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="file-input">
                        <label for="imagen" class="file-label">
                            <span class="file-icon">📁</span>
                            <span class="file-text">Seleccionar imagen (opcional)</span>
                        </label>
                        <div class="file-info">
                            Formatos: JPG, PNG, GIF, WEBP • Máximo: 5MB
                        </div>
                    </div>
                    <div id="preview-container" class="preview-container" style="display: none;">
                        <img id="preview-image" src="" alt="Preview">
                    </div>
                </div>

                <button type="submit" name="submit_traditional" class="btn-primary">Agregar Juego (con recarga)</button>
                <button type="button" id="btn-ajax-submit" class="btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #ec4899);">Agregar con AJAX (sin recarga)</button>
            </form>
        </div>

        <div class="games-list">
            <h2>Juegos actuales <span id="games-count">(cargando...)</span></h2>
            <div class="cards" id="games-container">
                <p style="text-align: center; padding: 40px; color: var(--text);">Cargando juegos...</p>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="modal-confirmacion" class="modal">
        <div class="modal-content">
            <h2>⚠️ Confirmar eliminación</h2>
            <p id="modal-mensaje">¿Estás seguro de que deseas eliminar este juego?</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                <button id="btn-confirmar" class="btn-confirm">Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        const tema = '<?php echo $tema; ?>';
        let currentDeleteId = null;

        // Cargar juegos al iniciar
        document.addEventListener('DOMContentLoaded', () => {
            cargarJuegos();
        });

        // Función para cargar juegos desde la API
        async function cargarJuegos() {
            try {
                const response = await fetch('inc/api.php?action=get_items');
                const data = await response.json();
                
                if (data.success) {
                    mostrarJuegos(data.data);
                    document.getElementById('games-count').textContent = `(${data.count})`;
                } else {
                    mostrarError('Error al cargar los juegos');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión con el servidor');
            }
        }

        // Mostrar juegos en el DOM
        function mostrarJuegos(items) {
            const container = document.getElementById('games-container');
            
            if (items.length === 0) {
                container.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text);">No hay juegos registrados.</p>';
                return;
            }
            
            container.innerHTML = items.map(item => `
                <div class="card">
                    <a href="item.php?id=${item.id}&tema=${tema}">
                        <img src="${escapeHtml(item.imagen)}" alt="${escapeHtml(item.titulo)}">
                    </a>
                    <div class="body">
                        <div class="cat">${escapeHtml(item.categoria)}</div>
                        <h3 class="title">${escapeHtml(item.titulo)}</h3>
                        <p>${escapeHtml(item.descripcion)}</p>
                        <button class="btn-delete" onclick="confirmarEliminacion(${item.id}, '${escapeHtml(item.titulo).replace(/'/g, "\\'")}')">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Envío con AJAX (sin recarga)
        document.getElementById('btn-ajax-submit').addEventListener('click', async (e) => {
            e.preventDefault();
            
            const form = document.getElementById('suggestion-form');
            const titulo = document.getElementById('titulo').value.trim();
            const categoria = document.getElementById('categoria').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const imagenInput = document.getElementById('imagen');
            
            // Validación
            if (!titulo || !categoria || !descripcion) {
                mostrarMensaje('⚠️ Todos los campos son obligatorios', 'error');
                return;
            }
            
            let imagenPath = 'assets/images/placeholder.jpg';
            
            // Si hay imagen, subirla primero (requeriría un endpoint adicional o procesarla como base64)
            // Por simplicidad, usamos placeholder
            
            try {
                const response = await fetch('inc/api.php?action=add_item', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        titulo: titulo,
                        categoria: categoria,
                        descripcion: descripcion,
                        imagen: imagenPath
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensaje(`✅ ${data.message}`, 'success');
                    form.reset();
                    document.getElementById('preview-container').style.display = 'none';
                    document.querySelector('.file-text').textContent = 'Seleccionar imagen (opcional)';
                    cargarJuegos(); // Recargar lista
                } else {
                    mostrarMensaje(`❌ ${data.message}`, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('❌ Error al agregar el juego', 'error');
            }
        });

        // Modal de confirmación
        const modal = document.getElementById('modal-confirmacion');
        const modalMensaje = document.getElementById('modal-mensaje');
        const btnConfirmar = document.getElementById('btn-confirmar');

        function confirmarEliminacion(id, titulo) {
            currentDeleteId = id;
            modalMensaje.textContent = `¿Estás seguro de que deseas eliminar "${titulo}"?`;
            modal.style.display = 'flex';
        }

        function cerrarModal() {
            modal.style.display = 'none';
            currentDeleteId = null;
        }

        btnConfirmar.addEventListener('click', async () => {
            if (!currentDeleteId) return;
            
            try {
                const response = await fetch(`inc/api.php?action=delete_item&id=${currentDeleteId}`, {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensaje(`🗑️ ${data.message}`, 'success');
                    cerrarModal();
                    cargarJuegos();
                } else {
                    mostrarMensaje(`❌ ${data.message}`, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('❌ Error al eliminar el juego', 'error');
            }
        });

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

        // Preview de imagen
        const inputImagen = document.getElementById('imagen');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const fileLabel = document.querySelector('.file-text');

        inputImagen.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                fileLabel.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                fileLabel.textContent = 'Seleccionar imagen (opcional)';
                previewContainer.style.display = 'none';
            }
        });

        // Funciones auxiliares
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function mostrarMensaje(texto, tipo) {
            const container = document.getElementById('mensaje-container');
            container.innerHTML = `<div class="mensaje ${tipo}">${texto}</div>`;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        function mostrarError(texto) {
            mostrarMensaje(texto, 'error');
        }
    </script>
</body>
</html>