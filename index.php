<?php
// Простая конфигурация
$config = [
    'upload_key' => 'my-secret-key', // Change this to your actual upload key
    'dirs' => [
        'storage_path' => __DIR__ . '/storage',
        'codepush' => 'codepush',
        'capgo' => 'capgo',
    ]
];

// Простой роутер
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Убираем trailing slash
$path = rtrim($path, '/');

// Роутинг
switch ($path) {
    case '/capgo/update_check':
        if ($requestMethod === 'GET' || $requestMethod === 'POST') {
            handleCapgoUpdateCheck($config);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;

    case '/v0.1/public/codepush/update_check':
        if ($requestMethod === 'GET' || $requestMethod === 'POST') {
            handleCodePushUpdateCheck($config);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;

    case '/v0.1/public/codepush/report_status/deploy':
        if ($requestMethod === 'POST') {
            echo json_encode(['status' => 'received']);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;

    case '/v0.1/public/codepush/report_status/download':
        if ($requestMethod === 'POST') {
            echo json_encode(['status' => 'received']);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;

    case '/upload':
        if ($requestMethod === 'POST') {
            handleUpload($config);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}

function handleCapgoUpdateCheck($config)
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $app_id = $data['app_id'] ?? '';
    $version_build = $data['version_build'] ?? '';
    $version_name = (int) preg_replace('/\D/', '', $data['version_name'] ?? '');
    $platform = $data['platform'] ?? '';

    if (!$app_id || !$version_build || !$platform) {
        echo json_encode(['available' => false]);
        return;
    }

    $dir = "{$config['dirs']['storage_path']}/{$config['dirs']['capgo']}/{$app_id}/{$version_build}/{$platform}";
    if (!is_dir($dir)) {
        echo json_encode(['available' => false]);
        return;
    }

    $files = scandir($dir);
    $found = [];

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = "{$scheme}://{$host}";

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $info = explode('-', $file);
        $fileVersion = (int)($info[3] ?? 0);

        if ($fileVersion > $version_name) {
            $found[] = [
                'available' => true,
                'version' => $fileVersion,
                'url' => $baseUrl . "/storage/{$config['dirs']['capgo']}/$file",
                'mandatory' => true
            ];
        }
    }

    usort($found, fn($a, $b) => $b['version'] <=> $a['version']);
    echo json_encode($found[0] ?? ['available' => false]);
}

function handleCodePushUpdateCheck($config)
{
    $deployment_key = $_GET['deployment_key'] ?? '';
    $app_version = $_GET['app_version'] ?? '';
    $label = (int) preg_replace('/\D/', '', $_GET['label'] ?? '0');

    if (!$deployment_key || !$app_version) {
        echo json_encode(['update_info' => ['is_available' => false]]);
        return;
    }

    $dir = "{$config['dirs']['storage_path']}/{$config['dirs']['codepush']}/{$deployment_key}/{$app_version}";
    if (!is_dir($dir)) {
        echo json_encode(['update_info' => ['is_available' => false]]);
        return;
    }

    $files = scandir($dir);
    $found = [];

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = "{$scheme}://{$host}";

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $info = explode('-', $file);
        $fileLabel = (int)($info[2] ?? 0);
        $packageHash = $info[3] ?? null;

        if ($fileLabel > $label) {
            $found[] = [
                'download_url' => $baseUrl . "/storage/{$config['dirs']['codepush']}/$file",
                'description' => "",
                'is_available' => true,
                'is_disabled' => false,
                'target_binary_range' => $app_version,
                'label' => $fileLabel,
                'package_hash' => $packageHash,
                'package_size' => filesize("$dir/$file"),
                'should_run_binary_version' => false,
                'update_app_version' => false,
                'is_mandatory' => true,
            ];
        }
    }

    usort($found, fn($a, $b) => $b['label'] <=> $a['label']);
    echo json_encode(['update_info' => $found[0] ?? ['is_available' => false]]);
}

function handleUpload($config)
{
    $type = $_POST['type'] ?? '';
    $uploadKey = $_POST['upload_key'] ?? '';

    if ($config['upload_key'] == 'my-secret-key') {
        http_response_code(403);
        echo json_encode(['error' => 'Upload key is not set']);
        return;
    }

    if ($uploadKey !== $config['upload_key']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid upload key']);
        return;
    }

    if (!in_array($type, ['capgo', 'codepush'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type. Must be "capgo" or "codepush".']);
        return;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload failed']);
        return;
    }

    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $path = explode('-', $fileName);

    if ($type == 'capgo') {
        $storageDir = $config['dirs']['storage_path'] . '/' . $config['dirs'][$type] . '/' . $path[0] . '/' . $path[1] . '/' . $path[2];
    } else {
        $storageDir = $config['dirs']['storage_path'] . '/' . $config['dirs'][$type] . '/' . $path[0] . '/' . $path[1];
    }

    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    $targetPath = $storageDir . '/' . basename($file['name']);

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file']);
        return;
    }

    echo json_encode(['status' => 'ok']);
}