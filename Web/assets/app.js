/* Datatable du catalogue : recherche, filtres, tri, pagination, infobulle DETAILS (sans dépendance). */
(function () {
  'use strict';

  var STEAM = 'https://steamcommunity.com/sharedfiles/filedetails/?id=';
  var mods = (window.RW_MODS || []).slice();
  var $ = function (id) { return document.getElementById(id); };
  var tbody = document.querySelector('#mods tbody');
  var headers = Array.prototype.slice.call(document.querySelectorAll('#mods th.sortable'));
  var COLS = document.querySelectorAll('#mods thead th').length;

  var fold = function (s) {
    return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  };
  var esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var cmpVer = function (a, b) {
    var x = a.split('.').map(Number), y = b.split('.').map(Number);
    return (x[0] - y[0]) || (x[1] - y[1]);
  };
  var terms = function () {
    return fold(state.q).split(/\s+/).filter(Boolean);
  };

  // Index de recherche et valeurs de tri
  mods.forEach(function (m, i) {
    m.i = i;
    m._work = fold(m.work);
    m._search = fold([m.date, m.ids.join(' '), m.name, m.package, m.zipText, m.version, m.work, m.types.join(' ')].join(' '));
    m._rwMax = m.rw.length ? m.rw[m.rw.length - 1] : '';
  });

  // Liste des versions RW
  var allRw = {};
  mods.forEach(function (m) { m.rw.forEach(function (v) { allRw[v] = 1; }); });
  Object.keys(allRw).sort(cmpVer).reverse().forEach(function (v) {
    var o = document.createElement('option');
    o.value = v; o.textContent = 'RimWorld ' + v;
    $('f-rw').appendChild(o);
  });

  var DEFAULT = { q: '', type: '', rw: '', zip: '', page: 1, sort: 'date', dir: 'desc' };
  var state = Object.assign({ len: 10 }, DEFAULT);

  var sorters = {
    date: function (m) { return m.dateIso || null; },
    id: function (m) { return m.ids.length ? Number(m.ids[0]) : null; },
    name: function (m) { return fold(m.name) || null; },
    package: function (m) { return fold(m.package) || null; },
    rw: function (m) { return m._rwMax || null; }
  };

  function compare(a, b) {
    var key = sorters[state.sort], va = key(a), vb = key(b);
    // Les valeurs vides (« Non documentée », sans ID…) restent toujours en fin de liste, dans l'ordre du ReadMe.
    if (va === null && vb === null) return a.n - b.n;
    if (va === null) return 1;
    if (vb === null) return -1;
    var r;
    if (state.sort === 'rw') r = cmpVer(va, vb);
    else if (typeof va === 'number') r = va - vb;
    else r = va < vb ? -1 : va > vb ? 1 : 0;
    if (r === 0) return a.n - b.n;
    return state.dir === 'asc' ? r : -r;
  }

  function filtered() {
    var t = terms();
    return mods.filter(function (m) {
      if (state.type && m.types.indexOf(state.type) < 0) return false;
      if (state.rw && m.rw.indexOf(state.rw) < 0) return false;
      if (state.zip === 'yes' && !m.zips.length) return false;
      if (state.zip === 'no' && m.zips.length) return false;
      for (var i = 0; i < t.length; i++) if (m._search.indexOf(t[i]) < 0) return false;
      return true;
    }).sort(compare);
  }

  function rowHtml(m) {
    var ids = m.ids.length
      ? m.ids.map(function (id) {
          return '<a href="' + STEAM + id + '" target="_blank" rel="noopener" title="Page Steam Workshop">' + id + '</a>';
        }).join('')
      : '<span class="nd">' + (m.idHtml || '—') + '</span>';

    var rw = m.rw.map(function (v) {
      return '<span' + (v === state.rw ? ' class="hl"' : '') + '>' + v + '</span>';
    }).join('');

    // Le bouton est signalé quand la recherche porte sur le texte masqué dans l'infobulle
    var hit = terms().some(function (x) { return x.length > 1 && m._work.indexOf(x) >= 0; });
    var details = m.work
      ? '<button type="button" class="btn-details' + (hit ? ' hit' : '') + '" data-i="' + m.i + '"' +
        ' aria-describedby="tip" aria-expanded="false">DETAILS</button>'
      : '<span class="nd">—</span>';

    var dl = m.zips.length
      ? m.zips.map(function (z) {
          return '<a class="button btn-dl" href="' + esc(z.url) + '" download="' + esc(z.name) + '"' +
            ' title="Télécharger ' + esc(z.name) + ' depuis GitHub">' +
            '<span aria-hidden="true">⬇</span><span class="zip-name">Télécharger' +
            (m.zips.length > 1 ? ' ' + esc(z.name.split(' - ')[0]) : '') + '</span></a>';
        }).join('')
      : '<span class="nd">' + (m.zipText && m.zipText !== '—' ? esc(m.zipText) : 'Aucune archive') + '</span>';

    return '<tr>' +
      '<td class="date" data-label="Date">' + (m.date || '<span class="nd">Non documentée</span>') + '</td>' +
      '<td class="ids" data-label="ID">' + ids + '</td>' +
      '<td class="name" data-label="Mod"><span class="mod-name">' + m.nameHtml + '</span>' +
        m.types.map(function (x) { return '<span class="tag tag-' + x + '">' + x + '</span>'; }).join('') + '</td>' +
      '<td class="package" data-label="Package / archive">' + m.packageHtml + '</td>' +
      '<td data-label="Version / RimWorld"><div class="version">' + m.versionHtml + '</div>' +
        (rw ? '<div class="rw">' + rw + '</div>' : '') + '</td>' +
      '<td class="work" data-label="Travail effectué">' + details + '</td>' +
      '<td class="dl" data-label="Télécharger">' + dl + '</td>' +
      '</tr>';
  }

  function highlight(root) {
    var t = terms().filter(function (x) { return x.length > 1; });
    if (!t.length) return;
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) { return n.parentNode.closest('button') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT; }
    });
    var nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(function (node) {
      var text = node.nodeValue, f = fold(text), ranges = [];
      // fold() conserve la longueur pour les lettres accentuées précomposées
      if (f.length !== text.length) return;
      t.forEach(function (x) {
        var i = f.indexOf(x);
        while (i >= 0) { ranges.push([i, i + x.length]); i = f.indexOf(x, i + x.length); }
      });
      if (!ranges.length) return;
      ranges.sort(function (a, b) { return a[0] - b[0]; });
      var frag = document.createDocumentFragment(), pos = 0;
      ranges.forEach(function (r) {
        if (r[0] < pos) return;
        frag.appendChild(document.createTextNode(text.slice(pos, r[0])));
        var mk = document.createElement('mark');
        mk.textContent = text.slice(r[0], r[1]);
        frag.appendChild(mk);
        pos = r[1];
      });
      frag.appendChild(document.createTextNode(text.slice(pos)));
      node.parentNode.replaceChild(frag, node);
    });
  }

  /* ---------- Infobulle « Travail effectué » ---------- */
  var tip = $('tip'), tipBtn = null, pinned = false, hideTimer;

  function placeTip() {
    if (!tipBtn) return;
    var r = tipBtn.getBoundingClientRect(), gap = 10, vw = document.documentElement.clientWidth;
    tip.style.maxWidth = Math.min(480, vw - 32) + 'px';
    var w = tip.offsetWidth, h = tip.offsetHeight;
    var left = Math.min(Math.max(16, r.left + r.width / 2 - w / 2), vw - w - 16);
    var below = r.bottom + gap + h <= window.innerHeight || r.top - gap - h < 0;
    var top = below ? r.bottom + gap : r.top - gap - h;
    tip.classList.toggle('above', !below);
    tip.style.left = left + window.scrollX + 'px';
    tip.style.top = top + window.scrollY + 'px';
    tip.style.setProperty('--arrow', (r.left + r.width / 2 - left) + 'px');
  }

  function showTip(btn, pin) {
    clearTimeout(hideTimer);
    if (tipBtn && tipBtn !== btn) tipBtn.setAttribute('aria-expanded', 'false');
    var m = mods[btn.dataset.i];
    tipBtn = btn;
    pinned = !!pin;
    tip.innerHTML = '<div class="tip-head"><span class="tip-title">' + m.nameHtml + '</span>' +
      (pinned ? '<button type="button" class="tip-close" aria-label="Fermer">×</button>' : '') + '</div>' +
      '<div class="tip-label">Travail effectué</div><div class="tip-body">' + m.workHtml + '</div>';
    highlight(tip.querySelector('.tip-body'));
    tip.hidden = false;
    tip.classList.toggle('pinned', pinned);
    btn.setAttribute('aria-expanded', 'true');
    placeTip();
  }

  function hideTip(now) {
    clearTimeout(hideTimer);
    var go = function () {
      tip.hidden = true;
      if (tipBtn) tipBtn.setAttribute('aria-expanded', 'false');
      tipBtn = null; pinned = false;
    };
    if (now) go(); else hideTimer = setTimeout(go, 160);
  }

  tbody.addEventListener('mouseover', function (e) {
    var b = e.target.closest('.btn-details');
    if (b && !pinned) showTip(b, false);
  });
  tbody.addEventListener('mouseout', function (e) {
    var b = e.target.closest('.btn-details');
    if (b && !pinned && !b.contains(e.relatedTarget)) hideTip();
  });
  tbody.addEventListener('focusin', function (e) {
    var b = e.target.closest('.btn-details');
    if (b && !pinned) showTip(b, false);
  });
  tbody.addEventListener('focusout', function (e) {
    var b = e.target.closest('.btn-details');
    if (b && !pinned && !tip.contains(e.relatedTarget)) hideTip();
  });
  tbody.addEventListener('click', function (e) {
    var b = e.target.closest('.btn-details');
    if (!b) return;
    if (pinned && tipBtn === b) hideTip(true); else showTip(b, true);
  });
  tip.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
  tip.addEventListener('mouseleave', function () { if (!pinned) hideTip(); });
  tip.addEventListener('click', function (e) {
    if (e.target.closest('.tip-close')) { var b = tipBtn; hideTip(true); if (b) b.focus(); }
  });
  document.addEventListener('click', function (e) {
    if (pinned && !tip.contains(e.target) && !e.target.closest('.btn-details')) hideTip(true);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !tip.hidden) { var b = tipBtn; hideTip(true); if (b) b.focus(); }
  });
  window.addEventListener('resize', placeTip);
  document.querySelector('.table-wrap').addEventListener('scroll', function () { if (!tip.hidden) placeTip(); });

  /* ---------- Rendu ---------- */
  function pageButton(label, page, opts) {
    opts = opts || {};
    return '<button type="button" data-page="' + page + '"' +
      (opts.current ? ' class="current" aria-current="page"' : '') +
      (opts.disabled ? ' disabled' : '') +
      (opts.title ? ' title="' + opts.title + '"' : '') + '>' + label + '</button>';
  }

  function renderPager(pages) {
    var p = state.page, html = '';
    if (pages <= 1) { $('pager').innerHTML = ''; return; }
    html += pageButton('«', p - 1, { disabled: p === 1, title: 'Page précédente' });
    var last = 0;
    for (var i = 1; i <= pages; i++) {
      if (i === 1 || i === pages || Math.abs(i - p) <= 2) {
        if (last && i - last > 1) html += '<span class="gap">…</span>';
        html += pageButton(i, i, { current: i === p });
        last = i;
      }
    }
    html += pageButton('»', p + 1, { disabled: p === pages, title: 'Page suivante' });
    $('pager').innerHTML = html;
  }

  function render() {
    hideTip(true);
    var rows = filtered();
    var len = state.len || rows.length || 1;
    var pages = Math.max(1, Math.ceil(rows.length / len));
    if (state.page > pages) state.page = pages;
    var start = (state.page - 1) * len;
    var slice = rows.slice(start, start + len);

    tbody.innerHTML = slice.length
      ? slice.map(rowHtml).join('')
      : '<tr class="empty"><td colspan="' + COLS + '">' +
        (mods.length ? 'Aucun mod ne correspond à ces critères.' : 'Le catalogue GitHub est momentanément indisponible.') + '</td></tr>';

    highlight(tbody);

    $('info').textContent = rows.length
      ? 'Affichage de ' + (start + 1) + ' à ' + (start + slice.length) + ' sur ' + rows.length +
        ' entrée' + (rows.length > 1 ? 's' : '') + (rows.length !== mods.length ? ' (filtrées sur ' + mods.length + ')' : '')
      : '0 entrée (sur ' + mods.length + ')';

    headers.forEach(function (th) {
      th.classList.remove('asc', 'desc');
      th.removeAttribute('aria-sort');
      if (th.dataset.key === state.sort) {
        th.classList.add(state.dir);
        th.setAttribute('aria-sort', state.dir === 'asc' ? 'ascending' : 'descending');
      }
    });
    renderPager(pages);
    saveHash();
  }

  // État dans l'URL (#q=...&type=...) pour partager une recherche
  function saveHash() {
    var p = [];
    ['q', 'type', 'rw', 'zip'].forEach(function (k) { if (state[k]) p.push(k + '=' + encodeURIComponent(state[k])); });
    if (state.sort !== DEFAULT.sort || state.dir !== DEFAULT.dir) p.push('sort=' + state.sort + '.' + state.dir);
    if (state.page > 1) p.push('page=' + state.page);
    var h = p.length ? '#' + p.join('&') : '';
    if (location.hash !== h) history.replaceState(null, '', h || location.pathname + location.search);
  }
  function syncInputs() {
    $('q').value = state.q; $('f-type').value = state.type; $('f-rw').value = state.rw; $('f-zip').value = state.zip;
  }
  function loadHash() {
    location.hash.replace(/^#/, '').split('&').forEach(function (kv) {
      var i = kv.indexOf('='); if (i < 0) return;
      var k = kv.slice(0, i), v = decodeURIComponent(kv.slice(i + 1));
      if (k === 'sort') { var s = v.split('.'); if (sorters[s[0]]) { state.sort = s[0]; state.dir = s[1] === 'asc' ? 'asc' : 'desc'; } }
      else if (k === 'page') state.page = parseInt(v, 10) || 1;
      else if (k in DEFAULT) state[k] = v;
    });
    syncInputs();
  }

  // Événements
  var timer;
  $('q').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () { state.q = $('q').value.trim(); state.page = 1; render(); }, 150);
  });
  [['f-type', 'type'], ['f-rw', 'rw'], ['f-zip', 'zip']].forEach(function (p) {
    $(p[0]).addEventListener('change', function () { state[p[1]] = this.value; state.page = 1; render(); });
  });
  $('f-len').addEventListener('change', function () { state.len = parseInt(this.value, 10); state.page = 1; render(); });
  $('reset').addEventListener('click', function () {
    state = Object.assign({ len: state.len }, DEFAULT);
    syncInputs();
    render();
  });
  headers.forEach(function (th) {
    th.tabIndex = 0;
    var go = function () {
      var k = th.dataset.key;
      if (state.sort === k) state.dir = state.dir === 'asc' ? 'desc' : 'asc';
      else { state.sort = k; state.dir = th.dataset.default || 'asc'; }
      state.page = 1; render();
    };
    th.addEventListener('click', go);
    th.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
  });
  $('pager').addEventListener('click', function (e) {
    var b = e.target.closest('button[data-page]');
    if (!b || b.disabled) return;
    state.page = parseInt(b.dataset.page, 10);
    render();
    $('mods').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  loadHash();
  render();
})();
