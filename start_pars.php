<?php
session_start();
require_once 'core/functions.php';

$currentUser = getCurrentUser();

if ($currentUser) {
    if (!empty($_POST['course']) && !empty($_POST['group'])) {
        header('Content-Type: application/json');
        $url = getLink($_POST['course'], $_POST['group']);

        $answer = request_func($url);
        if (!empty($answer)) {
            $response = json_decode($answer, true);
        } else {
            $response = ['success' => 'false'];
        }
        $response['course'] = $_POST['course'];
        $response['group'] = $_POST['group'];

        echo json_encode($response);
    }
}