<?php
require_once 'config.php';
require_once 'theme.php';
$message = '';
session_start();
$isLoggedIn = !empty($_SESSION['paste_admin']);
$themeAssets = resolve_theme_assets($config);

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Show error messages
if (!empty($_GET['error'])) {
    if ($_GET['error'] === 'notfound') {
        $message = 'Error: Paste not found.';
    } elseif ($_GET['error'] === 'expired') {
        $message = 'Error: This paste has expired.';
    }
}

// If there's an ID in query string, redirect to view
if (!empty($_GET['id'])) {
    header('Location: view.php?id=' . $_GET['id']);
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle new paste creation (logged in only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn && (($_POST['action'] ?? '') === 'create')) {
    $content = $_POST['content'] ?? '';
    $filename = $_POST['filename'] ?? '';
    $syntax = $_POST['syntax'] ?? 'plaintext';
    $expiry = $_POST['expiry'] ?? $config['default_expiry'];
    $burn = isset($_POST['burn']);
    $hidden = isset($_POST['hidden']);
    $isEncrypted = isset($_POST['is_encrypted']) && $_POST['is_encrypted'] === '1';
    $iv = $isEncrypted ? (preg_replace('/[^A-Za-z0-9\/_+=-]/', '', $_POST['iv'] ?? '') ?? '') : '';
    
    if (!empty($content)) {
        $id = bin2hex(random_bytes(8));
        $created = time();
        
        // Calculate expiry timestamp
        $expires = 'never' === $expiry ? null : time() + [
            '5min' => 300,
            '1hour' => 3600,
            '1day' => 86400,
            '1week' => 604800
        ][$expiry];
        
        $paste = [
            'id' => $id,
            'content' => $content,
            'filename' => $filename,
            'syntax' => $syntax,
            'created' => $created,
            'expires' => $expires,
            'burn' => $burn,
            'hidden' => $hidden,
            'encrypted' => $isEncrypted,
            'iv' => $isEncrypted ? $iv : '',
            'views' => 0
        ];

        file_put_contents($config['data_dir'] . "/{$id}.json", json_encode($paste));
        if ($burn) {
            $query = '?created=' . urlencode($id);
            if ($isEncrypted) {
                $query .= '&enc=1';
            }
            header('Location: index.php' . $query);
        } else {
            header('Location: view.php?id=' . $id);
        }
        exit;
    }
}

// Load and display pastes
$pastes = [];
$files = glob($config['data_dir'] . '/*.json') ?: [];
foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    if ($data) {
        if (isset($data['key'])) {
            unset($data['key']);
        }
        // Check if expired
        if ($data['expires'] && time() > $data['expires']) {
            unlink($file);
            continue;
        }
        $pastes[] = $data;
    }
}

// Sort by newest
usort($pastes, fn($a, $b) => $b['created'] - $a['created']);

function timeLeft($expires) {
    if (!$expires) return 'Never';
    $left = $expires - time();
    if ($left <= 0) return 'Expired';
    if ($left < 60) return $left . 's';
    if ($left < 3600) return floor($left / 60) . 'm';
    if ($left < 86400) return floor($left / 3600) . 'h';
    return floor($left / 86400) . 'd';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['base']); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['theme']); ?>">
    <script defer src="<?php echo htmlspecialchars($themeAssets['script']); ?>"></script>
</head>
<body class="<?php echo htmlspecialchars($themeAssets['body_class']); ?>" data-base-url="<?php echo htmlspecialchars($config['base_url']); ?>">
    <div class="header">
        <h1>📋 <?php echo htmlspecialchars($config['site_name']); ?></h1>
        <?php if ($isLoggedIn): ?>
            <a href="?logout=1" class="login-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login to create</a>
        <?php endif; ?>
    </div>

    <?php
        $createdId = $_GET['created'] ?? '';
        $createdEnc = isset($_GET['enc']) && $_GET['enc'] === '1';
    ?>
    <?php if ($message || $createdId): ?>
        <div class="message" <?php if ($createdId): ?>data-created-id="<?php echo htmlspecialchars($createdId); ?>" data-created-enc="<?php echo $createdEnc ? '1' : '0'; ?>"<?php endif; ?>>
            <?php echo $message ? htmlspecialchars($message) : 'Paste created!'; ?>
        </div>
    <?php endif; ?>
    
<?php if ($isLoggedIn): ?>
    <div class="new-form">
        <h3>New Paste</h3>
        <form method="post" id="paste-form" autocomplete="off">
            <input type="hidden" name="action" value="create">
            <input type="text" name="filename" placeholder="Filename (optional)" autocomplete="off">
            <textarea name="content" placeholder="Paste content here..."></textarea>
            <div class="form-row form-row--dual">
                <select name="syntax">
                    <option value="plaintext">Plain Text</option>
                    <option value="code">Code (auto-detect)</option>
                    <option value="markdown">Markdown</option>
                </select>
                <select name="expiry" id="expirySelect">
                    <option value="never">Never expire</option>
                    <option value="5min">5 minutes</option>
                    <option value="1hour">1 hour</option>
                    <option value="1day">1 day</option>
                    <option value="1week">1 week</option>
                </select>
            </div>
            <div class="form-options">
                <label class="checkbox-inline"><input type="checkbox" name="burn" id="burnCheck" onchange="toggleExpiry()"> Burn after reading</label>
                <label class="checkbox-inline"><input type="checkbox" name="hidden"> Hidden (only visible when logged in)</label>
            </div>
            <input type="hidden" name="is_encrypted" value="0">
            <input type="hidden" name="iv" value="">
            <script>
                function toggleExpiry() {
                    document.getElementById('expirySelect').disabled = document.getElementById('burnCheck').checked;
                }
                toggleExpiry();
            </script>
            <button type="submit" name="create" class="button-like">Create Paste</button>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="paste-list">
        <h3>Recent Pastes</h3>
        <?php if (empty($pastes)): ?>
            <p>No pastes yet.</p>
        <?php else: ?>
            <?php foreach ($pastes as $paste): ?>
                <?php if (!empty($paste['hidden']) && !$isLoggedIn) continue; ?>
                <div class="paste-item">
                    <a href="<?php echo htmlspecialchars($config['base_url'] . 'view.php?id=' . $paste['id']); ?>" data-paste-id="<?php echo htmlspecialchars($paste['id']); ?>"><?php echo !empty($paste['filename']) ? htmlspecialchars($paste['filename']) : htmlspecialchars($paste['id']); ?></a>
                    <span class="paste-meta">
                        - <?php echo 'code' === $paste['syntax'] ? 'Code' : 'Plain Text'; ?>
                        - <?php echo date('Y-m-d H:i', $paste['created']); ?>
                        - <?php echo timeLeft($paste['expires']); ?>
                        <?php if ($paste['burn']): ?> - 🔥<?php endif; ?>
                        <?php if (!empty($paste['encrypted'])): ?> - 🔐<?php endif; ?>
                        <?php if (!empty($paste['hidden'])): ?> - 🔒<?php endif; ?>
                    </span>
                    <?php if ($isLoggedIn): ?>
                        <a href="delete.php?id=<?php echo $paste['id']; ?>" class="danger-link" onclick="return confirm('Delete this paste?')">[Delete]</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
