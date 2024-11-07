<?php

/**
 * Valida la referencia HTTP de la solicitud.
 *
 * Esta función verifica si el valor de la cabecera `HTTP_REFERER` está presente y si el dominio
 * de la URL referida está en la lista de dominios permitidos. Se utiliza para verificar si
 * la solicitud proviene de una fuente confiable.
 *
 * @return bool Devuelve `true` si el dominio de la referencia está en la lista de dominios permitidos, 
 *              o `false` si no está presente o no coincide con los dominios válidos.
 *
 * @note Los dominios permitidos incluyen:
 *       - unicomfacauca.edu.co
 *       - mail.google.com
 *       - outlook.office.com
 *       - outlook.live.com
 *       - localhost
 *       - 127.0.0.1
 */
function validarReferencia()
{

    if (isset($_SERVER['HTTP_REFERER'])) {  // Verifica si la cabecera HTTP_REFERER está presente



        // Obtiene la URL del referer
        $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

        // Define los dominios válidos
        $dominiosPermitidos = [
            'unicomfacauca.edu.co',
            'localhost',
            '127.0.0.1'
        ];

        // Verifica si el dominio coincide o si es un subdominio permitido
        foreach ($dominiosPermitidos as $dominio) {
            if ($referer === $dominio || substr($referer, -strlen($dominio) - 1) === ".$dominio") {
                return true;
            }
        }
    }else{
        return in_array($_SERVER['SERVER_NAME'],['localhost','127.0.0.1','::1']);
    }
    return false;
}
$_PERMITIR_DESDE_URL_REFERIDA = validarReferencia();

define("INCLUDE_DIR", dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR);
include './include/ost-config.php';

// Configura la conexión a la base de datos MySQL con MySQLi
$DB = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

// Verifica la conexión
if ($DB->connect_error) {
    die("Error de conexión: " . $DB->connect_error);
}

if (isset($_GET['uid'])) {
    $_WHERE = "t.user_id  = {$_GET['uid']}";
    $_SQL_USER_EMIAL = "SELECT ue.address 
                   FROM ost_user u 
                   JOIN ost_user_email ue  ON ue.id = u.default_email_id 
                   WHERE u.id = '{$_GET['uid']}'";
    if ($result = $DB->query($_SQL_USER_EMIAL)) {
        if ($user = $result->fetch_object()) {
            $userEmail = $user->address;
        }
    }
} else {
    $userEmail = mb_strtolower(trim($_GET['user'] ?? 'coordsistemasinfo@unicomfacauca.edu.co'));
    $_WHERE = "ue.address = '{$userEmail}'";
}


$fromAndwhere = "
FROM ost_ticket t
    JOIN ost_user u ON u.id = t.user_id 
    JOIN ost_department d ON d.id = t.dept_id 
    LEFT JOIN ost_help_topic tp ON tp.topic_id = t.topic_id     
    JOIN ost_ticket_status s ON s.id = t.status_id 
    JOIN ost_ticket__cdata td ON td.ticket_id = t.ticket_id 
    JOIN ost_user_email ue ON ue.user_id = t.user_id
WHERE $_WHERE ";

// Variables para la paginación
$itemsPerPage = $_GET['per_page'] ?? 15; // Número de tickets por página
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual
$offset = ($page - 1) * $itemsPerPage; // Calcular el offset para la consulta SQL

// Consulta para obtener el número total de tickets
$totalQuery = "SELECT COUNT(*) AS total $fromAndwhere";
$totalResult = $DB->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalTickets = $totalRow['total'];

$from = $offset + 1;
$to = min($offset + $itemsPerPage, $totalTickets);

// Calcular el número total de páginas
$totalPages = ceil($totalTickets / $itemsPerPage);

// Consulta para obtener los tickets con límite y offset
$ticketsQuery = "
SELECT
    t.ticket_id,
    t.number,
    REPLACE( REPLACE(td.subject,'RE: ','' ) ,'FWD: ','' ) subject,
    t.user_id,
    UCASE(u.name) user,
    t.dept_id,
    d.name departament,
    t.topic_id,
    tp.topic ,
    t.staff_id,
    t.team_id,
    t.source,
    t.status_id,
    s.name status,
    CASE s.state
        WHEN 'open' THEN 'primary'
        WHEN 'closed' THEN 'success'
        WHEN 'archived' THEN 'secondary'
        WHEN 'deleted' THEN 'danger'
        ELSE 'info '
    END state,
    DATE_FORMAT(t.created, '%e/%m/%Y %I:%i %p') created,
    DATE_FORMAT(t.closed , '%e/%m/%Y %I:%i %p') closed
    {$fromAndwhere}
ORDER BY t.created DESC
    LIMIT $offset, $itemsPerPage
";
$ticketsResult = $DB->query($ticketsQuery);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Tickets</title>
    <link rel="icon" type="image/png" href="/images/oscar-favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="/images/oscar-favicon-16x16.png" sizes="16x16" />
    <!-- Bootstrap CSS -->
    <link type="text/css" rel="stylesheet" href="/css/bootstrap.min.css" />
    <style>
        .truncate-text {
            white-space: nowrap;
            /* Evita que el texto se desborde en múltiples líneas */
            overflow: hidden;
            /* Oculta el texto que se sale del ancho del contenedor */
            text-overflow: ellipsis;
            /* Agrega "..." al final del texto truncado */
            max-width: 250px;
            /* Ajusta el ancho máximo de la columna */            
        }
        .bold{
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-fluid my-4">
        <?php if (!$_PERMITIR_DESDE_URL_REFERIDA): ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Acceso restringido</h4>
                <p>Este enlace para ver los tickets no está disponible desde este medio.
                    Puede acceder al sistema en <a href="https://siu.unicomfacauca.edu.co">https://siu.unicomfacauca.edu.co</a> y selecciona una de las opciones de Soporte para ver esta información.
                </p>
            </div>
            <?php die("</div></body></html>") ?>
        <?php endif; ?>
        <h1 class="mb-4">Listado de Tickets</h1>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span>Mostrando
                <select name="per_page" id="per_page">
                    <?php foreach (range(15, 120, 15) as $number): ?>
                        <option <?= ($number == $itemsPerPage ? 'selected' : '') ?>><?= $number ?></option>
                    <?php endforeach; ?>
                </select>
                Registros, del <?= $from ?> al <?= $to ?> de <?= $totalTickets ?> tickets. Registrados por el usuario: <strong><?= $userEmail ?></strong></span>
        </div>
        <!-- Tabla de tickets -->
        <table class="table table-bordered table-striped table-hover small">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Número</th>
                    <th>Creado el</th>
                    <th>Departamento</th>
                    <th>Tema</th>
                    <th>Asunto</th>
                    <th>Fuente</th>
                    <th>Estado</th>
                    <th>Cerrado el</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ticket = $ticketsResult->fetch_assoc()): ?>
                    <tr title="<?= htmlspecialchars($ticket['subject']) ?>">
                        <td><?= $from++ ?></td>
                        <td class="bold"><a href="/timeline.php?tid=<?= $ticket['ticket_id'] ?>"><?= htmlspecialchars($ticket['number']) ?></a></td>
                        <td><?= htmlspecialchars($ticket['created']) ?></td>
                        <td><?= htmlspecialchars($ticket['departament']) ?></td>
                        <td><?= htmlspecialchars($ticket['topic']) ?></td>
                        <td class="truncate-text bold"><a href="/timeline.php?tid=<?= $ticket['ticket_id'] ?>"><?= htmlspecialchars($ticket['subject']) ?></a></td>
                        <td><?= htmlspecialchars($ticket['source']) ?></td>
                        <td><span class="badge  rounded-pill bg-<?= $ticket['state'] ?>"><?= htmlspecialchars($ticket['status']) ?></span></td>
                        <td><?= htmlspecialchars($ticket['closed']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Paginador -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php
                $visibleLinks = 10; // Cantidad de enlaces de página visibles
                $startPage = max(1, $page - floor($visibleLinks / 2));
                $endPage = min($totalPages, $startPage + $visibleLinks - 1);

                // Asegurar que haya 10 enlaces visibles siempre que sea posible
                if ($endPage - $startPage < $visibleLinks - 1) {
                    $startPage = max(1, $endPage - $visibleLinks + 1);
                }

                // Enlace "Anterior"
                if ($page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '&per_page=' . $itemsPerPage . '&user=' . $userEmail . '">Anterior</a></li>';
                }

                // Enlaces de número de página
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                    echo '<a class="page-link" href="?page=' . $i . '&per_page=' . $itemsPerPage . '&user=' . $userEmail . '">' . $i . '</a>';
                    echo '</li>';
                }

                // Enlace "Siguiente"
                if ($page < $totalPages) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '&per_page=' . $itemsPerPage . '&user=' . $userEmail . '">Siguiente</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>

    <script>
        document.getElementById('per_page').addEventListener('change', function() {
            var perPage = this.value; // Obtener el valor seleccionado            
            var url = new URL(window.location.href); // Obtener la URL actual            
            url.searchParams.set('per_page', perPage); // Establecer o actualizar el parámetro 'per_page' con el valor seleccionado            
            url.searchParams.set('page', '1');
            window.location.href = url; // Recargar la página con la nueva URL
        });
    </script>
</body>

</html>

<?php
// Cerrar la conexión a la base de datos
$DB->close();
?>