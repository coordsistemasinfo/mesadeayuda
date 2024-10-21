<?php

/**
 * @file
 * @brief Script para crear o actualizar usuarios en OSTicket a partir de una entrada JSON.
 *
 * Este script recibe un JSON con los datos del usuario, verifica si el usuario
 * ya existe en la base de datos de OSTicket basado en su correo electrónico.
 * Si existe, actualiza los datos del usuario; si no existe, crea un nuevo usuario.
 *
 * @details
 * El script se conecta a la base de datos de OSTicket y realiza las operaciones
 * necesarias para crear o actualizar la información de los usuarios en las tablas
 * `ost_user`, `ost_user__cdata`, y `ost_user_email`.
 *
 * @author Peter Emerson Pinchao <coordsistemasinfo@unicomfacauca.edu.co>
 * @date 2024-08-26
 */

// Define el tipo de contenido como JSON
header('Content-Type: application/json');


// Configura las credenciales válidas para autenticación básica
$validUsername = 'siu@unicomfacauca.edu.co';
$validPassword = 'I0ezyX4YyW5ReNA6DwVR';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    // Solicita autenticación
    header('WWW-Authenticate: Basic realm="Autenticación OsTicke"');
    header('HTTP/1.0 401 Unauthorized');
    die(json_encode(['status' => 'error', 'message' => 'Authenticación requerida']));
}

// Verifica las credenciales
if ($_SERVER['PHP_AUTH_USER'] !== $validUsername || $_SERVER['PHP_AUTH_PW'] !== $validPassword) {
    header('HTTP/1.0 403 Forbidden');
    die(json_encode(['status' => 'error', 'message' => 'Credenciales Invalidas']));
}

// Verifica que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    die(json_encode(['status' => 'error', 'message' => 'Solo estan permitidas solicitudes POST']));
}


// Obtén el cuerpo de la solicitud POST
$jsonInput = file_get_contents('php://input');


// Decodifica la entrada JSON
$postData = json_decode($jsonInput);

// Verifica si la decodificación fue exitosa
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Formato JSON No válido']));
}

// Verifica que todos los campos requeridos estén presentes
$requiredFields = ['identificacion', 'nombre_completo', 'tipo_vinculacion', 'correo', 'seccion_facultad'];
foreach ($requiredFields as $field) {
    if (empty($postData->$field)) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => "El Campo '$field' es Requerido"]));
    }
}

$vinculacion_values = ['DOCENTE','ESTUDIANTE','ADMINISTRATIVO','PROVEEDOR','OTRO'];
if(!in_array($postData->tipo_vinculacion,$vinculacion_values)){
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => "Los valores permitidos para 'tipo_vinculacion' son: ". implode(', ',$vinculacion_values) ]));
}

// Define el directorio de inclusión y carga el archivo de configuración
define("INCLUDE_DIR", basename(__FILE__));
include './include/ost-config.php';

// Establece la conexión con la base de datos
$DB = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

// Verifica si la conexión con la base de datos ha fallado
if ($DB->connect_error) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => "Connection failed: " . $DB->connect_error]));
}


// Start a transaction
$DB->begin_transaction();

try {
    /**
     * @brief Verifica si el correo electrónico ya existe en la base de datos.
     *
     * @param $postData->correo Correo electrónico a verificar.
     * @return void
     */
    if ($result = $DB->query("SELECT * FROM ost_user_email WHERE address ='{$postData->correo}'")) {
        if ($emailData = $result->fetch_object()) {
            //   die(json_encode(['status'=>'success', 'data'=> $userData]));
        }
    }

    $_tipo_vinculacion = $ids_vinculacion[$postData->tipo_vinculacion].','.$DB->real_escape_string($postData->tipo_vinculacion);
    /**
     * @brief Actualiza la información del usuario si ya existe.
     *
     * @details Actualiza las tablas `ost_user` y `ost_user__cdata` con la información
     * proporcionada en la entrada JSON si el correo electrónico ya existe.
     */
    if (is_object($emailData)) {
        $_SQL = "UPDATE `ost_user__cdata` SET 
                    `identificacion` = '" . $DB->real_escape_string($postData->identificacion) . "',
                    `area_depentencia` = '" . $DB->real_escape_string($postData->seccion_facultad) . "',
                    `tipo_vinculacion` = '" . $DB->real_escape_string($postData->tipo_vinculacion). "' ,
                    `phone` = '" . $DB->real_escape_string($postData->telefono_movil) . "',
                    `programa` = '" . $DB->real_escape_string($postData->programa) . "'
                WHERE `user_id` = {$emailData->user_id}";

        $DB->query($_SQL);

        $_SQL = "UPDATE `ost_user` SET
                `name` = '" . $DB->real_escape_string($postData->nombre_completo) . "',
                `updated` = NOW()
                WHERE `id` = {$emailData->user_id}";

        $DB->query($_SQL);
    } else {
        /**
         * @brief Crea un nuevo usuario si el correo no existe.
         *
         * @details Inserta registros en las tablas `ost_user`, `ost_user_email` y
         * `ost_user__cdata` para crear un nuevo usuario en la base de datos.
         */
        $_SQL = "INSERT INTO `ost_user` (`org_id`, `default_email_id`, `status`, `name`, `created`, `updated`) 
            VALUES (0, 1, 0, '" . $DB->real_escape_string($postData->nombre_completo) . "', NOW(), NOW())";

        if ($DB->query($_SQL)) {
            $userId = $DB->insert_id;
            $_SQL = "INSERT INTO `ost_user_email` (`user_id`, `flags`, `address`) 
                                      VALUES ($userId, 0, '" . $postData->correo . "')";
            if ($DB->query($_SQL)) {
                $defaultEmailId =  $DB->insert_id;
                $_SQL = "UPDATE `ost_user` SET `default_email_id` = {$defaultEmailId} WHERE `id`= {$userId}";
                $DB->query($_SQL);
            }

            $_SQL = "INSERT INTO `ost_user__cdata` (`user_id`, `area_depentencia`, `identificacion`,  `tipo_vinculacion`, `phone`, `programa`) 
                            VALUES ($userId, '" .  $DB->real_escape_string($postData->seccion_facultad) . "', '" .
                $DB->real_escape_string($postData->identificacion) . "', '".$DB->real_escape_string($postData->tipo_vinculacion)."', '" . 
                $DB->real_escape_string($postData->telefono_movil) . "', '".
                $DB->real_escape_string($postData->programa) . "')";

            $DB->query($_SQL);
        }
    }
    // Confirma la transacción
    $DB->commit();
    http_response_code(200);
    echo (json_encode(['status' => 'success', 'data' => $postData]));
} catch (Exception $e) {
    http_response_code(500);
    // Maneja cualquier error ocurrido durante la transacción
    echo (json_encode(['status' => 'error', 'data' => $postData, 'message' => "Error: " . $e->getMessage()]));
}

// Cierra la conexión con la base de datos
$DB->close();
