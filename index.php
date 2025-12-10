<?php
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';

// Parámetros GET
//$tema = isset($_GET['tema']) && $_GET['tema'] === 'oscuro' ? 'oscuro' : 'claro';
$tema = obtenerTema();

// Obtener datos de la base de datos
$pdo = getConnection();

// Obtener categorías
$stmtCat = $pdo->query("SELECT DISTINCT categoria FROM items ORDER BY categoria");
$categorias = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

?><!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GOTY 2025 - Lista</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="<?php echo $tema === 'oscuro' ? 'tema-oscuro' : ''; ?>">
    <div class="container">
        <div class="header">
            <h1>G.O.T.Y. 2025</h1>
            <div>
                <a href="?tema=claro">Tema claro</a> | <a href="?tema=oscuro">Tema oscuro</a>
                &nbsp;|&nbsp;<a href="sugerir.php">Sugerir un juego</a>
            </div>
        </div>

        <div id="mensaje-container"></div>

    <!-- Formulario para filtrar por categorias y nombre. Usando AJAX-->
    <form id="search-form" class="search-form" onsubmit="event.preventDefault();">
    <input type="text" id="search-input" placeholder="Buscar por nombre..." oninput="cargarItems()">

    <select id="categoria-select" onchange="cargarItems()">
        <option value="">-- Todas las categorías --</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?php echo e($cat); ?>">
                <?php echo e($cat); ?>
            </option>
        <?php endforeach; ?>
    </select>
    </form>

        <p id="results-count">Cargando...</p>

        <!-- Contenedor de tarjetas que se llenará con AJAX -->
        <div class="cards" id="cards-container">
            <p style="text-align: center; padding: 40px; color: var(--text);">Cargando juegos...</p>
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

        // Cargar items al iniciar
        document.addEventListener('DOMContentLoaded', () => {
            cargarItems();
        });

        // Manejar formulario de búsqueda
        document.getElementById('search-form').addEventListener('submit', (e) => {
            e.preventDefault();
            cargarItems();
        });

        // Función para cargar items desde la API
        async function cargarItems() {
            const searchValue = document.getElementById('search-input').value;
            const categoriaValue = document.getElementById('categoria-select').value;
            
            const params = new URLSearchParams();
            params.append('action', 'get_items');
            if (searchValue) params.append('q', searchValue);
            if (categoriaValue) params.append('categoria', categoriaValue);
            
            try {
                const response = await fetch(`inc/api.php?${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    mostrarItems(data.data);
                    actualizarContador(data.count, searchValue || categoriaValue);
                } else {
                    mostrarError('Error al cargar los juegos');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión con el servidor');
            }
        }

        // Función para mostrar items en el DOM
        function mostrarItems(items) {
            const container = document.getElementById('cards-container');
            
            if (items.length === 0) {
                container.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text);">No se encontraron juegos.</p>';
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

        // Actualizar contador de resultados
        function actualizarContador(count, hayFiltros) {
            const counter = document.getElementById('results-count');
            if (hayFiltros) {
                counter.textContent = `${count} resultado(s)`;
            } else {
                counter.textContent = `Mostrando todos los ítems (${count})`;
            }
        }

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

        // Confirmar eliminación
        btnConfirmar.addEventListener('click', async () => {
            if (!currentDeleteId) return;
            
            try {
                const response = await fetch(`inc/api.php?action=delete_item&id=${currentDeleteId}`, {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensaje(data.message, 'success');
                    cerrarModal();
                    cargarItems(); // Recargar lista
                } else {
                    mostrarMensaje(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error al eliminar el juego', 'error');
            }
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function (event) {
            if (event.target == modal) {
                cerrarModal();
            }
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                cerrarModal();
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
            container.innerHTML = `<div class="mensaje ${tipo}">${escapeHtml(texto)}</div>`;
            
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