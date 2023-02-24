<?php
require_once 'vendor/autoload.php';

$request_url = $_SERVER['REQUEST_URI'];
$path = parse_url($request_url, PHP_URL_PATH); 
$path = rtrim($path, '/');
// var_dump($path);
if (file_exists("download/".$path.".html") || file_exists("download/" . $path . ".css") || file_exists("download/" . $path . ".js") || $path == "") {
    # code...
    if($path == "") $path = "index";
    include_once "download/" . $path . ".html";
}
else{
    if (file_exists("download/404.html")) {
        # code...
        include_once "download/404.html";
    }
    else{
        echo "404 page not found";
    }
}