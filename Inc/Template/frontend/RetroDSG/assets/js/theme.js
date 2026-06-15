/*
 * RetroDSG Theme JS
 *
 * Core behavior runs in public/js/main.js.
 * This file adds RetroDSG-only visual interactions.
 */
(function () {
  'use strict';

  function withRevealSequence() {
    var selectors = [
      '.rdsg-reveal',
      '.rdsg-hero-card',
      '.rdsg-forum-card',
      '.rdsg-sidebar > div',
      'article[id^="post-"]'
    ];

    var elements = document.querySelectorAll(selectors.join(','));
    if (!elements.length) {
      document.body.classList.add('rdsg-loaded');
      return;
    }

    elements.forEach(function (el, index) {
      el.classList.add('rdsg-reveal');
      el.style.setProperty('--rdsg-delay', String(Math.min(index * 55, 520)) + 'ms');
    });

    window.requestAnimationFrame(function () {
      document.body.classList.add('rdsg-loaded');
      elements.forEach(function (el) {
        el.classList.add('is-visible');
      });
    });
  }

  function pulseRetroTitle() {
    var title = document.querySelector('.rdsg-category-title');
    if (!title) {
      return;
    }

    title.classList.add('rdsg-title-pulse');
    window.setTimeout(function () {
      title.classList.remove('rdsg-title-pulse');
    }, 1200);
  }

  function initRetroTheme() {
    withRevealSequence();
    pulseRetroTitle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRetroTheme);
  } else {
    initRetroTheme();
  }

  /* Soft navigation (#mfbb-page-container) yeni HTML getirir; script layout’ta olduğu için tekrar çalışmaz.
   * .rdsg-reveal opacity:0 kalır — Forum vb. boş görünür. */
  document.addEventListener('mfbb:soft-nav:loaded', function () {
    initRetroTheme();
  });
})();
