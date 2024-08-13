<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../src/config/config.php";

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

$routes = [
    "event" => ["controller" => "event", "auth" => false],
    "validate" => ["controller" => "validate", "auth" => false],
    "explore" => ["controller" => "explore", "auth" => false],

    "new" => ["controller" => "new", "auth" => true],
    "profile" => ["controller" => "profile", "auth" => true],
    "settings" => ["controller" => "settings", "auth" => true],
    "dashboard" => ["controller" => "dashboard", "auth" => true],

    "signup" => ["controller" => "signup", "auth" => false],
    "login" => ["controller" => "login", "auth" => false],
    "logout" => ["controller" => "logout", "auth" => true],

    "" => ["controller" => "index", "auth" => false],
    "dpa" => ["controller" => "dpa", "auth" => false],
    "about" => ["controller" => "about", "auth" => false],
    "terms" => ["controller" => "terms", "auth" => false],
    "contact" => ["controller" => "contact", "auth" => false],
    "privacy" => ["controller" => "privacy", "auth" => false],
];

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = trim($requestPath, "/");
$method = $_SERVER["REQUEST_METHOD"];
$segments = explode("/", $request);

if (array_key_exists($segments[0], $routes)) {
    $route = $routes[$segments[0]];

    if ($route['auth'] && !isLoggedIn()) {
        header('Location: /login');
        exit;
    }

    if ($method === 'POST' && $segments[0] === "signup") {
        require __DIR__ . "/../src/handlers/signup_handler.php";
    } elseif ($method === 'POST' && $segments[0] === "login") {
        require __DIR__ . "/../src/handlers/login_handler.php";
    } elseif ($method === 'POST' && $segments[0] === "events") {
        require __DIR__ . "/../src/handlers/payment_handler.php";
    } else {
        $controller = basename($route['controller']);
        require __DIR__ . "/../src/routes/" . $controller . ".php";
    }
} else {
    http_response_code(404);
    require __DIR__ . "/../src/views/404.php";
}
