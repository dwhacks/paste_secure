## Secure Client-Side Encrypted Pastebin

[GitHub Repository](https://github.com/dwhacks/paste_secure)

This project is a zero-knowledge pastebin inspired by PrivateBin. All paste
content is encrypted in the browser before it ever touches the server, keeping

### Features

- Client-side AES-GCM encryption with keys shared via URL fragments (`#key`).
- Support for burn-after-reading, hidden pastes, and raw/plaintext views.
- Password‑protected administration for creating/editing/deleting pastes.
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
     - `admin_password` (plain text; the application hashes it on first login)
     - `site_name`
     - `base_url` (include trailing slash; e.g. `https://example.com/paste/`)
   - Ensure the `data/` directory exists and is writable by your web server.

3. **Serve the application**
   - Development: `php -S localhost:8000`
   - Production: deploy under HTTPS (required for Web Crypto).

4. **Usage**
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

MIT License

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the “Software”), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
