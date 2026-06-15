/**
 * MegaforBB frontend core JS (theme agnostic)
 * Tema degistiginde de calismasi gereken sistem davranislari bu dosyada tutulur.
 * window.MEGAFORBB_UPLOAD_URL, MEGAFORBB_BASE_URL footer'da atanır.
 */
(function () {
  'use strict';

  var baseUrl = '';
  var mfbbGlobalInited = false;
  var mfbbSoftNavInited = false;

  function getBaseUrl() {
    if (baseUrl) return baseUrl;
    baseUrl = (window.MEGAFORBB_BASE_URL || '').replace(/\/$/, '');
    return baseUrl;
  }

  /** Toast UI Editor instance map: textarea id -> editor (for quote / form sync). */
  window.MEGAFORBB_EDITORS = window.MEGAFORBB_EDITORS || {};

  function getEditorLang(key) {
    var lang = window.MEGAFORBB_EDITOR_LANG;
    return (lang && lang[key]) || key;
  }

  function getLang(key, fallback) {
    var lang = window.MEGAFORBB_LANG;
    if (lang && lang[key] !== undefined && lang[key] !== null && lang[key] !== '') {
      return lang[key];
    }
    return fallback !== undefined ? fallback : key;
  }

  var COLOR_PRESETS = [
    '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3', '#ffffff',
    '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff', '#ff00ff',
    '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#cfe2f3', '#d9d2e9', '#ead1dc'
  ];

  function createColorPopupBody(editorId) {
    var wrap = document.createElement('div');
    wrap.className = 'mfbb-editor-color-popup';
    wrap.setAttribute('data-editor-id', editorId);
    wrap.style.cssText = 'padding:10px;min-width:200px;';
    function addSection(title, isBg) {
      var section = document.createElement('div');
      section.style.cssText = 'margin-bottom:12px;';
      var label = document.createElement('div');
      label.textContent = title;
      label.style.cssText = 'font-weight:600;font-size:11px;margin-bottom:6px;color:#555;';
      section.appendChild(label);
      var grid = document.createElement('div');
      grid.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;';
      COLOR_PRESETS.forEach(function (hex) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('data-color', hex);
        btn.setAttribute('data-bg', isBg ? '1' : '0');
        btn.style.cssText = 'width:18px;height:18px;border:1px solid #ccc;border-radius:2px;cursor:pointer;padding:0;background:' + hex + ';';
        btn.title = hex;
        grid.appendChild(btn);
      });
      section.appendChild(grid);
      var input = document.createElement('input');
      input.type = 'color';
      input.setAttribute('data-bg', isBg ? '1' : '0');
      input.value = '#000000';
      input.style.cssText = 'width:100%;height:26px;cursor:pointer;border:1px solid #ddd;border-radius:4px;';
      section.appendChild(input);
      wrap.appendChild(section);
    }
    addSection(getEditorLang('text_color'), false);
    addSection(getEditorLang('background_color'), true);
    wrap.addEventListener('click', function (e) {
      var btn = e.target.closest && e.target.closest('[data-color]');
      if (btn) {
        var hex = btn.getAttribute('data-color');
        var isBg = btn.getAttribute('data-bg') === '1';
        var ed = window.MEGAFORBB_EDITORS[editorId];
        if (ed && hex) {
          var sel = ed.getSelectedText ? ed.getSelectedText() : '';
          if (!sel) sel = ' ';
          var tag = isBg ? '<span style="background-color:' + hex + '">' + sel + '</span>' : '<span style="color:' + hex + '">' + sel + '</span>';
          if (ed.replaceSelection) ed.replaceSelection(tag); else if (ed.insertHTML) ed.insertHTML(tag);
        }
      }
    });
    wrap.addEventListener('change', function (e) {
      if (e.target.type === 'color') {
        var hex = e.target.value;
        var isBg = e.target.getAttribute('data-bg') === '1';
        var ed = window.MEGAFORBB_EDITORS[editorId];
        if (ed && hex) {
          var sel = ed.getSelectedText ? ed.getSelectedText() : '';
          if (!sel) sel = ' ';
          var tag = isBg ? '<span style="background-color:' + hex + '">' + sel + '</span>' : '<span style="color:' + hex + '">' + sel + '</span>';
          if (ed.replaceSelection) ed.replaceSelection(tag); else if (ed.insertHTML) ed.insertHTML(tag);
        }
      }
    });
    return wrap;
  }

  function createAlignmentPopupBody(editorId) {
    var wrap = document.createElement('div');
    wrap.className = 'mfbb-editor-align-popup';
    wrap.setAttribute('data-editor-id', editorId);
    wrap.style.cssText = 'padding:8px;min-width:140px;';
    var items = [
      { align: 'left', label: getEditorLang('align_left'), icon: '≡' },
      { align: 'center', label: getEditorLang('align_center'), icon: '≡' },
      { align: 'right', label: getEditorLang('align_right'), icon: '≡' },
      { align: 'justify', label: getEditorLang('align_justify'), icon: '≡' }
    ];
    items.forEach(function (item) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('data-align', item.align);
      btn.textContent = item.label;
      btn.style.cssText = 'display:block;width:100%;text-align:left;padding:8px 10px;border:none;background:transparent;cursor:pointer;font-size:13px;border-radius:4px;';
      btn.onmouseover = function () { btn.style.backgroundColor = '#eee'; };
      btn.onmouseout = function () { btn.style.backgroundColor = 'transparent'; };
      wrap.appendChild(btn);
    });
    wrap.addEventListener('click', function (e) {
      var btn = e.target.closest && e.target.closest('[data-align]');
      if (btn) {
        var align = btn.getAttribute('data-align');
        var ed = window.MEGAFORBB_EDITORS[editorId];
        if (ed && ed.exec) {
          var sel = ed.getSelectedText ? ed.getSelectedText() : '';
          if (!sel) sel = ' ';
          var html = '<div style="text-align:' + align + '">' + sel + '</div>';
          if (ed.replaceSelection) ed.replaceSelection(html); else if (ed.insertHTML) ed.insertHTML(html);
        }
      }
    });
    return wrap;
  }

  function createFormatPopupBody(editorId) {
    var wrap = document.createElement('div');
    wrap.className = 'mfbb-editor-format-popup';
    wrap.setAttribute('data-editor-id', editorId);
    wrap.style.cssText = 'padding:8px;min-width:160px;';
    var items = [
      { tag: 'u', label: getEditorLang('underline') },
      { tag: 'mark', label: getEditorLang('highlight') },
      { tag: 'sup', label: getEditorLang('superscript') },
      { tag: 'sub', label: getEditorLang('subscript') }
    ];
    items.forEach(function (item) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.setAttribute('data-tag', item.tag);
      btn.textContent = item.label;
      btn.style.cssText = 'display:block;width:100%;text-align:left;padding:8px 10px;border:none;background:transparent;cursor:pointer;font-size:13px;border-radius:4px;';
      btn.onmouseover = function () { btn.style.backgroundColor = '#eee'; };
      btn.onmouseout = function () { btn.style.backgroundColor = 'transparent'; };
      wrap.appendChild(btn);
    });
    wrap.addEventListener('click', function (e) {
      var btn = e.target.closest && e.target.closest('[data-tag]');
      if (btn) {
        var tag = btn.getAttribute('data-tag');
        var ed = window.MEGAFORBB_EDITORS[editorId];
        if (ed) {
          var sel = ed.getSelectedText ? ed.getSelectedText() : '';
          if (!sel) sel = ' ';
          var html = '<' + tag + '>' + sel + '</' + tag + '>';
          if (ed.replaceSelection) ed.replaceSelection(html); else if (ed.insertHTML) ed.insertHTML(html); else ed.insertText(html);
        }
      }
    });
    return wrap;
  }

  var smileyCache = null;
  function openSmileyPicker(editor, wrapEl, onInsert) {
    var popId = 'mfbb-smiley-popover-' + (wrapEl.id || Date.now());
    var existing = document.getElementById(popId);
    if (existing) {
      existing.style.display = existing.style.display === 'none' ? 'block' : 'none';
      return;
    }
    var pop = document.createElement('div');
    pop.id = popId;
    pop.className = 'mfbb-smiley-popover';
    pop.style.cssText = 'position:absolute;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);padding:10px;max-width:320px;max-height:280px;overflow:auto;';
    var apiUrl = getBaseUrl() + '/api/smileys';
    function render(data) {
      pop.innerHTML = '';
      if (data.unicode && data.unicode.length) {
        var titleU = document.createElement('div');
        titleU.textContent = 'Emoji';
        titleU.style.cssText = 'font-weight:bold;margin-bottom:6px;font-size:12px;';
        pop.appendChild(titleU);
        var gridU = document.createElement('div');
        gridU.className = 'mfbb-smiley-grid';
        gridU.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px;';
        data.unicode.forEach(function (item) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.textContent = item.char || item.unicode_char || '';
          btn.title = item.code || '';
          btn.style.cssText = 'font-size:20px;padding:4px 6px;border:1px solid #eee;border-radius:4px;cursor:pointer;background:#fff;';
          btn.addEventListener('click', function () {
            onInsert(btn.textContent, false);
            pop.remove();
          });
          gridU.appendChild(btn);
        });
        pop.appendChild(gridU);
      }
      if (data.gifs && data.gifs.length) {
        var titleG = document.createElement('div');
        titleG.textContent = 'GIF Smileys';
        titleG.style.cssText = 'font-weight:bold;margin-bottom:6px;font-size:12px;';
        pop.appendChild(titleG);
        var gridG = document.createElement('div');
        gridG.className = 'mfbb-smiley-grid gifs';
        gridG.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;';
        data.gifs.forEach(function (item) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.title = item.code || '';
          var imageUrl = item.image_url || item.url || '';
          if (imageUrl) {
            var img = document.createElement('img');
            img.src = imageUrl;
            img.alt = item.code || '';
            img.style.cssText = 'width:24px;height:24px;object-fit:contain;';
            btn.appendChild(img);
          } else {
            btn.textContent = item.code || '?';
          }
          btn.style.cssText = 'padding:2px;border:1px solid #eee;border-radius:4px;cursor:pointer;background:#fff;';
          btn.addEventListener('click', function () {
            var code = (item.code || '').trim();
            if (code) {
              onInsert(code, false);
            } else if (imageUrl) {
              var html = item.img_tag || ('<img src="' + imageUrl + '" alt="' + (item.code || '').replace(/"/g, '&quot;') + '" class="smiley-gif">');
              onInsert(html, true);
            }
            pop.remove();
          });
          gridG.appendChild(btn);
        });
        pop.appendChild(gridG);
      }
      if (!pop.innerHTML) {
        pop.innerHTML = '<p style="margin:0;font-size:12px;color:#666;">' + getLang('smiley_not_found', 'Smiley bulunamadı.') + '</p>';
      }
    }
    if (smileyCache) {
      render(smileyCache);
    } else {
      pop.innerHTML = '<p style="margin:0;font-size:12px;">' + getLang('loading', 'Yükleniyor...') + '</p>';
      fetch(apiUrl, { credentials: 'same-origin' }).then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      }).then(function (data) {
        if (data && (Array.isArray(data.unicode) || Array.isArray(data.gifs))) {
          smileyCache = { unicode: data.unicode || [], gifs: data.gifs || [] };
        } else {
          smileyCache = { unicode: [], gifs: [] };
        }
        render(smileyCache);
      }).catch(function () {
        pop.innerHTML = '<p style="margin:0;font-size:12px;color:#c00;">' + getLang('load_failed', 'Yüklenemedi. Rota önbelleğini temizleyip sayfayı yenileyin.') + '</p>';
      });
    }
    wrapEl.style.position = wrapEl.style.position || 'relative';
    wrapEl.appendChild(pop);
    var rect = wrapEl.getBoundingClientRect();
    pop.style.top = '10px';
    pop.style.left = '10px';
    document.addEventListener('click', function close(e) {
      if (pop.parentNode && !pop.contains(e.target) && !wrapEl.contains(e.target)) {
        pop.remove();
        document.removeEventListener('click', close);
      }
    });
  }

  /**
   * @mention autocomplete: modal ile kullanıcı seçimi, sadece @kullanıcı_adı yazılır (çift @ yok).
   */
  function initEditorMentionGlobal() {
    var modalId = 'mfbb-mention-modal';
    var modal = document.getElementById(modalId);
    if (!modal) {
      modal = document.createElement('div');
      modal.id = modalId;
      modal.className = 'mfbb-mention-modal hidden fixed inset-0 z-[9999] flex items-center justify-center p-4';
      modal.setAttribute('aria-modal', 'true');
      modal.setAttribute('aria-label', 'Kullanıcı seçin');
      modal.innerHTML = '<div class="mfbb-mention-modal-backdrop absolute inset-0 bg-black/50" data-dismiss></div>' +
        '<div class="mfbb-mention-modal-box relative bg-white rounded-xl shadow-xl border border-gray-200 max-w-sm w-full max-h-[70vh] flex flex-col overflow-hidden">' +
        '<div class="mfbb-mention-modal-title px-4 py-3 border-b border-gray-200 text-sm font-semibold text-[#1a252f]"></div>' +
        '<div class="mfbb-mention-modal-list overflow-y-auto flex-1 py-2"></div>' +
        '</div>';
      document.body.appendChild(modal);
      modal.addEventListener('click', function (e) {
        if (e.target.getAttribute('data-dismiss') !== null) hideMention();
      });
    }
    var backdrop = modal.querySelector('.mfbb-mention-modal-backdrop');
    var titleEl = modal.querySelector('.mfbb-mention-modal-title');
    var listEl = modal.querySelector('.mfbb-mention-modal-list');
    var state = {
      active: false,
      range: null,
      editableRoot: null,
      index: 0,
      debounce: null,
      mentionUsers: []
    };

    function hideMention() {
      state.active = false;
      state.range = null;
      state.editableRoot = null;
      modal.classList.add('hidden');
      listEl.innerHTML = '';
    }

    function getEditableRootFromSelection() {
      var sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return null;
      var node = sel.anchorNode;
      if (!node) return null;
      while (node && node !== document.body) {
        if (node.contentEditable === 'true') return node;
        node = node.parentElement;
      }
      return null;
    }

    function getEditorFromEditable(editable) {
      var wrap = editable && editable.closest && editable.closest('.mfbb-editor-wrap');
      if (!wrap || !wrap.id) return null;
      var id = wrap.id.replace(/^mfbb-editor-wrap-/, '');
      return (window.MEGAFORBB_EDITORS && window.MEGAFORBB_EDITORS[id]) || null;
    }

    function getTextBeforeCursor(maxChars) {
      var sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return '';
      var range = sel.getRangeAt(0).cloneRange();
      range.collapse(true);
      var startContainer = range.startContainer;
      var startOffset = range.startOffset;
      var text = '';
      var chars = 0;
      function walkBack(node, offset) {
        if (chars >= maxChars) return;
        if (node.nodeType === Node.TEXT_NODE) {
          var part = (node.textContent || '').slice(0, offset);
          if (part.length + chars > maxChars) part = part.slice(-(maxChars - chars));
          text = part + text;
          chars += part.length;
          if (chars >= maxChars) return;
          offset = 0;
          node = node.previousSibling;
        } else {
          node = offset > 0 ? (node.childNodes[offset - 1] || null) : node.previousSibling;
        }
        while (node) {
          if (node.nodeType === Node.TEXT_NODE) {
            part = (node.textContent || '').slice(-(maxChars - chars));
            text = part + text;
            chars += part.length;
            if (chars >= maxChars) return;
          } else {
            walkBack(node, node.childNodes.length);
            if (chars >= maxChars) return;
          }
          node = node.previousSibling;
        }
      }
      walkBack(startContainer, startOffset);
      return text;
    }

    function showModal() {
      titleEl.textContent = (window.MEGAFORBB_EDITOR_LANG && window.MEGAFORBB_EDITOR_LANG.mention_choose_user) || getLang('choose_user', 'Etiketlenecek kullanıcıyı seçin');
      modal.classList.remove('hidden');
    }

    function renderList(users) {
      state.index = 0;
      state.mentionUsers = users || [];
      listEl.innerHTML = '';
      if (!state.mentionUsers.length) {
        var empty = document.createElement('div');
        empty.className = 'px-4 py-3 text-sm text-gray-500';
        empty.textContent = (window.MEGAFORBB_EDITOR_LANG && window.MEGAFORBB_EDITOR_LANG.mention_no_users) || getLang('no_users', 'Kullanıcı bulunamadı');
        listEl.appendChild(empty);
        return;
      }
      state.mentionUsers.forEach(function (u, i) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'mfbb-mention-item w-full text-left px-4 py-2.5 flex items-center gap-3 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none border-0 cursor-pointer' + (i === 0 ? ' bg-gray-50' : '');
        row.setAttribute('role', 'option');
        var avatar = document.createElement('img');
        avatar.src = (u.avatar || '').replace(/</g, '&lt;');
        avatar.alt = '';
        avatar.className = 'w-8 h-8 rounded-full object-cover flex-shrink-0';
        var nameSpan = document.createElement('span');
        nameSpan.className = 'font-medium text-[#1a252f] truncate text-sm';
        nameSpan.textContent = u.username || '';
        row.appendChild(avatar);
        row.appendChild(nameSpan);
        row.addEventListener('click', function () {
          selectMention(u.username);
        });
        listEl.appendChild(row);
      });
    }

    /** Seçilen kullanıcıyı editöre yazar: yalnızca @kullanıcı_adı (yazdığımız @ka silinir, yerine @kaan gelir). */
    function selectMention(username) {
      if (!state.range || !state.editableRoot) return;
      var insertText = '@' + username + ' ';
      state.editableRoot.focus();
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(state.range);
      try {
        document.execCommand('insertText', false, insertText);
      } catch (e) {
        try {
          var range = state.range;
          range.deleteContents();
          var tn = document.createTextNode(insertText);
          range.insertNode(tn);
          range.setStartAfter(tn);
          range.collapse(true);
          sel.removeAllRanges();
          sel.addRange(range);
        } catch (e2) { }
      }
      hideMention();
    }

    function fetchUsers(q, callback) {
      if (!q || q.length < 1) { callback([]); return; }
      var url = (typeof getBaseUrl === 'function' ? getBaseUrl() : (window.MEGAFORBB_BASE_URL || '').replace(/\/$/, '')) + '/api/users/search?q=' + encodeURIComponent(q);
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) { callback(data.users || []); })
        .catch(function () { callback([]); });
    }

    function onKeyup(e) {
      var editable = getEditableRootFromSelection();
      var wrap = editable && editable.closest && editable.closest('.mfbb-editor-wrap');
      if (!wrap) {
        if (state.active) hideMention();
        return;
      }
      if (e.key === 'Escape') {
        hideMention();
        return;
      }
      if (state.active && (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter')) {
        e.preventDefault();
        var items = listEl.querySelectorAll('.mfbb-mention-item');
        if (e.key === 'ArrowDown') state.index = (state.index + 1) % Math.max(1, items.length);
        else if (e.key === 'ArrowUp') state.index = (state.index - 1 + items.length) % Math.max(1, items.length);
        else if (e.key === 'Enter' && state.mentionUsers[state.index]) {
          selectMention(state.mentionUsers[state.index].username);
          return;
        }
        items.forEach(function (el, i) {
          el.classList.toggle('bg-gray-50', i === state.index);
        });
        if (items[state.index]) items[state.index].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        return;
      }
      clearTimeout(state.debounce);
      state.debounce = setTimeout(function () {
        var sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
        editable = getEditableRootFromSelection();
        if (!editable || !editable.closest || !editable.closest('.mfbb-editor-wrap')) return;
        var editor = getEditorFromEditable(editable);
        if (!editor) return;
        var text = getTextBeforeCursor(80);
        var match = text.match(/@([a-zA-Z0-9_\u00c0-\u024f]*)$/);
        if (!match) {
          hideMention();
          return;
        }
        if (match[1].length < 2) {
          hideMention();
          return;
        }
        var cursorRange = sel.getRangeAt(0).cloneRange();
        cursorRange.collapse(true);
        var len = match[0].length;
        var startRange = cursorRange.cloneRange();
        try {
          var startNode = cursorRange.startContainer;
          var startOff = cursorRange.startOffset;
          if (startNode.nodeType === Node.TEXT_NODE && startOff >= len) {
            startRange.setStart(startNode, startOff - len);
          } else {
            startRange.setStart(startNode, Math.max(0, startOff - len));
          }
          startRange.setEnd(cursorRange.startContainer, cursorRange.startOffset);
        } catch (err) {
          hideMention();
          return;
        }
        state.range = startRange;
        state.editableRoot = editable;
        state.active = true;
        fetchUsers(match[1], function (users) {
          if (!state.active) return;
          renderList(users);
          showModal();
          var items = listEl.querySelectorAll('.mfbb-mention-item');
          if (items[0]) items[0].classList.add('bg-gray-50');
          if (items[state.index]) items[state.index].scrollIntoView({ block: 'nearest' });
        });
      }, 120);
    }

    function onKeydown(e) {
      if (!state.active) return;
      var editable = getEditableRootFromSelection();
      if (!editable || !editable.closest || !editable.closest('.mfbb-editor-wrap')) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') e.preventDefault();
    }

    document.addEventListener('keyup', onKeyup, true);
    document.addEventListener('keydown', onKeydown, true);
  }

  /** Toast UI parser "length undefined" hatası veren boş/sorunlu etiketleri temizler. */
  function sanitizeHtmlForToastUI(html) {
    if (typeof html !== 'string' || !html) return '';
    var s = html.replace(/\0/g, '');
    s = s.replace(/<p(\s[^>]*)?>\s*<\/p>/gi, '');
    s = s.replace(/<p(\s[^>]*)?>\s*(<br\s*\/?>\s*|&nbsp;)*\s*<\/p>/gi, '');
    s = s.replace(/<div(\s[^>]*)?>\s*<\/div>/gi, '');
    s = s.replace(/(<br\s*\/?>\s*){3,}/gi, '<br><br>');
    s = s.trim();
    return s;
  }

  /** Editör yüklenmezse (CDN gecikmesi vb.) textarea'ya base64 içeriği yazar; düzenleme sayfasında boş kalmayı önler. */
  function fallbackDecodeEditorContent() {
    document.querySelectorAll('textarea[data-editor][data-initial-base64]').forEach(function (ta) {
      if (ta.getAttribute('data-toastui-inited')) return;
      var raw = ta.getAttribute('data-initial-base64');
      if (!raw) return;
      try {
        var bstr = atob(raw);
        var decoded = decodeURIComponent(bstr.split('').map(function (c) {
          return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        if (decoded) ta.value = decoded;
      } catch (e) {
        try { var plain = atob(raw); if (plain) ta.value = plain; } catch (e2) { }
      }
    });
  }

  function initEditors() {
    if (window.MEGAFORBB_EDITOR === 'tinymce' || window.MEGAFORBB_EDITOR === 'ckeditor') {
      return;
    }
    var Editor = (window.toastui && window.toastui.Editor) || window.Editor;
    if (typeof Editor === 'undefined') {
      fallbackDecodeEditorContent();
      return;
    }
    var textareas = document.querySelectorAll('textarea[data-editor]');
    textareas.forEach(function (ta) {
      if (ta.getAttribute('data-toastui-inited')) return;
      var initialData = (ta.value || '').trim();
      if (ta.getAttribute('data-initial-base64')) {
        try {
          var bstr = atob(ta.getAttribute('data-initial-base64'));
          if (bstr) {
            var decoded = decodeURIComponent(bstr.split('').map(function (c) {
              return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            if (typeof decoded === 'string') initialData = decoded;
          }
        } catch (e) {
          try {
            var plain = atob(ta.getAttribute('data-initial-base64'));
            if (typeof plain === 'string') initialData = plain; else initialData = '';
          } catch (e2) { initialData = ''; }
        }
      }
      /* Toast UI "can't access property length, e is undefined" önlemi: her zaman string + parser'ı kıran boş etiketleri temizle */
      var rawInitial = (initialData != null && typeof initialData === 'string') ? initialData : '';
      initialData = sanitizeHtmlForToastUI(rawInitial);
      var id = ta.id || ('editor-' + Math.random().toString(36).slice(2));
      if (!ta.id) ta.id = id;
      var wrap = document.createElement('div');
      wrap.className = 'mfbb-editor-wrap';
      wrap.id = 'mfbb-editor-wrap-' + id;
      ta.parentNode.insertBefore(wrap, ta);
      wrap.appendChild(ta);
      ta.style.display = 'none';
      ta.setAttribute('tabindex', '-1');
      ta.setAttribute('aria-hidden', 'true');
      ta.removeAttribute('required');
      var secretTooltip = ta.getAttribute('data-secret-tooltip') || getLang('secret_tooltip', 'Misafire gizli içerik ekle');
      var secretPlaceholder = ta.getAttribute('data-secret-placeholder') || getLang('secret_placeholder', 'Gizli İçerik');
      var colorPopupBody = createColorPopupBody(id);
      var alignmentPopupBody = createAlignmentPopupBody(id);
      var formatPopupBody = createFormatPopupBody(id);
      var editorEl = document.createElement('div');
      editorEl.id = 'mfbb-tui-' + id;
      wrap.appendChild(editorEl);
      try {
        var editor = new Editor({
          el: editorEl,
          initialEditType: 'wysiwyg',
          initialValue: initialData,
          height: '100%',
          minHeight: '200px',
          usageStatistics: false,
          placeholder: ta.getAttribute('placeholder') || getLang('content_placeholder', 'İçerik yazın...'),
          hideModeSwitch: false,
          toolbarItems: [
            ['heading', 'bold', 'italic', { name: 'formatMenu', tooltip: (window.MEGAFORBB_EDITOR_LANG && window.MEGAFORBB_EDITOR_LANG.format_menu) || 'Biçimlendirme', popup: { body: formatPopupBody }, text: 'Aa', style: { fontWeight: 'bold', fontSize: '12px' } }, 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol', 'task', 'indent', 'outdent'],
            ['table', 'image', 'link', { name: 'media', tooltip: (window.MEGAFORBB_EDITOR_LANG && window.MEGAFORBB_EDITOR_LANG.media_embed) || 'Video ekle (YouTube, Dailymotion)', command: 'addMedia', text: '▶', style: { fontSize: '14px' } }],
            ['code', 'codeblock'],
            [
              { name: 'colorMenu', tooltip: getEditorLang('text_color'), popup: { body: colorPopupBody }, text: 'A', style: { fontWeight: 'bold', color: '#c00' } },
              { name: 'alignMenu', tooltip: getEditorLang('align_left'), popup: { body: alignmentPopupBody }, text: '≡', style: { fontSize: '16px' } },
              { name: 'smileys', tooltip: window.MEGAFORBB_SMILEY_TOOLTIP || 'Emoji / Smiley', command: 'openSmileys', text: '🙂', style: { fontSize: '14px' } },
              { name: 'secret', tooltip: secretTooltip, command: 'addSecret', text: '🔒', style: { fontSize: '14px' } },
              { name: 'spoiler', tooltip: getEditorLang('spoiler_tooltip'), command: 'addSpoiler', text: '👁', style: { fontSize: '14px' } }
            ]
          ],
          hooks: {
            addImageBlobHook: function (blob, callback) {
              var formData = new FormData();
              formData.append('file', blob);
              var path = window.MEGAFORBB_UPLOAD_URL || '/upload/image';
              var url = path.indexOf('http') === 0 ? path : (window.location.origin + path);
              fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
              }).then(function (res) { return res.json(); }).then(function (data) {
                if (data && data.url) {
                  callback(data.url, blob.name || '');
                } else {
                  var msg = (data && data.error && data.error.message) ? data.error.message : getLang('image_upload_failed', 'Resim yüklenemedi.');
                  console.warn('Editor image upload:', msg);
                  callback('');
                }
              }).catch(function (err) {
                console.warn('Editor image upload error', err);
                callback('');
              });
            }
          }
        });

        if (editor.addCommand) {
          editor.addCommand('markdown', 'addSecret', function () {
            var selectedText = editor.getSelection ? editor.getSelection()[0] : '';
            if (typeof selectedText !== 'string') {
              selectedText = editor.getSelectedText ? editor.getSelectedText() : '';
            }
            if (!selectedText) selectedText = secretPlaceholder;
            editor.insertText('[secret]' + selectedText + '[/secret]');
          });
          editor.addCommand('wysiwyg', 'addSecret', function () {
            var selectedText = editor.getSelection ? editor.getSelection()[0] : '';
            if (typeof selectedText !== 'string') {
              selectedText = editor.getSelectedText ? editor.getSelectedText() : '';
            }
            if (!selectedText) selectedText = secretPlaceholder;
            var html = '[secret]' + selectedText + '[/secret]';
            if (editor.insertHTML) {
              editor.insertHTML(html);
            } else {
              editor.insertText(html);
            }
          });
          editor.addCommand('markdown', 'openSmileys', function () {
            openSmileyPicker(editor, wrap, function (charOrHtml, isHtml) {
              if (isHtml) {
                editor.insertText(charOrHtml);
              } else {
                editor.insertText(charOrHtml);
              }
            });
          });
          editor.addCommand('wysiwyg', 'openSmileys', function () {
            openSmileyPicker(editor, wrap, function (charOrHtml, isHtml) {
              if (isHtml && editor.insertHTML) {
                editor.insertHTML(charOrHtml);
              } else {
                editor.insertText(charOrHtml);
              }
            });
          });

          function getSel() {
            var s = editor.getSelectedText ? editor.getSelectedText() : '';
            if (typeof s !== 'string') s = (editor.getSelection && editor.getSelection()[0]) || '';
            return s || ' ';
          }
          var spoilerPlaceholder = (window.MEGAFORBB_EDITOR_LANG && window.MEGAFORBB_EDITOR_LANG.spoiler_placeholder) || 'Spoiler içeriği';
          editor.addCommand('markdown', 'addSpoiler', function () {
            var sel = getSel();
            if (!sel) sel = spoilerPlaceholder;
            editor.insertText('<details class="mfbb-spoiler"><summary>Spoiler</summary>' + sel + '</details>');
          });
          editor.addCommand('wysiwyg', 'addSpoiler', function () {
            var sel = getSel();
            if (!sel) sel = spoilerPlaceholder;
            var html = '<details class="mfbb-spoiler"><summary>Spoiler</summary>' + sel + '</details>';
            if (editor.replaceSelection) editor.replaceSelection(html);
            else if (editor.insertHTML) editor.insertHTML(html);
            else editor.insertText(html);
          });

          function getMediaEmbedHtml(url) {
            if (!url || typeof url !== 'string') return '';
            url = url.trim();
            var ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            if (ytMatch) {
              return '<div class="mfbb-media-embed ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/' + ytMatch[1] + '" allowfullscreen loading="lazy"></iframe></div>';
            }
            var dmMatch = url.match(/dailymotion\.com\/(?:video\/|embed\/video\/)([a-zA-Z0-9]+)/);
            if (dmMatch) {
              return '<div class="mfbb-media-embed ratio ratio-16x9"><iframe src="https://www.dailymotion.com/embed/video/' + dmMatch[1] + '" allowfullscreen loading="lazy"></iframe></div>';
            }
            return '';
          }
          var mediaPromptText = (window.MEGAFORBB_EDITOR_LANG && window.MEGAFORBB_EDITOR_LANG.media_url_placeholder) || 'YouTube veya Dailymotion video URL\'sini yapıştırın';
          editor.addCommand('markdown', 'addMedia', function () {
            var url = window.prompt(mediaPromptText, 'https://');
            var html = getMediaEmbedHtml(url);
            if (html) editor.insertText(html);
          });
          editor.addCommand('wysiwyg', 'addMedia', function () {
            var url = window.prompt(mediaPromptText, 'https://');
            var html = getMediaEmbedHtml(url);
            if (html) {
              if (editor.insertHTML) editor.insertHTML(html);
              else editor.insertText(html);
            }
          });
        }
        ta.setAttribute('data-toastui-inited', '1');
        ta.classList.add('toastui-editor-ready');
        window.MEGAFORBB_EDITORS[id] = editor;

        /* Konu sayfasında reply editörü focus alınca sayfa sona kayıyordu; hash yoksa blur + üste al (köken düzeltme). */
        if (id === 'reply-body') {
          var _h = window.location.hash;
          if (!_h || _h === '#reply') {
            setTimeout(function () {
              try {
                if (editor.blur) editor.blur();
              } catch (e) { }
              window.scrollTo(0, 0);
            }, 10);
          }
        }

        /* Taslak (draft): LocalStorage ile yazıyı sakla; tarayıcı kapansa bile geri dönünce geri yükle. */
        (function initDraft() {
          var form = ta.closest('form');
          if (!form) return;
          var forumSlug = form.getAttribute('data-draft-forum-slug');
          var topicId = form.getAttribute('data-draft-topic-id');
          var draftKey = null;
          if (forumSlug && (id === 'body' || form.id === 'new-topic-form')) draftKey = 'mfbb_draft_topic_' + String(forumSlug).trim();
          else if (topicId && id === 'reply-body') draftKey = 'mfbb_draft_reply_' + String(topicId).trim();
          if (!draftKey) return;
          var isTopicForm = !!forumSlug;
          var titleEl = isTopicForm ? document.getElementById('title') : null;
          var getBody = function () {
            var g = editor && (editor.getHtml || editor.getHTML);
            return (typeof g === 'function' ? g.call(editor) : '') || '';
          };
          var getPayload = function () {
            var body = getBody();
            var title = (isTopicForm && titleEl && titleEl.value) ? titleEl.value : '';
            return isTopicForm ? { title: title, body: body } : { body: body };
          };
          /* Boş sayılan içerik: sadece HTML etiketleri / boşluk (editör <p><br></p> vb. döndürüyor) */
          var isEmptyBody = function (html) {
            if (!html || typeof html !== 'string') return true;
            var text = html.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();
            return text.length === 0;
          };
          var isEmptyTitle = function (t) { return !t || String(t).trim().length === 0; };
          try {
            var raw = localStorage.getItem(draftKey);
            var draft = null;
            if (raw) try { draft = JSON.parse(raw); } catch (e) { }
            var hasRealBody = draft && draft.body && !isEmptyBody(draft.body);
            var hasRealTitle = isTopicForm && draft && draft.title && !isEmptyTitle(draft.title);
            if (hasRealBody || hasRealTitle) {
              var btnWrap = document.createElement('div');
              btnWrap.style.cssText = 'padding: 8px 12px; background: #fdfcee; border: 1px solid #f2e27b; border-radius: 6px; margin-bottom: 12px; font-size: 13px; display: flex; justify-content: space-between; align-items: center; color: #555;';
              var msgSpan = document.createElement('span');
              msgSpan.innerHTML = '<strong>Ayy...</strong> ' + getLang('draft_saved', 'Kaydedilmiş bir taslağınız var.');
              var btns = document.createElement('div');

              var btnRestore = document.createElement('button');
              btnRestore.type = 'button';
              btnRestore.textContent = getLang('draft_restore', 'Taslağı Geri Yükle');
              btnRestore.style.cssText = 'background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 12px; font-weight: 500; color: #333;';

              var btnDiscard = document.createElement('button');
              btnDiscard.type = 'button';
              btnDiscard.textContent = 'Sil';
              btnDiscard.style.cssText = 'background: transparent; border: none; color: #d32f2f; padding: 4px; cursor: pointer; font-size: 12px; margin-left: 8px; text-decoration: underline;';

              btns.appendChild(btnRestore);
              btns.appendChild(btnDiscard);
              btnWrap.appendChild(msgSpan);
              btnWrap.appendChild(btns);

              btnRestore.addEventListener('click', function (e) {
                e.preventDefault();
                if (hasRealBody) {
                  var setContent = editor.setHtml || editor.setHTML;
                  if (typeof setContent === 'function') setContent.call(editor, draft.body);
                }
                if (hasRealTitle && titleEl) titleEl.value = draft.title;
                btnWrap.remove();
              });

              btnDiscard.addEventListener('click', function (e) {
                e.preventDefault();
                try { localStorage.removeItem(draftKey); } catch (err) { }
                btnWrap.remove();
              });

              wrap.parentNode.insertBefore(btnWrap, wrap);
            }
          } catch (e) { }
          var draftDisabled = false;
          var saveDraft = function () {
            if (draftDisabled) return;
            try {
              var p = getPayload();
              var bodyFilled = p.body && !isEmptyBody(p.body);
              var titleFilled = isTopicForm && p.title && !isEmptyTitle(p.title);
              if (bodyFilled || titleFilled) localStorage.setItem(draftKey, JSON.stringify(p));
              else try { localStorage.removeItem(draftKey); } catch (e2) { }
            } catch (e) { }
          };
          var draftTimer = null;
          var scheduleSave = function () {
            if (draftDisabled) return;
            if (draftTimer) clearTimeout(draftTimer);
            draftTimer = setTimeout(saveDraft, 1500);
          };
          if (titleEl) titleEl.addEventListener('input', scheduleSave);
          try {
            if (editor.on && typeof editor.on === 'function') editor.on('change', scheduleSave);
            else if (editor.addEventListener) editor.addEventListener('change', scheduleSave);
          } catch (e) { }
          setInterval(saveDraft, 4000);
          window.addEventListener('beforeunload', saveDraft);
          form._mfbbDraftSaveNow = saveDraft;
          form.addEventListener('submit', function () {
            draftDisabled = true;
            if (draftTimer) clearTimeout(draftTimer);
            try { localStorage.removeItem(draftKey); } catch (e) { }
          }, true);
        })();

        (function setupEditorResize(w, editorContainer) {
          var handle = document.createElement('div');
          handle.className = 'mfbb-editor-resize-handle';
          handle.setAttribute('aria-label', getLang('resize_editor', 'Editör yüksekliğini değiştirmek için sürükleyin'));
          w.appendChild(handle);

          var minH = 350;
          var maxH = Math.max(400, Math.floor((window.innerHeight || 600) * 0.85));
          var startY = 0;
          var startHeight = 0;

          function getMinHeight() {
            return window.matchMedia('(max-width: 768px)').matches ? 220 : 350;
          }

          function getMaxHeight() {
            return Math.max(getMinHeight() + 100, Math.floor((window.innerHeight || 600) * 0.85));
          }

          function onPointerDown(e) {
            e.preventDefault();
            startY = e.touches ? e.touches[0].clientY : e.clientY;
            startHeight = w.offsetHeight;
            minH = getMinHeight();
            maxH = getMaxHeight();
            if (e.touches) {
              document.addEventListener('touchmove', onPointerMove, { passive: false });
              document.addEventListener('touchend', onPointerUp);
            } else {
              document.addEventListener('mousemove', onPointerMove);
              document.addEventListener('mouseup', onPointerUp);
            }
          }

          function onPointerMove(e) {
            e.preventDefault();
            var y = e.touches ? e.touches[0].clientY : e.clientY;
            var delta = y - startY;
            var newHeight = Math.min(maxH, Math.max(minH, startHeight + delta));
            w.style.height = newHeight + 'px';
          }

          function onPointerUp() {
            document.removeEventListener('mousemove', onPointerMove);
            document.removeEventListener('mouseup', onPointerUp);
            document.removeEventListener('touchmove', onPointerMove);
            document.removeEventListener('touchend', onPointerUp);
          }

          handle.addEventListener('mousedown', onPointerDown);
          handle.addEventListener('touchstart', onPointerDown, { passive: false });
        })(wrap, editorEl);

        var form = ta.closest('form');
        if (form && !form.getAttribute('data-toastui-sync')) {
          form.setAttribute('data-toastui-sync', '1');
          form.addEventListener('submit', function (e) {
            if (typeof form._mfbbBeforeSyncSubmit === 'function') {
              try {
                form._mfbbBeforeSyncSubmit();
              } catch (hookErr) {
                console.warn('Form pre-sync hook error', hookErr);
              }
            }
            var getContent = editor && (editor.getHtml || editor.getHTML);
            if (typeof getContent === 'function') {
              ta.value = getContent.call(editor);
            }
            if ((ta.value || '').trim() === '') {
              e.preventDefault();
              var msg = document.getElementById('mfbb-editor-required-msg');
              if (msg) msg.classList.remove('hidden'); else alert(getLang('enter_content', 'İçerik girin.'));
              return;
            }
            /* Gönderimde taslağı temizle */
            var slug = form.getAttribute('data-draft-forum-slug');
            var tid = form.getAttribute('data-draft-topic-id');
            try {
              if (slug) localStorage.removeItem('mfbb_draft_topic_' + String(slug).trim());
              if (tid) localStorage.removeItem('mfbb_draft_reply_' + String(tid).trim());
            } catch (err) { }
          }, true);
        }
      } catch (err) {
        /* İlk deneme başarısızsa boş içerikle açıp setHTML ile yüklemeyi dene (parser bazen initialValue'da takılıyor) */
        if (rawInitial && editorEl && editorEl.parentNode) {
          try {
            editorEl.innerHTML = '';
            var editor2 = new Editor({
              el: editorEl,
              initialEditType: 'wysiwyg',
              initialValue: '',
              height: '100%',
              minHeight: '200px',
              usageStatistics: false,
              placeholder: ta.getAttribute('placeholder') || getLang('content_placeholder', 'İçerik yazın...'),
              hideModeSwitch: false,
              toolbarItems: [
                ['heading', 'bold', 'italic', 'strike'],
                ['hr', 'quote'],
                ['ul', 'ol', 'task', 'indent', 'outdent'],
                ['table', 'image', 'link'],
                ['code', 'codeblock']
              ]
            });
            var setHtml = editor2.setHTML || editor2.setHtml;
            if (typeof setHtml === 'function') {
              var contentToSet = sanitizeHtmlForToastUI(rawInitial) || rawInitial;
              setHtml.call(editor2, contentToSet);
            }
            ta.setAttribute('data-toastui-inited', '1');
            ta.classList.add('toastui-editor-ready');
            window.MEGAFORBB_EDITORS[id] = editor2;
            var form = ta.closest('form');
            if (form && !form.getAttribute('data-toastui-sync')) {
              form.setAttribute('data-toastui-sync', '1');
              form.addEventListener('submit', function (e) {
                if (typeof form._mfbbBeforeSyncSubmit === 'function') {
                  try {
                    form._mfbbBeforeSyncSubmit();
                  } catch (hookErr) {
                    console.warn('Form pre-sync hook error', hookErr);
                  }
                }
                var getContent = editor2.getHTML || editor2.getHtml;
                if (typeof getContent === 'function') ta.value = getContent.call(editor2);
                if ((ta.value || '').trim() === '') { e.preventDefault(); alert(getLang('enter_content', 'İçerik girin.')); }
              }, true);
            }
            return;
          } catch (err2) { }
        }
        console.warn('Toast UI Editor init error (fallback: textarea)', err && (err.message || err));
        ta.style.display = '';
        ta.value = typeof rawInitial === 'string' ? rawInitial : (ta.value || '');
        ta.setAttribute('data-toastui-inited', '1');
        if (wrap.contains(editorEl)) wrap.removeChild(editorEl);
        wrap.parentNode.insertBefore(ta, wrap);
        wrap.remove();
      }
    });
  }

  function initLightbox() {
    var lb = document.getElementById('mfbb-lightbox');
    var lbImg = document.getElementById('mfbb-lightbox-img');
    if (!lb || !lbImg) return;
    function openLightbox(src) {
      if (!src) return;
      lbImg.src = src;
      lb.classList.remove('hidden');
      lb.classList.add('flex');
    }
    document.addEventListener('click', function (e) {
      var cover = e.target.closest('.ips-profile__cover-img--lightbox');
      if (cover) {
        e.preventDefault();
        openLightbox(cover.getAttribute('data-lightbox-src') || cover.src);
        return;
      }
      var img = e.target.closest('.prose img, .post-content img');
      if (!img) return;
      e.preventDefault();
      openLightbox(img.src);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var cover = e.target.closest('.ips-profile__cover-img--lightbox');
      if (!cover) return;
      e.preventDefault();
      openLightbox(cover.getAttribute('data-lightbox-src') || cover.src);
    });
  }

  function initProfileWatcher() {
    var watcher = document.getElementById('profile-watcher');
    var profile = document.getElementById('ips-profile-header');
    if (!watcher || !profile) return;
    var pupils = watcher.querySelectorAll('.ips-profile__pupil');
    if (!pupils.length) return;
    function movePupils(clientX, clientY) {
      pupils.forEach(function (pupil) {
        var eye = pupil.parentElement;
        if (!eye) return;
        var rect = eye.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        var dx = clientX - cx;
        var dy = clientY - cy;
        var angle = Math.atan2(dy, dx);
        var dist = Math.min(8, Math.hypot(dx, dy) / 18);
        var x = Math.cos(angle) * dist;
        var y = Math.sin(angle) * dist;
        pupil.style.transform = 'translate(' + x + 'px,' + y + 'px)';
      });
    }
    document.addEventListener('mousemove', function (e) {
      movePupils(e.clientX, e.clientY);
    });
  }

  function initProfileViewersModal() {
    var btn = document.getElementById('profile-viewers-btn');
    var modal = document.getElementById('profile-viewers-modal');
    var listEl = document.getElementById('profile-viewers-list');
    var summaryEl = document.getElementById('profile-viewers-summary');
    if (!btn || !modal || !listEl) return;
    var viewersUrl = listEl.getAttribute('data-viewers-url');
    var loaded = false;

    function closeModal() {
      modal.classList.add('hidden');
      modal.setAttribute('aria-hidden', 'true');
    }

    function openModal() {
      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');
      if (loaded || !viewersUrl) return;
      fetch(viewersUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          loaded = true;
          var unique = data.unique_viewers || 0;
          var total = data.total_views || 0;
          var summaryTpl = listEl.getAttribute('data-summary-template') || '';
          var emptyText = listEl.getAttribute('data-empty-text') || '';
          var visitsTpl = listEl.getAttribute('data-visits-template') || '';
          if (summaryEl && summaryTpl) {
            summaryEl.textContent = summaryTpl
              .replace(':unique', String(unique))
              .replace(':total', String(total));
          }
          var viewers = data.viewers || [];
          if (!viewers.length) {
            listEl.innerHTML = '<p class="ips-profile-modal__loading">' + emptyText + '</p>';
            return;
          }
          listEl.innerHTML = viewers.map(function (v) {
            var visits = visitsTpl.replace(':count', String(v.view_count || 1));
            return '<div class="ips-profile-viewer-row">' +
              '<img src="' + (v.avatar_url || '') + '" alt="" class="ips-profile-viewer-row__avatar">' +
              '<div class="ips-profile-viewer-row__main">' +
              '<a href="' + (v.profile_url || '#') + '" class="ips-profile-viewer-row__name">' + (v.username || '') + '</a>' +
              '<div class="ips-profile-viewer-row__meta">' + (v.last_viewed_at || '') + ' · ' + visits + '</div>' +
              '</div></div>';
          }).join('');
        })
        .catch(function () {
          var errText = listEl.getAttribute('data-error-text') || '';
          listEl.innerHTML = '<p class="ips-profile-modal__loading">' + errText + '</p>';
        });
    }

    btn.addEventListener('click', openModal);
    modal.querySelectorAll('[data-profile-modal-close]').forEach(function (el) {
      el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
  }

  function initProfileStatsDashboard() {
    var root = document.getElementById('ips-stats-dashboard');
    if (!root) return;
    var raw = root.getAttribute('data-charts');
    if (!raw) return;
    var data;
    try { data = JSON.parse(raw); } catch (e) { return; }
    if (!data || !data.labels) return;

    var colors = {
      content: '#4338ca',
      topics: '#1d4ed8',
      posts: '#64748b',
      articles: '#be185d',
      ideas: '#b45309',
      likes: '#ec4899',
      rep_received: '#d97706'
    };

    function drawSparkline(canvas, series, color) {
      if (!canvas || !series || !series.length) return;
      var dpr = window.devicePixelRatio || 1;
      var rect = canvas.getBoundingClientRect();
      var w = rect.width || 180;
      var h = parseInt(canvas.getAttribute('height'), 10) || 56;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      var ctx = canvas.getContext('2d');
      ctx.scale(dpr, dpr);
      ctx.clearRect(0, 0, w, h);
      var max = Math.max.apply(null, series.concat([1]));
      var pad = 4;
      var barW = Math.max(3, (w - pad * 2) / series.length - 2);
      series.forEach(function (v, i) {
        var bh = Math.max(2, ((v / max) * (h - pad * 2)));
        var x = pad + i * (barW + 2);
        var y = h - pad - bh;
        ctx.fillStyle = color;
        ctx.globalAlpha = i === series.length - 1 ? 1 : 0.55;
        ctx.fillRect(x, y, barW, bh);
      });
      ctx.globalAlpha = 1;
    }

    function setTrend(el, key) {
      if (!el || !data.deltas || !data.deltas[key]) return;
      var d = data.deltas[key];
      var thisTpl = root.getAttribute('data-this-month') || '';
      var deltaTpl = root.getAttribute('data-month-delta') || '';
      var delta = d.delta || 0;
      var sign = delta > 0 ? '+' : '';
      el.textContent = thisTpl.replace(':count', String(d.this_month || 0));
      if (delta !== 0) {
        el.textContent += ' · ' + deltaTpl.replace(':delta', sign + String(delta));
        el.classList.add(delta > 0 ? 'ips-dash-card__trend--up' : 'ips-dash-card__trend--down');
      }
    }

    root.querySelectorAll('.ips-dash-card[data-series]').forEach(function (card) {
      var key = card.getAttribute('data-series');
      var series = data[key];
      if (!series) return;
      var canvas = card.querySelector('.ips-dash-card__chart');
      drawSparkline(canvas, series, colors[key] || '#105289');
      setTrend(card.querySelector('[data-delta="' + key + '"]'), key);
    });

    var main = document.getElementById('ips-stats-main-chart');
    if (!main) return;
    var dpr = window.devicePixelRatio || 1;
    var rect = main.getBoundingClientRect();
    var w = rect.width || 600;
    var h = 220;
    main.width = w * dpr;
    main.height = h * dpr;
    var ctx = main.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, w, h);

    var labels = data.labels || [];
    var sets = [
      { key: 'content', color: colors.content },
      { key: 'topics', color: colors.topics },
      { key: 'posts', color: colors.posts }
    ];
    if (data.articles && data.articles.some(function (v) { return v > 0; })) {
      sets.push({ key: 'articles', color: colors.articles });
    }
    var n = labels.length || 1;
    var max = 1;
    labels.forEach(function (_, i) {
      sets.forEach(function (s) {
        max = Math.max(max, (data[s.key] && data[s.key][i]) || 0);
      });
    });
    var padL = 36;
    var padR = 12;
    var padT = 12;
    var padB = 28;
    var chartW = w - padL - padR;
    var chartH = h - padT - padB;
    var groupW = chartW / n;
    var barW = Math.max(4, (groupW - 8) / sets.length);

    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    for (var g = 0; g <= 4; g++) {
      var gy = padT + (chartH / 4) * g;
      ctx.beginPath();
      ctx.moveTo(padL, gy);
      ctx.lineTo(w - padR, gy);
      ctx.stroke();
    }

    labels.forEach(function (lbl, i) {
      var gx = padL + i * groupW + 4;
      sets.forEach(function (s, si) {
        var v = (data[s.key] && data[s.key][i]) || 0;
        var bh = Math.max(2, (v / max) * chartH);
        var x = gx + si * (barW + 1);
        var y = padT + chartH - bh;
        ctx.fillStyle = s.color;
        ctx.fillRect(x, y, barW, bh);
      });
      if (i % 2 === 0 || n <= 6) {
        ctx.fillStyle = '#94a3b8';
        ctx.font = '10px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(lbl, padL + i * groupW + groupW / 2, h - 8);
      }
    });
  }

  function initPostsBulkBar() {
    var bar = document.getElementById('posts-bulk-bar');
    if (!bar) return;
    var countEl = document.getElementById('posts-bulk-count');
    var mergeBtn = document.getElementById('posts-bulk-merge');
    var reportBtn = document.getElementById('posts-bulk-report-btn');
    var reportModal = document.getElementById('posts-bulk-report-modal');
    var reportForm = document.getElementById('posts-bulk-report-form');
    var reportIdsContainer = document.getElementById('posts-bulk-report-ids');
    var reportCancel = document.getElementById('posts-bulk-report-cancel');
    function updateBar() {
      var cbs = document.querySelectorAll('.post-select-cb:checked');
      var n = cbs.length;
      if (n === 0) { bar.classList.add('hidden'); return; }
      bar.classList.remove('hidden');
      var format = (bar.getAttribute('data-posts-selected-format') || getLang('posts_selected', ':count mesaj seçildi'));
      if (countEl) countEl.textContent = format.replace(/:count/g, String(n));
      if (mergeBtn) mergeBtn.disabled = n < 2;
    }
    if (reportBtn && reportIdsContainer) {
      reportBtn.addEventListener('click', function () {
        var cbs = document.querySelectorAll('.post-select-cb:checked');
        reportIdsContainer.innerHTML = '';
        cbs.forEach(function (cb) {
          var i = document.createElement('input');
          i.type = 'hidden';
          i.name = 'post_ids[]';
          i.value = cb.value;
          reportIdsContainer.appendChild(i);
        });
        if (reportModal) reportModal.classList.remove('hidden');
      });
    }
    if (reportCancel) reportCancel.addEventListener('click', function () { if (reportModal) reportModal.classList.add('hidden'); });
    if (reportModal) reportModal.addEventListener('click', function (e) { if (e.target === reportModal) reportModal.classList.add('hidden'); });
    document.querySelectorAll('.post-select-cb').forEach(function (cb) { cb.addEventListener('change', updateBar); });
  }

  function initPostQuote() {
    function escapeHtml(s) {
      if (!s) return '';
      var div = document.createElement('div');
      div.textContent = s;
      return div.innerHTML;
    }

    var replyHintCancel = document.getElementById('reply-hint-cancel');
    var replyToIdInput = document.getElementById('reply-to-id-input');
    var replyHintBox = document.getElementById('reply-hint-box');
    var replyHintText = document.getElementById('reply-hint-text');

    if (replyHintCancel) {
      replyHintCancel.addEventListener('click', function () {
        if (replyToIdInput) replyToIdInput.value = '';
        if (replyHintBox) replyHintBox.classList.add('hidden');
      });
    }

    // --- 1. NESTED REPLY BUTTON ---
    document.querySelectorAll('.post-reply-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var postId = this.getAttribute('data-post-id');
        var username = (this.getAttribute('data-username') || '').replace(/"/g, '');

        var replyBlock = document.getElementById('reply');
        if (replyBlock) replyBlock.scrollIntoView({ behavior: 'smooth' });

        if (replyToIdInput) replyToIdInput.value = postId;
        if (replyHintBox && replyHintText) {
          replyHintText.innerHTML = '<i class="fa-solid fa-reply mr-1"></i> Replying to <strong>@' + escapeHtml(username) + '</strong> (Post #' + escapeHtml(postId) + ')';
          replyHintBox.classList.remove('hidden');
        }

        var replyEl = document.getElementById('reply-body');
        var editor = replyEl && window.MEGAFORBB_EDITORS && (window.MEGAFORBB_EDITORS['reply-body'] || (replyEl.id && window.MEGAFORBB_EDITORS[replyEl.id]));
        if (editor && typeof editor.focus === 'function') {
          editor.focus();
        } else if (replyEl) {
          replyEl.focus();
        }
      });
    });

    // --- 2. SINGLE QUOTE BUTTON ---
    function getReplyEditor() {
      var replyEl = document.getElementById('reply-body');
      var editor = replyEl && window.MEGAFORBB_EDITORS && (window.MEGAFORBB_EDITORS['reply-body'] || (replyEl.id && window.MEGAFORBB_EDITORS[replyEl.id]));
      return { replyEl: replyEl, editor: editor };
    }

    function normalizeQuoteBody(body) {
      return String(body || '').replace(/\r\n?/g, '\n').trim();
    }

    function buildQuoteAuthorLink(username) {
      var cleanUsername = String(username || '').trim();
      var memberUrl = getBaseUrl() + '/member/' + encodeURIComponent(cleanUsername);
      return '<a href="' + escapeHtml(memberUrl) + '" class="mention mfbb-quote-author" data-mention-username="' + escapeHtml(cleanUsername) + '">@' + escapeHtml(cleanUsername) + '</a>';
    }

    function buildQuoteHtml(username, postId, body) {
      var safeBody = escapeHtml(normalizeQuoteBody(body)).replace(/\n/g, '<br>');
      return '<p></p><blockquote class="mfbb-quote" data-author="' + escapeHtml(username) + '" data-post="' + escapeHtml(postId) + '">' +
        '<div class="mfbb-quote-header"><i class="fa-solid fa-quote-left" aria-hidden="true"></i>' + buildQuoteAuthorLink(username) + '</div>' +
        '<div class="mfbb-quote-body">' + safeBody + '</div>' +
        '</blockquote><p></p>';
    }

    function buildQuoteBb(username, postId, body) {
      return '\n\n[quote author="' + String(username || '') + '" post="' + String(postId || '') + '"]\n' + normalizeQuoteBody(body) + '\n[/quote]\n\n';
    }

    function appendQuotesToEditor(quoteHtml, quoteBb) {
      var refs = getReplyEditor();
      var replyEl = refs.replyEl;
      var editor = refs.editor;
      var getContent = editor && (editor.getHtml || editor.getHTML);

      if (typeof getContent === 'function') {
        var cur = getContent.call(editor) || '';
        var setContent = editor.setHtml || editor.setHTML;
        if (typeof setContent === 'function') {
          setContent.call(editor, cur + quoteHtml);
          if (typeof editor.focus === 'function') {
            editor.focus();
          }
          return true;
        }
      }

      if (replyEl) {
        replyEl.value = (replyEl.value || '') + quoteBb;
        replyEl.focus();
        return true;
      }

      return false;
    }

    function getQuoteBodyFromTemplate(postId) {
      var bodyEl = document.getElementById('post-body-' + postId);
      if (bodyEl) {
        var domText = typeof bodyEl.innerText === 'string' ? bodyEl.innerText : bodyEl.textContent;
        if (domText) return normalizeQuoteBody(domText);
      }

      var tpl = document.getElementById('post-quote-body-' + postId);
      if (!tpl) return '';
      var tplText = (tpl.content && typeof tpl.content.textContent !== 'undefined') ? (tpl.content.textContent || '').trim() : (tpl.textContent || '').trim();
      return normalizeQuoteBody(tplText);
    }

    function getQuoteData(postId) {
      var quoteBtn = document.querySelector('.post-quote-btn[data-post-id="' + postId + '"]');
      return {
        postId: String(postId || ''),
        username: quoteBtn ? (quoteBtn.getAttribute('data-username') || 'User') : 'User',
        body: getQuoteBodyFromTemplate(postId)
      };
    }

    function clearReplyContext() {
      if (replyToIdInput) replyToIdInput.value = '';
      if (replyHintBox) replyHintBox.classList.add('hidden');
    }

    function insertQuoteToEditor(username, postId, body) {
      return appendQuotesToEditor(buildQuoteHtml(username, postId, body), buildQuoteBb(username, postId, body));
    }

    function insertSelectedMultiQuotes() {
      if (!Array.isArray(window.megaforMultiQuotes) || window.megaforMultiQuotes.length === 0) {
        return false;
      }

      var htmlParts = [];
      var bbParts = [];

      window.megaforMultiQuotes.forEach(function (pid) {
        var quote = getQuoteData(pid);
        htmlParts.push(buildQuoteHtml(quote.username, quote.postId, quote.body));
        bbParts.push(buildQuoteBb(quote.username, quote.postId, quote.body));
      });

      clearReplyContext();

      if (!appendQuotesToEditor(htmlParts.join(''), bbParts.join(''))) {
        return false;
      }

      clearMultiQuotes();
      return true;
    }

    document.querySelectorAll('.post-quote-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var postId = this.getAttribute('data-post-id');
        var username = (this.getAttribute('data-username') || '').replace(/"/g, '');
        var body = getQuoteBodyFromTemplate(postId);

        clearReplyContext();

        var replyBlock = document.getElementById('reply');
        if (replyBlock) replyBlock.scrollIntoView({ behavior: 'smooth' });

        insertQuoteToEditor(username, postId, body);
      });
    });

    // --- 3. MULTI-QUOTE SYSTEM ---
    window.megaforMultiQuotes = [];
    var replyFormElem = document.getElementById('topic-reply-form');
    var topicId = replyFormElem && replyFormElem.dataset ? replyFormElem.dataset.draftTopicId : null;
    var mqKey = topicId ? 'mfbb_mq_' + String(topicId).trim() : null;

    var mqBar = document.getElementById('multi-quote-bar');
    var mqCount = document.getElementById('multi-quote-count');
    var mqInsertBtn = document.getElementById('multi-quote-insert-btn');
    var mqClearBtn = document.getElementById('multi-quote-clear-btn');

    function updateMultiQuoteUI() {
      if (!mqBar) return;
      if (window.megaforMultiQuotes.length > 0) {
        if (mqCount) mqCount.textContent = window.megaforMultiQuotes.length;
        mqBar.classList.remove('hidden');
      } else {
        mqBar.classList.add('hidden');
      }

      document.querySelectorAll('.post-multiquote-btn').forEach(function (btn) {
        var pid = btn.getAttribute('data-post-id');
        var icon = btn.querySelector('.mq-icon');
        var hasIt = window.megaforMultiQuotes.indexOf(pid) !== -1;

        if (hasIt) {
          btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
          btn.classList.remove('text-slate-600', 'bg-white', 'border-slate-200');
          if (icon) {
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-check');
          }
        } else {
          btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
          btn.classList.add('text-slate-600', 'bg-white', 'border-slate-200');
          if (icon) {
            icon.classList.add('fa-plus');
            icon.classList.remove('fa-check');
          }
        }
      });

      if (mqKey) {
        localStorage.setItem(mqKey, JSON.stringify(window.megaforMultiQuotes));
      }
    }

    function clearMultiQuotes() {
      window.megaforMultiQuotes = [];
      if (mqKey) localStorage.removeItem(mqKey);
      updateMultiQuoteUI();
    }

    // Try to load from localStorage
    if (mqKey) {
      try {
        var savedMq = localStorage.getItem(mqKey);
        if (savedMq) {
          var parsedMq = JSON.parse(savedMq);
          window.megaforMultiQuotes = Array.isArray(parsedMq) ? parsedMq.map(function (pid) { return String(pid); }) : [];
          setTimeout(updateMultiQuoteUI, 50);
        }
      } catch (e) { }
    }

    document.querySelectorAll('.post-multiquote-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var pid = this.getAttribute('data-post-id');
        var idx = window.megaforMultiQuotes.indexOf(pid);
        if (idx === -1) {
          window.megaforMultiQuotes.push(pid);
        } else {
          window.megaforMultiQuotes.splice(idx, 1);
        }
        updateMultiQuoteUI();
      });
    });

    if (mqClearBtn) {
      mqClearBtn.addEventListener('click', function () {
        clearMultiQuotes();
      });
    }

    if (mqInsertBtn) {
      mqInsertBtn.addEventListener('click', function () {
        if (window.megaforMultiQuotes.length === 0) return;

        if (!insertSelectedMultiQuotes()) return;

        var replyBlock = document.getElementById('reply');
        if (replyBlock) replyBlock.scrollIntoView({ behavior: 'smooth' });
      });
    }

    // Clear multi-quotes on form submit
    if (replyFormElem) {
      replyFormElem.addEventListener('submit', function () {
        setTimeout(function () {
          clearMultiQuotes();
        }, 100);
      });
    }
  }

  function initPostbitReportModal() {
    var modal = document.getElementById('postbit-report-modal');
    var form = document.getElementById('postbit-report-form');
    var reasonInput = document.getElementById('postbit-report-reason');
    var cancelBtn = document.getElementById('postbit-report-cancel');
    var backdrop = document.getElementById('postbit-report-backdrop');
    if (!modal || !form) return;
    var url = getBaseUrl();
    document.querySelectorAll('.post-report-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var postId = this.getAttribute('data-post-id') || '';
        form.action = url + '/post/' + postId + '/report';
        if (reasonInput) reasonInput.value = '';
        modal.classList.remove('hidden');
      });
    });
    function closeModal() { modal.classList.add('hidden'); }
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
  }

  function initPostbitRepModal() {
    var modal = document.getElementById('postbit-rep-modal');
    var form = document.getElementById('postbit-rep-form');
    var valueInput = document.getElementById('postbit-rep-value');
    var postIdInput = document.getElementById('postbit-rep-post-id');
    var commentInput = document.getElementById('postbit-rep-comment');
    var cancelBtn = document.getElementById('postbit-rep-cancel');
    var backdrop = document.getElementById('postbit-rep-backdrop');
    if (!modal || !form) return;
    var url = getBaseUrl();
    document.querySelectorAll('.post-rep-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var username = this.getAttribute('data-username');
        var value = this.getAttribute('data-value');
        var postId = this.getAttribute('data-post-id') || '';
        if (valueInput) valueInput.value = value;
        if (postIdInput) postIdInput.value = postId;
        if (commentInput) commentInput.value = '';
        form.action = url + '/member/' + encodeURIComponent(username) + '/give-rep';
        modal.classList.remove('hidden');
      });
    });
    function closeModal() { modal.classList.add('hidden'); }
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
  }

  function initTopicLiveReplies() {
    if (window._mfbbTopicLiveSse && typeof window._mfbbTopicLiveSse.close === 'function') {
      try { window._mfbbTopicLiveSse.close(); } catch (e) { }
      window._mfbbTopicLiveSse = null;
    }

    var region = document.getElementById('thread-posts-region');
    if (!region) return;
    var streamUrl = (region.getAttribute('data-live-stream-url') || '').trim();
    if (!streamUrl || typeof EventSource === 'undefined') return;

    var replyForm = document.getElementById('topic-reply-form');
    var latestSeenId = parseInt(region.getAttribute('data-live-last-post-id') || '0', 10) || 0;
    var currentUserId = parseInt(region.getAttribute('data-current-user-id') || '0', 10) || 0;
    var topicId = (replyForm && replyForm.dataset && replyForm.dataset.draftTopicId) ? String(replyForm.dataset.draftTopicId).trim() : '';
    var noticeText = (region.getAttribute('data-live-reply-notice-text') || '').trim() || 'Yeni yorum geldi.';
    var showText = (region.getAttribute('data-live-reply-show-text') || '').trim() || 'Goster';
    var pendingPostIds = [];
    var activeToast = null;
    var reconnectTimer = null;
    var esc = function (s) {
      if (!s) return '';
      var div = document.createElement('div');
      div.textContent = String(s);
      return div.innerHTML;
    };

    function saveReplyDraftNow() {
      if (!replyForm) return;
      try {
        if (typeof replyForm._mfbbDraftSaveNow === 'function') {
          replyForm._mfbbDraftSaveNow();
          return;
        }
      } catch (e) { }

      var topicId = (replyForm.dataset && replyForm.dataset.draftTopicId) ? String(replyForm.dataset.draftTopicId).trim() : '';
      if (!topicId) return;
      var ta = document.getElementById('reply-body');
      if (!ta) return;
      var editor = window.MEGAFORBB_EDITORS && (window.MEGAFORBB_EDITORS['reply-body'] || window.MEGAFORBB_EDITORS[ta.id]);
      var body = (ta.value || '');
      try {
        var getContent = editor && (editor.getHtml || editor.getHTML);
        if (typeof getContent === 'function') {
          body = getContent.call(editor) || '';
        }
      } catch (e) { }
      if (!body || !String(body).trim()) return;
      localStorage.setItem('mfbb_draft_reply_' + topicId, JSON.stringify({ body: body }));
    }

    function fetchAndInsertPost(postId) {
      if (!topicId || !postId) return;
      var base = getBaseUrl();
      var url = base + '/api/topic/' + encodeURIComponent(topicId) + '/live-post/' + encodeURIComponent(String(postId));
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.ok || !d.html) return;
          if (document.getElementById('post-' + postId)) return;
          region.insertAdjacentHTML('beforeend', d.html);
          region.setAttribute('data-live-last-post-id', String(postId));
          if (typeof window.MegaforBBInitTopicPostScrubber === 'function') {
            window.MegaforBBInitTopicPostScrubber();
          }
          var el = document.getElementById('post-' + postId);
          if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        })
        .catch(function () { });
    }

    function showLiveToast() {
      if (activeToast || pendingPostIds.length === 0) return;
      activeToast = showToast(noticeText, 'info', {
        duration: 8000,
        actionLabel: showText,
        onAction: function () {
          saveReplyDraftNow();
          var nextPostId = pendingPostIds.shift();
          fetchAndInsertPost(nextPostId);
          activeToast = null;
          if (pendingPostIds.length > 0) showLiveToast();
        }
      });
      setTimeout(function () {
        if (activeToast && !document.body.contains(activeToast)) {
          activeToast = null;
          if (pendingPostIds.length > 0) showLiveToast();
        }
      }, 8500);
    }

    function startSse() {
      var qs = (streamUrl.indexOf('?') === -1 ? '?' : '&') + 'last_id=' + encodeURIComponent(String(latestSeenId));
      var es = new EventSource(streamUrl + qs);
      window._mfbbTopicLiveSse = es;

      es.addEventListener('topic-reply', function (e) {
        try {
          var data = JSON.parse(e.data || '{}');
          var postId = parseInt(data.post_id || '0', 10) || 0;
          var authorId = parseInt(data.user_id || '0', 10) || 0;
          if (!postId || postId <= latestSeenId) return;
          latestSeenId = postId;
          if (currentUserId > 0 && authorId === currentUserId) return;
          if (pendingPostIds.indexOf(postId) === -1) pendingPostIds.push(postId);
          showLiveToast();
        } catch (err) { }
      });

      es.onerror = function () {
        try { es.close(); } catch (e) { }
        if (reconnectTimer) clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(startSse, 3000);
      };
    }

    startSse();
  }

  function initRepModal() {
    var modal = document.getElementById('rep-modal');
    var form = document.getElementById('rep-form');
    var valueInput = document.getElementById('rep-value');
    var commentInput = document.getElementById('rep-comment');
    var cancelBtn = document.getElementById('rep-modal-cancel');
    var backdrop = document.getElementById('rep-modal-backdrop');
    if (!modal || !form) return;
    var url = getBaseUrl();
    document.querySelectorAll('.give-rep-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var username = this.getAttribute('data-username');
        var value = this.getAttribute('data-value');
        if (valueInput) valueInput.value = value;
        if (commentInput) commentInput.value = '';
        form.action = url + '/member/' + encodeURIComponent(username) + '/give-rep';
        modal.classList.remove('hidden');
      });
    });
    function closeModal() { modal.classList.add('hidden'); }
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
  }

  function initCoverUpload() {
    var input = document.getElementById('cover-upload');
    if (!input) return;
    input.addEventListener('change', function () {
      var file = this.files[0];
      if (!file) return;
      var fd = new FormData();
      fd.append('cover', file);
      fd.append('_token', input.getAttribute('data-csrf') || '');
      fetch(input.getAttribute('data-upload-url'), { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.url) {
            var img = document.querySelector('.h-48.md\\:h-56 img') || document.querySelector('.h-48 img');
            if (img) img.src = data.url;
            location.reload();
          }
        });
    });
  }

  function initAvatarUpload() {
    var input = document.getElementById('avatar-upload-input');
    var preview = document.getElementById('profile-avatar-preview');
    var status = document.getElementById('avatar-upload-status');
    if (!input || !preview) return;
    input.addEventListener('change', function () {
      var file = this.files[0];
      if (!file) return;
      if (status) {
        status.classList.remove('hidden');
        status.textContent = getLang('loading', 'Yükleniyor...');
        status.className = 'mt-1 text-gray-600';
      }
      var fd = new FormData();
      fd.append('avatar', file);
      fetch(input.getAttribute('data-url'), { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (status) {
            if (data && data.ok && data.url) {
              preview.src = data.url;
              status.textContent = getLang('avatar_updated', 'Profil fotoğrafı güncellendi.');
              status.className = 'mt-1 text-green-600';
            } else {
              status.textContent = (data && data.error) ? data.error : getLang('upload_failed', 'Yükleme başarısız.');
              status.className = 'mt-1 text-red-600';
            }
          }
        })
        .catch(function () {
          if (status) {
            status.textContent = 'Yükleme başarısız.';
            status.className = 'mt-1 text-red-600';
          }
        });
      this.value = '';
    });
  }

  function initTopicCreateTabs() {
    var container = document.getElementById('topic-create-tabs');
    if (!container) return;
    var tabs = container.querySelectorAll('.mfbb-topic-create-tab');
    var panels = container.querySelectorAll('.mfbb-topic-create-panel');
    function showPanel(tabId) {
      tabs.forEach(function (t) {
        var id = t.getAttribute('data-tab');
        t.setAttribute('data-active', id === tabId ? '1' : '0');
        t.setAttribute('aria-selected', id === tabId ? 'true' : 'false');
      });
      panels.forEach(function (p) {
        var id = p.getAttribute('data-panel');
        p.classList.toggle('hidden', id !== tabId);
      });
    }
    var placeholder = container.querySelector('.mfbb-topic-create-tabs-placeholder');
    function showPlaceholder(show) {
      if (placeholder) placeholder.classList.toggle('hidden', !show);
      panels.forEach(function (p) { p.classList.toggle('hidden', show); });
    }
    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        var tabId = t.getAttribute('data-tab');
        tabs.forEach(function (tb) {
          tb.setAttribute('data-active', tb.getAttribute('data-tab') === tabId ? '1' : '0');
          tb.setAttribute('aria-selected', tb.getAttribute('data-tab') === tabId ? 'true' : 'false');
        });
        panels.forEach(function (p) {
          p.classList.toggle('hidden', p.getAttribute('data-panel') !== tabId);
        });
        if (placeholder) placeholder.classList.add('hidden');
      });
    });
    showPlaceholder(true);
    var scheduleInput = document.getElementById('scheduled_publish_at');
    if (scheduleInput) {
      var now = new Date();
      var y = now.getFullYear();
      var m = String(now.getMonth() + 1).padStart(2, '0');
      var d = String(now.getDate()).padStart(2, '0');
      var h = String(now.getHours()).padStart(2, '0');
      var min = String(now.getMinutes()).padStart(2, '0');
      scheduleInput.setAttribute('min', y + '-' + m + '-' + d + 'T' + h + ':' + min);
    }
  }

  /** Konu ayarlarındaki "Soru – Cevap" checkbox ile topic_type senkronu. */
  function initTopicCreateQuestionSync() {
    var cb = document.getElementById('topic-create-is-question');
    var typeInput = document.getElementById('topic_type_input');
    var typeSelect = document.getElementById('topic_type_select');
    if (!cb) return;
    if (typeInput) {
      cb.addEventListener('change', function () {
        typeInput.value = this.checked ? 'question' : 'topic';
      });
      if (cb.checked) typeInput.value = 'question';
    }
    if (typeSelect) {
      cb.addEventListener('change', function () {
        if (this.checked) typeSelect.value = 'question';
        else if (typeSelect.value === 'question') typeSelect.value = 'topic';
      });
      typeSelect.addEventListener('change', function () {
        cb.checked = (this.value === 'question');
      });
      if (cb.checked) typeSelect.value = 'question';
    }
  }

  function initPollAddOption() {
    var btn = document.getElementById('poll-add-option');
    var container = document.getElementById('poll-options-container');
    if (!btn || !container) return;
    btn.addEventListener('click', function () {
      var count = container.querySelectorAll('input').length + 1;
      var prefix = container.getAttribute('data-option-placeholder') || getLang('option', 'Seçenek');
      var inp = document.createElement('input');
      inp.type = 'text';
      inp.name = 'poll_options[]';
      inp.className = 'w-full border-gray-300 rounded-md shadow-sm focus:border-[#1a252f] focus:ring-[#1a252f] sm:text-sm p-2 border';
      inp.placeholder = prefix + ' ' + count;
      container.appendChild(inp);
    });
  }

  var MAX_TOPIC_TAGS = 5;

  function initTopicCreateTagSelector() {
    var wrap = document.getElementById('topic-create-tabs') || document.getElementById('panel-settings');
    var input = document.getElementById('topic-create-tag-input');
    var suggestionsEl = document.getElementById('topic-create-tag-suggestions');
    var chipsEl = document.getElementById('topic-create-tag-chips');
    var idsContainer = document.getElementById('topic-create-tag-ids');
    var addBtn = document.getElementById('topic-create-tag-add-btn');
    if (!input || !suggestionsEl || !chipsEl || !idsContainer) return;

    var selectedIds = [];
    var selectedNamesNormalized = [];
    var debounceTimer = null;

    function applyInitialTags() {
      var raw = wrap ? wrap.getAttribute('data-initial-tags') : null;
      if (!raw) return;
      try {
        var list = JSON.parse(raw);
        if (Array.isArray(list)) {
          list.forEach(function (t) {
            if (t && t.id && selectedIds.indexOf(t.id) === -1 && selectedIds.length < MAX_TOPIC_TAGS) {
              addTag(parseInt(t.id, 10), String(t.name || '').trim());
            }
          });
        }
      } catch (e) { }
    }

    function normalizeName(name) {
      return (name || '').trim().toLowerCase();
    }

    function addTag(id, name) {
      if (selectedIds.indexOf(id) !== -1) return;
      if (selectedIds.length >= MAX_TOPIC_TAGS) return;
      var n = normalizeName(name);
      if (selectedNamesNormalized.indexOf(n) !== -1) return;
      selectedIds.push(id);
      selectedNamesNormalized.push(n);
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'tag_ids[]';
      hidden.value = String(id);
      hidden.id = 'topic-tag-id-' + id;
      idsContainer.appendChild(hidden);
      var chip = document.createElement('span');
      chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded text-sm bg-[#1a252f]/10 text-[#1a252f]';
      chip.innerHTML = '<span>' + (name.replace(/</g, '&lt;')) + '</span><button type="button" class="text-slate-400 hover:text-red-600" data-tag-id="' + id + '" aria-label="' + getLang('remove', 'Kaldır') + '"><i class="fa-solid fa-times text-xs"></i></button>';
      chipsEl.appendChild(chip);
      chip.querySelector('button').addEventListener('click', function () {
        selectedIds = selectedIds.filter(function (x) { return x !== id; });
        selectedNamesNormalized = selectedNamesNormalized.filter(function (x) { return x !== n; });
        var h = document.getElementById('topic-tag-id-' + id);
        if (h) h.remove();
        chip.remove();
        suggestionsEl.classList.add('hidden');
        if (selectedIds.length < MAX_TOPIC_TAGS) {
          input.disabled = false;
          input.placeholder = getLang('tag_placeholder', 'Etiket yazın veya seçin...');
        }
      });
      suggestionsEl.classList.add('hidden');
      input.value = '';
      input.focus();
      if (selectedIds.length >= MAX_TOPIC_TAGS) {
        input.disabled = true;
        input.placeholder = 'En fazla ' + MAX_TOPIC_TAGS + ' etiket seçebilirsiniz.';
      }
    }

    function tryAddTagByName(name, done) {
      name = (name || '').trim();
      if (!name) {
        if (done) done();
        return;
      }
      if (selectedIds.length >= MAX_TOPIC_TAGS) {
        if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast('En fazla ' + MAX_TOPIC_TAGS + ' etiket ekleyebilirsiniz.', 'error');
        else alert('En fazla ' + MAX_TOPIC_TAGS + ' etiket ekleyebilirsiniz.');
        if (done) done();
        return;
      }
      if (selectedNamesNormalized.indexOf(normalizeName(name)) !== -1) {
        input.value = '';
        suggestionsEl.classList.add('hidden');
        if (done) done();
        return;
      }
      var url = getBaseUrl() + '/api/tags/create';
      var fd = new FormData();
      fd.append('name', name);
      var tagCsrf = document.querySelector('input[name="api_tag_csrf"]');
      fd.append('api_tag_csrf', tagCsrf && tagCsrf.value ? tagCsrf.value : '');
      fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j }; }); })
        .then(function (res) {
          if (res.ok && res.json.id) {
            addTag(res.json.id, res.json.name);
          } else {
            var msg = (res.json && res.json.error) ? res.json.error : 'Etiket eklenemedi.';
            if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(msg, 'error');
            else alert(msg);
          }
          if (done) done();
        })
        .catch(function () {
          if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(getLang('connection_error', 'Bağlantı hatası.'), 'error');
          else alert(getLang('connection_error', 'Bağlantı hatası.'));
          if (done) done();
        });
    }

    function commitInputAsTag() {
      var raw = input.value.trim();
      if (!raw) return;
      suggestionsEl.classList.add('hidden');
      var parts = raw.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
      input.value = '';
      if (parts.length === 0) return;
      function addNext(i) {
        if (i >= parts.length) return;
        tryAddTagByName(parts[i], function () { addNext(i + 1); });
      }
      addNext(0);
    }

    function fetchSuggestions(q) {
      if (!q || q.length < 1) {
        suggestionsEl.innerHTML = '';
        suggestionsEl.classList.add('hidden');
        return;
      }
      var url = getBaseUrl() + '/api/tags/suggest?q=' + encodeURIComponent(q);
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var tags = data.tags || [];
          suggestionsEl.innerHTML = '';
          tags.forEach(function (t) {
            if (selectedIds.indexOf(t.id) !== -1) return;
            var li = document.createElement('button');
            li.type = 'button';
            li.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 flex items-center gap-2';
            li.textContent = t.name;
            li.addEventListener('click', function () { addTag(t.id, t.name); });
            suggestionsEl.appendChild(li);
          });
          var hint = document.createElement('div');
          hint.className = 'px-3 py-2 text-xs text-gray-500 border-t border-gray-100';
          hint.textContent = 'Listede yoksa Enter veya "Ekle" ile yeni etiket ekleyin.';
          suggestionsEl.appendChild(hint);
          suggestionsEl.classList.remove('hidden');
        })
        .catch(function () {
          suggestionsEl.classList.add('hidden');
        });
    }

    input.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      var q = this.value.trim();
      debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 250);
    });
    input.addEventListener('focus', function () {
      var q = this.value.trim();
      if (q) fetchSuggestions(q);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        suggestionsEl.classList.add('hidden');
        return;
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        commitInputAsTag();
      }
    });

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        commitInputAsTag();
      });
    }

    document.addEventListener('click', function (e) {
      if (!suggestionsEl.contains(e.target) && !input.contains(e.target) && e.target !== addBtn && !addBtn.contains(e.target)) suggestionsEl.classList.add('hidden');
    });

    applyInitialTags();
  }

  var MAX_PRIVATE_VIEWERS = 20;

  function initTopicCreatePrivateViewers() {
    var wrap = document.getElementById('topic-create-tabs') || document.getElementById('panel-settings');
    var input = document.getElementById('topic-create-private-viewer-input');
    var suggestionsEl = document.getElementById('topic-create-private-viewer-suggestions');
    var chipsEl = document.getElementById('topic-create-private-viewer-chips');
    var idsContainer = document.getElementById('topic-create-private-viewer-ids');
    var viewersWrap = document.getElementById('topic-create-private-viewers-wrap');
    var chipsRow = document.getElementById('topic-create-private-viewers-chips-row');
    var isPrivateCheckbox = document.getElementById('topic-create-is-private');
    if (!input || !suggestionsEl || !chipsEl || !idsContainer) return;

    var selectedIds = [];
    var debounceTimer = null;

    function toggleViewersVisibility() {
      var show = isPrivateCheckbox && isPrivateCheckbox.checked;
      if (viewersWrap) viewersWrap.style.display = show ? 'block' : 'none';
      if (chipsRow) chipsRow.style.display = show ? '' : 'none';
    }
    if (isPrivateCheckbox) {
      isPrivateCheckbox.addEventListener('change', toggleViewersVisibility);
      toggleViewersVisibility();
    }

    function addViewer(id, username) {
      id = parseInt(id, 10);
      if (selectedIds.indexOf(id) !== -1) return;
      if (selectedIds.length >= MAX_PRIVATE_VIEWERS) return;
      selectedIds.push(id);
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'private_viewer_user_ids[]';
      hidden.value = String(id);
      hidden.id = 'topic-private-viewer-id-' + id;
      idsContainer.appendChild(hidden);
      var chip = document.createElement('span');
      chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded text-sm bg-[#1a252f]/10 text-[#1a252f]';
      chip.innerHTML = '<span>' + (String(username || '').replace(/</g, '&lt;')) + '</span><button type="button" class="text-slate-400 hover:text-red-600" data-viewer-id="' + id + '" aria-label="' + getLang('remove', 'Kaldır') + '"><i class="fa-solid fa-times text-xs"></i></button>';
      chipsEl.appendChild(chip);
      chip.querySelector('button').addEventListener('click', function () {
        selectedIds = selectedIds.filter(function (x) { return x !== id; });
        var h = document.getElementById('topic-private-viewer-id-' + id);
        if (h) h.remove();
        chip.remove();
        suggestionsEl.classList.add('hidden');
      });
      suggestionsEl.classList.add('hidden');
      input.value = '';
    }

    function applyInitialViewers() {
      if (!wrap) return;
      var raw = wrap.getAttribute('data-initial-private-viewers');
      if (!raw) return;
      try {
        var list = JSON.parse(raw);
        if (Array.isArray(list)) {
          list.forEach(function (u) {
            if (u && u.id && selectedIds.indexOf(parseInt(u.id, 10)) === -1 && selectedIds.length < MAX_PRIVATE_VIEWERS) {
              addViewer(parseInt(u.id, 10), u.username || '');
            }
          });
        }
      } catch (e) { }
    }

    function fetchUserSuggestions(q) {
      if (!q || q.length < 2) {
        suggestionsEl.innerHTML = '';
        suggestionsEl.classList.add('hidden');
        return;
      }
      var url = getBaseUrl() + '/api/users/search?q=' + encodeURIComponent(q);
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var users = data.users || [];
          suggestionsEl.innerHTML = '';
          users.forEach(function (u) {
            if (selectedIds.indexOf(u.id) !== -1) return;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-3 py-2.5 text-sm hover:bg-gray-100 flex items-center gap-2 border-b border-gray-100 last:border-b-0';
            btn.textContent = u.username;
            btn.addEventListener('click', function () { addViewer(u.id, u.username); });
            suggestionsEl.appendChild(btn);
          });
          suggestionsEl.classList.remove('hidden');
        })
        .catch(function () { suggestionsEl.classList.add('hidden'); });
    }

    input.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      var q = this.value.trim();
      debounceTimer = setTimeout(function () { fetchUserSuggestions(q); }, 250);
    });
    input.addEventListener('focus', function () {
      var q = this.value.trim();
      if (q) fetchUserSuggestions(q);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') suggestionsEl.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
      if (!suggestionsEl.contains(e.target) && !input.contains(e.target)) suggestionsEl.classList.add('hidden');
    });

    applyInitialViewers();
  }

  function initTopicCreateAttachments() {
    var form = document.getElementById('new-topic-form');
    var input = document.getElementById('topic-create-attachment-input');
    var btn = document.getElementById('topic-create-attachment-btn');
    var list = document.getElementById('topic-create-attachment-list');
    var idsContainer = document.getElementById('topic-create-attachment-ids');
    if (!form || !input || !btn || !list || !idsContainer) return;
    var url = getBaseUrl() + '/upload/attachment';
    btn.addEventListener('click', function () { input.click(); });
    input.addEventListener('change', function () {
      var files = this.files;
      if (!files || files.length === 0) return;
      for (var i = 0; i < files.length; i++) {
        (function (file) {
          var fd = new FormData();
          fd.append('attachment', file);
          var tokenEl = form.querySelector('input[name="upload_attachment_token"]') || form.querySelector('input[name="_token"]');
          if (tokenEl && tokenEl.value) fd.append('_token', tokenEl.value);
          fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.error) {
                if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(res.error, 'error');
                else alert(res.error);
                return;
              }
              var id = res.id;
              var name = res.original_name || file.name;
              var hidden = document.createElement('input');
              hidden.type = 'hidden';
              hidden.name = 'attachment_ids[]';
              hidden.value = String(id);
              hidden.id = 'attachment-id-' + id;
              idsContainer.appendChild(hidden);
              var chip = document.createElement('span');
              chip.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 border border-gray-200 rounded-md text-sm';
              chip.innerHTML = '<i class="fa-solid fa-file text-slate-500 text-xs"></i><span class="truncate max-w-[140px]">' + (name.replace(/</g, '&lt;')) + '</span><button type="button" class="text-slate-400 hover:text-red-600 ml-0.5" data-attachment-id="' + id + '" aria-label="Kaldır"><i class="fa-solid fa-times text-xs"></i></button>';
              list.appendChild(chip);
              chip.querySelector('button').addEventListener('click', function () {
                var hid = document.getElementById('attachment-id-' + id);
                if (hid) hid.remove();
                chip.remove();
              });
            })
            .catch(function () {
              if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(getLang('upload_failed', 'Yükleme başarısız.'), 'error');
              else alert(getLang('upload_failed', 'Yükleme başarısız.'));
            });
        })(files[i]);
      }
      this.value = '';
    });
  }

  function initReplyAttachments() {
    var form = document.getElementById('topic-reply-form');
    var input = document.getElementById('reply-attachment-input');
    var btn = document.getElementById('reply-attachment-btn');
    var list = document.getElementById('reply-attachment-list');
    var idsContainer = document.getElementById('reply-attachment-ids');
    if (!form || !input || !btn || !list || !idsContainer) return;
    var url = getBaseUrl() + '/upload/attachment';
    btn.addEventListener('click', function () { input.click(); });
    input.addEventListener('change', function () {
      var files = this.files;
      if (!files || files.length === 0) return;
      for (var i = 0; i < files.length; i++) {
        (function (file) {
          var fd = new FormData();
          fd.append('attachment', file);
          var tokenEl = form.querySelector('input[name="upload_attachment_token"]') || form.querySelector('input[name="_token"]');
          if (tokenEl && tokenEl.value) fd.append('_token', tokenEl.value);
          fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (res.error) {
                if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(res.error, 'error');
                else alert(res.error);
                return;
              }
              var id = res.id;
              var name = res.original_name || file.name;
              var hidden = document.createElement('input');
              hidden.type = 'hidden';
              hidden.name = 'attachment_ids[]';
              hidden.value = String(id);
              hidden.id = 'reply-attachment-id-' + id;
              idsContainer.appendChild(hidden);
              var chip = document.createElement('span');
              chip.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 border border-gray-200 rounded-md text-sm';
              chip.innerHTML = '<i class="fa-solid fa-file text-slate-500 text-xs"></i><span class="truncate max-w-[140px]">' + (name.replace(/</g, '&lt;')) + '</span><button type="button" class="text-slate-400 hover:text-red-600 ml-0.5" data-attachment-id="' + id + '" aria-label="Kaldır"><i class="fa-solid fa-times text-xs"></i></button>';
              list.appendChild(chip);
              chip.querySelector('button').addEventListener('click', function () {
                var hid = document.getElementById('reply-attachment-id-' + id);
                if (hid) hid.remove();
                chip.remove();
              });
            })
            .catch(function () {
              if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(getLang('upload_failed', 'Yükleme başarısız.'), 'error');
              else alert(getLang('upload_failed', 'Yükleme başarısız.'));
            });
        })(files[i]);
      }
      this.value = '';
    });
  }

  function initAttachmentDelete() {
    document.querySelectorAll('.attachment-delete-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!confirm(getLang('delete_attachment_confirm', 'Bu eki silmek istediğinize emin misiniz?'))) return;
        var fd = new FormData(form);
        fetch(form.action, { method: 'POST', body: fd })
          .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
          .then(function (res) {
            if (res.ok && res.json.success) {
              form.closest('.inline-flex').remove();
            } else {
              alert(res.json.error || 'Silinemedi.');
            }
          })
          .catch(function () { alert(getLang('error_occurred', 'Bir hata oluştu.')); });
      });
    });
  }

  function initPortal() {
    var section = document.querySelector('.portal-tab-panels');
    if (!section) return;
    var cfg = section.closest('.son-olaylar-card') || section.closest('[data-api-url]') || section.closest('section');
    var apiUrl = (cfg && cfg.getAttribute('data-api-url')) || '';
    var tabLimit = parseInt((cfg && cfg.getAttribute('data-tab-limit')) || 10, 10);
    var tabMax = parseInt((cfg && cfg.getAttribute('data-tab-max')) || 20, 10);
    var refreshInterval = parseInt((cfg && cfg.getAttribute('data-refresh-interval')) || 30, 10) * 1000;
    var url = getBaseUrl();
    var loadMoreSize = Math.min(10, tabMax - tabLimit);

    var sonOlaylarSettings = {};
    if (cfg && cfg.getAttribute('data-son-olaylar-settings')) {
      try { sonOlaylarSettings = JSON.parse(cfg.getAttribute('data-son-olaylar-settings') || '{}'); } catch (e) { }
    }
    var showTopicIcon = sonOlaylarSettings.show_topic_icon !== false;
    var topicTitleLimit = sonOlaylarSettings.topic_title_limit || 80;
    var commentSnippetLimit = sonOlaylarSettings.comment_snippet_limit || 80;
    var cols = sonOlaylarSettings.column_order || ['title', 'replies_views', 'last_reply', 'category'];
    if (!Array.isArray(cols) || cols.length === 0) cols = ['title'];

    function esc(s) { return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function truncate(str, len) { str = str || ''; return str.length > len ? str.substring(0, len) + '…' : str; }
    function timeAgo(dateStr) {
      if (!dateStr) return '';
      var diff = Math.floor((Date.now() - new Date(dateStr.replace(' ', 'T')).getTime()) / 1000);
      if (diff < 0) return getLang('time_ago', 'az önce');
      if (diff < 60) return getLang('time_ago', 'az önce');
      if (diff < 3600) return getLang('time_minutes_ago', ':count dk önce').replace(':count', String(Math.floor(diff / 60)));
      if (diff < 86400) return getLang('time_hours_ago', ':count saat önce').replace(':count', String(Math.floor(diff / 3600)));
      if (diff < 604800) return getLang('time_days_ago', ':count gün önce').replace(':count', String(Math.floor(diff / 86400)));
      var d = new Date(dateStr.replace(' ', 'T'));
      var months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
      return d.getDate() + ' ' + months[d.getMonth()];
    }

    function getActiveTab() {
      var btn = document.querySelector('.portal-tab-btn[data-active="true"]');
      return btn ? btn.getAttribute('data-tab') : 'newest_topics';
    }

    /* --- Tab switching --- */
    var activeClasses = ['bg-white', 'dark:bg-slate-900', 'text-primary', 'border-slate-300', 'dark:border-slate-700', 'border-b-white', 'dark:border-b-slate-900', 'active-tab-style'];
    var inactiveClasses = ['bg-slate-100', 'dark:bg-slate-800', 'text-slate-500', 'dark:text-slate-400', 'border-transparent', 'hover:bg-slate-200', 'dark:hover:bg-slate-700', 'inactive-tab-style'];

    document.querySelectorAll('.portal-tab-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tab = this.getAttribute('data-tab');
        document.querySelectorAll('.portal-tab-btn').forEach(function (b) {
          var isActive = b.getAttribute('data-tab') === tab;
          b.setAttribute('data-active', isActive ? 'true' : 'false');
          if (isActive) {
            b.classList.remove(...inactiveClasses);
            b.classList.add(...activeClasses);
            b.style.marginBottom = '-1px';
          } else {
            b.classList.remove(...activeClasses);
            b.classList.add(...inactiveClasses);
            b.style.marginBottom = '0';
          }
        });
        document.querySelectorAll('.portal-tab-panel').forEach(function (p) {
          p.classList.toggle('hidden', p.getAttribute('data-tab') !== tab);
        });
        var lmBtn = document.querySelector('.portal-load-more-btn');
        if (lmBtn) lmBtn.setAttribute('data-tab', tab);
      });
    });

    /* ui-avatars için baş harfler (kullanıcı adının ilk 1–2 karakteri) */
    function avatarInitialsForUrl(name) {
      var s = (name || 'User').trim();
      if (!s) return 'User';
      if (s.length === 1) return s.toUpperCase();
      return (s.charAt(0) + ' ' + s.charAt(1)).toUpperCase();
    }

    /* --- Build row HTML for topic tabs --- */
    function buildTopicRow(item, tab) {
      var tds = '';
      cols.forEach(function (col) {
        if (col === 'title') {
          var title = truncate(item.title, topicTitleLimit);
          var avatarHtml = showTopicIcon ? '<img src="' + (item.author_avatar_url || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(avatarInitialsForUrl(item.username)) + '&size=32')) + '" alt="" width="28" height="28" class="rounded-full object-cover flex-shrink-0 border border-gray-200" style="width:28px;height:28px;min-width:28px">' : '';
          var timeStr = tab === 'newest_topics' ? timeAgo(item.created_at) : '';
          var metaHtml = '<span class="text-xs text-gray-400">' + esc(item.username || '') + (timeStr ? ' · ' + timeStr : '') + '</span>';
          tds += '<td class="px-3 md:px-4 py-2.5"><a href="' + url + '/topic/' + item.id + '" class="flex items-center gap-2 group">' + avatarHtml + '<div class="min-w-0"><span class="font-medium text-[#1a252f] group-hover:text-primary truncate text-sm block">' + esc(title) + '</span>' + metaHtml + '</div></a></td>';
        } else if (col === 'replies_views') {
          tds += '<td class="hidden sm:table-cell px-3 md:px-4 py-2.5 text-gray-600 text-sm">' + (item.reply_count || 0) + ' <span class="inline-block w-1 h-1 rounded-full bg-blue-400 mx-1"></span> ' + (item.view_count || 0) + '</td>';
        } else if (col === 'last_reply') {
          var lrHtml = '—';
          if (item.last_post_username) {
            lrHtml = '<span>' + esc(item.last_post_username) + '</span>';
            if (item.last_post_at) lrHtml += '<br><span class="text-xs text-gray-400">' + timeAgo(item.last_post_at) + '</span>';
          }
          tds += '<td class="hidden md:table-cell px-3 md:px-4 py-2.5 text-gray-600 text-sm">' + lrHtml + '</td>';
        } else if (col === 'category') {
          tds += '<td class="hidden lg:table-cell px-3 md:px-4 py-2.5 text-gray-500 text-xs">' + esc(item.forum_name || '—') + '</td>';
        }
      });
      return '<tr class="hover:bg-gray-50/80 transition-colors">' + tds + '</tr>';
    }

    /* --- Build comment row HTML --- */
    function buildCommentRow(item) {
      var topicPath = (item.topic_type === 'article') ? '/article/' + item.topic_id : '/topic/' + item.topic_id;
      var anchor = item.post_num ? '#post-' + item.post_num : '';
      var snip = String(item.body_html || item.body || '').replace(/<[^>]+>/g, '').substring(0, commentSnippetLimit);
      if (snip.length >= commentSnippetLimit) snip += '…';
      var tt = truncate(item.topic_title, topicTitleLimit);
      var cAv = item.avatar_url || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(avatarInitialsForUrl(item.username)) + '&size=24');
      var isArticle = item.topic_type === 'article';
      var tAgo = timeAgo(item.created_at);
      var topicIcon = isArticle ? 'fa-newspaper' : 'fa-message';
      return '<a href="' + url + topicPath + anchor + '" class="flex items-center gap-2 px-3 md:px-5 py-2.5 hover:bg-gray-50/60 transition-colors group">'
        + '<i class="fa-regular ' + topicIcon + ' text-xs text-gray-400 flex-shrink-0 hidden sm:inline"></i>'
        + '<img src="' + cAv + '" alt="" title="' + esc(item.username) + '" width="22" height="22" class="rounded-full object-cover flex-shrink-0" style="width:22px;height:22px">'
        + '<span class="text-sm font-medium text-gray-800 group-hover:text-primary transition-colors truncate" style="max-width:40%">' + esc(tt) + '</span>'
        + '<span class="text-gray-300 flex-shrink-0 hidden sm:inline">›</span>'
        + '<span class="text-sm text-gray-500 truncate min-w-0 hidden sm:inline">' + esc(snip) + '</span>'
        + (tAgo ? '<span class="text-xs text-gray-400 flex-shrink-0 ml-auto whitespace-nowrap">' + esc(tAgo) + '</span>' : '')
        + '</a>';
    }

    /* --- Build popular user row HTML --- */
    function buildUserRow(item) {
      var av = item.avatar_url || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(avatarInitialsForUrl(item.username)) + '&size=44');
      var repPos = parseInt(item.reputation_positive || 0, 10);
      var repNeg = parseInt(item.reputation_negative || 0, 10);
      var repHtml = '';
      if (repPos > 0 || repNeg > 0) {
        repHtml = '<span class="inline-flex items-center gap-1.5">';
        if (repPos > 0) repHtml += '<span class="text-green-600"><i class="fa-solid fa-thumbs-up text-[10px]"></i> ' + repPos + '</span>';
        if (repNeg > 0) repHtml += '<span class="text-red-500"><i class="fa-solid fa-thumbs-down text-[10px]"></i> ' + repNeg + '</span>';
        repHtml += '</span>';
      }
      var locHtml = item.location ? '<div class="text-xs text-gray-400 truncate"><i class="fa-solid fa-location-dot text-[10px] mr-1"></i>' + esc(item.location) + '</div>' : '';
      var joinHtml = item.created_at ? '<span class="text-xs text-gray-400 flex-shrink-0">' + esc(String(item.created_at).substring(0, 10)) + '</span>' : '';
      return '<a href="' + url + '/member/' + encodeURIComponent(item.username || '') + '" class="flex gap-4 px-5 py-4 hover:bg-gray-50/60 transition-colors group">'
        + '<img src="' + av + '" alt="" width="44" height="44" class="rounded-full object-cover flex-shrink-0 border-2 border-gray-200" style="width:44px;height:44px">'
        + '<div class="flex-1 min-w-0">'
        + '<div class="flex items-center justify-between gap-2 mb-1.5"><span class="font-semibold text-sm text-gray-900 group-hover:text-primary transition-colors">' + esc(item.username) + '</span>' + joinHtml + '</div>'
        + '<div class="flex items-center gap-4 text-xs text-gray-500 mb-1.5"><span><strong class="text-gray-700">' + (item.post_count || 0) + '</strong> mesaj</span><span><strong class="text-gray-700">' + (item.topic_count || 0) + '</strong> konu</span>' + repHtml + '</div>'
        + locHtml
        + '</div></a>';
    }

    /* --- Render items into a panel body --- */
    function renderItems(body, items, tab, append) {
      if (!append) body.innerHTML = '';
      var isTableBody = body.tagName === 'TBODY';
      if (items.length === 0 && !append) {
        if (isTableBody) {
          body.innerHTML = '<tr><td colspan="' + cols.length + '" class="px-4 py-8 text-center text-gray-500">' + (tab === 'latest_comments' ? getLang('no_comments', 'Henüz yorum yok.') : (tab === 'popular_users' ? getLang('no_data', 'Henüz veri yok.') : getLang('no_topics', 'Henüz konu yok.'))) + '</td></tr>';
        } else {
          body.innerHTML = '<div class="p-8 text-center text-gray-500 text-sm">' + (tab === 'latest_comments' ? getLang('no_comments', 'Henüz yorum yok.') : (tab === 'popular_users' ? getLang('no_data', 'Henüz veri yok.') : getLang('no_topics', 'Henüz konu yok.'))) + '</div>';
        }
        return;
      }
      items.forEach(function (item) {
        if (isTableBody && (tab === 'newest_topics' || tab === 'most_viewed' || tab === 'most_replied')) {
          var tr = document.createElement('tr');
          tr.className = 'hover:bg-gray-50/80 transition-colors';
          tr.innerHTML = buildTopicRow(item, tab).replace(/^<tr[^>]*>/, '').replace(/<\/tr>$/, '');
          body.appendChild(tr);
        } else if (tab === 'latest_comments') {
          var tmp = document.createElement('div');
          tmp.innerHTML = buildCommentRow(item);
          if (tmp.firstChild) body.appendChild(tmp.firstChild);
        } else if (tab === 'popular_users') {
          var tmp2 = document.createElement('div');
          tmp2.innerHTML = buildUserRow(item);
          if (tmp2.firstChild) body.appendChild(tmp2.firstChild);
        }
      });
    }

    /* --- AJAX: Fetch tab data from API --- */
    function fetchTabData(tab, offset, limit, callback) {
      if (!apiUrl) return;
      fetch(apiUrl + '?tab=' + encodeURIComponent(tab) + '&offset=' + offset + '&limit=' + limit)
        .then(function (r) { return r.json(); })
        .then(function (data) { callback(data.items || [], data.has_more); })
        .catch(function () { callback([], false); });
    }

    /* --- Auto-refresh: reload active tab from offset=0 --- */
    function refreshActiveTab() {
      if (document.hidden) return;
      var tab = getActiveTab();
      var panel = document.querySelector('.portal-tab-panel[data-tab="' + tab + '"]:not(.hidden)');
      if (!panel) panel = document.querySelector('.portal-tab-panel[data-tab="' + tab + '"]');
      if (!panel) return;
      var body = panel.querySelector('tbody') || panel;

      fetchTabData(tab, 0, tabLimit, function (items, hasMore) {
        renderItems(body, items, tab, false);
        panel.setAttribute('data-offset', String(tabLimit));
        var lmBtn = document.querySelector('.portal-load-more-btn');
        if (lmBtn) lmBtn.style.display = hasMore ? '' : 'none';
      });
    }

    /* --- Start auto-refresh interval --- */
    if (apiUrl && refreshInterval > 0) {
      setInterval(refreshActiveTab, refreshInterval);
    }

    /* --- Load More button --- */
    var loadBtn = document.querySelector('.portal-load-more-btn');
    if (loadBtn && apiUrl) {
      loadBtn.addEventListener('click', function () {
        var tab = getActiveTab();
        var panel = document.querySelector('.portal-tab-panel[data-tab="' + tab + '"]:not(.hidden)');
        if (!panel) panel = document.querySelector('.portal-tab-panel[data-tab="' + tab + '"]');
        var offset = panel ? parseInt(panel.getAttribute('data-offset'), 10) : tabLimit;
        var limit = parseInt(this.getAttribute('data-limit'), 10) || loadMoreSize;
        /* tbody varsa onu kullan, yoksa panel'in kendisi (popular_users gibi) */
        var body = panel ? (panel.querySelector('tbody') || panel) : null;
        if (!body) return;
        loadBtn.disabled = true;
        loadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + getLang('loading', 'Yükleniyor...');

        fetchTabData(tab, offset, limit, function (items, hasMore) {
          renderItems(body, items, tab, true);
          loadBtn.disabled = false;
          loadBtn.innerHTML = '<i class="fa-solid fa-chevron-down text-[10px]"></i> Daha fazla';
          if (panel) panel.setAttribute('data-offset', String(offset + items.length));
          if (!hasMore) loadBtn.style.display = 'none';
        });
      });
    }
  }

  function getToastContainer() {
    var container = document.getElementById('toast-container');
    if (container) return container;
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'mfbb-toast-container';
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
    return container;
  }

  function removeToast(el) {
    if (!el) return;
    el.classList.remove('mfbb-toast--visible');
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 220);
  }

  function showToast(message, type, options) {
    type = type || 'success';
    options = options || {};
    var text = (message == null ? '' : String(message)).trim();
    if (!text) return null;

    var container = getToastContainer();
    var el = document.createElement('article');
    el.className = 'mfbb-toast mfbb-toast--' + type;
    el.setAttribute('role', 'status');

    var icon = document.createElement('span');
    icon.className = 'mfbb-toast__icon';

    var content = document.createElement(options.href ? 'a' : 'div');
    content.className = options.href ? 'mfbb-toast__content mfbb-toast__link' : 'mfbb-toast__content';
    if (options.href) {
      content.href = options.href;
      content.setAttribute('data-soft-nav', '1');
    }
    content.textContent = text;

    var actionBtn = null;
    if (!options.href && options.actionLabel) {
      actionBtn = document.createElement('button');
      actionBtn.type = 'button';
      actionBtn.className = 'mfbb-toast__action';
      actionBtn.textContent = String(options.actionLabel);
      actionBtn.style.marginLeft = '8px';
      actionBtn.style.padding = '4px 10px';
      actionBtn.style.borderRadius = '6px';
      actionBtn.style.border = '1px solid rgba(59,130,246,.35)';
      actionBtn.style.background = 'rgba(59,130,246,.12)';
      actionBtn.style.color = 'inherit';
      actionBtn.style.fontWeight = '600';
    }

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'mfbb-toast__close';
    closeBtn.setAttribute('aria-label', 'Kapat');
    closeBtn.innerHTML = '&times;';

    el.appendChild(icon);
    el.appendChild(content);
    if (actionBtn) el.appendChild(actionBtn);
    el.appendChild(closeBtn);
    container.appendChild(el);

    requestAnimationFrame(function () {
      el.classList.add('mfbb-toast--visible');
    });

    var startedAt = Date.now();
    var duration = Math.max(1500, parseInt(options.duration || 3600, 10));
    var remaining = duration;
    var timer = null;

    function scheduleRemove() {
      timer = setTimeout(function () {
        removeToast(el);
      }, remaining);
    }

    function pauseTimer() {
      if (!timer) return;
      clearTimeout(timer);
      timer = null;
      remaining = Math.max(0, remaining - (Date.now() - startedAt));
    }

    function resumeTimer() {
      if (timer) return;
      startedAt = Date.now();
      scheduleRemove();
    }

    closeBtn.addEventListener('click', function (ev) {
      ev.preventDefault();
      pauseTimer();
      removeToast(el);
    });

    if (actionBtn) {
      actionBtn.addEventListener('click', function (ev) {
        ev.preventDefault();
        pauseTimer();
        if (typeof options.onAction === 'function') {
          try { options.onAction(el); } catch (err) { }
        }
        removeToast(el);
      });
    }

    el.addEventListener('mouseenter', pauseTimer);
    el.addEventListener('mouseleave', resumeTimer);
    scheduleRemove();

    return el;
  }

  function loadSeenNotificationMap() {
    var key = 'mfbb_seen_notifications_v1';
    try {
      var raw = window.sessionStorage.getItem(key);
      if (!raw) return {};
      var parsed = JSON.parse(raw);
      if (!parsed || typeof parsed !== 'object') return {};
      var now = Date.now();
      var maxAge = 1000 * 60 * 60 * 24;
      Object.keys(parsed).forEach(function (id) {
        if (typeof parsed[id] !== 'number' || now - parsed[id] > maxAge) {
          delete parsed[id];
        }
      });
      return parsed;
    } catch (e) {
      return {};
    }
  }

  function saveSeenNotificationMap(map) {
    try {
      window.sessionStorage.setItem('mfbb_seen_notifications_v1', JSON.stringify(map || {}));
    } catch (e) { }
  }

  var seenNotificationMap = loadSeenNotificationMap();

  function wasNotificationSeen(notificationId) {
    if (!notificationId) return false;
    return !!seenNotificationMap[String(notificationId)];
  }

  function rememberNotification(notificationId) {
    if (!notificationId) return;
    seenNotificationMap[String(notificationId)] = Date.now();
    saveSeenNotificationMap(seenNotificationMap);
  }

  function pushNotificationToast(notification) {
    if (!notification || !notification.id) return false;
    if (wasNotificationSeen(notification.id)) return false;

    rememberNotification(notification.id);
    var label = notification.label || 'Yeni bir bildiriminiz var.';
    var sender = notification.from_username ? String(notification.from_username) + ' - ' : '';
    var url = notification.read_url || (getBaseUrl() + '/notifications');
    showToast(sender + label, 'info', {
      href: url,
      duration: 7000
    });
    return true;
  }

  window.MegaforBBToast = showToast;
  window.mfbbToast = showToast;
  window.MegaforBBPushNotification = pushNotificationToast;

  function initPostLikeAjax() {
    document.querySelectorAll('.post-like-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var postId = form.getAttribute('data-post-id');
        var btn = form.querySelector('.post-like-btn');
        var icon = form.querySelector('.post-like-icon');
        var label = form.querySelector('.post-like-label');
        var countEl = form.querySelector('.post-like-count');
        if (!postId || !btn) return;
        var fd = new FormData(form);
        var url = getBaseUrl() + '/post/' + postId + '/like';
        btn.disabled = true;
        fetch(url, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json().then(function (j) { return { status: r.status, json: j }; }); })
          .then(function (res) {
            if (res.json.ok) {
              var liked = res.json.liked;
              var count = res.json.count;
              form.setAttribute('data-liked', liked ? '1' : '0');
              form.setAttribute('data-count', String(count));
              if (icon) {
                icon.classList.remove('fa-regular', 'fa-solid');
                icon.classList.add(liked ? 'fa-solid' : 'fa-regular', 'fa-thumbs-up');
              }
              if (label) {
                var labelLike = form.getAttribute('data-label-like');
                var labelUnlike = form.getAttribute('data-label-unlike');
                label.textContent = liked ? (labelUnlike || getLang('unlike', 'Beğeniyi kaldır')) : (labelLike || getLang('like', 'Beğen'));
              }
              if (countEl) countEl.textContent = '(' + count + ')';
              showToast(liked ? getLang('liked', 'Beğenildi') : getLang('like_removed', 'Beğeni kaldırıldı'), 'success');
            } else {
              showToast(res.json.error || getLang('operation_failed', 'İşlem başarısız'), 'error');
            }
          })
          .catch(function () {
            showToast(getLang('error_occurred', 'Bir hata oluştu'), 'error');
          })
          .then(function () {
            btn.disabled = false;
          });
      });
    });
  }

  function initPostVoteAjax() {
    document.querySelectorAll('.mfbb-post-vote-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var postId = form.getAttribute('data-post-id');
        var btn = e.submitter || document.activeElement;
        var value = btn ? btn.value : null;
        if (!postId || !value) return;

        var fd = new FormData(form);
        fd.set('value', value);
        var url = form.action;

        var wrap = form.closest('.mfbb-post-vote-wrap');
        if (wrap) {
          wrap.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
        }

        fetch(url, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }).catch(function () { return { ok: false, json: { error: getLang('invalid_response', 'Geçersiz yanıt.') } }; }); })
          .then(function (res) {
            if (res.ok && res.json.ok) {
              if (wrap) {
                var countSpan = wrap.querySelector('span.text-sm.font-semibold');
                if (countSpan) countSpan.textContent = res.json.net_votes;

                var upBtn = wrap.querySelector('button[value="1"]');
                var downBtn = wrap.querySelector('button[value="-1"]');

                if (upBtn) {
                  if (res.json.voted == 1) upBtn.classList.add('text-green-600', 'bg-green-50');
                  else upBtn.classList.remove('text-green-600', 'bg-green-50');
                }
                if (downBtn) {
                  if (res.json.voted == -1) downBtn.classList.add('text-red-600', 'bg-red-50');
                  else downBtn.classList.remove('text-red-600', 'bg-red-50');
                }
              }
            } else {
              if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(res.json.error || getLang('operation_failed', 'İşlem başarısız.'), 'error');
              else alert(res.json.error || getLang('operation_failed', 'İşlem başarısız.'));
            }
          })
          .catch(function () {
            if (typeof window.MegaforBBToast === 'function') window.MegaforBBToast(getLang('error_occurred', 'Bir hata oluştu.'), 'error');
          })
          .then(function () {
            if (wrap) {
              wrap.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
            }
          });
      });
    });
  }

  function initAnnouncementDismiss() {
    document.querySelectorAll('.announcement-dismiss').forEach(function (btn) {
      if (btn.getAttribute('data-mfbb-dismiss-bound') === '1') return;
      btn.setAttribute('data-mfbb-dismiss-bound', '1');
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var id = parseInt(btn.getAttribute('data-announcement-id'), 10);
        var token = btn.getAttribute('data-token') || '';
        if (!id) return;
        var item = btn.closest('.announcement-item, .announcement-forum-item');
        var url = getBaseUrl() + '/api/announcement-dismiss';
        fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body: 'id=' + id + '&_token=' + encodeURIComponent(token),
          credentials: 'same-origin'
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok && item) {
              item.style.transition = 'opacity 0.2s';
              item.style.opacity = '0';
              setTimeout(function () {
                item.remove();
                var slot = document.getElementById('announcements-header');
                var forumBox = document.querySelector('.forum-announcements-box');
                if (slot && !slot.querySelector('.announcement-item')) slot.remove();
                if (forumBox && !forumBox.querySelector('.announcement-forum-item')) {
                  forumBox.remove();
                  location.reload();
                }
              }, 200);
            }
          })
          .catch(function () { });
      });
    });
  }

  function initCaptchaV3() {
    document.querySelectorAll('.captcha-widget[data-provider="recaptcha"][data-recaptcha-version="v3"]').forEach(function (widget) {
      var form = widget.closest('form');
      if (!form) return;
      var siteKey = widget.getAttribute('data-captcha-site-key');
      if (!siteKey) return;
      form.addEventListener('submit', function (e) {
        var tokenEl = form.querySelector('#recaptcha-v3-token');
        if (tokenEl && tokenEl.value) return;
        e.preventDefault();
        if (typeof grecaptcha === 'undefined') { form.submit(); return; }
        grecaptcha.ready(function () {
          grecaptcha.execute(siteKey, { action: 'submit' }).then(function (token) {
            if (tokenEl) tokenEl.value = token;
            form.submit();
          });
        });
      });
    });
  }

  function initTurnstile() {
    var pending = document.querySelectorAll('.cf-turnstile:not([data-mfbb-turnstile-bound])');
    if (pending.length === 0) {
      return;
    }

    function renderAll() {
      if (typeof window.turnstile === 'undefined' || typeof window.turnstile.ready !== 'function') {
        return;
      }
      window.turnstile.ready(function () {
        document.querySelectorAll('.cf-turnstile:not([data-mfbb-turnstile-bound])').forEach(function (el) {
          var siteKey = el.getAttribute('data-sitekey');
          if (!siteKey) {
            return;
          }
          el.setAttribute('data-mfbb-turnstile-bound', '1');
          try {
            window.turnstile.render(el, { sitekey: siteKey });
          } catch (e) {
            el.removeAttribute('data-mfbb-turnstile-bound');
          }
        });
      });
    }

    if (typeof window.turnstile !== 'undefined' && typeof window.turnstile.ready === 'function') {
      renderAll();
      return;
    }

    var existing = document.querySelector('script[data-mfbb-turnstile-api]');
    if (existing) {
      existing.addEventListener('load', renderAll);
      return;
    }

    var s = document.createElement('script');
    s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    s.async = true;
    s.defer = true;
    s.setAttribute('data-mfbb-turnstile-api', '1');
    s.onload = renderAll;
    document.head.appendChild(s);
  }

  function initNewContentModal() {
    if (initNewContentModal._delegationDone) {
      return;
    }
    initNewContentModal._delegationDone = true;

    function getModal() {
      return document.getElementById('new-content-modal');
    }

    function closeModal() {
      var m = getModal();
      if (!m) return;
      m.classList.add('hidden');
      m.setAttribute('aria-hidden', 'true');
    }

    function updateForumVisibility(modal) {
      var forumWrap = document.getElementById('new-content-modal-forum-wrap');
      var checked = modal.querySelector('input[name="new_content_type"]:checked');
      var value = checked ? checked.value : '';
      if (forumWrap) forumWrap.style.display = value === 'article' ? 'none' : '';
    }

    document.addEventListener('open-new-content-modal', function () {
      var modal = getModal();
      if (!modal) return;
      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');
      var firstRadio = modal.querySelector('input[name="new_content_type"]');
      if (firstRadio) {
        firstRadio.checked = true;
        updateForumVisibility(modal);
        try {
          firstRadio.focus();
        } catch (err) {}
      }
      var currentSlug = modal.getAttribute('data-current-forum-slug');
      var forumSelect = document.getElementById('new-content-modal-forum');
      if (forumSelect && currentSlug) forumSelect.value = currentSlug;
    });

    document.addEventListener('click', function (e) {
      var modal = getModal();
      if (!modal) return;
      var t = e.target;
      var closeEl = t.closest && t.closest('.new-content-modal-close');
      if (closeEl && modal.contains(closeEl)) {
        closeModal();
        return;
      }
      if (t.classList && t.classList.contains('new-content-modal-backdrop') && modal.contains(t)) {
        closeModal();
        return;
      }
      if (t.id === 'new-content-modal-submit' && modal.contains(t)) {
        var checked = modal.querySelector('input[name="new_content_type"]:checked');
        var type = checked ? checked.value : '';
        if (!type) {
          alert(getLang('select_content_type', 'İçerik türü seçin.'));
          return;
        }
        var base = getBaseUrl();
        if (type === 'article') {
          window.location.href = base + '/articles/new';
          return;
        }
        var forumSelect = document.getElementById('new-content-modal-forum');
        var slug = forumSelect ? forumSelect.value : '';
        if (!slug) {
          alert(getLang('select_forum', 'Forum seçin.'));
          return;
        }
        window.location.href = base + '/forum/' + encodeURIComponent(slug) + '/new-topic?type=' + encodeURIComponent(type);
      }
    });

    document.addEventListener('change', function (e) {
      if (e.target.name !== 'new_content_type') return;
      var m = getModal();
      if (!m || !m.contains(e.target)) return;
      updateForumVisibility(m);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var m = getModal();
      if (m && !m.classList.contains('hidden')) closeModal();
    });
  }

  function initTinyMCESaveOnSubmit() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || !form.querySelector) return;
      if (!form.querySelector('textarea[data-editor]')) return;
      if (window.MEGAFORBB_EDITOR !== 'tinymce' || typeof window.tinymce === 'undefined') return;
      window.tinymce.triggerSave();
    }, true);
  }

  function initCkeditorSaveOnSubmit() {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!form || !form.querySelector) return;
      if (!form.querySelector('textarea[data-editor]')) return;
      if (window.MEGAFORBB_EDITOR !== 'ckeditor' || !window.MEGAFORBB_CKEDITORS) return;
      var ids = Object.keys(window.MEGAFORBB_CKEDITORS);
      ids.forEach(function (id) {
        var ed = window.MEGAFORBB_CKEDITORS[id];
        if (ed && ed.updateSourceElement) ed.updateSourceElement();
      });
    }, true);
  }

  /**
   * Konu gezgini (#topic-post-scrubber) #mfbb-page-container dışında olduğu için soft-nav ile silinmez;
   * gelen HTML ile senkronize edilir (konudan çıkınca kaldırılır, konuya girince eklenir).
   */
  function syncTopicPostScrubberFromFetchedDoc(nextDoc) {
    if (!nextDoc || !nextDoc.getElementById) return;
    var nextNav = nextDoc.getElementById('topic-post-scrubber');
    var curNav = document.getElementById('topic-post-scrubber');
    if (nextNav) {
      var clone = nextNav.cloneNode(true);
      if (curNav) {
        curNav.replaceWith(clone);
      } else {
        var cont = document.getElementById('mfbb-page-container');
        if (cont && cont.parentNode) {
          if (cont.nextSibling) {
            cont.parentNode.insertBefore(clone, cont.nextSibling);
          } else {
            cont.parentNode.appendChild(clone);
          }
        }
      }
    } else if (curNav) {
      curNav.remove();
    }
  }

  function initSoftNavigation() {
    if (mfbbSoftNavInited) return;
    if (!window.MEGAFORBB_AJAX_ENABLED) return;
    if (typeof window.fetch !== 'function' || typeof window.DOMParser === 'undefined' || !window.history || typeof window.history.pushState !== 'function') return;

    mfbbSoftNavInited = true;
    var rootSelector = '#mfbb-page-container';
    var activeController = null;
    var htmlContentType = /text\/html|application\/xhtml\+xml/i;

    function isModifiedClick(event) {
      return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
    }

    function canHandleUrl(urlObj) {
      if (!urlObj || urlObj.origin !== window.location.origin) return false;
      var path = (urlObj.pathname || '').toLowerCase();
      if (path.indexOf('/logout') !== -1) return false;
      if (/\.(zip|rar|7z|pdf|png|jpe?g|gif|webp|svg|mp4|mp3|docx?|xlsx?|pptx?)$/i.test(path)) return false;
      return true;
    }

    function shouldHandleLink(link, event) {
      if (!link || isModifiedClick(event)) return false;
      if (event.defaultPrevented) return false;
      if (link.hasAttribute('download') || link.getAttribute('target') === '_blank') return false;
      if (link.hasAttribute('data-no-soft-nav') || link.closest('[data-no-soft-nav]')) return false;
      var href = (link.getAttribute('href') || '').trim();
      if (!href || href === '#') return false;
      if (/^(mailto:|tel:|javascript:|data:|blob:)/i.test(href)) return false;
      var urlObj;
      try {
        urlObj = new URL(link.href, window.location.href);
      } catch (e) {
        return false;
      }
      if (!canHandleUrl(urlObj)) return false;
      if (urlObj.pathname === window.location.pathname && urlObj.search === window.location.search && urlObj.hash) return false;
      return true;
    }

    function executeScriptsWithin(rootEl) {
      if (!rootEl) return;
      var scripts = rootEl.querySelectorAll('script');
      scripts.forEach(function (oldScript) {
        var replacement = document.createElement('script');
        var src = oldScript.getAttribute('src');
        if (src) {
          var absSrc = new URL(src, window.location.href).href;
          var exists = Array.prototype.some.call(document.scripts, function (s) {
            try {
              return s.src && new URL(s.src, window.location.href).href === absSrc;
            } catch (e) {
              return false;
            }
          });
          if (exists) return;
        }
        Array.prototype.forEach.call(oldScript.attributes, function (attr) {
          replacement.setAttribute(attr.name, attr.value);
        });
        replacement.text = oldScript.textContent || '';
        oldScript.parentNode.replaceChild(replacement, oldScript);
      });
    }

    function hydrateSwappedPage(url, opts) {
      opts = opts || {};
      if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        var newRoot = document.querySelector(rootSelector);
        if (newRoot) window.Alpine.initTree(newRoot);
      }
      if (typeof window.MegaforBBReinitPage === 'function') {
        window.MegaforBBReinitPage();
      }
      var urlObj = new URL(url, window.location.href);
      if (!opts.keepScroll) {
        if (urlObj.hash) {
          var anchorId = decodeURIComponent(urlObj.hash.slice(1));
          var anchorEl = document.getElementById(anchorId);
          if (anchorEl) anchorEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
          else window.scrollTo(0, 0);
        } else {
          window.scrollTo(0, 0);
        }
      }
      /* document: window'a dispatch edilen olay document dinleyicilerine gitmez (Retro theme.js vb.) */
      var detail = { url: url };
      document.dispatchEvent(new CustomEvent('mfbb:soft-nav:loaded', { detail: detail }));
      window.dispatchEvent(new CustomEvent('mfbb-soft-nav-loaded', { detail: detail }));
    }

    /**
     * Döküman vb. sayfalar head_extra ile ek stylesheet yükler; soft nav sadece #mfbb-page-container
     * değiştirdiği için bu CSS hiç eklenmiyordu — layout boş/bozuk görünüyordu.
     */
    function mergeHeadStylesheetsFromParsedDoc(nextDoc, pageUrl) {
      var head = nextDoc.head;
      if (!head || !head.querySelectorAll) return;
      var resolveBase = pageUrl || window.location.href;
      var baseTag = head.querySelector('base[href]');
      if (baseTag) {
        try {
          resolveBase = new URL(baseTag.getAttribute('href'), window.location.origin + '/').href;
        } catch (e) {}
      }
      head.querySelectorAll('link[rel="stylesheet"]').forEach(function (link) {
        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#') return;
        var abs;
        try {
          abs = new URL(href, resolveBase).href;
        } catch (e) {
          return;
        }
        var already = Array.prototype.some.call(document.querySelectorAll('link[rel="stylesheet"]'), function (l) {
          try {
            return l.href === abs;
          } catch (e2) {
            return false;
          }
        });
        if (already) return;
        var n = document.createElement('link');
        n.rel = 'stylesheet';
        n.href = abs;
        var media = link.getAttribute('media');
        if (media) n.setAttribute('media', media);
        document.head.appendChild(n);
      });
    }

    function swapPageFromHtml(html, finalUrl, opts) {
      var parser = new DOMParser();
      var nextDoc = parser.parseFromString(html, 'text/html');
      var nextRoot = nextDoc.querySelector(rootSelector);
      var currentRoot = document.querySelector(rootSelector);
      if (!nextRoot || !currentRoot) return false;

      mergeHeadStylesheetsFromParsedDoc(nextDoc, finalUrl);

      if (nextDoc.title) document.title = nextDoc.title;
      if (nextDoc.documentElement && nextDoc.documentElement.lang) {
        document.documentElement.lang = nextDoc.documentElement.lang;
      }
      if (nextDoc.body && typeof nextDoc.body.className === 'string') {
        document.body.className = nextDoc.body.className;
      }

      if (typeof window.MegaforBBDestroyTopicPostScrubber === 'function') {
        window.MegaforBBDestroyTopicPostScrubber();
      }
      currentRoot.replaceWith(nextRoot);
      syncTopicPostScrubberFromFetchedDoc(nextDoc);
      executeScriptsWithin(nextRoot);
      hydrateSwappedPage(finalUrl, opts || {});
      return true;
    }

    function navigateSoft(url, opts) {
      opts = opts || {};
      var urlObj;
      try {
        urlObj = new URL(url, window.location.href);
      } catch (e) {
        return Promise.resolve(false);
      }
      if (!canHandleUrl(urlObj)) {
        window.location.href = urlObj.href;
        return Promise.resolve(false);
      }

      if (activeController) activeController.abort();
      activeController = new AbortController();
      if (window.MegaforBBLoading && typeof window.MegaforBBLoading.show === 'function') window.MegaforBBLoading.show();

      return fetch(urlObj.href, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-MegaforBB-Soft-Nav': '1'
        },
        signal: activeController.signal
      })
        .then(function (response) {
          var finalUrl = response.url || urlObj.href;
          var contentType = response.headers.get('content-type') || '';
          if (!response.ok) throw new Error('HTTP ' + response.status);
          if (!htmlContentType.test(contentType)) {
            window.location.href = finalUrl;
            return null;
          }
          return response.text().then(function (html) {
            return {
              html: html,
              finalUrl: finalUrl
            };
          });
        })
        .then(function (payload) {
          if (!payload) return false;
          var swapped = swapPageFromHtml(payload.html, payload.finalUrl, opts);
          if (!swapped) {
            window.location.href = payload.finalUrl;
            return false;
          }
          if (opts.pushState) {
            window.history.pushState({ mfbbSoftNav: true, url: payload.finalUrl }, '', payload.finalUrl);
          }
          return true;
        })
        .catch(function (err) {
          if (err && err.name === 'AbortError') return false;
          window.location.href = urlObj.href;
          return false;
        })
        .finally(function () {
          if (window.MegaforBBLoading && typeof window.MegaforBBLoading.hide === 'function') window.MegaforBBLoading.hide();
          activeController = null;
        });
    }

    document.addEventListener('click', function (event) {
      var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
      if (!shouldHandleLink(link, event)) return;
      event.preventDefault();
      navigateSoft(link.href, { pushState: true });
    }, true);

    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
      if (form.hasAttribute('data-no-soft-nav') || form.closest('[data-no-soft-nav]')) return;
      if ((form.getAttribute('target') || '').toLowerCase() === '_blank') return;

      var method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method !== 'GET') return;
      if ((form.enctype || '').toLowerCase() === 'multipart/form-data') return;

      event.preventDefault();
      var action = form.getAttribute('action') || window.location.href;
      var actionUrl;
      try {
        actionUrl = new URL(action, window.location.href);
      } catch (e) {
        window.location.href = action;
        return;
      }
      var formData = new FormData(form);
      var params = new URLSearchParams();
      formData.forEach(function (value, key) {
        if (typeof value === 'string') params.append(key, value);
      });
      actionUrl.search = params.toString();
      navigateSoft(actionUrl.href, { pushState: true });
    }, true);

    window.addEventListener('popstate', function () {
      navigateSoft(window.location.href, { pushState: false, keepScroll: true });
    });

    window.MegaforBBSoftNavigate = navigateSoft;
  }

  function initProfileActivity() {
    var box = document.getElementById('profile-activity-box');
    if (!box) return;
    var ajaxUrl = box.getAttribute('data-ajax-url');
    var scrollEl = document.getElementById('profile-activity-scroll');
    var streamEl = document.getElementById('profile-activity-stream');
    var loaderEl = document.getElementById('profile-activity-loader');
    if (!ajaxUrl || !scrollEl || !streamEl) return;

    var loading = false;
    var limit = 15;

    function setHasMore(val) {
      box.setAttribute('data-has-more', val ? '1' : '0');
      if (loaderEl) loaderEl.classList.toggle('is-hidden', !val);
    }

    function fetchActivity(filter, offset, append) {
      if (loading) return;
      loading = true;
      if (loaderEl) loaderEl.classList.remove('is-hidden');
      var url = ajaxUrl + '?filter=' + encodeURIComponent(filter) + '&offset=' + offset + '&limit=' + limit;
      fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!append) {
            streamEl.innerHTML = data.html || '';
            if (!data.html) {
              streamEl.innerHTML = '<div class="ips-stream__empty"><i class="fa-regular fa-folder-open"></i><p>' + getLang('no_activity', 'Henüz etkinlik yok.') + '</p></div>';
            }
          } else if (data.html) {
            streamEl.insertAdjacentHTML('beforeend', data.html);
          }
          var newOffset = append ? offset + (data.count || 0) : (data.count || 0);
          box.setAttribute('data-offset', String(newOffset));
          box.setAttribute('data-filter', filter);
          setHasMore(!!data.has_more);
        })
        .catch(function () {
          if (!append) {
            streamEl.innerHTML = '<div class="ips-stream__empty"><p>' + getLang('load_activity_failed', 'Yüklenemedi.') + '</p></div>';
          }
          setHasMore(false);
        })
        .finally(function () {
          loading = false;
          if (box.getAttribute('data-has-more') !== '1' && loaderEl) loaderEl.classList.add('is-hidden');
        });
    }

    box.querySelectorAll('.ips-activity-filters__chip[data-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var filter = btn.getAttribute('data-filter') || 'all';
        box.querySelectorAll('.ips-activity-filters__chip').forEach(function (b) {
          b.classList.toggle('is-active', b === btn);
        });
        box.setAttribute('data-offset', '0');
        fetchActivity(filter, 0, false);
      });
    });

    scrollEl.addEventListener('scroll', function () {
      if (box.getAttribute('data-has-more') !== '1' || loading) return;
      var threshold = 80;
      if (scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - threshold) {
        var offset = parseInt(box.getAttribute('data-offset'), 10) || 0;
        var filter = box.getAttribute('data-filter') || 'all';
        fetchActivity(filter, offset, true);
      }
    });
  }

  function initPageFeatures() {
    initEditors();
    /* Editör CDN gecikmesiyle geç yüklenirse (makale/konu düzenlemede editör boş kalmasın diye) kısa aralıklarla tekrar dene. */
    (function retryEditors(attempt) {
      attempt = attempt || 0;
      if (attempt > 3) return;
      if (window.MEGAFORBB_EDITOR === 'tinymce' || window.MEGAFORBB_EDITOR === 'ckeditor') return;
      var pending = document.querySelectorAll('textarea[data-editor]:not([data-toastui-inited])');
      if (pending.length === 0) return;
      var Editor = (window.toastui && window.toastui.Editor) || window.Editor;
      if (typeof Editor !== 'undefined') {
        initEditors();
        return;
      }
      if (attempt === 0) fallbackDecodeEditorContent();
      setTimeout(function () { retryEditors(attempt + 1); }, attempt === 0 ? 300 : (attempt === 1 ? 600 : 800));
    })();
    initPostLikeAjax();
    initPostVoteAjax();
    initAnnouncementDismiss();
    initPostsBulkBar();
    initPostQuote();
    initPostbitReportModal();
    initPostbitRepModal();
    initRepModal();
    initCoverUpload();
    initAvatarUpload();
    initTopicCreateTabs();
    initTopicCreateQuestionSync();
    initPollAddOption();
    initTopicCreateTagSelector();
    initTopicCreatePrivateViewers();
    initTopicCreateAttachments();
    initReplyAttachments();
    initTopicLiveReplies();
    initAttachmentDelete();
    initPortal();
    initProfileActivity();
    initProfileWatcher();
    initProfileViewersModal();
    initProfileStatsDashboard();
    initCaptchaV3();
    initTurnstile();
    if (typeof window.MegaforBBInitTopicPostScrubber === 'function') {
      window.MegaforBBInitTopicPostScrubber();
    }
    if (window.Prism && document.querySelector('.mfbb-code-block')) {
      try { window.Prism.highlightAll(); } catch (e) { }
    }
  }

  function initGlobalFeatures() {
    if (mfbbGlobalInited) return;
    mfbbGlobalInited = true;

    if (window.MEGAFORBB_EDITOR === 'tinymce') {
      initTinyMCESaveOnSubmit();
    }
    if (window.MEGAFORBB_EDITOR === 'ckeditor') {
      initCkeditorSaveOnSubmit();
    }
    initEditorMentionGlobal();
    initLightbox();
    initNewContentModal();
    initHoverCards();
    initSoftNavigation();
  }

  function runAll() {
    initGlobalFeatures();
    initPageFeatures();
  }

  window.MegaforBBReinitPage = initPageFeatures;

  function initHoverCards() {
    var hoverTimer = null;
    var currentCard = null;
    var currentTarget = null;
    var cache = {};

    function removeCard() {
      if (currentCard) {
        currentCard.remove();
        currentCard = null;
      }
      currentTarget = null;
    }

    function createCard(html, x, y) {
      removeCard();
      var card = document.createElement('div');
      card.className = 'hover-card absolute z-50 bg-white dark:bg-slate-800 shadow-xl rounded-lg border border-slate-200 dark:border-slate-700 p-4 w-72 text-sm text-slate-700 dark:text-slate-300 pointer-events-none transition-opacity duration-200 opacity-0';
      card.innerHTML = html;
      document.body.appendChild(card);

      // Calculate position
      var rect = card.getBoundingClientRect();
      var top = y + 15;
      var left = x + 15;

      if (left + rect.width > window.innerWidth) {
        left = window.innerWidth - rect.width - 15;
      }
      if (top + rect.height > window.innerHeight + window.scrollY) {
        top = y - rect.height - 15;
      }

      card.style.top = top + 'px';
      card.style.left = left + 'px';

      // trigger fade in
      setTimeout(function () { card.style.opacity = '1'; }, 10);
      currentCard = card;
    }

    document.addEventListener('mouseover', function (e) {
      var target = e.target.closest('.mention, .post-ref');
      if (!target) return;
      if (currentTarget === target) return;
      currentTarget = target;

      var isMention = target.classList.contains('mention');
      var username = target.getAttribute('data-mention-username');
      if (isMention && !username) {
        username = (target.textContent || '').trim();
      }
      if (isMention) {
        username = String(username || '').trim().replace(/^@+/, '');
      }
      var postId = target.getAttribute('data-post-ref-id');
      var topicId = target.getAttribute('data-topic-id');
      var postPos = target.getAttribute('data-post-pos');

      if (!username && !postId && !(topicId && postPos)) return;

      var baseUrl = window.MEGAFORBB_BASE_URL || '';
      var cacheKey = isMention ? 'u_' + username : (topicId && postPos ? 'p_t' + topicId + '_p' + postPos : 'p_' + postId);
      var url = isMention
        ? baseUrl + '/api/hover/user?username=' + encodeURIComponent(username)
        : (topicId && postPos
          ? baseUrl + '/api/hover/post?topic_id=' + topicId + '&pos=' + postPos
          : baseUrl + '/api/hover/post?id=' + postId);

      hoverTimer = setTimeout(function () {
        if (cache[cacheKey]) {
          createCard(cache[cacheKey], e.pageX, e.pageY);
          return;
        }

        // Show loading state
        createCard('<div class="flex items-center justify-center p-2"><svg class="animate-spin h-5 w-5 text-primary" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>', e.pageX, e.pageY);

        fetch(url)
          .then(res => res.json())
          .then(data => {
            if (data.error) {
              removeCard();
              return;
            }
            var html = '';
            if (isMention) {
              var roleBadge = data.role_name ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: ' + data.role_color + '20; color: ' + data.role_color + '; border: 1px solid ' + data.role_color + '40;">' + data.role_name + '</span>' : '';
              html = '<div class="flex items-start gap-4">' +
                '<img src="' + data.avatar_url + '" class="w-12 h-12 rounded-full object-cover shrink-0">' +
                '<div>' +
                '<div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">' + data.username + roleBadge + '</div>' +
                '<div class="text-xs mt-1 text-slate-500"><i class="fas fa-calendar-alt w-4"></i> ' + getLang('joined_at', ':date katıldı').replace(':date', data.joined_at) + '</div>' +
                '<div class="text-xs mt-1 text-slate-500"><i class="fas fa-comment w-4"></i> ' + data.post_count + ' mesaj</div>' +
                '</div></div>';
            } else {
              html = '<div class="flex items-start gap-3 mb-2">' +
                '<img src="' + data.avatar_url + '" class="w-8 h-8 rounded-full object-cover shrink-0">' +
                '<div><div class="font-bold text-slate-900 dark:text-white text-sm">' + data.username + '</div>' +
                '<div class="text-xs text-slate-500">' + data.created_at + '</div></div></div>' +
                '<div class="text-xs font-semibold text-primary mb-1 truncate">' + data.topic_title + '</div>' +
                '<div class="text-sm line-clamp-3">' + data.snippet + '</div>';
            }
            cache[cacheKey] = html;
            createCard(html, e.pageX, e.pageY);
          })
          .catch(err => removeCard());
      }, 400); // 400ms delay
    });

    document.addEventListener('mouseout', function (e) {
      var target = e.target.closest('.mention, .post-ref');
      if (target) {
        var next = e.relatedTarget;
        if (next && target.contains(next)) {
          return;
        }
        clearTimeout(hoverTimer);
        removeCard();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runAll);
  } else {
    runAll();
  }

  // -- Topic Active Viewers (heartbeat + polling) ----------------------
  (function () {
    var bar = document.getElementById('topic-viewers-bar');
    if (!bar) return;
    var topicId = bar.dataset.topicId || '';
    var pingUrl = bar.dataset.apiPing || '';
    var getUrl  = bar.dataset.apiGet  || '';
    if (!topicId || !pingUrl || !getUrl) return;

    var storageKey = 'mfbb_viewer_token';
    // Sunucuyla aynı belirteç (data-viewer-token) yoksa eski davranış; çift sayımı önler.
    var token = (bar.dataset.viewerToken || '').trim();
    try {
      if (!token) {
        token = localStorage.getItem(storageKey) || '';
        if (!token) {
          token = 'tv' + Math.random().toString(36).slice(2) + Date.now().toString(36);
          localStorage.setItem(storageKey, token);
        }
      }
    } catch (e) {
      if (!token) {
        token = 'tv' + Math.random().toString(36).slice(2);
      }
    }

    var summaryEl = document.getElementById('tvb-summary');
    var namesEl   = document.getElementById('tvb-names');

    function ping() {
      var fd = new FormData();
      fd.append('topic_id', topicId);
      fd.append('token', token);
      fetch(pingUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
    }

    function fetchViewers() {
      fetch(getUrl + '?topic_id=' + encodeURIComponent(topicId), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!summaryEl) return;
          var total   = data.total        || 0;
          var members = data.member_count || 0;
          var guests  = data.guest_count  || 0;
          var names   = (data.members || []).map(function (m) { return m.username; }).join(', ');
          var lang    = window.MEGAFORBB_LANG || {};
          var summary = '';
          if (members > 0 && guests > 0) {
            summary = (lang['topic.active_viewers'] || 'Konuyu toplam :total kisi okuyor. (:members uye ve :guests misafir)')
              .replace(':total', total).replace(':members', members).replace(':guests', guests);
          } else if (members > 0) {
            summary = (lang['topic.active_viewers_members_only'] || 'Konuyu toplam :total kisi okuyor. (:members uye)')
              .replace(':total', total).replace(':members', members);
          } else if (guests > 0) {
            summary = (lang['topic.active_viewers_guests_only'] || 'Konuyu toplam :total kisi okuyor. (:guests misafir)')
              .replace(':total', total).replace(':guests', guests);
          }
          summaryEl.textContent = summary;
          if (namesEl) namesEl.textContent = names;
        })
        .catch(function () {});
    }

    ping();
    fetchViewers();
    setInterval(function () { ping(); fetchViewers(); }, 30000);
  })();
  // -- / Topic Active Viewers ------------------------------------------

})();
