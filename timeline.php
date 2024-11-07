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
            'mail.google.com',
            'outlook.office.com',
            'outlook.live.com',
            'localhost',
            '127.0.0.1'
        ];


        // Verifica si el dominio coincide o si es un subdominio permitido
        foreach ($dominiosPermitidos as $dominio) {
            if ($referer === $dominio || substr($referer, -strlen($dominio) - 1) === ".$dominio") {
                return true;
            }
        }
    } else {
        return in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']);
    }

    return false;
}

/**
 * Valida si una fecha de cierre no es null y si han pasado más de 6 meses desde esa fecha.
 *
 * @param string|null $fechaCierre Fecha de cierre en formato 'YYYY-mm-dd HH:ii:ss'.
 *                                Puede ser null.
 * @return bool Retorna `true` si la fecha no es null y han pasado más de 6 meses; 
 *              de lo contrario, retorna `false`.
 *
 * ### Descripción:
 * La función primero verifica si la fecha de cierre es `null`. Si es así, retorna `false`.
 * Luego convierte `$fechaCierre` a un objeto `DateTime` y calcula una fecha límite
 * restando 6 meses a la fecha actual. Si `$fechaCierre` es anterior a esta fecha límite,
 * retorna `true`; de lo contrario, `false`.
 */
function validarFechaCierre($fechaCierre)
{
    // Verifica si la fecha no es null
    if ($fechaCierre === null) {
        return false;
    }

    // Convierte la fecha en un objeto DateTime
    $fechaCierreObj = new DateTime($fechaCierre);

    // Obtiene la fecha actual y resta 6 meses
    $fechaLimite = new DateTime();
    $fechaLimite->modify('-6 months');

    // Compara la fecha de cierre con la fecha límite
    return $fechaCierreObj < $fechaLimite;
}


$_PERMITIR_ACCESO_SIN_RESTICION_TIEMPO = validarReferencia();
// Uso de la función




$ticketId = $_GET['tid'] ?? 2347;

include './get-file.php';

$DB->query("SET lc_time_names = 'es_ES'");

if ($result = $DB->query("SELECT * FROM ost_thread WHERE object_id = {$ticketId}")) {
    if ($thread = $result->fetch_object()) {
        //   die(json_encode(['status'=>'success', 'data'=> $userData]));
    }
}
$_SQL_TICKET = "SELECT t.ticket_id ,t.`number` , t.user_id ,t.dept_id, t.topic_id , t.staff_id 
, t.team_id, t.source , t.isanswered , t.duedate , t.closed , t.lastupdate , t.created
, u.name user_name, ts.name status, d.name departament, tp.topic, tm.name  team,  CONCAT_WS(' ', st.firstname, st.lastname) staff
FROM ost_ticket t
JOIN ost_user u ON u.id  = t.user_id 
JOIN ost_department d ON d.id = t.dept_id 
JOIN ost_ticket_status ts ON ts.id=t.status_id 
JOIN ost_help_topic tp ON tp.topic_id = t.topic_id 
LEFT JOIN ost_team tm ON tm.team_id = t.team_id 
LEFT JOIN ost_staff st ON st.staff_id = t.staff_id 
WHERE t.ticket_id = {$ticketId}";

if ($result = $DB->query($_SQL_TICKET)) {
    if ($ticket = $result->fetch_object()) {
        //   die(json_encode(['status'=>'success', 'data'=> $userData]));
    }
}

// se esta accediendo a esta pagina sin provedir de url de dominio unicomfacauca.edu.co talvez desde un link de correo
// Por lo cual se validara si el ticket tiene menos de 6 meses de cerrado para permitir ver contendido del ticket
if (!$_PERMITIR_ACCESO_SIN_RESTICION_TIEMPO) {
    $_PERMITIR_VER_CONTENIDO = validarFechaCierre($ticket->closed);
} else {
    $_PERMITIR_VER_CONTENIDO = TRUE;
}


$_SQL_ENTRYS =
    "SELECT * FROM (
    SELECT
        te.id,
        te.`type`,	
        te.staff_id,
        UCASE(te.poster) poster ,
        CASE te.source
            WHEN 'Web' THEN 'fa-solid fa-globe'
            WHEN 'Email' THEN 'fas fa-envelope'
            WHEN 'Phone' THEN 'fas fa-phone'
            WHEN 'API' THEN 'fas fa-code'
            WHEN 'WhatsApp' THEN 'fab fa-whatsapp'
            WHEN 'ChatLive' THEN 'fas fa-comments'
            WHEN 'Presencial' THEN 'fas fa-user'
            WHEN 'Other' THEN 'fas fa-question-circle'
            ELSE 'fa-solid fa-globe' 
        END AS source ,
        te.title ,
        te.body ,
        DATE_FORMAT(te.created, '%a, %d %b %Y a las %I:%i %p')  created,
        te.created creado
    FROM ost_thread_entry te
    WHERE te.thread_id = {$thread->id}	
    UNION 
    SELECT 
        te.id,
        'event' as type,
        te.staff_id,
        IF(st.staff_id, CONCAT_WS(' ', st.firstname,st.lastname),'Sistema' ) poster,
        'web' source,
        e.description title,
        te.data body,
        DATE_FORMAT(te.`timestamp`, '%a, %d %b %Y a las %I:%i %p')  created,
        te.`timestamp` creado
    FROM ost_thread_event te
    LEFT JOIN ost_staff st ON st.staff_id = te.staff_id 
    JOIN ost_event e ON e.id = te.event_id 
    WHERE te.thread_id = {$thread->id}
   ) timeline 
   ORDER BY timeline.creado DESC";

if ($entries = $DB->query($_SQL_ENTRYS)) {
  #  echo $_SQL_ENTRYS;
}

?>

<!doctype html>
<html lang="es">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/images/oscar-favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="/images/oscar-favicon-16x16.png" sizes="16x16" />
    <!-- Bootstrap CSS -->
    <link type="text/css" rel="stylesheet" href="/css/bootstrap.min.css" />

    <title>Ticket # <?= $ticketId ?></title>
    <style>
        .timeline-with-icons {
            border-left: 3px solid hsl(0, 0%, 90%);
            position: relative;
            list-style: none;
        }

        .timeline-with-icons .timeline-item {
            position: relative;
        }

        .timeline-with-icons .timeline-item:after {
            position: absolute;
            display: block;
            top: 0;
        }

        .timeline-with-icons .timeline-icon {
            position: absolute;
            left: -54px;
            background-color: hsl(217, 88.2%, 90%);
            color: hsl(217, 88.8%, 35.1%);
            border-radius: 50%;
            height: 41px;
            width: 41px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .span-pre {
            font-size: 0.875em;
            margin-bottom: 1rem;
        }
    </style>
    <script type="text/javascript" src="/js/jquery-3.7.0.min.js?ca95150"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

    <div class="container mt-4">

        <?php if (!$_PERMITIR_VER_CONTENIDO) : ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Este enlace ya no está disponible</h4>
                <p>Este enlace para ver el ticket ya no está disponible desde este medio, ya que ha expirado. Los enlaces de tickets expiran 6 meses después de que se cierran. Para ver los detalles de este ticket, accede al sistema en <a href="https://siu.unicomfacauca.edu.co">https://siu.unicomfacauca.edu.co</a> y selecciona una de las opciones de Soporte.</p>
                <hr>
                <p class="mb-0">El ticket fue cerrado el: <?php echo $ticket->closed ? date('d/m/Y H:i:s', strtotime($ticket->closed)) : 'N/A'; ?>.</p>
            </div>
            <?php die("</div></body></html>") ?>
        <?php endif; ?>

        <?php if (!empty($_SERVER['HTTP_REFERER'])): ?>
            <div class="text-end mb-2">
                <a class="btn btn-primary" href="<?= $_SERVER['HTTP_REFERER'] ?>"><i class="fa-solid fa-rotate-left"></i> Regresar a la página anterior</a>
            </div>
        <?php else: ?>
            <div class="text-end mb-2">
                <a class="btn btn-primary" href="/tickets_list.php?uid=<?= $ticket->user_id ?>"><i class="fa-solid fa-list-check"></i> Lista de Tickets</a>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-ticket-alt"></i> Detalles del Ticket #<?php echo $ticket->number; ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID del Ticket:</strong> <?php echo $ticket->number; ?></p>
                        <p><strong>Nombre del Usuario:</strong> <?php echo $ticket->user_name; ?></p>
                        <p><strong>Departamento:</strong> <?php echo $ticket->departament; ?></p>
                        <p><strong>Tema:</strong> <?php echo $ticket->topic; ?></p>
                        <p><strong>Origen:</strong> <i class="fas fa-envelope"></i> <?php echo $ticket->source; ?></p>
                        <p><strong>Estado:</strong> <?php echo $ticket->status; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Staff:</strong> <?php echo $ticket->staff ?? 'No asignado'; ?></p>
                        <p><strong>Equipo:</strong> <?php echo $ticket->team ?? 'N/A'; ?></p>
                        <p><strong>Fecha de Creación:</strong> <?php echo date('d/m/Y h:i A', strtotime($ticket->created)); ?></p>
                        <p><strong>Última Actualización:</strong> <?php echo date('d/m/Y h:i A', strtotime($ticket->lastupdate)); ?></p>
                        <p><strong>Fecha de Cierre:</strong> <?php echo $ticket->closed ? date('d/m/Y h:i A', strtotime($ticket->closed)) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                <i class="fas fa-info-circle"></i> Información del ticket # <?php echo $ticket->number; ?>, Creado el <?php echo date('d/m/Y h:i A', strtotime($ticket->created)); ?>
            </div>
        </div>
        <!-- Section: Timeline -->
        <section class="py-5">
            <ul class="timeline-with-icons">
                <?php while ($entry = $entries->fetch_object()): ?>

                    <?php if (!in_array($entry->type, ['event', 'N'])): ?>
                        <li class="timeline-item mb-4 mt-3">
                            <hr />
                            <span class="timeline-icon">
                                <?php if ($entry->staff_id): ?>
                                    <i class="fa-solid fa-life-ring text-blue-800 fa-fw"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-user text-blue-800 fa-fw "></i>
                                <?php endif; ?>
                            </span>
                            <h5 class="fw-bold text-primary">
                                <i class="<?= $entry->source ?>"></i>
                                <?= $entry->poster ?> : <?= $entry->title ?>
                            </h5>
                            <p class="text-muted mb-2 fw-bold">Publicado el: <?= $entry->created ?></p>
                            <p class="text-muted">
                                <?= $entry->body ?>
                            </p>
                            <?php
                            $_SQL_ATTACHMENT = "SELECT f.id file_id, f.`key`, f.name, f.`size`,  f.`type`,  f.signature   
                            FROM ost_attachment a
                            JOIN ost_file f ON f.id = a.file_id 
                            WHERE a.object_id = {$entry->id} AND a.inline=0";

                            if ($result = $DB->query($_SQL_ATTACHMENT)) {
                                if ($result->num_rows) {
                                    $files = [];
                                    while ($attachment = $result->fetch_object()) {

                                        if (strpos($attachment->type, 'image') === 0) {
                                            $opt = array('disposition' => 'inline');
                                        } else {
                                            $opt = array('id' => $attachment->file_id);
                                        }
                                        $files[] = "<a href=\"" . generateDownloadUrl($attachment->file_id, strtolower($attachment->key), $attachment->signature, $opts) . "\" target=\"blank\"><i class=\"fa-solid fa-paperclip me-1\"></i>{$attachment->name}</a>";
                                    }

                                    echo  implode(', ', $files);
                                }
                            }

                            ?>
                        </li>

                    <?php elseif ($entry->type == 'event'): ?>
                        <li class="timeline-item mb-4 mt-3">
                        <hr />
                            <span class="timeline-icon">
                                <i class="fa-solid fa-pencil text-indigo-800 fa-fw"></i>
                            </span>
                            <p class="mb-2"><i class="fa-solid fa-user"></i> <strong><?= $entry->poster ?></strong>, <i class="fa-solid fa-pencil"></i> <strong><?= $entry->title?$entry->title:'' ?></strong><?= $entry->title?', ':'' ?>
                            <?= $entry->body?'<i class="fa-solid fa-keyboard"></i>':''?> <span class="span-pre"><?= $entry->body?json_encode(json_decode($entry->body), JSON_UNESCAPED_UNICODE).',':'' ?></span> <i class="fa-solid fa-calendar-day"></i> El: <?= $entry->created ?></p>
                        </li>
                    <?php endif; ?>

                <?php endwhile; ?>
            </ul>
            <?php if (!empty($_SERVER['HTTP_REFERER'])): ?>
                <div class="text-end mb-2">
                    <a class="btn btn-primary" href="<?= $_SERVER['HTTP_REFERER'] ?>"><i class="fa-solid fa-rotate-left"></i> Regresar a la página anterior</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Section: Timeline -->

    </div>
    <script>
        $(function() {


            $("img").each(function() {
                // Obtener el valor de 'src' de la imagen actual
                var src = $(this).attr("src").replace('cid:', '');
                var img = this;
                // Enviar la solicitud GET a '/get-file.php' con el valor de 'src' como parámetro 'key'
                $.getJSON('/get-file.php', {
                    key: src
                }, function(response) {
                    // Procesar la respuesta aquí
                    console.log(img);
                    $(img).attr('src', response.src).addClass("img-fluid").removeAttr('width').removeAttr('height');
                });
            });
        });
    </script>
</body>

</html>