<?php
require_once 'config.php';

$id = $_GET['id'] ?? '';
$file = rtrim($config['data_dir'], '/')."/{$id}.json";

if (!$id || !file_exists($file)) {
    http_response_code(404);
    echo "Paste not found";
    exit;
}

$data = json_decode(file_get_contents($file), true);
if (!$data) {
    http_response_code(500);
    echo "Invalid paste";
    exit;
}

if ($data['burn'] ?? false) {
    if (($data['views'] ?? 0) > 0) {
        unlink($file);
        http_response_code(410);
        echo "Paste burned";
        exit;
    }
    $data['views'] = ($data['views'] ?? 0) + 1;
    file_put_contents($file, json_encode($data));
}

if (!empty($data['encrypted'])) {
    header('Content-Type: text/html; charset=utf-8');
    $baseUrl = htmlspecialchars($config['base_url'], ENT_QUOTES, 'UTF-8');
    $idEsc = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
    $contentEsc = htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8');
    $ivEsc = htmlspecialchars($data['iv'] ?? '', ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Raw Paste</title>
</head>
<body data-base-url="<?php echo $baseUrl; ?>" data-raw="1">
    <div id="paste-data" data-id="<?php echo $idEsc; ?>" data-encrypted="1" data-content="<?php echo $contentEsc; ?>" data-iv="<?php echo $ivEsc; ?>" data-plain=""></div>
    <script src="encryption.js"></script>
</body>
</html>
<?php
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo $data['content'];
