/*
 * MegaforBB Default Theme JS
 *
 * Sistem cekirdegi JS artik public/js/main.js dosyasinda calisir.
 * Bu dosya sadece default temaya ozel gorunum/etkilesim ihtiyaclari icin ayrilmistir.
 */
(function () {
  'use strict';

  var topicScrubberTeardown = null;

  function destroyTopicPostScrubber() {
    if (typeof topicScrubberTeardown === 'function') {
      try {
        topicScrubberTeardown();
      } catch (e) {}
      topicScrubberTeardown = null;
    }
  }

  function prefersReducedMotion() {
    try {
      return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
      return false;
    }
  }

  function initTopicPostScrubber() {
    destroyTopicPostScrubber();

    var nav = document.getElementById('topic-post-scrubber');
    var region = document.getElementById('thread-posts-region');
    if (!nav || !region) return;

    var links = nav.querySelectorAll('a[data-post-id]');
    if (!links.length) return;

    var readout = document.getElementById('topic-scrubber-readout');
    var totalPosts = readout ? parseInt(readout.getAttribute('data-total') || '0', 10) : 0;
    var btnUp = document.getElementById('topic-scrubber-up');
    var btnDown = document.getElementById('topic-scrubber-down');

    function positionNav() {
      if (!nav || !region) return;
      var wide = true;
      try {
        wide = window.matchMedia('(min-width: 1024px)').matches;
      } catch (e) {}
      if (!wide) {
        nav.style.left = '';
        nav.style.right = '';
        return;
      }
      var r = region.getBoundingClientRect();
      var gap = Math.round(Math.min(14, Math.max(6, window.innerWidth * 0.01)));
      var w = nav.offsetWidth || 44;
      var left = r.right + gap;
      var maxLeft = window.innerWidth - w - 8;
      if (left > maxLeft) left = maxLeft;
      if (left < 8) left = 8;
      nav.style.left = Math.round(left) + 'px';
      nav.style.right = 'auto';
    }

    function getPostIds() {
      return Array.prototype.map.call(region.querySelectorAll('article[id^="post-"]'), function (el) {
        var id = el.id || '';
        return id.indexOf('post-') === 0 ? id.slice(5) : '';
      }).filter(Boolean);
    }

    function getActivePostId() {
      var active = nav.querySelector('a.topic-post-scrubber__dot--active');
      if (active) return active.getAttribute('data-post-id');
      var ids = getPostIds();
      return ids.length ? ids[0] : null;
    }

    function updateNavButtons() {
      if (!btnUp || !btnDown) return;
      var ids = getPostIds();
      var aid = getActivePostId();
      var idx = aid ? ids.indexOf(String(aid)) : -1;
      if (idx < 0 && ids.length) idx = 0;
      btnUp.disabled = ids.length === 0 || idx <= 0;
      btnDown.disabled = ids.length === 0 || idx < 0 || idx >= ids.length - 1;
    }

    function setActive(postId) {
      var idStr = String(postId);
      links.forEach(function (a) {
        var on = a.getAttribute('data-post-id') === idStr;
        a.classList.toggle('topic-post-scrubber__dot--active', on);
        a.setAttribute('aria-current', on ? 'true' : 'false');
      });
      if (readout && totalPosts > 0) {
        var active = nav.querySelector('a.topic-post-scrubber__dot--active');
        var g = active ? parseInt(active.getAttribute('data-global-index') || '0', 10) : 0;
        if (g > 0) readout.textContent = g + ' / ' + totalPosts;
      }
      updateNavButtons();
      var dot = nav.querySelector('a.topic-post-scrubber__dot--active');
      var track = nav.querySelector('.mfbb-topic-post-scrubber__dots');
      if (dot && track && track.scrollHeight > track.clientHeight) {
        dot.scrollIntoView({ block: 'nearest', behavior: 'auto' });
      }
    }

    function scrollToPostId(pid) {
      var target = document.getElementById('post-' + pid);
      if (!target) return;
      var reduce = prefersReducedMotion();
      target.scrollIntoView({ block: 'start', behavior: reduce ? 'auto' : 'smooth' });
      try {
        history.replaceState(null, '', '#post-' + pid);
      } catch (err) {}
      setActive(pid);
    }

    function pickActiveFromViewport() {
      var articles = region.querySelectorAll('article[id^="post-"]');
      if (!articles.length) return;
      var mid = window.innerHeight * 0.32;
      var bestId = null;
      var bestDist = Infinity;
      for (var i = 0; i < articles.length; i++) {
        var el = articles[i];
        var r = el.getBoundingClientRect();
        if (r.bottom <= 0 || r.top >= window.innerHeight) continue;
        var center = (r.top + r.bottom) / 2;
        var d = Math.abs(center - mid);
        if (d < bestDist) {
          bestDist = d;
          var id = el.id || '';
          if (id.indexOf('post-') === 0) bestId = id.slice(5);
        }
      }
      if (!bestId && articles.length) {
        for (var j = 0; j < articles.length; j++) {
          var r0 = articles[j].getBoundingClientRect();
          if (r0.bottom > 8 && r0.top < window.innerHeight - 8) {
            var id0 = articles[j].id || '';
            if (id0.indexOf('post-') === 0) bestId = id0.slice(5);
            break;
          }
        }
      }
      if (!bestId && articles.length) {
        var id1 = articles[0].id || '';
        if (id1.indexOf('post-') === 0) bestId = id1.slice(5);
      }
      if (bestId) setActive(bestId);
      else updateNavButtons();
    }

    var ticking = false;
    function onScrollOrResize() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        ticking = false;
        positionNav();
        pickActiveFromViewport();
      });
    }

    function onNavClick(e) {
      var a = e.target.closest('a[data-post-id]');
      if (!a || !nav.contains(a)) return;
      e.preventDefault();
      scrollToPostId(a.getAttribute('data-post-id'));
    }

    nav.addEventListener('click', onNavClick);

    function stepPost(delta) {
      var ids = getPostIds();
      if (!ids.length) return;
      var aid = getActivePostId();
      var idx = aid ? ids.indexOf(String(aid)) : 0;
      if (idx < 0) idx = 0;
      var next = idx + delta;
      if (next < 0 || next >= ids.length) return;
      scrollToPostId(ids[next]);
    }

    var btnUpHandler = function () { stepPost(-1); };
    var btnDownHandler = function () { stepPost(1); };

    if (btnUp) btnUp.addEventListener('click', btnUpHandler);
    if (btnDown) btnDown.addEventListener('click', btnDownHandler);

    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    positionNav();
    pickActiveFromViewport();

    if (window.location.hash && /^#post-\d+$/.test(window.location.hash)) {
      var m = window.location.hash.match(/^#post-(\d+)$/);
      if (m) setTimeout(function () { setActive(m[1]); }, 0);
    }

    topicScrubberTeardown = function () {
      window.removeEventListener('scroll', onScrollOrResize, { passive: true });
      window.removeEventListener('resize', onScrollOrResize, { passive: true });
      nav.removeEventListener('click', onNavClick);
      if (btnUp) btnUp.removeEventListener('click', btnUpHandler);
      if (btnDown) btnDown.removeEventListener('click', btnDownHandler);
    };
  }

  function initDefaultThemeOnly() {
    initTopicPostScrubber();
  }

  window.MegaforBBDestroyTopicPostScrubber = destroyTopicPostScrubber;
  window.MegaforBBInitTopicPostScrubber = initTopicPostScrubber;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDefaultThemeOnly);
  } else {
    initDefaultThemeOnly();
  }
})();
