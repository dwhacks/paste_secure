<?php
require_once 'config.php';
require_once 'theme.php';
session_start();
$isLoggedIn = !empty($_SESSION['paste_admin']);
$id = $_GET['id'] ?? '';
$error = '';
$themeAssets = resolve_theme_assets($config);

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

function timeLeft($expires) {
    if (!$expires) return 'Never';
    $left = $expires - time();
    if ($left <= 0) return 'Expired';
    if ($left < 60) return $left . 's';
    if ($left < 3600) return floor($left / 60) . 'm';
    if ($left < 86400) return floor($left / 3600) . 'h';
    return floor($left / 86400) . 'd';
}

if (!$id) {
    header('Location: index.php?error=notfound');
    exit;
}

$dataFile = $config['data_dir'] . "/{$id}.json";
if (!file_exists($dataFile)) {
    header('Location: index.php?error=notfound');
    exit;
}

$data = json_decode(file_get_contents($dataFile), true);
if (isset($data['key'])) {
    unset($data['key']);
}

// Check if expired
if ($data['expires'] && time() > $data['expires']) {
    unlink($dataFile);
    header('Location: index.php?error=expired');
    exit;
}

// Check burn after reading
if ($data['burn'] && $data['views'] > 0) {
    unlink($config['data_dir'] . "/{$id}.json");
    $error = 'This paste has been burned and can no longer be viewed.';
    $data = null;
} else {
    // Increment view count
    $data['views']++;
    file_put_contents($config['data_dir'] . "/{$id}.json", json_encode($data));
}

// Handle update (logged in only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn && (($_POST['action'] ?? '') === 'update') && $data) {
    $isEncryptedPost = isset($_POST['is_encrypted']) && $_POST['is_encrypted'] === '1';
    $ivPost = $isEncryptedPost ? (preg_replace('/[^A-Za-z0-9\/_+=-]/', '', $_POST['iv'] ?? '') ?? '') : '';
    $data['content'] = $_POST['content'] ?? '';
    $data['filename'] = $_POST['filename'] ?? '';
    $data['syntax'] = $_POST['syntax'] ?? 'plaintext';
    $data['hidden'] = isset($_POST['hidden']);
    $data['encrypted'] = $isEncryptedPost;
    $data['iv'] = $isEncryptedPost ? $ivPost : '';
    file_put_contents($config['data_dir'] . "/{$id}.json", json_encode($data));
    header('Location: view.php?id=' . $id);
    exit;
}

$isEncryptedData = $data && !empty($data['encrypted']);
$ivValue = $isEncryptedData ? ($data['iv'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_name']); ?> - <?php echo htmlspecialchars($id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css?v=2">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js?v=2"></script>
    <script defer src="<?php echo htmlspecialchars($themeAssets['script']); ?>"></script>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['base']); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['theme']); ?>">
    <style>
        textarea { height: 300px; }
    </style>
</head>
<body class="<?php echo htmlspecialchars($themeAssets['body_class']); ?>" data-base-url="<?php echo htmlspecialchars($config['base_url']); ?>">
<?php if ($data && $data['burn']): ?>
    <script>alert('Warning: This paste will be deleted after being viewed. Close this message and refresh to delete it.');</script>
<?php endif; ?>
    <div class="header">
        <a href="<?php echo $config['base_url']; ?>">← Back to list</a>
        <?php if ($isLoggedIn): ?>
            <span>
                <a href="index.php?logout=1">Logout</a>
                |
                <a href="<?php echo $config['base_url']; ?>delete.php?id=<?php echo $id; ?>" class="danger-link" onclick="return confirm('Delete this paste?')">Delete</a>
            </span>
        <?php else: ?>
            <a href="login.php">Login to edit</a>
        <?php endif; ?>
    </div>
    <?php if ($data): ?>
        <div id="paste-data" data-id="<?php echo htmlspecialchars($id); ?>" data-encrypted="<?php echo $isEncryptedData ? '1' : '0'; ?>" data-content="<?php echo $isEncryptedData ? htmlspecialchars($data['content']) : ''; ?>" data-iv="<?php echo $isEncryptedData ? htmlspecialchars($ivValue) : ''; ?>" data-plain="<?php echo !$isEncryptedData ? htmlspecialchars($data['content']) : ''; ?>"></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($data): ?>
        <div class="content">
            <h3><?php echo !empty($data['filename']) ? htmlspecialchars($data['filename']) : 'Paste: ' . htmlspecialchars($id); ?></h3>
            <?php if ($isEncryptedData): ?>
                <div class="info-box">
                    This paste is end-to-end encrypted. Copy the direct link (including the part after the <code>#</code>). Without it the contents cannot be recovered.
                </div>
            <?php endif; ?>
            <div class="direct-link-block">
                <label for="direct-link">Direct link:</label>
                <div class="direct-link-actions">
                    <input id="direct-link" type="text" data-base="<?php echo htmlspecialchars($config['base_url'] . 'view.php?id=' . $id); ?>" value="<?php echo htmlspecialchars($config['base_url'] . 'view.php?id=' . $id); ?>" readonly>
                    <button id="copy-btn" type="button" onclick="copyLink()" class="button-like">Copy</button>
                    <a href="<?php echo htmlspecialchars($config['base_url'] . 'raw.php?id=' . $id); ?>" target="_blank" class="secondary-btn button-like">Raw</a>
                </div>
            </div>
            <p class="meta">
                Syntax: <?php echo htmlspecialchars($data['syntax']); ?> | 
                Views: <?php echo $data['views']; ?> | 
                Expires: <?php echo timeLeft($data['expires']); ?> |
                Created: <?php echo date('Y-m-d H:i', $data['created']); ?>
                <span id="detected-language" style="display:none;"> | Detected: <span id="detected-language-value"></span></span>
                <?php if ($data['burn']): ?> | 🔥 Burn after reading<?php endif; ?>
                <?php if ($isEncryptedData): ?> | 🔐 Encrypted<?php endif; ?>
                <?php if (!empty($data['hidden'])): ?> | 🔒 Hidden<?php endif; ?>
            </p>
            
            <?php if ($isLoggedIn): ?>
                <div style="margin-bottom: 10px;">
                    <button type="button" onclick="showView()" class="button-like">View</button>
                    <button type="button" onclick="showEdit()" class="button-like">Edit</button>
                </div>
                <div id="view-mode" style="display:none;">
                    <pre><code id="decrypted-view" class="hljs"><?php echo $isEncryptedData ? 'Encrypted paste - waiting for key.' : htmlspecialchars($data['content']); ?></code></pre>
                </div>
                <div id="edit-mode">
                     <form method="post" id="edit-form" autocomplete="off">
                         <input type="hidden" name="action" value="update">
                         <input type="text" name="filename" value="<?php echo htmlspecialchars($data['filename'] ?? ''); ?>" placeholder="Filename (optional)" autocomplete="off">
                        <textarea id="content" name="content" placeholder="<?php echo $isEncryptedData ? 'Content will appear after decryption' : 'Edit content'; ?>"><?php echo $isEncryptedData ? '' : htmlspecialchars($data['content']); ?></textarea>
                        <div class="form-row">
                            <select name="syntax">
                                <option value="plaintext" <?php echo 'plaintext' === $data['syntax'] ? 'selected' : ''; ?>>Plain Text</option>
                                <option value="code" <?php echo 'code' === $data['syntax'] ? 'selected' : ''; ?>>Code (auto-detect)</option>
                            </select>
                        </div>
                        <div class="form-options">
                            <label class="checkbox-inline"><input type="checkbox" name="hidden" <?php echo !empty($data['hidden']) ? 'checked' : ''; ?>> Hidden (only visible when logged in)</label>
                        </div>
                        <input type="hidden" name="is_encrypted" value="<?php echo $isEncryptedData ? '1' : '0'; ?>">
                        <input type="hidden" name="iv" value="<?php echo htmlspecialchars($ivValue); ?>">
                        <button type="submit" name="update" class="button-like">Save Changes</button>
                    </form>
                </div>
                <script>
                    function showView() {
                        document.getElementById('view-mode').style.display = 'block';
                        document.getElementById('edit-mode').style.display = 'none';
                    }
                    function showEdit() {
                        document.getElementById('view-mode').style.display = 'none';
                        document.getElementById('edit-mode').style.display = 'block';
                    }
                </script>
            <?php else: ?>
                <pre><code id="decrypted-view" class="hljs"><?php echo $isEncryptedData ? 'Encrypted paste - waiting for key.' : htmlspecialchars($data['content']); ?></code></pre>
            <?php endif; ?>
            
            <script>
                function copyLink() {
                    if (window.updateLinkInput) {
                        window.updateLinkInput();
                    }
                    const input = document.getElementById('direct-link');
                    input.select();
                    input.setSelectionRange(0, 99999);
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(input.value).then(() => {
                            showCopyNotice();
                        });
                    } else {
                        document.execCommand('copy');
                        showCopyNotice();
                    }
                }
                function showCopyNotice() {
                    const btn = document.getElementById('copy-btn');
                    if (!btn) return;
                    const originalText = btn.textContent;
                    btn.textContent = 'Copied!';
                    btn.disabled = true;
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }, 1500);
                }
            </script>
        </div>
    <?php endif; ?>
</body>
</html>
