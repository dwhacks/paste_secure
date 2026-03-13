## Secure Client-Side Encrypted Pastebin

This project is a pastebin inspired by PrivateBin. Paste content is encrypted
in the browser before it ever touches the server when encryption is enabled,
keeping the server from seeing the content. This requires `allow_unencrypted`
to remain disabled (the default).

### Features

- Client-side AES-GCM encryption with keys shared via URL fragments (`#key`).
- Support for burn-after-reading, hidden pastes, and raw/plaintext views.
- Password‑protected user accounts for creating/editing/deleting pastes.
- Automatic redirect after creation/edit for seamless UX.
- Graceful fallback to plaintext storage if Web Crypto is unavailable.

### Getting Started

1. **Clone or copy the repo**
   ```bash
   git clone https://github.com/dwhacks/paste_secure.git
   cd paste_secure
   ```

2. **Configure**
   - Open `config.php` and set:
     - `admin_password` (plain text for the built-in admin user; the application hashes it on first login)
     - `site_name`
     - `base_url` (include trailing slash; e.g. `https://example.com/paste/`)
     - `theme` (`terminal`, `paper`, `midnight`, or `classic`; add more by dropping `*.css` files into `themes/`)
     - `allow_unencrypted` (`true` to let users store plain pastes; when enabled, creators get a "Store unencrypted" checkbox)
   - The built-in `admin` user has full access to view and delete all pastes, including hidden ones
   - Ensure the `data/` directory exists and is writable by your web server.

3. **Add Users (Optional)**
   - Create users in `users.php` (one entry per user):
     ```php
     return [
         'john' => 'password123',
         'jane' => 'anothertemppass',
     ];
     ```
   - Plain text passwords will be hashed on first login and stored in `data/user_hashes/`
   - The `users.php` file will be automatically updated with hashed passwords
   - Users can change their password after logging in via "Change Password" link
   - Admin password cannot be changed this way - update `config.php` directly

4. **Serve the application**
   - Development preview: `php -S localhost:8000`
   - Production: deploy under HTTPS (required for Web Crypto).

### Themes

- Switch looks by changing the `theme` value in `config.php`, then refresh your browser.
- Available themes ship in `themes/`:
  - `terminal` — amber CRT look inspired by retro Apple consoles.
  - `paper` — warm notebook-inspired palette with soft shadows.
  - `midnight` — deep blue glassmorphism with neon accents.
  - `classic` — original standalone Secure Paste styling.
- To create your own, duplicate an existing file in `themes/`, adjust the CSS variables, and point `theme` to the new filename (without `.css`).

5. **Usage**
   - Visit `/login.php`, enter the admin password, and create pastes from the
     main page.
   - Share the generated URL with the `#key` fragment; anyone without it cannot
     decrypt the paste.
   - The direct-link box and raw view respect cached keys stored in the
     browser (local/session storage).

### Notes

- For true zero-knowledge, always use HTTPS or `http://localhost` so that
  `window.crypto.subtle` is available.
- Changing the admin password? Update `config.php`, delete `data/admin.hash`,
  then log in once to regenerate the hash.
- Existing plaintext pastes remain as-is until you edit/save them while
  encryption is active.

### License

This project is licensed under the [MIT License](LICENSE).
