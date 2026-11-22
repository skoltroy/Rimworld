/* Datatable du catalogue : recherche, filtres, tri, pagination (sans dépendance). */
(function () {
  'use strict';

  var STEAM = 'https://steamcommunity.com/sharedfiles/filedetails/?id=';
  var mods = (window.RW_MODS || []).slice();
  var $ = function (id) { return document.getElementById(id); };
  var tbody = document.querySelector('#mods tbody');
  var headers = Array.prototype.slice.call(document.querySelectorAll('#mods th.sortable'));

  var fold = function (s) {
    return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  };
  var esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var fmtSize = function (b) {
    return b < 1024 * 1024 ? Math.max(1, Math.round(b / 1024)) + ' Ko' : (b / 1048576).toFixed(1).replace('.', ',') + ' Mo';
  };
  var cmpVer = function (a, b) {
    var x = a.split('.').map(Number), y = b.split('.').map(Number);
    return (x[0] - y[0]) || (x[1] - y[1]);
  };

  // Index de recherche et valeurs de tri
  mods.forEach(function (m) {
    m._search = fold([m.name, m.folder, m.ids.join(' '), m.version, m.work, m.types.join(' '),
      m.date, m.local ? 'local' : 'steam workshop'].join(' '));
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

  var state = { q: '', type: '', rw: '', src: '', len: 10, page: 1, sort: 'date', dir: 'desc' };

  var sorters = {
    name: function (m) { return fold(m.name); },
    type: function (m) { return m.types.join(' '); },
    id: function (m) { return m.ids.length ? Number(m.ids[0]) : null; },
    rw: function (m) { return m._rwMax || null; },
    version: function (m) { return fold(m.version); },
    date: function (m) { return m.dateIso || null; }
  };

  function compare(a, b) {
    var key = sorters[state.sort], va = key(a), vb = key(b);
    // Les valeurs vides (« Non documentée », « Local ») restent toujours en fin de liste.
    if (va === null && vb === null) return a.n - b.n;
    if (va === null) return 1;
    if (vb === null) return -1;
    var r;
    if (state.sort === 'rw') r = cmpVer(va, vb);
    else if (typeof va === 'number') r = va - vb;
    else r = va < vb ? -1 : va > vb ? 1 : 0;
    if (r === 0) r = fold(a.name).localeCompare(fold(b.name));
    return state.dir === 'asc' ? r : -r;
  }

  function filtered() {
    var terms = fold(state.q).split(/\s+/).filter(Boolean);
    return mods.filter(function (m) {
      if (state.type && m.types.indexOf(state.type) < 0) return false;
      if (state.rw && m.rw.indexOf(state.rw) < 0) return false;
      if (state.src === 'local' && !m.local) return false;
      if (state.src === 'steam' && m.local) return false;
      for (var i = 0; i < terms.length; i++) if (m._search.indexOf(terms[i]) < 0) return false;
      return true;
    }).sort(compare);
  }

  function rowHtml(m) {
    var links = '';
    m.ids.slice(0, 1).forEach(function (id) {
      links += '<a href="' + STEAM + id + '" target="_blank" rel="noopener">Steam</a>';
    });
    if (m.github) links += '<a href="' + esc(m.github) + '" target="_blank" rel="noopener">GitHub</a>';

    var ids = m.ids.length
      ? m.ids.map(function (id) {
          return '<a href="' + STEAM + id + '" target="_blank" rel="noopener" title="Page Steam Workshop">' + id + '</a>';
        }).join('')
      : '<span class="local">Local</span>';

    var rw = m.rw.map(function (v) {
      return '<span' + (v === state.rw ? ' class="hl"' : '') + '>' + v + '</span>';
    }).join('');

    return '<tr>' +
      '<td class="name"><span class="mod-name">' + m.nameHtml + '</span>' +
        '<span class="mod-folder">' + m.folderHtml + '</span>' +
        (links ? '<span class="mod-links">' + links + '</span>' : '') + '</td>' +
      '<td data-label="Type">' + m.types.map(function (t) {
        return '<span class="tag tag-' + t + '">' + t + '</span>';
      }).join('') + '</td>' +
      '<td class="ids" data-label="ID Workshop">' + ids + '</td>' +
      '<td data-label="RimWorld"><div class="rw">' + (rw || '<span>?</span>') + '</div></td>' +
      '<td class="version" data-label="Version">' + m.versionHtml + '</td>' +
      '<td class="date" data-label="Date">' + (m.date || '<span class="nd">Non documentée</span>') + '</td>' +
      '<td class="work" data-label="Travail effectué"><div class="work-text">' + m.workHtml + '</div></td>' +
      '<td class="dl">' + (m.zip
        ? '<a class="button btn-dl" href="' + esc(m.zip.url) + '" download title="Télécharger ' + esc(m.folder) + '.zip">' +
          '<span aria-hidden="true">⬇</span> Zip <small>' + fmtSize(m.zip.size) + '</small></a>'
        : '<span class="nd">—</span>') + '</td>' +
      '</tr>';
  }

  function highlight(root) {
    var terms = fold(state.q).split(/\s+/).filter(function (t) { return t.length > 1; });
    if (!terms.length) return;
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
    var nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(function (node) {
      var text = node.nodeValue, f = fold(text), ranges = [];
      // fold() conserve la longueur pour les lettres accentuées précomposées
      if (f.length !== text.length) return;
      terms.forEach(function (t) {
        var i = f.indexOf(t);
        while (i >= 0) { ranges.push([i, i + t.length]); i = f.indexOf(t, i + t.length); }
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

  function clampLongTexts() {
    Array.prototype.forEach.call(tbody.querySelectorAll('.work-text'), function (el) {
      if (el.textContent.length < 260 || state.q) return;
      el.classList.add('clamp');
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'more'; b.textContent = 'Afficher plus';
      b.addEventListener('click', function () {
        var c = el.classList.toggle('clamp');
        b.textContent = c ? 'Afficher plus' : 'Réduire';
      });
      el.parentNode.appendChild(b);
    });
  }

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
    var rows = filtered();
    var len = state.len || rows.length || 1;
    var pages = Math.max(1, Math.ceil(rows.length / len));
    if (state.page > pages) state.page = pages;
    var start = (state.page - 1) * len;
    var slice = rows.slice(start, start + len);

    tbody.innerHTML = slice.length
      ? slice.map(rowHtml).join('')
      : '<tr class="empty"><td colspan="8">Aucun mod ne correspond à ces critères.</td></tr>';

    highlight(tbody);
    clampLongTexts();

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
    ['q', 'type', 'rw', 'src'].forEach(function (k) { if (state[k]) p.push(k + '=' + encodeURIComponent(state[k])); });
    if (state.sort !== 'date' || state.dir !== 'desc') p.push('sort=' + state.sort + '.' + state.dir);
    if (state.page > 1) p.push('page=' + state.page);
    var h = p.length ? '#' + p.join('&') : ' ';
    if (location.hash !== h.trim()) history.replaceState(null, '', h === ' ' ? location.pathname + location.search : h);
  }
  function loadHash() {
    location.hash.replace(/^#/, '').split('&').forEach(function (kv) {
      var i = kv.indexOf('='); if (i < 0) return;
      var k = kv.slice(0, i), v = decodeURIComponent(kv.slice(i + 1));
      if (k === 'sort') { var s = v.split('.'); if (sorters[s[0]]) { state.sort = s[0]; state.dir = s[1] === 'asc' ? 'asc' : 'desc'; } }
      else if (k === 'page') state.page = parseInt(v, 10) || 1;
      else if (k in state) state[k] = v;
    });
    $('q').value = state.q; $('f-type').value = state.type; $('f-rw').value = state.rw; $('f-src').value = state.src;
  }

  // Événements
  var timer;
  $('q').addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () { state.q = $('q').value.trim(); state.page = 1; render(); }, 150);
  });
  [['f-type', 'type'], ['f-rw', 'rw'], ['f-src', 'src']].forEach(function (p) {
    $(p[0]).addEventListener('change', function () { state[p[1]] = this.value; state.page = 1; render(); });
  });
  $('f-len').addEventListener('change', function () { state.len = parseInt(this.value, 10); state.page = 1; render(); });
  $('reset').addEventListener('click', function () {
    state = { q: '', type: '', rw: '', src: '', len: state.len, page: 1, sort: 'date', dir: 'desc' };
    $('q').value = ''; $('f-type').value = ''; $('f-rw').value = ''; $('f-src').value = '';
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
    document.getElementById('mods').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  loadHash();
  render();
})();
