<?php

define("INCLUDE_DIR", dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR);


function _genUrlSignature($id, $key, $signature, $expires)
{
    $pieces = array(
        'Host=' . $_SERVER['HTTP_HOST'],
        'Path=' . '/',
        'Id=' . $id,
        'Key=' . strtolower($key),
        'Hash=' . $signature,
        'Expires=' . $expires,
    );
    return hash_hmac('sha1', implode("\n", $pieces), SECRET_SALT);
}

function _gmtime($time = false, $user = false)
{
    global $cfg;

    $tz = new DateTimeZone($user ? $cfg->getDbTimezone($user) : 'UTC');

    if ($time && is_numeric($time))
        $time = DateTime::createFromFormat('U', $time);
    elseif (!($time = new DateTime($time ?: 'now'))) {
        // Old standard
        return time() - date('Z');
    }

    return $time->getTimestamp() - $tz->getOffset($time);
}

function generateDownloadUrl($id, $key, $hash, $options = array())
{

    // Expire at the nearest midnight, allow at least12 hrs access
    $minage = @$options['minage'] ?: 43200;
    $gmnow = _gmtime() +  $options['minage'];
    $expires = $gmnow + 86400 - ($gmnow % 86400);

    // Generate a signature based on secret content
    $signature = _genUrlSignature($id, $key, $hash, $expires);

    // Handler / base url
    $handler = @$options['handler'] ?: '/' . 'file.php';

    // Return sanitized query string
    $args = array(
        'key' => $key,
        'expires' => $expires,
        'signature' => $signature,
    );

    if (isset($options['disposition']))
        $args['disposition'] =  $options['disposition'];

    if (isset($options['id']))
        $args['id'] =  $options['id'];

    return sprintf('%s?%s', $handler, http_build_query($args));
}


include './include/ost-config.php';
$DB = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

if (!empty($_GET['key'])) {


    if ($result = $DB->query("SELECT * FROM ost_file WHERE `key` = '{$_GET['key']}'")) {
        if ($file = $result->fetch_object()) {
            //   die(json_encode(['status'=>'success', 'data'=> $userData]));
        }
    }

    header('Content-Type: application/json');
    die(json_encode([
        'src' => generateDownloadUrl($file->id, strtolower($file->key), $file->signature, array('disposition' => 'inline')),
        'file' => $file,
    ]));
}
