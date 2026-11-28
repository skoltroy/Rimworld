/* Catalogue : mods du jeu ↔ extensions Skoltroy. Recherche, filtres, tri, pagination, tooltips et fiche détails (sans dépendance). */
(function () {
  'use strict';

  var mods = (window.RW_MODS || []).slice();
  var game = (window.RW_GAME || []).slice();
  var $ = function (id) { return document.getElementById(id); };

  /* ---------- Outils ---------- */
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
  var termsOf = function (q) { return fold(q).split(/\s+/).filter(Boolean); };
  var plural = function (n, word) { return n + ' ' + word + (n > 1 ? 's' : ''); };
  var ORIGIN = { workshop: 'Workshop', official: 'Jeu officiel', skoltroy: 'Mod Skoltroy', local: 'Package local' };
  var TYPE_LABEL = { Traduction: 'Traduction FR', Correctif: 'Correctif', Mod: 'Mod' };
  var shortName = function (name) { return String(name).replace(/^RimWorld – /, ''); };

  // Icônes inline (héritent de la couleur du texte)
  var ICON = {
    dl: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5v8.2m0 0L4.8 6.5M8 9.7l3.2-3.2M2.5 11v2.5h11V11" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ext: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M9.5 2.5h4v4m0-4L7.5 8.5M12 9.5v4H2.5V4h4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    info: '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6.2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 7.2v4M8 4.6v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
    game: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.5 5.5h11v6h-11zM5 8.5h2M6 7.5v2M10.5 8h.1M11.5 9.2h.1" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
  };

  function highlight(root, terms) {
    var t = terms.filter(function (x) { return x.length > 1; });
    if (!t.length) return;
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) { return n.parentNode.closest('button, .icon-btn, .chip, .rw') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT; }
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

  function typeChips(m) {
    return m.types.map(function (x) { return '<span class="chip chip-' + x + '">' + TYPE_LABEL[x] + '</span>'; }).join('');
  }

  function dlIcon(z) {
    return '<a class="icon-btn icon-dl" href="' + esc(z.url) + '" download="' + esc(z.name) + '"' +
      ' data-tip="Télécharger ' + esc(z.name) + '" aria-label="Télécharger ' + esc(z.name) + '">' + ICON.dl + '</a>';
  }

  /* ---------- Tableau générique : état, tri, pagination, rendu ---------- */
  function Table(cfg) {
    var self = this;
    this.cfg = cfg;
    this.tbody = document.querySelector('#' + cfg.table + ' tbody');
    this.headers = Array.prototype.slice.call(document.querySelectorAll('#' + cfg.table + ' th.sortable'));
    this.cols = document.querySelectorAll('#' + cfg.table + ' thead th').length;
    this.state = Object.assign({}, cfg.defaults, { len: parseInt($(cfg.len).value, 10) });

    var timer;
    $(cfg.q).addEventListener('input', function () {
      clearTimeout(timer);
      var input = this;
      timer = setTimeout(function () { self.state.q = input.value.trim(); self.state.page = 1; self.render(); }, 150);
    });
    Object.keys(cfg.filters).forEach(function (key) {
      $(cfg.filters[key]).addEventListener('change', function () { self.state[key] = this.value; self.state.page = 1; self.render(); });
    });
    $(cfg.len).addEventListener('change', function () { self.state.len = parseInt(this.value, 10); self.state.page = 1; self.render(); });
    $(cfg.reset).addEventListener('click', function () { self.set({}); });
    this.headers.forEach(function (th) {
      th.tabIndex = 0;
      var go = function () {
        var k = th.dataset.key;
        if (self.state.sort === k) self.state.dir = self.state.dir === 'asc' ? 'desc' : 'asc';
        else { self.state.sort = k; self.state.dir = th.dataset.default || 'asc'; }
        self.state.page = 1; self.render();
      };
      th.addEventListener('click', go);
      th.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
    });
    $(cfg.pager).addEventListener('click', function (e) {
      var b = e.target.closest('button[data-page]');
      if (!b || b.disabled) return;
      self.state.page = parseInt(b.dataset.page, 10);
      self.render();
      $(cfg.table).scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  Table.prototype.terms = function () { return termsOf(this.state.q); };

  /** Remplace les critères (les autres reviennent à leur valeur par défaut). */
  Table.prototype.set = function (values) {
    this.state = Object.assign({ len: this.state.len }, this.cfg.defaults, values);
    this.sync();
    this.render();
  };

  Table.prototype.sync = function () {
    var s = this.state, cfg = this.cfg;
    $(cfg.q).value = s.q;
    Object.keys(cfg.filters).forEach(function (k) { $(cfg.filters[k]).value = s[k]; });
  };

  Table.prototype.compare = function (a, b) {
    var s = this.state, key = this.cfg.sorters[s.sort], va = key(a), vb = key(b);
    // Les valeurs vides restent toujours en fin de liste, dans l'ordre d'origine.
    if (va === null && vb === null) return a.i - b.i;
    if (va === null) return 1;
    if (vb === null) return -1;
    var r;
    if (s.sort === 'rw') r = cmpVer(va, vb);
    else if (typeof va === 'number') r = va - vb;
    else r = va < vb ? -1 : va > vb ? 1 : 0;
    if (r === 0) return a.i - b.i;
    return s.dir === 'asc' ? r : -r;
  };

  Table.prototype.render = function () {
    var self = this, s = this.state, cfg = this.cfg, t = this.terms();
    hideTip();
    if (cfg.beforeRender) cfg.beforeRender();
    var rows = cfg.data.filter(function (m) {
      if (!cfg.match(m, s)) return false;
      for (var i = 0; i < t.length; i++) if (m._search.indexOf(t[i]) < 0) return false;
      return true;
    }).sort(function (a, b) { return self.compare(a, b); });

    var len = s.len || rows.length || 1;
    var pages = Math.max(1, Math.ceil(rows.length / len));
    if (s.page > pages) s.page = pages;
    if (s.page < 1) s.page = 1;
    var start = (s.page - 1) * len;
    var slice = rows.slice(start, start + len);

    this.tbody.innerHTML = slice.length
      ? slice.map(function (m) { return cfg.row(m, s); }).join('')
      : '<tr class="empty"><td colspan="' + this.cols + '">' + (cfg.data.length ? 'Aucun résultat pour ces critères.' : cfg.emptyAll) + '</td></tr>';
    highlight(this.tbody, t);

    $(cfg.info).textContent = rows.length
      ? (start + 1) + '–' + (start + slice.length) + ' sur ' + plural(rows.length, cfg.noun) +
        (rows.length !== cfg.data.length ? ' (filtrés parmi ' + cfg.data.length + ')' : '')
      : '0 ' + cfg.noun + ' (sur ' + cfg.data.length + ')';

    this.headers.forEach(function (th) {
      th.classList.remove('asc', 'desc');
      th.removeAttribute('aria-sort');
      if (th.dataset.key === s.sort) {
        th.classList.add(s.dir);
        th.setAttribute('aria-sort', s.dir === 'asc' ? 'ascending' : 'descending');
      }
    });
    this.renderPager(pages);
    saveHash();
  };

  Table.prototype.renderPager = function (pages) {
    var p = this.state.page, html = '', last = 0;
    var btn = function (label, page, opts) {
      opts = opts || {};
      return '<button type="button" data-page="' + page + '"' +
        (opts.current ? ' class="current" aria-current="page"' : '') +
        (opts.disabled ? ' disabled' : '') +
        (opts.title ? ' aria-label="' + opts.title + '"' : '') + '>' + label + '</button>';
    };
    if (pages > 1) {
      html += btn('‹', p - 1, { disabled: p === 1, title: 'Page précédente' });
      for (var i = 1; i <= pages; i++) {
        if (i === 1 || i === pages || Math.abs(i - p) <= 2) {
          if (last && i - last > 1) html += '<span class="gap">…</span>';
          html += btn(i, i, { current: i === p });
          last = i;
        }
      }
      html += btn('›', p + 1, { disabled: p === pages, title: 'Page suivante' });
    }
    $(this.cfg.pager).innerHTML = html;
  };

  /* ---------- Index de recherche ---------- */
  mods.forEach(function (m, i) {
    m.i = i;
    m._work = fold(m.work);
    var targets = (m.targets || []).map(function (t) { return t.id + ' ' + t.name; }).join(' ');
    m._search = fold([m.date, m.ids.join(' '), m.name, m.package, m.zipText, m.version, m.work, m.types.join(' '), targets, ORIGIN[m.origin]].join(' '));
    m._rwMax = m.rw.length ? m.rw[m.rw.length - 1] : '';
  });
  game.forEach(function (g, i) {
    g.i = i;
    var ext = g.ext.concat(g.examined).map(function (n) { return mods[n] ? mods[n].name + ' ' + mods[n].package : ''; }).join(' ');
    g._search = fold([g.name, g.id, g.kind === 'official' ? 'jeu officiel dlc ludeon' : 'workshop', ext].join(' '));
  });

  // Liste des versions RW
  var allRw = {};
  mods.forEach(function (m) { m.rw.forEach(function (v) { allRw[v] = 1; }); });
  Object.keys(allRw).sort(cmpVer).reverse().forEach(function (v) {
    var o = document.createElement('option');
    o.value = v; o.textContent = 'RimWorld ' + v;
    $('f-rw').appendChild(o);
  });

  /* ---------- Onglet « Mods du jeu » ---------- */
  function gameRow(g) {
    var kind = g.kind === 'official'
      ? '<span class="chip chip-official">Jeu officiel</span>'
      : '<span class="chip chip-workshop">Workshop</span><span class="mod-id">' + esc(g.id) + '</span>';

    var ext;
    if (g.ext.length) {
      ext = g.ext.map(function (n) {
        var m = mods[n], type = m.types[0] || 'Mod';
        // Plusieurs extensions pour un même mod : la pastille porte le nom court de chacune
        var label = g.ext.length > 1 ? esc(m.name.replace(/\s*-\s*French TradFix$/i, '')) : TYPE_LABEL[type];
        return '<span class="ext-pill">' +
          '<button type="button" class="chip chip-' + type + ' chip-link" data-goto-ext="' + n + '"' +
          ' data-tip="Voir la fiche : ' + esc(m.name) + '">' + label + '</button>' +
          m.zips.map(dlIcon).join('') + '</span>';
      }).join('');
    } else if (g.examined.length) {
      ext = '<button type="button" class="chip chip-Audit chip-link" data-goto-ext="' + g.examined[0] + '"' +
        ' data-tip="Mod examiné : aucun texte à traduire. Voir le compte rendu">Rien à traduire</button>';
    } else {
      ext = '<span class="nd">—</span>';
    }

    var steamTip = g.kind === 'official' ? 'Boutique Steam' : 'Page Steam Workshop';
    return '<tr' + (g.ext.length ? ' class="has-ext"' : '') + '>' +
      '<td class="c-mod" data-label="Mod"><span class="mod-name">' + esc(g.name) + '</span><span class="meta">' + kind + '</span></td>' +
      '<td class="c-ext" data-label="Extension">' + ext + '</td>' +
      '<td class="c-act" data-label="Steam"><a class="icon-btn" href="' + esc(g.url) + '" target="_blank" rel="noopener"' +
        ' data-tip="' + steamTip + '" aria-label="' + steamTip + ' : ' + esc(g.name) + '">' + ICON.ext + '</a></td>' +
      '</tr>';
  }

  var gameTable = new Table({
    table: 'game-mods', q: 'g-q', len: 'g-len', reset: 'g-reset', info: 'g-info', pager: 'g-pager',
    filters: { src: 'g-src', ext: 'g-ext' },
    defaults: { q: '', src: '', ext: '', page: 1, sort: 'name', dir: 'asc' },
    data: game, noun: 'mod', emptyAll: 'Inventaire des mods du jeu indisponible.',
    sorters: {
      // Le contenu officiel reste en tête (ordre de sortie) lors du tri par nom
      name: function (g) { return g.kind === 'official' ? '0' + String(g.i).padStart(3, '0') : '1' + fold(g.name); },
      ext: function (g) { return g.ext.length ? 2 : g.examined.length ? 1 : 0; }
    },
    match: function (g, s) {
      if (s.src && g.kind !== s.src) return false;
      if (s.ext === 'yes' && !g.ext.length) return false;
      if (s.ext === 'no' && g.ext.length) return false;
      if (s.ext === 'examined' && !g.examined.length) return false;
      return true;
    },
    row: gameRow
  });

  /* ---------- Onglet « Mes extensions » ---------- */
  function originHtml(m) {
    var targets = m.targets || [];
    if (!targets.length) return '<span class="origin"><span class="chip chip-' + m.origin + '">' + ORIGIN[m.origin] + '</span></span>';
    return '<span class="origin"><span class="origin-label">pour</span>' + targets.map(function (t) {
      var name = shortName(t.name || 'Mod ' + t.id);
      // Nom déjà contenu dans celui de l'extension : inutile de le répéter
      var label = targets.length === 1 && fold(m.name).indexOf(fold(name)) >= 0 ? 'mod d’origine' : esc(name);
      var tipText = (t.kind === 'official' ? 'Boutique Steam · ' : 'Steam Workshop · ') + esc(name) + (t.kind === 'official' ? '' : ' (ID ' + esc(t.id) + ')');
      return '<span class="origin-item">' +
        '<a href="' + esc(t.url) + '" target="_blank" rel="noopener" data-tip="' + tipText + '">' + label + ' ↗</a>' +
        (t.installed
          ? '<button type="button" class="icon-btn xs" data-goto-game="' + esc(t.id) + '" data-tip="Voir dans les mods du jeu" aria-label="Voir ' + esc(name) + ' dans les mods du jeu">' + ICON.game + '</button>'
          : '<span class="chip chip-missing" data-tip="Mod d’origine absent du jeu installé">absent</span>') +
        '</span>';
    }).join('<span class="sep">·</span>') + '</span>';
  }

  function extRow(m, s) {
    // Plus de deux versions : plage condensée, liste complète dans le tooltip
    var rwList = m.rw.length > 2 ? [m.rw[0] + '–' + m.rw[m.rw.length - 1]] : m.rw;
    var rw = rwList.map(function (v) {
      var hl = s.rw && (v === s.rw || (m.rw.length > 2 && m.rw.indexOf(s.rw) >= 0));
      return '<span' + (hl ? ' class="hl"' : '') + '>' + v + '</span>';
    }).join('');
    var rwTip = 'RimWorld ' + m.rw.join(', ') + (m.version ? ' · ' + m.version : '');

    // Le bouton est signalé quand la recherche porte sur le texte visible seulement dans la fiche
    var hit = termsOf(s.q).some(function (x) { return x.length > 1 && (m._work.indexOf(x) >= 0 || fold(m.package).indexOf(x) >= 0); });
    var actions = '<button type="button" class="icon-btn icon-info' + (hit ? ' hit' : '') + '" data-pop="' + m.i + '"' +
      ' aria-haspopup="dialog" aria-expanded="false" data-tip="Détails : package, version, travail effectué" aria-label="Détails de ' + esc(m.name) + '">' + ICON.info + '</button>' +
      (m.zips.length ? m.zips.map(dlIcon).join('') : '<span class="icon-btn disabled" data-tip="Aucune archive publiée" aria-label="Aucune archive">' + ICON.dl + '</span>');

    return '<tr>' +
      '<td class="c-date" data-label="Date">' + (m.date || '<span class="nd">—</span>') + '</td>' +
      '<td class="c-mod" data-label="Extension"><span class="mod-name">' + m.nameHtml + '</span>' + originHtml(m) + '</td>' +
      '<td class="c-type" data-label="Type">' + (m.zips.length ? '' : '<span class="chip chip-Audit">Examiné</span>') + typeChips(m) + '</td>' +
      '<td class="c-rw" data-label="RimWorld">' + (rw ? '<div class="rw" data-tip="' + esc(rwTip) + '">' + rw + '</div>' : '<span class="nd">—</span>') + '</td>' +
      '<td class="c-act" data-label="Actions">' + actions + '</td>' +
      '</tr>';
  }

  var extTable = new Table({
    table: 'mods', q: 'q', len: 'f-len', reset: 'reset', info: 'info', pager: 'pager',
    filters: { type: 'f-type', origin: 'f-origin', rw: 'f-rw', zip: 'f-zip' },
    defaults: { q: '', type: '', origin: '', rw: '', zip: 'yes', page: 1, sort: 'date', dir: 'desc' },
    data: mods, noun: 'entrée', emptyAll: 'Le catalogue GitHub est momentanément indisponible.',
    sorters: {
      date: function (m) { return m.dateIso || null; },
      name: function (m) { return fold(m.name) || null; },
      type: function (m) { return m.types.length ? m.types.join(' ') : null; },
      rw: function (m) { return m._rwMax || null; }
    },
    match: function (m, s) {
      if (s.type && m.types.indexOf(s.type) < 0) return false;
      if (s.origin && m.origin !== s.origin) return false;
      if (s.rw && m.rw.indexOf(s.rw) < 0) return false;
      if (s.zip === 'yes' && !m.zips.length) return false;
      if (s.zip === 'no' && m.zips.length) return false;
      return true;
    },
    beforeRender: function () { closePop(); },
    row: extRow
  });

  /* ---------- Tooltip moderne : [data-tip] au survol et au focus ---------- */
  var ttip = $('ttip'), tipTarget = null, tipTimer;

  function place(el, anchor, gap) {
    var r = anchor.getBoundingClientRect(), vw = document.documentElement.clientWidth, vh = window.innerHeight;
    var w = el.offsetWidth, h = el.offsetHeight;
    var left = Math.min(Math.max(12, r.left + r.width / 2 - w / 2), vw - w - 12);
    var above = r.top - gap - h >= 8 || r.bottom + gap + h > vh;
    var top = above ? r.top - gap - h : r.bottom + gap;
    el.dataset.side = above ? 'top' : 'bottom';
    el.style.left = left + window.scrollX + 'px';
    el.style.top = top + window.scrollY + 'px';
    el.style.setProperty('--arrow', (r.left + r.width / 2 - left) + 'px');
  }

  function showTip(target) {
    clearTimeout(tipTimer);
    tipTarget = target;
    ttip.textContent = target.dataset.tip;
    ttip.hidden = false;
    ttip.classList.remove('in');
    place(ttip, target, 8);
    void ttip.offsetWidth; // relance l'animation
    ttip.classList.add('in');
    target.setAttribute('aria-describedby', 'ttip');
  }

  function hideTip() {
    clearTimeout(tipTimer);
    if (tipTarget) tipTarget.removeAttribute('aria-describedby');
    tipTarget = null;
    ttip.hidden = true;
    ttip.classList.remove('in');
  }

  document.addEventListener('mouseover', function (e) {
    var t = e.target.closest('[data-tip]');
    if (t === tipTarget) return;
    clearTimeout(tipTimer);
    if (!t) { if (tipTarget) hideTip(); return; }
    if (tipTarget) showTip(t); // passage d'un élément à l'autre : immédiat
    else tipTimer = setTimeout(function () { showTip(t); }, 280);
  });
  document.addEventListener('focusin', function (e) {
    var t = e.target.closest('[data-tip]');
    if (t && e.target.matches(':focus-visible')) showTip(t); else hideTip();
  });
  document.addEventListener('focusout', hideTip);
  window.addEventListener('scroll', hideTip, { passive: true });

  /* ---------- Fiche détails (popover) ---------- */
  var pop = $('pop'), popBtn = null;

  function row(label, html) {
    return html ? '<div class="pop-row"><div class="pop-label">' + label + '</div><div class="pop-value">' + html + '</div></div>' : '';
  }

  function openPop(btn) {
    var m = mods[btn.dataset.pop];
    hideTip();
    if (popBtn) popBtn.setAttribute('aria-expanded', 'false');
    popBtn = btn;
    var dl = m.zips.map(function (z) {
      return '<a class="button btn-dl" href="' + esc(z.url) + '" download="' + esc(z.name) + '">' + ICON.dl + '<span>' + esc(z.name) + '</span></a>';
    }).join('');
    pop.innerHTML =
      '<div class="pop-head"><div><div class="pop-title">' + m.nameHtml + '</div><div class="pop-sub">' +
        (m.date || 'Date non documentée') + ' · ' + (typeChips(m) || '<span class="chip chip-Audit">Examiné</span>') + '</div></div>' +
        '<button type="button" class="pop-close" aria-label="Fermer">×</button></div>' +
      '<div class="pop-body">' +
        row('Mod d’origine', originHtml(m)) +
        row('Package', m.packageHtml) +
        row('Version', m.versionHtml) +
        row('Travail effectué', '<div class="pop-work">' + m.workHtml + '</div>') +
      '</div>' +
      (dl ? '<div class="pop-foot">' + dl + '</div>' : '');
    highlight(pop.querySelector('.pop-body'), extTable.terms());
    pop.hidden = false;
    btn.setAttribute('aria-expanded', 'true');
    place(pop, btn, 10);
    pop.querySelector('.pop-close').focus({ preventScroll: true });
  }

  function closePop(restore) {
    if (pop.hidden) return;
    pop.hidden = true;
    if (popBtn) {
      popBtn.setAttribute('aria-expanded', 'false');
      if (restore) popBtn.focus({ preventScroll: true });
    }
    popBtn = null;
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pop]');
    if (b) { if (popBtn === b) closePop(true); else openPop(b); return; }
    if (e.target.closest('.pop-close')) { closePop(true); return; }
    if (!pop.hidden && !pop.contains(e.target)) closePop(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (!pop.hidden) closePop(true); else hideTip();
  });
  window.addEventListener('resize', function () { if (popBtn) place(pop, popBtn, 10); hideTip(); });

  /* ---------- Onglets ---------- */
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.catalog-tabs [role="tab"]'));
  var tabLinks = Array.prototype.slice.call(document.querySelectorAll('[data-tab-link]'));
  var current = (tabs.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0] || tabs[0]).dataset.tab;

  function activateTab(name, focus) {
    current = name;
    hideTip();
    closePop();
    tabs.forEach(function (item) {
      var selected = item.dataset.tab === name;
      item.classList.toggle('active', selected);
      item.setAttribute('aria-selected', selected ? 'true' : 'false');
      item.tabIndex = selected ? 0 : -1;
      $(item.getAttribute('aria-controls')).hidden = !selected;
      if (selected && focus) item.focus();
    });
    tabLinks.forEach(function (a) { a.classList.toggle('active', a.dataset.tabLink === name); });
    saveHash();
  }
  tabs.concat(tabLinks).forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      e.preventDefault();
      activateTab(tab.dataset.tab || tab.dataset.tabLink, false);
    });
  });
  tabs.forEach(function (tab, index) {
    tab.addEventListener('keydown', function (e) {
      var next = index;
      if (e.key === 'ArrowRight') next = (index + 1) % tabs.length;
      else if (e.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
      else if (e.key === 'Home') next = 0;
      else if (e.key === 'End') next = tabs.length - 1;
      else return;
      e.preventDefault();
      activateTab(tabs[next].dataset.tab, true);
    });
  });

  /* ---------- Liens croisés mod du jeu ↔ extension ---------- */
  document.addEventListener('click', function (e) {
    var toExt = e.target.closest('[data-goto-ext]');
    var toGame = e.target.closest('[data-goto-game]');
    if (toExt) {
      var m = mods[toExt.dataset.gotoExt];
      if (!m) return;
      extTable.set({ q: m.zips.length ? m.zips[0].name.replace(/\.zip$/i, '') : m.ids[0] || m.name, zip: m.zips.length ? 'yes' : '' });
      activateTab('extensions', false);
      $('mods').scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else if (toGame) {
      closePop();
      gameTable.set({ q: toGame.dataset.gotoGame });
      activateTab('game', false);
      $('game-mods').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  /* ---------- État dans l'URL (?tab=…#q=…&gq=…) pour partager une recherche ---------- */
  var tables = [[gameTable, 'g'], [extTable, '']];
  var ready = false;

  function saveHash() {
    if (!ready) return;
    var p = [];
    tables.forEach(function (pair) {
      var t = pair[0], pre = pair[1], s = t.state, d = t.cfg.defaults;
      Object.keys(d).forEach(function (k) {
        if (k === 'sort' || k === 'dir' || k === 'page') return;
        if (s[k] !== d[k]) p.push(pre + k + '=' + encodeURIComponent(s[k]));
      });
      if (s.sort !== d.sort || s.dir !== d.dir) p.push(pre + 'sort=' + s.sort + '.' + s.dir);
      if (s.page > 1) p.push(pre + 'page=' + s.page);
    });
    var url = location.pathname + '?tab=' + current + (p.length ? '#' + p.join('&') : '');
    if (location.pathname + location.search + location.hash !== url) history.replaceState(null, '', url);
  }

  function loadHash() {
    var values = { g: {}, '': {} };
    location.hash.replace(/^#/, '').split('&').forEach(function (kv) {
      var i = kv.indexOf('='); if (i < 0) return;
      var k = kv.slice(0, i), v;
      try { v = decodeURIComponent(kv.slice(i + 1)); } catch (err) { return; }
      var pre = k.charAt(0) === 'g' && k !== 'g' ? 'g' : '';
      values[pre][pre ? k.slice(1) : k] = v;
    });
    tables.forEach(function (pair) {
      var t = pair[0], v = values[pair[1]], s = t.state, d = t.cfg.defaults;
      Object.keys(d).forEach(function (k) {
        if (!(k in v)) return;
        if (k === 'page') s.page = parseInt(v.page, 10) || 1;
        else if (k === 'sort') {
          var x = v.sort.split('.');
          if (t.cfg.sorters[x[0]]) { s.sort = x[0]; s.dir = x[1] === 'asc' ? 'asc' : 'desc'; }
        } else if (k !== 'dir') s[k] = v[k];
      });
      t.sync();
    });
  }

  loadHash();
  gameTable.render();
  extTable.render();
  ready = true;
  saveHash();
})();
