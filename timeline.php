<?php
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
        DATE_FORMAT(te.created, '%a, %d %b %Y a las %I:%i %p')  created
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
        DATE_FORMAT(te.`timestamp`, '%a, %d %b %Y a las %I:%i %p')  created
    FROM ost_thread_event te
    LEFT JOIN ost_staff st ON st.staff_id = te.staff_id 
    JOIN ost_event e ON e.id = te.event_id 
    WHERE te.thread_id = {$thread->id}
   ) timeline 
   ORDER BY timeline.created ASC";

if ($entries = $DB->query($_SQL_ENTRYS)) {
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
    <link type="text/css" rel="stylesheet"  href="/css/bootstrap.min.css" />

    <title>Ticket # <?= $ticketId ?></title>
    <style>
        .timeline-with-icons {
            border-left: 1px solid hsl(0, 0%, 90%);
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
            left: -48px;
            background-color: hsl(217, 88.2%, 90%);
            color: hsl(217, 88.8%, 35.1%);
            border-radius: 50%;
            height: 31px;
            width: 31px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script type="text/javascript" src="/js/jquery-3.7.0.min.js?ca95150"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

    <div class="container mt-4">
        <?php if (!empty($_SERVER['HTTP_REFERER'])): ?>
            <div class="text-end mb-2">
                <a class="btn btn-primary" href="<?= $_SERVER['HTTP_REFERER'] ?>"><i class="fa-solid fa-rotate-left"></i> Regresar a la página anterior</a>
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
                        <p><strong>Fecha de Creación:</strong> <?php echo date('d/m/Y H:i:s', strtotime($ticket->created)); ?></p>
                        <p><strong>Última Actualización:</strong> <?php echo date('d/m/Y H:i:s', strtotime($ticket->lastupdate)); ?></p>
                        <p><strong>Fecha de Cierre:</strong> <?php echo $ticket->closed ? date('d/m/Y H:i:s', strtotime($ticket->closed)) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                <i class="fas fa-info-circle"></i> Información del ticket # <?php echo $ticket->number; ?>, Generada el <?= date('Y-m-d H:i:s') ?>
            </div>
        </div>
        <!-- Section: Timeline -->
        <section class="py-5">
            <ul class="timeline-with-icons">
                <?php while ($entry = $entries->fetch_object()): ?>

                    <?php if (!in_array($entry->type, ['event', 'N'])): ?>
                        <li class="timeline-item mb-4 mt-1">
                            <hr />
                            <span class="timeline-icon">
                                <?php if ($entry->staff_id): ?>
                                    <i class="fa-solid fa-life-ring text-blue-800 fa-sm fa-fw"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-user text-blue-800 fa-sm fa-fw"></i>
                                <?php endif; ?>
                            </span>
                            <h5 class="fw-bold">
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
                        <li class="timeline-item mb-3 mt-3">
                            <span class="timeline-icon">
                                <i class="fa-solid fa-pen text-indigo-800 fa-sm fa-fw"></i>
                            </span>
                            <p class="mb-2"><?= $entry->poster ?>, Tipo Edición: <?= $entry->title ?>, <?= $entry->body ?>, el: <?= $entry->created ?></p>
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