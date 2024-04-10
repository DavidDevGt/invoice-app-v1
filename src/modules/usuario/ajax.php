<?php
// ini_set('log_errors', 1);
// ini_set('error_log', './errors.log');
// error_reporting(E_ALL);

require_once "./../../config/db_functions.php";

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {

    case "mostrar_usuarios":
        $sql = "SELECT
                usuarios.id,
                usuarios.codigo,
                usuarios.usuario,
                usuarios.active,
                roles.nombre AS nombre_rol,
                usuarios.rol_id
                FROM
                    usuarios
                LEFT JOIN
                    roles ON usuarios.rol_id = roles.id
                ORDER BY usuarios.active DESC";

        $query = dbQuery($sql);
        $json = array();
        while ($row = dbFetchAssoc($query)) {
            $json[] = $row;
        }

        echo json_encode($json);

        break;

    case "usuario_seleccionado":

        $usuario = $_POST['usuario_id'];

        $sql = "SELECT
                    usuarios.id,
                    usuarios.codigo,
                    usuarios.usuario,
                    usuarios.rol_id,
                    usuarios.active
                FROM
                    usuarios
                WHERE
                    usuarios.id = " . $usuario;

        $query = dbQuery($sql);
        $json = array();
        while ($row = dbFetchAssoc($query)) {
            $json[] = $row;
        }

        echo json_encode($json);

        break;

    case "mostrar_modulos":

        $sql = "SELECT * FROM modulos WHERE active = 1 ORDER BY orden ASC";

        $query = dbQuery($sql);
        $json = array();
        while ($row = dbFetchAssoc($query)) {
            $json[] = $row;
        }

        echo json_encode($json);

        break;

    case "mostrar_roles":

        $sql = "SELECT id, nombre FROM roles WHERE active ORDER BY id ASC";

        $query = dbQuery($sql);
        $json = array();
        while ($row = dbFetchAssoc($query)) {
            $json[] = $row;
        }

        echo json_encode($json);

        break;

    case "actualizar_permisos":
        $usuario_id = $_POST["usuario_id"];
        $permisos = $_POST["permisos"];

        foreach ($permisos as $permiso) {
            $module_id = $permiso['module_id'];
            $escritura = $permiso['escritura'];
            $lectura = $permiso['lectura'];

            $sql = "SELECT id FROM permisos WHERE usuario_id = $usuario_id AND module_id = $module_id";
            $query = dbQuery($sql);
            if (dbFetchAssoc($query)) {
                $sql = "UPDATE permisos SET escritura = $escritura, lectura = $lectura WHERE usuario_id = $usuario_id AND module_id = $module_id";
            } else {
                $sql = "INSERT INTO permisos (usuario_id, module_id, escritura, lectura) VALUES ($usuario_id, $module_id, $escritura, $lectura)";
            }
            dbQuery($sql);
        }

        echo json_encode(['success' => true]);
        break;

    case "obtener_permisos_usuarios":
        // Obtener permisos
        $usuario_id = $_POST["usuario_id"];
        $sql = "SELECT * FROM permisos WHERE usuario_id = $usuario_id";
        $query = dbQuery($sql);

        $permisos = array();
        while ($row = dbFetchAssoc($query)) {
            $permisos[] = $row;
        }

        echo json_encode($permisos);
        break;

    case "mostrar_modulos_hijo":
        $modulo_padre_id = $_POST['modulo_padre_id'];
        $sql = "SELECT * FROM modulos WHERE padre_id = " . $modulo_padre_id . " ORDER BY orden";

        $query = dbQuery($sql);
        $json = array();
        while ($row = dbFetchAssoc($query)) {
            $json[] = $row;
        }

        echo json_encode($json);

        break;

    case "mostrar_modulos_hijo_usuario":
        $usuario = $_POST['usuario'];
        $sql = "SELECT module_id FROM permisos WHERE usuario_id = " . $usuario;

        $query = dbQuery($sql);
        $json = array();
        while ($row = dbFetchAssoc($query)) {
            $json[] = $row;
        }

        echo json_encode($json);

        break;

    case "crear_usuario":
        // Obtener valores
        $codigo = sanitizeInput($_POST['datos']['codigo']);
        $usuario = sanitizeInput($_POST['datos']['usuario']);
        $password = sanitizeInput($_POST['datos']['password']);
        $rol_id = sanitizeInput($_POST['datos']['rol_id']);

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $query = "CALL RegisterUsuario(?, ?, ?, ?)";

        $conn = getDbConnection();
        $stmt = $conn->prepare($query);

        $stmt->bind_param("sssi", $codigo, $usuario, $passwordHash, $rol_id);

        $result = $stmt->execute();

        $stmt->close();
        $conn->close();

        if (!$result) {
            $response = [
                'icon' => 'error',
                'title' => '¡Error!',
                'text' => 'No se guardaron los cambios, error en la ejecución.'
            ];
        } else {
            $response = [
                'icon' => 'success',
                'title' => '¡Éxito!',
                'text' => 'Se guardaron los cambios correctamente.'
            ];
        }
        echo json_encode($response);

        break;

    case "editar_usuario":
        $id = sanitizeInput($_POST['formData']['usuario_id']);
        $codigo = sanitizeInput($_POST['formData']['codigo']);
        $usuario = sanitizeInput($_POST['formData']['usuario']);
        $rol_id = sanitizeInput($_POST['formData']['rol']);
        $active = sanitizeInput($_POST['formData']['estado']);

        if (isset($_POST['formData']['password']) && !empty($_POST['formData']['password'])) {
            $password = password_hash($_POST['formData']['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET 
                            codigo = '$codigo', 
                            usuario = '$usuario', 
                            rol_id = $rol_id, 
                            active = $active,
                            password = '$password' 
                        WHERE id = $id";
        } else {
            $sql = "UPDATE usuarios SET 
                            codigo = '$codigo', 
                            usuario = '$usuario', 
                            rol_id = $rol_id, 
                            active = $active 
                        WHERE id = $id";
        }

        $result = dbQuery($sql);

        if (!$result) {
            $response = [
                'icon' => 'error',
                'title' => '¡Error!',
                'text' => 'No se guardaron los cambios, error en consulta.'
            ];
        } else {
            $response = [
                'icon' => 'success',
                'title' => '¡Éxito!',
                'text' => 'Se guardaron los cambios correctamente.'
            ];
        }
        echo json_encode($response);

        break;
}
