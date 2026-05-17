(function () {
  function initDocumentPosBuilder(config) {
    const articleMap = config.articleMap || {};
    const articleOptions = config.articleOptions || [];
    const endpoints = config.endpoints || {};

    const linesBody = document.getElementById('linesBody');
    const addLineBtn = document.getElementById('addLineBtn');
    const posProductInput = document.getElementById('posProductInput');
    const posQtyInput = document.getElementById('posQtyInput');
    const posSearchResults = document.getElementById('posSearchResults');
    const posAddFallbackBtn = document.getElementById('posAddFallbackBtn');
    const posCameraBtn = document.getElementById('posCameraBtn');
    const sumHt = document.getElementById('sumHt');
    const sumTva = document.getElementById('sumTva');
    const sumTtc = document.getElementById('sumTtc');

    if (!linesBody || !posProductInput) return;

    let searchAbort = null;
    let searchItems = [];
    let activeSearchIndex = -1;
    let searchTimer = null;
    let lastSearchQuery = '';
    let lastSearchAt = 0;
    let currentScannerTarget = null;
    let cameraStream = null;
    let cameraRaf = null;
    let lastRenderedResultKey = '';
    let scanSequence = 0;
    let addingLineLock = false;
    let queuedScans = Promise.resolve();
    let computeRaf = null;

    function money(v) { return Number(v || 0).toFixed(2); }

    function showPosMessage(message, theme) {
      let box = document.getElementById('posMessageBox');
      if (!box) {
        box = document.createElement('div');
        box.id = 'posMessageBox';
        box.className = 'small mt-2';
        posProductInput.closest('.position-relative')?.appendChild(box);
      }
      box.className = `small mt-2 text-${theme || 'muted'}`;
      box.textContent = message || '';
    }

    function renumberRows() {
      linesBody.querySelectorAll('tr.line-row').forEach((row, idx) => {
        row.querySelectorAll('input, select').forEach((field) => {
          const name = field.getAttribute('name');
          if (!name) return;
          field.setAttribute('name', name.replace(/lines\[\d+\]/, `lines[${idx}]`));
        });
      });
    }

    function computeRow(row) {
      const articleId = row.querySelector('.line-article').value;
      const qty = Number(row.querySelector('.line-qty').value || 0);
      const priceInput = row.querySelector('.line-price');
      const discount = Math.max(0, Math.min(100, Number(row.querySelector('.line-discount').value || 0)));
      const article = articleMap[articleId] || null;
      const tva = Number(article ? article.tva : 0);

      if (article && Number(priceInput.value || 0) === 0) {
        priceInput.value = article.price || article.buy_price || 0;
      }

      const price = Number(priceInput.value || 0);
      const gross = qty * price;
      const discountAmount = gross * (discount / 100);
      const totalHt = gross - discountAmount;
      const totalTva = totalHt * (tva / 100);
      const totalTtc = totalHt + totalTva;

      row.querySelector('.line-tva').value = money(tva);
      row.querySelector('.line-ht').value = money(totalHt);
      row.querySelector('.line-ttc').value = money(totalTtc);
      return { totalHt, totalTva, totalTtc };
    }

    function computeAllNow() {
      let totalHt = 0, totalTva = 0, totalTtc = 0;
      linesBody.querySelectorAll('tr.line-row').forEach((row) => {
        const totals = computeRow(row);
        totalHt += totals.totalHt;
        totalTva += totals.totalTva;
        totalTtc += totals.totalTtc;
      });
      sumHt.textContent = money(totalHt);
      sumTva.textContent = money(totalTva);
      sumTtc.textContent = money(totalTtc);
    }

    function scheduleComputeAll() {
      if (computeRaf) return;
      computeRaf = requestAnimationFrame(() => {
        computeRaf = null;
        computeAllNow();
      });
    }

    function buildArticleOptionsHtml() {
      return ['<option value="">Selectionner</option>'].concat(articleOptions.map((a) => (
        `<option value="${a.id}">${a.label}</option>`
      ))).join('');
    }

    function addRow() {
      const idx = linesBody.querySelectorAll('tr.line-row').length;
      const row = document.createElement('tr');
      row.className = 'line-row';
      row.innerHTML = `
        <td><select name="lines[${idx}][article_id]" class="form-control form-control-sm line-article">${buildArticleOptionsHtml()}</select></td>
        <td><input type="number" step="0.001" min="0" name="lines[${idx}][dl_qte]" class="form-control form-control-sm line-qty" value="1"></td>
        <td><input type="number" step="0.00001" min="0" name="lines[${idx}][dl_prix_unitaire_ht]" class="form-control form-control-sm line-price" value="0"></td>
        <td><input type="number" step="0.01" min="0" max="100" name="lines[${idx}][dl_remise_percent]" class="form-control form-control-sm line-discount" value="0"></td>
        <td><input type="text" class="form-control form-control-sm line-tva" value="0.00" readonly></td>
        <td><input type="text" class="form-control form-control-sm line-ht" value="0.00" readonly></td>
        <td><input type="text" class="form-control form-control-sm line-ttc" value="0.00" readonly></td>
        <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger btn-remove-line"><i class="fas fa-times"></i></button></td>
      `;
      linesBody.appendChild(row);
      scheduleComputeAll();
      return row;
    }

    function ensureArticleOption(select, article) {
      const id = String(article.id);
      if ([...select.options].some((o) => o.value === id)) return;
      const option = document.createElement('option');
      option.value = id;
      option.textContent = article.label || `${article.code} - ${article.name}`;
      select.appendChild(option);
    }

    function addOrIncrementLine(article, qtyToAdd) {
      if (addingLineLock) return false;
      addingLineLock = true;
      try {
        const qty = Math.max(0.001, Number(qtyToAdd || 1));
        const existing = [...linesBody.querySelectorAll('tr.line-row')]
          .find((row) => row.querySelector('.line-article').value === String(article.id));

        if (existing) {
          const qtyInput = existing.querySelector('.line-qty');
          qtyInput.value = Number(qtyInput.value || 0) + qty;
          scheduleComputeAll();
          return true;
        }

        const row = addRow();
        const select = row.querySelector('.line-article');
        ensureArticleOption(select, article);
        select.value = String(article.id);
        row.querySelector('.line-qty').value = qty;
        row.querySelector('.line-price').value = Number(article.price || article.buy_price || 0);
        scheduleComputeAll();
        return true;
      } finally {
        addingLineLock = false;
      }
    }

    function hideResults() {
      posSearchResults.style.display = 'none';
      posSearchResults.innerHTML = '';
      searchItems = [];
      activeSearchIndex = -1;
      lastRenderedResultKey = '';
    }

    function renderResults(items) {
      const key = items.map((i) => i.id).join(',');
      if (key === lastRenderedResultKey) return;
      lastRenderedResultKey = key;

      searchItems = items;
      activeSearchIndex = -1;
      posSearchResults.innerHTML = '';
      if (!items.length) return hideResults();

      items.forEach((item, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action py-2';
        btn.dataset.index = String(idx);
        btn.innerHTML = `<strong>${item.code}</strong> - ${item.name}<span class="float-right text-muted">${money(item.price)} DH</span>`;
        posSearchResults.appendChild(btn);
      });
      posSearchResults.style.display = 'block';
    }

    function setActiveSearchItem(index) {
      activeSearchIndex = index;
      [...posSearchResults.querySelectorAll('[data-index]')].forEach((el) => {
        const isActive = Number(el.dataset.index) === index;
        el.classList.toggle('active', isActive);
        if (isActive) el.scrollIntoView({ block: 'nearest' });
      });
    }

    async function fetchWithTimeout(url, opts, timeoutMs) {
      const controller = new AbortController();
      const id = setTimeout(() => controller.abort(), timeoutMs || 3000);
      try {
        const resp = await fetch(url, { ...(opts || {}), signal: controller.signal });
        return resp;
      } finally {
        clearTimeout(id);
      }
    }

    async function apiSearch(q) {
      if (searchAbort) searchAbort.abort();
      searchAbort = new AbortController();
      const resp = await fetchWithTimeout(`${endpoints.search}?q=${encodeURIComponent(q)}`, {
        signal: searchAbort.signal,
        headers: { Accept: 'application/json' },
      }, 3500);
      if (!resp.ok) return [];
      const payload = await resp.json();
      return payload.data || [];
    }

    async function apiBarcode(code) {
      const resp = await fetchWithTimeout(endpoints.barcode.replace('__CODE__', encodeURIComponent(code)), {
        headers: { Accept: 'application/json' },
      }, 2500);
      if (!resp.ok) return null;
      const payload = await resp.json();
      return payload.data || null;
    }

    function keepScannerFocus() {
      setTimeout(() => posProductInput.focus(), 20);
    }

    function startSearchDebounced(q) {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(async () => {
        const now = Date.now();
        if (q === lastSearchQuery && now - lastSearchAt < 400) return;
        lastSearchQuery = q;
        lastSearchAt = now;
        try {
          const items = await apiSearch(q);
          renderResults(items);
        } catch (_) {
          showPosMessage('Reseau lent: recherche indisponible, scanner direct toujours actif.', 'warning');
          hideResults();
        }
      }, 140);
    }

    function cycleFocus(className) {
      const fields = [...linesBody.querySelectorAll(className)];
      if (!fields.length) return;
      const idx = currentScannerTarget && fields.includes(currentScannerTarget) ? fields.indexOf(currentScannerTarget) : -1;
      const next = fields[idx + 1] || fields[0];
      next.focus();
      next.select?.();
      currentScannerTarget = next;
    }

    function stopCameraScan() {
      if (cameraRaf) { cancelAnimationFrame(cameraRaf); cameraRaf = null; }
      if (cameraStream) { cameraStream.getTracks().forEach((t) => t.stop()); cameraStream = null; }
      document.getElementById('posCameraWrap')?.remove();
      keepScannerFocus();
    }

    async function startCameraScan() {
      if (!navigator.mediaDevices?.getUserMedia) {
        showPosMessage('Camera non supportee sur ce navigateur.', 'danger');
        return;
      }

      stopCameraScan();
      const wrap = document.createElement('div');
      wrap.id = 'posCameraWrap';
      wrap.className = 'mt-2 border rounded p-2 bg-white';
      wrap.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong class="small">Scan camera actif</strong>
          <button type="button" class="btn btn-xs btn-outline-danger" id="posCameraStopBtn">Fermer</button>
        </div>
        <video id="posCameraVideo" autoplay playsinline style="width:100%;max-height:220px;object-fit:cover;"></video>
        <small class="text-muted d-block mt-1">Pointez le code-barres. Le scan ajoute automatiquement l'article.</small>
      `;
      posCameraBtn.closest('.mt-2').appendChild(wrap);
      document.getElementById('posCameraStopBtn').addEventListener('click', stopCameraScan);

      cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      const video = document.getElementById('posCameraVideo');
      video.srcObject = cameraStream;
      if (!('BarcodeDetector' in window)) {
        showPosMessage('Detection barcode indisponible ici. Utilisez scanner clavier.', 'warning');
        return;
      }

      const detector = new window.BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'qr_code'] });
      let lastValue = '';
      let lastTs = 0;

      const tick = async () => {
        if (!video || video.readyState < 2) {
          cameraRaf = requestAnimationFrame(tick);
          return;
        }
        try {
          const barcodes = await detector.detect(video);
          if (barcodes.length) {
            const raw = (barcodes[0].rawValue || '').trim();
            const now = Date.now();
            if (raw && (raw !== lastValue || now - lastTs > 1200)) {
              lastValue = raw;
              lastTs = now;
              queuedScans = queuedScans.then(async () => {
                const item = await apiBarcode(raw);
                if (item) addOrIncrementLine(item, posQtyInput.value);
              }).catch(() => {});
            }
          }
        } catch (_) {}
        cameraRaf = requestAnimationFrame(tick);
      };
      cameraRaf = requestAnimationFrame(tick);
    }

    linesBody.addEventListener('input', (event) => {
      currentScannerTarget = event.target.closest('input') || currentScannerTarget;
      scheduleComputeAll();
    });

    linesBody.addEventListener('change', (event) => {
      currentScannerTarget = event.target.closest('input') || currentScannerTarget;
      scheduleComputeAll();
      keepScannerFocus();
    });

    linesBody.addEventListener('click', (event) => {
      const btn = event.target.closest('.btn-remove-line');
      if (!btn) return;
      btn.closest('tr')?.remove();
      if (!linesBody.querySelector('tr.line-row')) addRow();
      renumberRows();
      scheduleComputeAll();
      keepScannerFocus();
    });

    addLineBtn?.addEventListener('click', () => { addRow(); keepScannerFocus(); });
    posAddFallbackBtn?.addEventListener('click', () => { addRow(); keepScannerFocus(); });
    posCameraBtn?.addEventListener('click', () => startCameraScan().catch(() => showPosMessage('Impossible d\'ouvrir la camera.', 'danger')));

    posProductInput.addEventListener('input', () => {
      const q = posProductInput.value.trim();
      if (q.length < 2) return hideResults();
      startSearchDebounced(q);
    });

    posSearchResults.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-index]');
      if (!btn) return;
      const item = searchItems[Number(btn.dataset.index)];
      if (!item) return;
      addOrIncrementLine(item, posQtyInput.value);
      posProductInput.value = '';
      hideResults();
      showPosMessage('', 'muted');
      keepScannerFocus();
    });

    posProductInput.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        hideResults();
        keepScannerFocus();
        return;
      }
      if (event.key === 'ArrowDown' && searchItems.length) {
        event.preventDefault();
        const next = activeSearchIndex + 1 >= searchItems.length ? 0 : activeSearchIndex + 1;
        setActiveSearchItem(next);
        return;
      }
      if (event.key === 'ArrowUp' && searchItems.length) {
        event.preventDefault();
        const prev = activeSearchIndex - 1 < 0 ? searchItems.length - 1 : activeSearchIndex - 1;
        setActiveSearchItem(prev);
        return;
      }
      if (event.key !== 'Enter') return;
      event.preventDefault();
      const raw = posProductInput.value.trim();
      if (!raw) {
        showPosMessage('Veuillez scanner ou saisir un code produit.', 'warning');
        keepScannerFocus();
        return;
      }

      const currentSeq = ++scanSequence;
      queuedScans = queuedScans.then(async () => {
        if (currentSeq !== scanSequence) return;

        if (activeSearchIndex >= 0 && searchItems[activeSearchIndex]) {
          addOrIncrementLine(searchItems[activeSearchIndex], posQtyInput.value);
          posProductInput.value = '';
          hideResults();
          showPosMessage('', 'muted');
          return;
        }

        try {
          let item = await apiBarcode(raw);
          if (!item) {
            const items = await apiSearch(raw);
            item = items[0] || null;
          }
          if (item) {
            addOrIncrementLine(item, posQtyInput.value);
            posProductInput.value = '';
            hideResults();
            showPosMessage('', 'muted');
          } else {
            showPosMessage(`Produit introuvable: ${raw}`, 'danger');
          }
        } catch (_) {
          showPosMessage('Recherche indisponible pour le moment. Reessayez.', 'warning');
        }
      }).finally(() => keepScannerFocus());
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'F8') { event.preventDefault(); keepScannerFocus(); return; }
      if (event.key === 'F2') { event.preventDefault(); cycleFocus('.line-qty'); return; }
      if (event.key === 'F4') { event.preventDefault(); cycleFocus('.line-discount'); }
    });

    posProductInput.addEventListener('blur', () => {
      setTimeout(() => { if (document.activeElement !== posProductInput) posProductInput.focus(); }, 120);
    });

    scheduleComputeAll();
    keepScannerFocus();
    window.addEventListener('beforeunload', stopCameraScan);
  }

  window.initDocumentPosBuilder = initDocumentPosBuilder;
})();
