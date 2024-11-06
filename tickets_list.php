<?php
define("INCLUDE_DIR", dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR);
include './include/ost-config.php';

// Configura la conexión a la base de datos MySQL con MySQLi
$DB = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

// Verifica la conexión
if ($DB->connect_error) {
    die("Error de conexión: " . $DB->connect_error);
}

$userEmail = mb_strtolower(trim($_GET['user']??'coordsistemasinfo@unicomfacauca.edu.co'));

$fromAndwhere = "
FROM ost_ticket t
    JOIN ost_user u ON u.id = t.user_id 
    JOIN ost_department d ON d.id = t.dept_id 
    LEFT JOIN ost_help_topic tp ON tp.topic_id = t.topic_id     
    JOIN ost_ticket_status s ON s.id = t.status_id 
    JOIN ost_ticket__cdata td ON td.ticket_id = t.ticket_id 
    JOIN ost_user_email ue ON ue.user_id = t.user_id
WHERE ue.address = '{$userEmail}'";

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
    <link type="text/css" rel="stylesheet"  href="/css/bootstrap.min.css" />
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
    </style>
</head>

<body>
    <div class="container-fluid my-4">
        <h1 class="mb-4">Listado de Tickets</h1>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span>Mostrando
                <select name="per_page" id="per_page">
                    <?php foreach (range(15, 120, 15) as $number): ?>
                        <option <?= ($number == $itemsPerPage ? 'selected' : '') ?>><?= $number ?></option>
                    <?php endforeach; ?>
                </select>
                Registros, del <?= $from ?> al <?= $to ?> de <?= $totalTickets ?> tickets. Registrados por el usuario: <strong><?=$userEmail?></strong></span>
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
                        <td><a href="/timeline.php?tid=<?= $ticket['ticket_id'] ?>"><?= htmlspecialchars($ticket['number']) ?></a></td>
                        <td><?= htmlspecialchars($ticket['created']) ?></td>
                        <td><?= htmlspecialchars($ticket['departament']) ?></td>
                        <td><?= htmlspecialchars($ticket['topic']) ?></td>                        
                        <td class="truncate-text"><a href="/timeline.php?tid=<?= $ticket['ticket_id'] ?>"><?= htmlspecialchars($ticket['subject']) ?></a></td>
                        <td><?= htmlspecialchars($ticket['source']) ?></td>
                        <td><?= htmlspecialchars($ticket['status']) ?></td>
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
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '&per_page=' . $itemsPerPage . '&user='.$userEmail.'">Anterior</a></li>';
                }

                // Enlaces de número de página
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                    echo '<a class="page-link" href="?page=' . $i . '&per_page=' . $itemsPerPage . '&user='.$userEmail.'">' . $i . '</a>';
                    echo '</li>';
                }

                // Enlace "Siguiente"
                if ($page < $totalPages) {
                    echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '&per_page=' . $itemsPerPage . '&user='.$userEmail.'">Siguiente</a></li>';
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
            window.location.href = url; // Recargar la página con la nueva URL
        });
    </script>
</body>

</html>

<?php
// Cerrar la conexión a la base de datos
$DB->close();
?>