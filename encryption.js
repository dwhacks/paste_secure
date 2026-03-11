(() => {
  const textEncoder = new TextEncoder();
  const textDecoder = new TextDecoder();
  const cryptoAvailable = !!(window.isSecureContext && window.crypto && window.crypto.subtle);
  if (!cryptoAvailable) {
    console.warn('Web Crypto API not available (requires HTTPS or http://localhost). Pastes will be stored unencrypted.');
  }

  function toUint8(bufferOrArray) {
    if (bufferOrArray instanceof Uint8Array) return bufferOrArray;
    return new Uint8Array(bufferOrArray);
  }

  function toBase64(bufferOrArray) {
    const bytes = toUint8(bufferOrArray);
    let binary = '';
    const len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
  }

  function fromBase64(base64) {
    const binary = atob(base64);
    const len = binary.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
  }

  function base64ToUrl(base64) {
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }

  function urlToBase64(url) {
    let base64 = url.replace(/-/g, '+').replace(/_/g, '/');
    while (base64.length % 4) {
      base64 += '=';
    }
    return base64;
  }

  function getKeyFromHash() {
    const hash = window.location.hash;
    if (!hash) return '';
    return decodeURIComponent(hash.substring(1));
  }

  function setHash(keyUrl) {
    if (keyUrl) {
      window.location.hash = encodeURIComponent(keyUrl);
    }
  }

  function updateLinkInput() {
    const input = document.getElementById('direct-link');
    if (!input) return;
    const base = input.dataset.base || input.value;
    const hash = window.location.hash || '';
    input.value = base + hash;
  }

function storePendingKey(keyUrl, pasteId) {
  if (!keyUrl) return;
  try {
    sessionStorage.setItem('pendingKey', keyUrl);
    if (pasteId) {
      sessionStorage.setItem('pendingKey_' + pasteId, keyUrl);
    }
    if (pasteId) {
      saveKeyForPaste(pasteId, keyUrl);
    }
  } catch (e) {
    console.warn('Unable to store key in sessionStorage', e);
  }
}

  function retrievePendingKey(pasteId) {
    try {
      if (pasteId) {
        const keyed = sessionStorage.getItem('pendingKey_' + pasteId);
        if (keyed) {
          sessionStorage.removeItem('pendingKey_' + pasteId);
          sessionStorage.removeItem('pendingKey');
          return keyed;
        }
      }
      const generic = sessionStorage.getItem('pendingKey');
      if (generic) {
        sessionStorage.removeItem('pendingKey');
        return generic;
      }
    } catch (e) {
      console.warn('Unable to access sessionStorage', e);
    }
    return '';
  }

function clearPendingKey(pasteId) {
  try {
    if (pasteId) {
      sessionStorage.removeItem('pendingKey_' + pasteId);
    }
    sessionStorage.removeItem('pendingKey');
  } catch (e) {
    console.warn('Unable to clear sessionStorage key', e);
  }

  if (pasteId) {
    saveKeyForPaste(pasteId, window.currentPasteKeyUrl || getKeyFromHash());
  }
}

function saveKeyForPaste(pasteId, keyUrl) {
  if (!pasteId || !keyUrl) return;
  try {
    localStorage.setItem('pasteKey_' + pasteId, keyUrl);
  } catch (e) {
    console.warn('Unable to save key to localStorage', e);
  }
}

function getSavedKeyForPaste(pasteId) {
  if (!pasteId) return '';
  try {
    return localStorage.getItem('pasteKey_' + pasteId) || '';
  } catch (e) {
    console.warn('Unable to read key from localStorage', e);
    return '';
  }
}

function applyStoredKeysToList() {
  const links = document.querySelectorAll('.paste-item a[data-paste-id]');
  links.forEach((link) => {
    const pasteId = link.dataset.pasteId || '';
    if (!pasteId) return;
    const stored = getSavedKeyForPaste(pasteId);
    if (!stored) return;
    const base = link.href.split('#')[0];
    link.href = base + '#' + encodeURIComponent(stored);
  });
}

function handleCreationMessage() {
  const message = document.querySelector('.message[data-created-id]');
  if (!message) return;

  const pasteId = message.dataset.createdId || '';
  const encrypted = message.dataset.createdEnc === '1';
  if (!pasteId) return;

  const baseAttr = document.body.dataset.baseUrl || document.body.dataset.pasteBase || '/paste/';
  const normalized = baseAttr.endsWith('/') ? baseAttr.slice(0, -1) : baseAttr;
  const baseUrl = /^https?:/i.test(normalized) ? normalized : window.location.origin + (normalized.startsWith('/') ? normalized : '/' + normalized);

  let key = retrievePendingKey(pasteId);
  if (!key) {
    key = getSavedKeyForPaste(pasteId);
  }

  if (encrypted && key) {
    saveKeyForPaste(pasteId, key);
  }

  const linkBase = baseUrl + '/view.php?id=' + pasteId;
  const fullLink = linkBase + (key ? '#' + encodeURIComponent(key) : '');

  message.innerHTML = '';

  if (encrypted && !key) {
    const warn = document.createElement('div');
    warn.textContent = 'Paste created, but the encryption key was not found in this browser. Please copy the full URL (including the #key) from the previous page or regenerate the paste.';
    message.appendChild(warn);
    return;
  }

  const info = document.createElement('div');
  info.textContent = 'Paste created! Copy and save this link:';

  const input = document.createElement('input');
  input.type = 'text';
  input.readOnly = true;
  input.value = fullLink;

  const copyBtn = document.createElement('button');
  copyBtn.className = 'button-like';
  copyBtn.textContent = 'Copy';
  copyBtn.addEventListener('click', () => {
    input.select();
    input.setSelectionRange(0, input.value.length);
    const setCopied = () => {
      copyBtn.textContent = 'Copied!';
      setTimeout(() => (copyBtn.textContent = 'Copy'), 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(input.value).then(setCopied).catch(() => {
        document.execCommand('copy');
        setCopied();
      });
    } else {
      document.execCommand('copy');
      setCopied();
    }
  });

  const openLink = document.createElement('a');
  openLink.href = fullLink;
  openLink.target = '_blank';
  openLink.rel = 'noopener';
  openLink.className = 'button-like secondary-btn';
  openLink.textContent = 'Open';

  message.append(info, input, copyBtn, openLink);
}

  async function encryptText(plainText, keyBytes, ivBytes) {
    const key = await crypto.subtle.importKey('raw', keyBytes, { name: 'AES-GCM' }, false, ['encrypt']);
    const encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: ivBytes }, key, textEncoder.encode(plainText));
    return toBase64(encrypted);
  }

  async function decryptText(cipherB64, keyBytes, ivBytes) {
    const key = await crypto.subtle.importKey('raw', keyBytes, { name: 'AES-GCM' }, false, ['decrypt']);
    const cipherBytes = fromBase64(cipherB64);
    const plainBuffer = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: ivBytes }, key, cipherBytes);
    return textDecoder.decode(plainBuffer);
  }

  function generateKey() {
    const keyBytes = crypto.getRandomValues(new Uint8Array(32));
    const keyB64 = toBase64(keyBytes);
    const keyUrl = base64ToUrl(keyB64);
    return { keyBytes, keyB64, keyUrl };
  }

  function highlight(element) {
    if (window.hljs && element) {
      window.hljs.highlightElement(element);
    }
  }

  async function decryptIfNeeded() {
    const dataElem = document.getElementById('paste-data');
    if (!dataElem) {
      updateLinkInput();
      return;
    }

    const encrypted = dataElem.dataset.encrypted === '1';
    const display = document.getElementById('decrypted-view');
    const pasteId = dataElem.dataset.id || '';

    if (!encrypted) {
      if (display) {
        display.textContent = dataElem.dataset.plain || '';
        highlight(display);
      }
      updateLinkInput();
      return;
    }

    if (!cryptoAvailable) {
      if (display) {
        display.textContent = 'Encrypted paste. Your browser does not support the Web Crypto API.';
      }
      updateLinkInput();
      return;
    }

    const cipher = dataElem.dataset.content;
    const iv = dataElem.dataset.iv;
    if (!cipher || !iv) return;

    let keyUrl = getKeyFromHash();
    if (!keyUrl && pasteId) {
      keyUrl = retrievePendingKey(pasteId);
      if (keyUrl) {
        setHash(keyUrl);
        updateLinkInput();
      }
    }
    if (!keyUrl) {
      const fallback = retrievePendingKey('');
      if (fallback) {
        keyUrl = fallback;
        setHash(keyUrl);
        updateLinkInput();
      }
    }
    if (!keyUrl && pasteId) {
      const storedKey = getSavedKeyForPaste(pasteId);
      if (storedKey) {
        keyUrl = storedKey;
        setHash(keyUrl);
        updateLinkInput();
      }
    }
    if (!keyUrl) {
      keyUrl = prompt('Enter decryption key:');
      if (!keyUrl) {
        const fallbackUrl = document.body.dataset.baseUrl || '/';
        window.location.replace(fallbackUrl);
        return;
      }
      setHash(keyUrl);
      updateLinkInput();
    }

    window.currentPasteKeyUrl = keyUrl;

    try {
      const keyBytes = fromBase64(urlToBase64(keyUrl));
      const ivBytes = fromBase64(iv);
      const plainText = await decryptText(cipher, keyBytes, ivBytes);

      const rawOnly = document.body.dataset.raw === '1';
      if (rawOnly) {
        document.body.textContent = plainText;
      } else if (display) {
        display.textContent = plainText;
        highlight(display);
      }

      const textarea = document.getElementById('content');
      if (textarea) {
        textarea.value = plainText;
      }

      clearPendingKey(pasteId);
      saveKeyForPaste(pasteId, keyUrl);

      updateLinkInput();
    } catch (error) {
      console.error(error);
      alert('Failed to decrypt paste. Check the key.');
    }
  }

  function setupCreateForm() {
    const form = document.getElementById('paste-form');
    if (!form || !cryptoAvailable) return;
    setupFormEncryption(form, { isEdit: false });
  }

  function setupEditForm() {
    const form = document.getElementById('edit-form');
    if (!form || !cryptoAvailable) return;
    const dataElem = document.getElementById('paste-data');
    const pasteId = dataElem ? dataElem.dataset.id || '' : '';
    setupFormEncryption(form, { isEdit: true, pasteId });
  }

  function getKeyBytesFromUrl(url) {
    const base64 = urlToBase64(url);
    return fromBase64(base64);
  }

  function showNewKeyNotice() {
    alert('A new encryption key was generated. Please save the updated link.');
  }

  function setupFormEncryption(form, options) {
    const textarea = form.querySelector('textarea[name="content"]');
    if (!textarea) return;

    const handler = async (event) => {
      event.preventDefault();

      try {
        let keyUrl = window.currentPasteKeyUrl || getKeyFromHash();
        let keyBytes;

        if (options.isEdit && !keyUrl) {
          const generated = generateKey();
          keyBytes = generated.keyBytes;
          keyUrl = generated.keyUrl;
          window.currentPasteKeyUrl = keyUrl;
          setHash(keyUrl);
          updateLinkInput();
          showNewKeyNotice();
        }

        if (!keyUrl) {
          const generated = generateKey();
          keyBytes = generated.keyBytes;
          keyUrl = generated.keyUrl;
          window.currentPasteKeyUrl = keyUrl;
          setHash(keyUrl);
          updateLinkInput();
        } else if (!keyBytes) {
          keyBytes = getKeyBytesFromUrl(keyUrl);
        }

        const ivBytes = crypto.getRandomValues(new Uint8Array(12));
        const encryptedContent = await encryptText(textarea.value, keyBytes, ivBytes);

        const isEncryptedInput = form.querySelector('input[name="is_encrypted"]');
        if (isEncryptedInput) isEncryptedInput.value = '1';

        const ivInput = form.querySelector('input[name="iv"]');
        if (ivInput) ivInput.value = toBase64(ivBytes);

        storePendingKey(keyUrl, options.pasteId || '');

        textarea.value = encryptedContent;

        form.removeEventListener('submit', handler);
        form.submit();
      } catch (error) {
        console.error(error);
        alert('Encryption failed. Paste was not saved.');
        const encInput = form.querySelector('input[name="is_encrypted"]');
        if (encInput) encInput.value = '0';
        const ivInput = form.querySelector('input[name="iv"]');
        if (ivInput) ivInput.value = '';
        clearPendingKey(options.pasteId || '');
        form.removeEventListener('submit', handler);
        form.submit();
      }
    };

    form.addEventListener('submit', handler);
  }

function handlePendingKeyOnView() {
  const dataElem = document.getElementById('paste-data');
  if (!dataElem) return;
  const pasteId = dataElem.dataset.id || '';
  const key = retrievePendingKey(pasteId);
  if (key) {
    window.currentPasteKeyUrl = key;
    setHash(key);
    updateLinkInput();
    saveKeyForPaste(pasteId, key);
    return;
  }
  const storedKey = getSavedKeyForPaste(pasteId);
  if (storedKey) {
    window.currentPasteKeyUrl = storedKey;
    setHash(storedKey);
    updateLinkInput();
  }
}

  window.updateLinkInput = updateLinkInput;

  document.addEventListener('DOMContentLoaded', () => {
    setupCreateForm();
    setupEditForm();
    handleCreationMessage();
    handlePendingKeyOnView();
    decryptIfNeeded();
    applyStoredKeysToList();
    updateLinkInput();
  });
})();
