<?php
// Funciones comunes


// Sanear salida para evitar XSS
function e($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


// Filtrar items por nombre (search) y/o categoría
function filtrar_items($items, $search = '', $categoria = '')
{
    $search = mb_strtolower(trim($search));
    $categoria = trim($categoria);


    return array_filter($items, function ($item) use ($search, $categoria) {
        $matchSearch = true;
        $matchCat = true;


        if ($search !== '') {
            $matchSearch = mb_stripos($item['titulo'], $search) !== false;
        }
        if ($categoria !== '') {
            $matchCat = $item['categoria'] === $categoria;
        }
        return $matchSearch && $matchCat;
    });
}


// Obtener item por id
function get_item_by_id($items, $id)
{
    foreach ($items as $it)
        if ($it['id'] == $id)
            return $it;
    return null;
}

//Cambia el tema Claro/Oscuro si el usuario clickea en 'Cambiar Tema' y lo guarda en una COOKIE
function obtenerTema() {
    $tema = 'claro';
    if (isset($_GET['tema'])) {
        $tema = $_GET['tema'] === 'oscuro' ? 'oscuro' : 'claro';

        // Guardamos la eleccion en una cookie
        setcookie('tema_preferido', $tema, time() + (86400 * 30), "/");
        return $tema;
    }

    // Validacion para saber si el usuario ya tenia una cookie guardada
    if (isset ($_COOKIE['tema_preferido'])) {
        return $_COOKIE['tema_preferido'];
    }

    // Validacion para saber si es usuario nuevo
    return $tema;
}



