<?php
// Paste Configuration

// Admin password (plain text). It will be securely hashed and stored
// in data/admin.hash on first use. CHANGE THIS!
$config['admin_password'] = 'CHANGEME_SUPER_SECRET_PASSWORD';

// Data directory (where paste JSON files are stored)
$config['data_dir'] = __DIR__ . '/data';

// Default expiry for new pastes (never, 5min, 1hour, 1day, 1week)
$config['default_expiry'] = 'never';

// Site name (shown on the UI header)
$config['site_name'] = 'My Secure Pastebin';

// Base URL for generating full links (include trailing slash)
$config['base_url'] = 'http://localhost:8000/';

// UI theme (matches a file name in themes/*.css)
$config['theme'] = 'terminal'; // options: terminal, paper, midnight, classic
