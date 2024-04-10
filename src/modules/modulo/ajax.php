<?php
require_once "./../../config/db_functions.php";

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'crear':
        crearModulo();
        break;
    case 'actualizar':
        actualizarModulo();
        break;
    case 'eliminar':
        eliminarModulo();
        break;
    case 'leer':
        leerModulos();
        break;
    case 'modulo_seleccionado':
        leerModuloPorId();
        break;

    case 'leer_modulos_primarios':
        leerModulosPrimarios();
        break;

    default:
        echo json_encode(['error' => 'Acción no reconocida']);
        break;
}

function leerModulosPrimarios()
{
    $sql = "SELECT id, nombre FROM modulos WHERE padre_id IS NULL AND active = 1 ORDER BY nombre";
    $result = dbQuery($sql);
    $modulos = [];
    while ($modulo = dbFetchAssoc($result)) {
        $modulos[] = $modulo;
    }
    echo json_encode($modulos);
}
function crearModulo()
{
    $nombre_modulo = sanitizeInput($_POST['nombre_modulo']);
    $orden = sanitizeInput($_POST['orden']);
    $ruta = sanitizeInput($_POST['ruta']);
    $padre_id = sanitizeInput($_POST['padre_id']);

    $sql = "INSERT INTO modulos (nombre, orden, ruta, padre_id) VALUES ('$nombre_modulo', '$orden', '$ruta', '$padre_id')";

    $result = dbQuery($sql);

    if ($result) {
        echo json_encode(['success' => 'Módulo creado exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al crear el módulo']);
    }
}

function actualizarModulo()
{
    $id = sanitizeInput($_POST['id']);
    $nombre_modulo = sanitizeInput($_POST['nombre_modulo']);
    $orden = sanitizeInput($_POST['orden']);
    $ruta = sanitizeInput($_POST['ruta']);

    $sql = "UPDATE modulos SET nombre = '$nombre_modulo', orden = '$orden', ruta = '$ruta' WHERE id = $id";

    $result = dbQuery($sql);

    if ($result) {
        echo json_encode(['success' => 'Módulo actualizado exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar el módulo']);
    }
}

function eliminarModulo()
{
    $id = sanitizeInput($_POST['id']);

    $sql = "UPDATE modulos SET active = 0 WHERE id = $id";

    $result = dbQuery($sql);

    if ($result) {
        echo json_encode(['success' => 'Módulo eliminado exitosamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar el módulo']);
    }
}

function leerModulos()
{
    // Seleccionamos todos los módulos y los ordenamos primero por si tienen padre_id (los padres primero) y luego por orden
    $sql = 'SELECT * FROM modulos ORDER BY CASE WHEN padre_id IS NULL THEN 0 ELSE 1 END, padre_id, orden';
    $result = dbQuery($sql);

    $modulos = [];

    if (dbNumRows($result) > 0) {
        while ($modulo = dbFetchAssoc($result)) {
            $modulos[] = $modulo;
        }
    }

    // Creamos un array para los padres
    $modulosOrdenados = [];
    foreach ($modulos as $modulo) {
        if ($modulo['padre_id'] == null) {
            // Agregamos el modulo padre al array
            $modulosOrdenados[$modulo['id']] = $modulo;
            // Y le agregamos una clave 'hijos' para sus módulos hijos
            $modulosOrdenados[$modulo['id']]['hijos'] = [];
        }
    }

    // Ahora agregamos los hijos a sus respectivos padres
    foreach ($modulos as $modulo) {
        if ($modulo['padre_id'] != null && isset($modulosOrdenados[$modulo['padre_id']])) {
            $modulosOrdenados[$modulo['padre_id']]['hijos'][] = $modulo;
        }
    }

    echo json_encode(array_values($modulosOrdenados));
}

function leerModuloPorId()
{
    $id = sanitizeInput($_POST['id']);
    $sql = "SELECT * FROM modulos WHERE id = $id";
    $result = dbQuery($sql);

    if ($modulo = dbFetchAssoc($result)) {
        echo json_encode($modulo);
    } else {
        echo json_encode(['error' => 'Módulo no encontrado']);
    }
}
