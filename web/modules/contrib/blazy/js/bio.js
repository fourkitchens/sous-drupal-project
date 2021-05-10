/**
 * @file
 * Provides Intersection Observer API loader.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 * @see https://developers.google.com/web/updates/2016/04/intersectionobserver
 */

/* global define, module */
(function (root, factory) {

  'use strict';

  // Inspired by https://github.com/addyosmani/memoize.js/blob/master/memoize.js
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([window.dBlazy], factory);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but only CommonJS-like
    // environments that support module.exports, like Node.
    module.exports = factory(window.dBlazy);
  }
  else {
    // Browser globals (root is window).
    root.Bio = factory(window.dBlazy);
  }
})(this, function (dBlazy) {

  'use strict';

  /**
   * Private variables.
   */
  var _doc = document;
  var _db = dBlazy;
  var _bioTick = 0;
  var _revTick = 0;
  var _disconnected = false;
  var _observed = false;

  /**
   * Constructor for Bio, Blazy IntersectionObserver.
   *
   * @param {object} options
   *   The Bio options.
   *
   * @namespace
   */
  function Bio(options) {
    var me = this;

    me.options = _db.extend({}, me.defaults, options || {});

    // Initializes Blazy IntersectionObserver.
    _disconnected = false;
    _observed = false;
    init(me);
  }

  // Cache our prototype.
  var _proto = Bio.prototype;
  _proto.constructor = Bio;

  // Prepare prototype to interchange with Blazy as fallback.
  _proto.count = 0;
  _proto.counted = -1;
  _proto.erCounted = 0;
  _proto._er = -1;
  _proto._ok = 1;
  _proto.defaults = {
    root: null,
    disconnect: false,
    error: false,
    success: false,
    intersecting: false,
    observing: false,
    successClass: 'b-loaded',
    selector: '.b-lazy',
    errorClass: 'b-error',
    bgClass: 'b-bg',
    rootMargin: '0px',
    threshold: [0]
  };

  // BC for interchanging with bLazy.
  _proto.load = function (elms) {
    var me = this;

    // Manually load elements regardless of being disconnected, or not, relevant
    // for Slick slidesToShow > 1 which rebuilds clones of unloaded elements.
    if (me.isValid(elms)) {
      me.intersecting(elms);
    }
    else {
      _db.forEach(elms, function (el) {
        if (me.isValid(el)) {
          me.intersecting(el);
        }
      });
    }

    if (!_disconnected) {
      me.disconnect();
    }
  };

  _proto.isLoaded = function (el) {
    return el.classList.contains(this.options.successClass);
  };

  _proto.isValid = function (el) {
    return typeof el === 'object' && typeof el.length === 'undefined' && !this.isLoaded(el);
  };

  _proto.prepare = function () {
    // Do nothing, let extenders do their jobs.
  };

  _proto.revalidate = function (force) {
    var me = this;

    // Prevents from too many revalidations unless needed.
    if ((force === true || me.count !== me.counted) && (_revTick < me.counted)) {
      _disconnected = false;
      me.elms = (me.options.root || _doc).querySelectorAll(me.options.selector);
      me.observe();

      _revTick++;
    }
  };

  _proto.intersecting = function (el) {
    var me = this;

    // If not extending/ overriding, at least provide the option.
    if (typeof me.options.intersecting === 'function') {
      me.options.intersecting(el, me.options);
    }

    // Be sure to throttle, or debounce your method when calling this.
    _db.trigger(el, 'bio.intersecting', {options: me.options});

    me.lazyLoad(el);
    me.counted++;

    if (!_disconnected) {
      me.observer.unobserve(el);
    }
  };

  _proto.lazyLoad = function (el) {
    // Do nothing, let extenders do their own lazy, can be images, AJAX, etc.
  };

  _proto.success = function (el, status, parent) {
    var me = this;

    if (typeof me.options.success === 'function') {
      me.options.success(el, status, parent, me.options);
    }

    if (me.erCounted > 0) {
      me.erCounted--;
    }
  };

  _proto.error = function (el, status, parent) {
    var me = this;

    if (typeof me.options.error === 'function') {
      me.options.error(el, status, parent, me.options);
    }

    me.erCounted++;
  };

  _proto.loaded = function (el, status, parent) {
    var me = this;

    el.classList.add(status === me._ok ? me.options.successClass : me.options.errorClass);
    me[status === me._ok ? 'success' : 'error'](el, status, parent);
  };

  _proto.observe = function () {
    var me = this;

    _bioTick = me.elms.length;
    _db.forEach(me.elms, function (entry) {
      // Only observes if not already loaded.
      if (!me.isLoaded(entry)) {
        me.observer.observe(entry);
      }
    });
  };

  _proto.observing = function (entries, observer) {
    var me = this;

    me.entries = entries;
    // Stop watching if already disconnected.
    if (_disconnected) {
      return;
    }

    // Load each on entering viewport.
    _db.forEach(entries, function (entry) {
      // Provides option such as to animate bg or elements regardless position.
      if (typeof me.options.observing === 'function') {
        me.options.observing(entry, observer, me.options);
      }

      // The element is being intersected.
      if (entry.isIntersecting || entry.intersectionRatio > 0) {
        if (!me.isLoaded(entry.target)) {
          me.intersecting(entry.target);
        }

        _bioTick--;
      }
    });

    // Disconnect when all is done.
    me.disconnect();
  };

  _proto.disconnect = function (force) {
    var me = this;

    // Do not disconnect if any error found.
    if (me.erCounted > 0 && !force) {
      return;
    }

    // Disconnect when all entries are loaded, if so configured.
    if (((_bioTick === 0 || me.count === me.counted) && me.options.disconnect) || force) {
      me.observer.disconnect();
      me.count = 0;
      me.elms = null;
      _disconnected = true;
    }
  };

  _proto.destroy = function (force) {
    var me = this;
    me.disconnect(force);
    me.observer = null;
  };

  _proto.disconnected = function () {
    return _disconnected;
  };

  _proto.reinit = function () {
    _disconnected = false;
    _observed = false;
    init(this);
  };

  function init(me) {
    var config = {
      rootMargin: me.options.rootMargin,
      threshold: me.options.threshold
    };

    me.elms = (me.options.root || _doc).querySelectorAll(me.options.selector + ':not(.' + me.options.successClass + ')');
    me.count = me.elms.length;
    me.windowWidth = _db.windowWidth();

    me.prepare();

    // Initializes the IO.
    me.observer = new IntersectionObserver(me.observing.bind(me), config);

    // Observes once on the page load regardless multiple observer instances.
    // Possible as we nullify the root option to allow querying the DOM once.
    // Should you need to re-validate, or re-observe, just call ::observe().
    if (!_observed) {
      me.observe();
      _observed = true;
    }
  }

  return Bio;

});
