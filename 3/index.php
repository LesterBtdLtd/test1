<?php

include "TreeController.php";

define('AJAX_URL', $_SERVER['REQUEST_URI']);
define('APP_URL', str_replace('index.php', '', AJAX_URL));

$TreeController = new TreeController();
$response = '';

try {
    if(isset($_GET['action'])) {
        $response = array(
            'success'   => true,
            'message'   => 'All right',
            'data'      => $TreeController->do($_GET['action'], $_GET)
        );
    }
} catch (Exception $ex) {
    header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
    header('Status:  500 Server Error');
    $response = array(
        'success'   => false,
        'message'   => $ex->getMessage()
    );
}

// if page used like API, return json-response and stop script
if(!empty($response)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    die();
}

$TreeController->render();