<?php

$result = [];

$result['content-type'] = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : null;
$result['method'] = $_SERVER['REQUEST_METHOD'];
$result['query_string'] = $_GET;
$result['post_string'] = $_POST;
if (isset($_SERVER['PHP_AUTH_USER'])) {
    $result["authinfo"] = $_SERVER['PHP_AUTH_USER'] . ":" . $_SERVER['PHP_AUTH_PW'];
}
if (isset($_SERVER['HTTP_REFERER'])) {
    $result["referer"] = $_SERVER['HTTP_REFERER'];
}
if (isset($_SERVER['HTTP_X_CUSTOM_HEADER'])) {
    $result["custom_header"] = $_SERVER['HTTP_X_CUSTOM_HEADER'];
}

$result['payload'] = file_get_contents('php://input');

if (count($_FILES) > 0) {
    $result['files'] = $_FILES;
    foreach ($result['files'] as $key => $values) {
        $result['files'][$key]["content"] = file_get_contents($values["tmp_name"]);
        unset($result['files'][$key]["tmp_name"]);
    }
}

header('Content-Type: application/json;charset=utf-8;');
//echo json_encode($_SERVER);
//die();
echo json_encode($result);
