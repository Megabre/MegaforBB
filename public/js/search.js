/**
 * MegaforBB — Live AJAX search on /search page.
 */
(function () {
  'use strict';

  var root = document.getElementById('mfbb-search-app');
  if (!root) return;

  var cfg = window.MFBB_SEARCH || {};
  var apiUrl = cfg.apiUrl || '/api/search';
  var debounceMs = cfg.debounceMs || 320;
  var minChars = cfg.minChars || 2;

  var input = root.querySelector('[data-search-input]');
  var form = root.querySelector('[data-search-form]');
  var resultsEl = root.querySelector('[data-search-results]');
  var statusEl = root.querySelector('[data-search-status]');
  var categoryBtns = root.querySelectorAll('[data-search-category]');
  var typeInput = root.querySelector('[data-search-type]');
  var paginationEl = root.querySelector('[data-search-pagination]');

  var state = {
    type: typeInput ? typeInput.value : 'all',
    page: parseInt(cfg.page || '1', 10) || 1,
    loading: false,
    timer: null,
    requestId: 0,
  };

  function getParams(live) {
    var fd = new FormData(form);
    var params = new URLSearchParams();
    fd.forEach(function (val, key) {
      if (val !== '') params.set(key, val);
    });
    if (live) params.set('live', '1');
    if (state.page > 1) params.set('page', String(state.page));
    return params;
  }

  function updateUrl() {
    var params = getParams(false);
    params.delete('live');
    var qs = params.toString();
    var url = window.location.pathname + (qs ? '?' + qs : '');
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, '', url);
    }
  }

  function setLoading(on) {
    state.loading = on;
    root.classList.toggle('mfbb-search--loading', on);
    if (statusEl) {
      statusEl.textContent = on ? (cfg.i18n.searching || 'Searching…') : '';
    }
  }

  function updateCategoryCounts(categories) {
    if (!categories) return;
    categories.forEach(function (cat) {
      var btn = root.querySelector('[data-search-category="' + cat.key + '"]');
      if (!btn) return;
      var badge = btn.querySelector('[data-count]');
      if (badge) {
        badge.textContent = cat.count > 0 ? cat.count : '';
        badge.classList.toggle('hidden', cat.count <= 0);
      }
      btn.classList.toggle('is-active', cat.key === state.type);
    });
  }

  function escapeHtml(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  function avatarUrl(user) {
    if (user.avatar_url) return escapeHtml(user.avatar_url);
    var name = encodeURIComponent(user.username || 'U');
    return 'https://ui-avatars.com/api/?name=' + name + '&background=105289&color=fff&size=80';
  }

  function renderSection(key, title, icon, items, total, i18n) {
    if (!items || items.length === 0) return '';
    var showMore = state.type === 'all' && total > items.length;
    var html = '<section class="mfbb-search-section" data-section="' + key + '">';
    html += '<header class="mfbb-search-section__head">';
    html += '<span class="mfbb-search-section__icon mfbb-search-section__icon--' + key + '"><i class="fa-solid ' + icon + '"></i></span>';
    html += '<h2 class="mfbb-search-section__title">' + escapeHtml(title) + '</h2>';
    html += '<span class="mfbb-search-section__count">' + total + '</span>';
    if (showMore) {
      html += '<button type="button" class="mfbb-search-section__more" data-filter-type="' + key + '">' + escapeHtml(i18n.viewAll || 'View all') + ' →</button>';
    }
    html += '</header><div class="mfbb-search-section__body">';

    items.forEach(function (item) {
      html += renderItem(key, item, i18n);
    });

    html += '</div></section>';
    return html;
  }

  function renderItem(key, item, i18n) {
    var url = escapeHtml(item.url || '#');
    switch (key) {
      case 'forums':
        return (
          '<a href="' + url + '" class="mfbb-search-item mfbb-search-item--forum">' +
          '<span class="mfbb-search-item__icon"><i class="fa-solid fa-folder-open"></i></span>' +
          '<div class="mfbb-search-item__main">' +
          '<h3 class="mfbb-search-item__title">' + escapeHtml(item.name) + '</h3>' +
          (item.description ? '<p class="mfbb-search-item__snippet">' + escapeHtml(item.description) + '</p>' : '') +
          '<div class="mfbb-search-item__meta">' +
          '<span>' + (item.topic_count || 0) + ' ' + escapeHtml(i18n.topics || 'topics') + '</span>' +
          '<span>' + (item.post_count || 0) + ' ' + escapeHtml(i18n.posts || 'posts') + '</span>' +
          '</div></div><i class="fa-solid fa-chevron-right mfbb-search-item__arrow"></i></a>'
        );
      case 'topics':
      case 'articles':
        return (
          '<a href="' + url + '" class="mfbb-search-item mfbb-search-item--topic">' +
          '<div class="mfbb-search-item__main">' +
          '<h3 class="mfbb-search-item__title">' + escapeHtml(item.title) + '</h3>' +
          '<div class="mfbb-search-item__meta">' +
          '<span><i class="fa-solid fa-user"></i> ' + escapeHtml(item.username) + '</span>' +
          (item.forum_name ? '<span><i class="fa-solid fa-folder"></i> ' + escapeHtml(item.forum_name) + '</span>' : '') +
          '<span>' + (item.reply_count || 0) + ' ' + escapeHtml(i18n.replies || 'replies') + '</span>' +
          '<span>' + (item.view_count || 0) + ' ' + escapeHtml(i18n.views || 'views') + '</span>' +
          '<span>' + escapeHtml(item.created_at) + '</span>' +
          '</div></div><i class="fa-solid fa-chevron-right mfbb-search-item__arrow"></i></a>'
        );
      case 'posts':
        return (
          '<a href="' + url + '" class="mfbb-search-item mfbb-search-item--post">' +
          '<p class="mfbb-search-item__snippet">' + escapeHtml(item.body_snippet) + '</p>' +
          '<div class="mfbb-search-item__meta">' +
          '<span class="mfbb-search-item__topic">' + escapeHtml(item.topic_title) + '</span>' +
          '<span><i class="fa-solid fa-user"></i> ' + escapeHtml(item.username) + '</span>' +
          (item.forum_name ? '<span><i class="fa-solid fa-folder"></i> ' + escapeHtml(item.forum_name) + '</span>' : '') +
          '<span>' + escapeHtml(item.created_at) + '</span>' +
          '</div></a>'
        );
      case 'users':
        return (
          '<a href="' + url + '" class="mfbb-search-item mfbb-search-item--user">' +
          '<img src="' + avatarUrl(item) + '" alt="" class="mfbb-search-item__avatar" loading="lazy">' +
          '<div class="mfbb-search-item__main">' +
          '<h3 class="mfbb-search-item__title">' + escapeHtml(item.username) + '</h3>' +
          '<div class="mfbb-search-item__meta">' +
          '<span>' + (item.post_count || 0) + ' ' + escapeHtml(i18n.posts || 'posts') + '</span>' +
          '<span><i class="fa-solid fa-calendar"></i> ' + escapeHtml(item.created_at) + '</span>' +
          '</div></div><i class="fa-solid fa-chevron-right mfbb-search-item__arrow"></i></a>'
        );
      case 'docs':
        return (
          '<a href="' + url + '" class="mfbb-search-item mfbb-search-item--doc">' +
          '<span class="mfbb-search-item__icon"><i class="fa-solid fa-book-open"></i></span>' +
          '<div class="mfbb-search-item__main">' +
          '<h3 class="mfbb-search-item__title">' + escapeHtml(item.title) + '</h3>' +
          (item.section_title ? '<span class="mfbb-search-item__badge">' + escapeHtml(item.section_title) + '</span>' : '') +
          (item.snippet ? '<p class="mfbb-search-item__snippet">' + escapeHtml(item.snippet) + '</p>' : '') +
          '</div><i class="fa-solid fa-chevron-right mfbb-search-item__arrow"></i></a>'
        );
      case 'ideas':
        return (
          '<a href="' + url + '" class="mfbb-search-item mfbb-search-item--idea">' +
          '<span class="mfbb-search-item__icon"><i class="fa-solid fa-lightbulb"></i></span>' +
          '<div class="mfbb-search-item__main">' +
          '<h3 class="mfbb-search-item__title">' + escapeHtml(item.title) + '</h3>' +
          (item.snippet ? '<p class="mfbb-search-item__snippet">' + escapeHtml(item.snippet) + '</p>' : '') +
          '<div class="mfbb-search-item__meta">' +
          (item.category_name ? '<span>' + escapeHtml(item.category_name) + '</span>' : '') +
          '<span>' + (item.vote_count || 0) + ' ' + escapeHtml(i18n.votes || 'votes') + '</span>' +
          '<span><i class="fa-solid fa-user"></i> ' + escapeHtml(item.username) + '</span>' +
          '</div></div><i class="fa-solid fa-chevron-right mfbb-search-item__arrow"></i></a>'
        );
      default:
        return '';
    }
  }

  function renderResults(data) {
    if (!resultsEl) return;
    var i18n = cfg.i18n || {};
    var results = data.results || {};
    var totals = data.totals || {};
    var type = data.type || 'all';
    var totalResults = data.totalResults || 0;

    if (!data.query && !data.tag) {
      resultsEl.innerHTML =
        '<div class="mfbb-search-empty mfbb-search-empty--idle">' +
        '<i class="fa-solid fa-magnifying-glass"></i>' +
        '<p>' + escapeHtml(i18n.placeholder || '') + '</p></div>';
      return;
    }

    if (totalResults === 0) {
      resultsEl.innerHTML =
        '<div class="mfbb-search-empty">' +
        '<i class="fa-solid fa-face-frown"></i>' +
        '<p class="mfbb-search-empty__title">' + escapeHtml(i18n.noResults || 'No results') + '</p>' +
        '<p class="mfbb-search-empty__hint">' + escapeHtml(i18n.noResultsHint || '') + '</p></div>';
      return;
    }

    var sections = [];
    var sectionMap = {
      forums: { title: i18n.sectionForums, icon: 'fa-folder-tree' },
      topics: { title: i18n.sectionTopics, icon: 'fa-comments' },
      articles: { title: i18n.sectionArticles, icon: 'fa-newspaper' },
      posts: { title: i18n.sectionPosts, icon: 'fa-message' },
      users: { title: i18n.sectionUsers, icon: 'fa-user-group' },
      docs: { title: i18n.sectionDocs, icon: 'fa-book' },
      ideas: { title: i18n.sectionIdeas, icon: 'fa-lightbulb' },
    };

    if (type === 'all') {
      Object.keys(sectionMap).forEach(function (key) {
        if (results[key] && results[key].length > 0) {
          sections.push(renderSection(key, sectionMap[key].title, sectionMap[key].icon, results[key], totals[key] || 0, i18n));
        }
      });
    } else if (results[type] && results[type].length > 0) {
      var sm = sectionMap[type] || { title: type, icon: 'fa-circle' };
      sections.push(renderSection(type, sm.title, sm.icon, results[type], totals[type] || 0, i18n));
    }

    resultsEl.innerHTML = sections.join('');

    resultsEl.querySelectorAll('[data-filter-type]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setType(btn.getAttribute('data-filter-type'));
      });
    });
  }

  function renderPagination(data) {
    if (!paginationEl) return;
    var page = data.page || 1;
    var totalPages = data.totalPages || 1;
    var type = data.type || 'all';

    if (type === 'all' || totalPages <= 1) {
      paginationEl.innerHTML = '';
      paginationEl.classList.add('hidden');
      return;
    }

    paginationEl.classList.remove('hidden');
    var html = '';
    if (page > 1) {
      html += '<button type="button" class="mfbb-search-page-btn" data-page="' + (page - 1) + '"><i class="fa-solid fa-chevron-left"></i> ' + escapeHtml(cfg.i18n.prev || 'Prev') + '</button>';
    }
    html += '<span class="mfbb-search-page-info">' + page + ' / ' + totalPages + '</span>';
    if (page < totalPages) {
      html += '<button type="button" class="mfbb-search-page-btn" data-page="' + (page + 1) + '">' + escapeHtml(cfg.i18n.next || 'Next') + ' <i class="fa-solid fa-chevron-right"></i></button>';
    }
    paginationEl.innerHTML = html;

    paginationEl.querySelectorAll('[data-page]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.page = parseInt(btn.getAttribute('data-page'), 10) || 1;
        fetchResults(false);
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  function updateStatus(data) {
    if (!statusEl) return;
    var i18n = cfg.i18n || {};
    if (!data.query && !data.tag) {
      statusEl.textContent = '';
      return;
    }
    if (data.totalResults > 0) {
      var tpl = i18n.resultsCount || ':count results';
      statusEl.innerHTML = '<strong>' + tpl.replace(':count', data.totalResults) + '</strong>';
    } else if (!state.loading) {
      statusEl.textContent = '';
    }
  }

  function fetchResults(live) {
    var q = input ? input.value.trim() : '';
    var tagEl = form.querySelector('[name="tag"]');
    var tag = tagEl ? tagEl.value.trim() : '';

    if (q.length < minChars && !tag) {
      if (statusEl) statusEl.textContent = cfg.i18n.minChars || '';
      if (resultsEl) {
        resultsEl.innerHTML =
          '<div class="mfbb-search-empty mfbb-search-empty--idle">' +
          '<i class="fa-solid fa-keyboard"></i>' +
          '<p>' + escapeHtml(cfg.i18n.minChars || '') + '</p></div>';
      }
      if (paginationEl) paginationEl.innerHTML = '';
      updateCategoryCounts(cfg.categories || []);
      return;
    }

    var reqId = ++state.requestId;
    setLoading(true);

    var params = getParams(live);
    fetch(apiUrl + '?' + params.toString(), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (reqId !== state.requestId) return;
        setLoading(false);
        if (!data.ok) return;
        updateCategoryCounts(data.categories);
        updateStatus(data);
        renderResults(data);
        renderPagination(data);
        if (!live) updateUrl();
      })
      .catch(function () {
        if (reqId !== state.requestId) return;
        setLoading(false);
      });
  }

  function scheduleSearch() {
    clearTimeout(state.timer);
    state.page = 1;
    state.timer = setTimeout(function () {
      fetchResults(true);
    }, debounceMs);
  }

  function setType(type) {
    state.type = type || 'all';
    state.page = 1;
    if (typeInput) typeInput.value = state.type;
    categoryBtns.forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-search-category') === state.type);
    });
    fetchResults(state.type === 'all');
    updateUrl();
  }

  if (input) {
    input.addEventListener('input', scheduleSearch);
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearTimeout(state.timer);
      state.page = 1;
      fetchResults(false);
    });
  }

  categoryBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setType(btn.getAttribute('data-search-category'));
    });
  });

  var advToggle = root.querySelector('[data-advanced-toggle]');
  var advPanel = root.querySelector('[data-advanced-panel]');
  if (advToggle && advPanel) {
    advToggle.addEventListener('click', function () {
      advPanel.classList.toggle('is-open');
      advToggle.setAttribute('aria-expanded', advPanel.classList.contains('is-open') ? 'true' : 'false');
    });
  }

  var filterInputs = form.querySelectorAll('select, input[type="date"], input[name="author"]');
  filterInputs.forEach(function (el) {
    el.addEventListener('change', function () {
      state.page = 1;
      scheduleSearch();
    });
  });

  function wireViewAllButtons() {
    if (!resultsEl) return;
    resultsEl.querySelectorAll('[data-filter-type]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setType(btn.getAttribute('data-filter-type'));
      });
    });
  }

  wireViewAllButtons();

  if (paginationEl) {
    paginationEl.querySelectorAll('[data-page]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.page = parseInt(btn.getAttribute('data-page'), 10) || 1;
        fetchResults(false);
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }
})();
