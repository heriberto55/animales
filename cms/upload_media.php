<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
cms_start_session();
cms_require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

if (!cms_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'La sesion expiro. Vuelve a ingresar.']);
    exit;
}

if (empty($_FILES['media']) || !is_array($_FILES['media'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No se recibio ningun archivo.']);
    exit;
}

$file = $_FILES['media'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No se pudo subir el archivo.']);
    exit;
}

$maxBytes = 80 * 1024 * 1024;
if ((int) ($file['size'] ?? 0) > $maxBytes) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'El archivo es demasiado grande.']);
    exit;
}

$tmpName = (string) ($file['tmp_name'] ?? '');
$mime = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = (string) finfo_file($finfo, $tmpName);
        finfo_close($finfo);
    }
}

$allowedByMime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/ogg' => 'ogv',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'text/plain' => 'txt',
    'text/csv' => 'csv',
    'application/zip' => 'zip',
    'application/x-zip-compressed' => 'zip',
];

$originalFilename = (string) ($file['name'] ?? 'archivo');
$originalExtension = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
$blockedExtensions = [
    'php', 'phtml', 'phar', 'html', 'htm', 'js', 'exe', 'bat', 'cmd', 'ps1',
    'sh', 'msi', 'com', 'scr', 'jar', 'asp', 'aspx', 'jsp'
];
$documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'];

if (in_array($originalExtension, $blockedExtensions, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Este tipo de archivo no esta permitido.']);
    exit;
}

$extension = in_array($originalExtension, $documentExtensions, true)
    ? $originalExtension
    : ($allowedByMime[$mime] ?? $originalExtension);
if (!preg_match('/^[a-z0-9]{1,8}$/', $extension)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No se pudo reconocer el tipo de archivo.']);
    exit;
}

if (!is_dir(CMS_UPLOAD_DIR)) {
    mkdir(CMS_UPLOAD_DIR, 0755, true);
}

$originalName = pathinfo($originalFilename, PATHINFO_FILENAME);
$name = cms_slug($originalName) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
$destination = CMS_UPLOAD_DIR . '/' . $name;

if (!move_uploaded_file($tmpName, $destination)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No se pudo guardar el archivo.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'url' => CMS_UPLOAD_URL . '/' . $name,
    'type' => strpos($mime, 'image/') === 0 ? 'image' : (strpos($mime, 'video/') === 0 ? 'video' : 'file'),
    'fileType' => $extension,
    'name' => $originalFilename,
]);
