<?php
require_once 'vendor/autoload.php';

$request_url = $_SERVER['REQUEST_URI'];
$path = parse_url($request_url, PHP_URL_PATH); 
$path = rtrim($path, '/');
// var_dump($path);
if($path == "") $path = "index";
if (file_exists("download/".$path.".html") || $path == "") {
    # code...
    include_once "download/" . $path . ".html";
}
elseif (file_exists("download/" . $path . ".css")) {
    # code...
    include_once "download/" . $path . ".css";
}
elseif (file_exists("download/" . $path . ".js")) {
    # code...
    include_once "download/" . $path . ".js";
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