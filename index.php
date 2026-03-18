<?php
// Автозагрузка классов
spl_autoload_register(function ($class_name) {
    $paths = ['models/', 'controllers/', 'config/'];
    foreach($paths as $path) {
        $file = __DIR__ . '/' . $path . $class_name . '.php';
        if(file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Разрешаем CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));
$method = $_SERVER['REQUEST_METHOD'];

if($uri[0] !== 'api') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Not found']);
    exit;
}

$resource = $uri[1] ?? null;
$id = $uri[2] ?? null;
$subId = $uri[3] ?? null;

switch($resource) {
    case 'projects':
        $controller = new ProjectController($db);
        
        // Сначала проверяем более специфичные маршруты
        if($id === 'my' && $method === 'GET') {
            $controller->my();
        } elseif($id && $subId === 'bid' && $method === 'POST') {
            $bidController = new BidController($db);
            $bidController->createProjectBid($id);
        } elseif($id && $subId === 'bids' && $method === 'GET') {  // ЭТО ДОЛЖНО БЫТЬ РАНЬШЕ
            $bidController = new BidController($db);
            $bidController->getProjectBids($id);
        } elseif($id && $method === 'GET') {  // ЭТО ПОСЛЕ
            $controller->show($id);
        } elseif($id && $method === 'PUT') {
            $controller->update($id);
        } elseif($id && $method === 'DELETE') {
            $controller->delete($id);
        } elseif(!$id && $method === 'GET') {
            $controller->index();
        } elseif(!$id && $method === 'POST') {
            $controller->store();
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        }
        break;

    case 'auth':
        $controller = new AuthController($db);
        if($id === 'register' && $method === 'POST') {
            $controller->register();
        } elseif($id === 'login' && $method === 'POST') {
            $controller->login();
        } elseif($id === 'me' && $method === 'GET') {
            $controller->me();
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
        }
        break;

    case 'bids':
        $controller = new BidController($db);
        if($id === 'my' && $method === 'GET') {
            $controller->my();
        } elseif($id && $subId === 'accept' && $method === 'POST') {
            $controller->accept($id);
        } elseif($id && $subId === 'reject' && $method === 'POST') {
            $controller->reject($id);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
        }
        break;

    case 'tasks':
        $controller = new TaskController($db);
        if($id === 'available' && $method === 'GET') {
            $controller->available();
        } elseif($id && $subId === 'bids' && $method === 'POST') {
            $bidController = new BidController($db);
            $bidController->createTaskBid($id);
        } elseif($id && $method === 'GET') {
            $controller->show($id);
        } elseif($id && $method === 'PUT') {
            $controller->update($id);
        } elseif(!$id && $method === 'GET') {
            $controller->index();
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Resource not found']);
        break;
}
?>