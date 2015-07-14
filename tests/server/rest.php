<?php

$result = [];

$result['content-type'] = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : null;
$result['method'] = $_SERVER['REQUEST_METHOD'];
$result['query_string'] = $_GET;
$result['post_string'] = $_POST;
$result['payload'] = file_get_contents('php://input');

echo json_encode($result);
