<?php
require_once __DIR__ . '/vendor/autoload.php';


use App\Capgo;
use App\CodePush;


$f3 = Base::instance();

$f3->set('config', [
    'upload_key' => 'my-secret-key', // Change this to your actual upload key
    'dirs' => [
        'storage_path' => __DIR__ . '/storage',
        'codepush' => 'codepush',
        'capgo' => 'capgo',
    ]
]);

$f3->route("POST|GET /capgo/update_check", function ($f3) {
    Capgo::updateCheck();
});


$f3->route("POST|GET /v0.1/public/codepush/update_check", function ($f3) {
    CodePush::updateCheck();
});


$f3->route('POST /v0.1/public/codepush/report_status/deploy', function ($f3) {
    echo json_encode(['status' => 'received']);
});
$f3->route('POST /v0.1/public/codepush/report_status/download', function ($f3) {
    echo json_encode(['status' => 'received']);
});


$f3->route('POST /upload', function ($f3) {
    $config = $f3->get('config');

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

    $fileName =  basename($file['name']);

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

    echo json_encode([
        'status' => 'ok',

    ]);
});




$f3->run();