/**
 * @file
 * Provides a fullscreen video view for Intense, Slick Browser, etc.
 */

(function (Drupal, _db, window, document) {

  'use strict';

  Drupal.blazyBox = Drupal.blazyBox || {};

  Drupal.blazyBox.el = document.querySelector('.blazybox');

  /**
   * Theme function for a fullscreen lightbox video container.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyBox = function () {
    var html;

    html = '<div id="blazybox" class="blazybox visually-hidden" tabindex="-1" role="dialog" aria-hidden="true">';
    html += '<div class="blazybox__content">' + Drupal.t('Dynamic video content.') + '</div>';
    html += '<button class="blazybox__close" data-role="none">&times;</button>';
    html += '</div>';

    return html;
  };

  /**
   * Theme function for a standalone fullscreen video.
   *
   * @param {Object} settings
   *   An object containing the embed url.
   *
   * @return {HTMLElement}
   *   Returns a HTMLElement object.
   */
  Drupal.theme.blazyBoxMedia = function (settings) {
    var html;

    html = '<div class="media media--fullscreen">';
    html += '<iframe src="' + settings.embedUrl + '" width="100%" height="100%" allowfullscreen></iframe>';
    html += '</div>';

    return html;
  };

  /**
   * Open the blazyBox.
   *
   * @param {string} embedUrl
   *   The video embed url.
   */
  Drupal.blazyBox.open = function (embedUrl) {
    var me = this;
    var mediaEl = Drupal.theme('blazyBoxMedia', {embedUrl: embedUrl});

    Drupal.attachBehaviors(me.el);
    me.el.querySelector('.blazybox__content').innerHTML = mediaEl;

    me.el.classList.remove('visually-hidden');
    me.el.setAttribute('aria-hidden', false);
    document.body.classList.add('is-blazybox--open');
  };

  /**
   * Attach the blazyBox.
   */
  Drupal.blazyBox.attach = function () {
    if (document.querySelector('.blazybox') === null) {
      // https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentHTML
      document.body.insertAdjacentHTML('beforeend', Drupal.theme('blazyBox'));
    }
  };

  /**
   * Close the blazyBox.
   *
   * @param {Event} e
   *   The mouse event triggering the close.
   */
  Drupal.blazyBox.close = function (e) {
    var el = Drupal.blazyBox.el;
    e.preventDefault();

    el.classList.add('visually-hidden');
    el.setAttribute('aria-hidden', true);
    el.querySelector('.blazybox__content').innerHTML = '';
    document.body.classList.remove('is-blazybox--open');
  };

  /**
   * BlazyBox utility functions.
   *
   * @param {HTMLElement} box
   *   The blazybox HTML element.
   */
  function doBlazyBox(box) {
    box.classList.add('blazybox--on');
    Drupal.blazyBox.el = box;

    _db.on(Drupal.blazyBox.el, 'click', '.blazybox__close', Drupal.blazyBox.close);
  }

  /**
   * Attaches Blazybox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyBox = {
    attach: function (context) {
      var boxes = context.querySelectorAll('.blazybox:not(.blazybox--on)');
      if (boxes.length > 0) {
        _db.once(_db.forEach(boxes, doBlazyBox, context));
      }
    }
  };

})(Drupal, dBlazy, this, this.document);
