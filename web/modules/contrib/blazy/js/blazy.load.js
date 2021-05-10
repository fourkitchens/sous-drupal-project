/**
 * @file
 * Provides native, Intersection Observer API, or bLazy lazy loader.
 */

(function (Drupal, drupalSettings, _db, window, document) {

  'use strict';

  var _dataAnimation = 'data-animation';
  var _dataDimensions = 'data-dimensions';
  var _dataBg = 'data-backgrounds';
  var _dataRatio = 'data-ratio';
  var _isNativeExecuted = false;
  var _resizeTick = 0;

  /**
   * Blazy public methods.
   *
   * @namespace
   */
  Drupal.blazy = Drupal.blazy || {
    context: null,
    init: null,
    instances: [],
    items: [],
    windowWidth: 0,
    blazySettings: drupalSettings.blazy || {},
    ioSettings: drupalSettings.blazyIo || {},
    revalidate: false,
    options: {},
    globals: function () {
      var me = this;
      var commons = {
        success: me.clearing.bind(me),
        error: me.clearing.bind(me),
        selector: '.b-lazy',
        errorClass: 'b-error',
        successClass: 'b-loaded'
      };

      return _db.extend(me.blazySettings, me.ioSettings, commons);
    },

    clearing: function (el) {
      var me = this;
      var cn = _db.closest(el, '.media');
      var an = _db.closest(el, '[' + _dataAnimation + ']');

      // Clear loading classes.
      me.clearLoading(el);

      // Reevaluate the element.
      me.reevaluate(el);

      // Container might be the el itself for BG, do not NULL check here.
      me.updateContainer(el, cn);

      // Supports blur, animate.css for CSS background, picture, image, media.
      if (an !== null || me.has(el, _dataAnimation)) {
        _db.animate(an !== null ? an : el);
      }

      // Provides event listeners for easy overrides without full overrides.
      // Runs before native to allow native use this on its own onload event.
      _db.trigger(el, 'blazy.done', {options: me.options});

      // Initializes the native lazy loading once the first found is loaded.
      if (!_isNativeExecuted) {
        _db.trigger(me.context, 'blazy.native', {options: me.options});

        _isNativeExecuted = true;
      }
    },

    clearLoading: function (el) {
      // The .b-lazy element can be attached to IMG, or DIV as CSS background.
      // The .(*)loading can be .media, .grid, .slide__content, .box, etc.
      var loaders = [
        el,
        _db.closest(el, '.is-loading'),
        _db.closest(el, '[class*="loading"]')
      ];

      _db.forEach(loaders, function (loader) {
        if (loader !== null) {
          loader.className = loader.className.replace(/(\S+)loading/g, '');
        }
      });
    },

    isLoaded: function (el) {
      return el !== null && el.classList.contains(this.options.successClass);
    },

    reevaluate: function (el) {
      var me = this;
      var ie = el.classList.contains('b-responsive') && el.hasAttribute('data-pfsrc');

      // In case an error, try forcing it, once.
      if (me.init !== null && _db.hasClass(el, me.options.errorClass) && !_db.hasClass(el, 'b-checked')) {
        el.classList.add('b-checked');

        // This is a rare case, hardly called, just nice to have for errors.
        window.setTimeout(function () {
          if (me.has(el, _dataBg)) {
            _db.updateBg(el, me.options.mobileFirst);
          }
          else {
            me.init.load(el);
          }
        }, 100);
      }

      // @see http://scottjehl.github.io/picturefill/
      if (window.picturefill && ie) {
        window.picturefill({
          reevaluate: true,
          elements: [el]
        });
      }
    },

    has: function (el, attribute) {
      return el !== null && el.hasAttribute(attribute);
    },

    contains: function (el, name) {
      return el !== null && el.classList.contains(name);
    },

    updateContainer: function (el, cn) {
      var me = this;
      var isPicture = _db.equal(el.parentNode, 'picture') && me.has(cn, _dataDimensions);

      // Fixed for effect Blur messes up Aspect ratio Fluid calculation.
      window.setTimeout(function () {
        if (me.isLoaded(el)) {
          // Adds context for effetcs: blur, etc. considering BG, or just media.
          (me.contains(cn, 'media') ? cn : el).classList.add('is-b-loaded');

          // Only applies to ratio fluid.
          if (isPicture) {
            me.updatePicture(el, cn);
          }

          // Basically makes multi-breakpoint BG work for IO or old bLazy once.
          if (me.has(el, _dataBg)) {
            _db.updateBg(el, me.options.mobileFirst);
          }
        }
      });
    },

    updatePicture: function (el, cn) {
      var me = this;
      var pad = Math.round(((el.naturalHeight / el.naturalWidth) * 100), 2);

      cn.style.paddingBottom = pad + '%';

      // Swap all aspect ratio once to reduce abrupt ratio changes for the rest.
      if (me.instances.length > 0) {
        var picture = function (elm) {
          if (!('blazyInstance' in elm) && !('blazyUniform' in elm)) {
            return;
          }

          if ((elm.blazyInstance === cn.blazyInstance) && (_resizeTick > 1 || !('isBlazyPicture' in elm))) {
            _db.trigger(elm, 'blazy.uniform.' + elm.blazyInstance, {pad: pad});
            elm.isBlazyPicture = true;
          }
        };

        // Uniform sizes must apply to each instance, not globally.
        _db.forEach(me.instances, function (elm) {
          Drupal.debounce(picture(elm), 201, true);
        }, me.context);
      }
    },

    /**
     * Attempts to fix for Views rewrite stripping out data URI causing 404.
     *
     * E.g.: src="image/jpg;base64 should be src="data:image/jpg;base64.
     * The "Placeholder" 1px.gif via Blazy UI costs extra HTTP requests. This is
     * a less costly solution, but not bulletproof due to being client-side
     * which means too late to the party. Yet not bad for 404s below the fold.
     * This must be run before any lazy (native, bLazy or IO) kicks in.
     *
     * @todo remove if a permanent non-client available other than Placeholder.
     */
    fixMissingDataUri: function () {
      var me = this;
      var doc = me.context;
      var sel = me.options.selector + '[src^="image"]:not(.' + me.options.successClass + ')';
      var els = doc.querySelector(sel) === null ? [] : doc.querySelectorAll(sel);

      var fixDataUri = function (img) {
        var src = img.getAttribute('src');
        if (src.indexOf('base64') !== -1 || src.indexOf('svg+xml') !== -1) {
          img.setAttribute('src', src.replace('image', 'data:image'));
        }
      };

      if (els.length > 0) {
        _db.forEach(els, fixDataUri);
      }
    },

    /**
     * Updates the dynamic multi-breakpoint aspect ratio: bg, picture or image.
     *
     * This only applies to Responsive images with aspect ratio fluid.
     * Static ratio (media--ratio--169, etc.) is ignored and uses CSS instead.
     *
     * @param {HTMLElement} cn
     *   The .media--ratio--fluid container HTML element.
     */
    updateRatio: function (cn) {
      var me = this;
      var el = _db.closest(cn, '.blazy');
      var dimensions = _db.parse(cn.getAttribute(_dataDimensions));

      if (!dimensions) {
        me.updateFallbackRatio(cn);
        return;
      }

      // For picture, this is more a dummy space till the image is downloaded.
      var isPicture = cn.querySelector('picture') !== null && _resizeTick > 0;
      var pad = _db.activeWidth(dimensions, isPicture);

      // Provides marker for grouping between multiple instances.
      cn.blazyInstance = 'blazyInstance' in el ? el.blazyInstance : null;
      if (pad !== 'undefined') {
        cn.style.paddingBottom = pad + '%';
      }

      // Fix for picture or bg element with resizing.
      if (_resizeTick > 0 && (isPicture || me.has(cn, _dataBg))) {
        me.updateContainer((isPicture ? cn.querySelector('img') : cn), cn);
      }
    },

    updateFallbackRatio: function (cn) {
      // Only rewrites if the style is indeed stripped out by Twig, and not set.
      if (!cn.hasAttribute('style') && cn.hasAttribute(_dataRatio)) {
        cn.style.paddingBottom = cn.getAttribute(_dataRatio) + '%';
      }
    },

    /**
     * Swap lazy attributes to let supportive browsers lazy load them.
     *
     * This means Blazy and even IO should not lazy-load them any more.
     * Ensures to not touch lazy-loaded AJAX, or likely non-supported elements:
     * Video, DIV, etc. Only IMG and IFRAME are supported for now.
     * Due to native init is deferred, the first row is still using IO/ bLazy.
     */
    doNativeLazy: function () {
      var me = this;

      if (!me.isNativeLazy()) {
        return;
      }

      var doc = me.context;
      var sel = me.options.selector + '[loading]:not(.' + me.options.successClass + ')';

      me.items = doc.querySelector(sel) === null ? [] : doc.querySelectorAll(sel);
      if (me.items.length === 0) {
        return;
      }

      var onNativeEvent = function (e) {
        var el = e.target;
        var er = e.type === 'error';

        // Refines based on actual result, runs clearing, animation, etc.
        el.classList.add(me.options[er ? 'errorClass' : 'successClass']);
        me.clearing(el);

        _db.unbindEvent(el, e.type, onNativeEvent);
      };

      var doNative = function (el) {
        // Reset attributes, and let supportive browsers lazy load natively.
        _db.setAttrs(el, ['srcset', 'src'], true);

        // Also supports PICTURE or (future) VIDEO which contains SOURCEs.
        _db.setAttrsWithSources(el, false, true);

        // Blur thumbnail is just making use of the swap due to being small.
        if (me.contains(el, 'b-blur')) {
          el.removeAttribute('loading');
        }
        else {
          // Mark it loaded to prevent bLazy/ IO to do any further work.
          el.classList.add(me.options.successClass);

          // Attempts to make nice with the harsh native, defer clearing, etc.
          _db.bindEvent(el, 'load', onNativeEvent);
          _db.bindEvent(el, 'error', onNativeEvent);
        }
      };

      var onNative = function () {
        _db.forEach(me.items, doNative);
      };

      _db.bindEvent(me.context, 'blazy.native', onNative, {once: true});
    },

    isNativeLazy: function () {
      return 'loading' in HTMLImageElement.prototype;
    },

    isIo: function () {
      return this.ioSettings && this.ioSettings.enabled && 'IntersectionObserver' in window;
    },

    isRo: function () {
      return 'ResizeObserver' in window;
    },

    isBlazy: function () {
      return !this.isIo() && 'Blazy' in window;
    },

    forEach: function (context) {
      var blazies = context.querySelectorAll('.blazy:not(.blazy--on)');

      // Various use cases: w/o formaters, custom, or basic, and mixed.
      // The [data-blazy] is set by the module for formatters, or Views gallery.
      if (blazies.length > 0) {
        _db.forEach(blazies, doBlazy, context);
      }

      // Initializes blazy, we'll decouple features from lazy load scripts.
      // We'll revert to 2.1 if any issue with this.
      initBlazy(context);
    },

    run: function (opts) {
      return this.isIo() ? new BioMedia(opts) : new Blazy(opts);
    },

    afterInit: function () {
      var me = this;
      var doc = me.context;
      var rObserver = false;
      var ratioItems = doc.querySelector('.media--ratio') === null ? [] : doc.querySelectorAll('.media--ratio');
      var shouldLoop = ratioItems.length > 0;

      var loopRatio = function (entries) {
        me.windowWidth = _db.windowWidth();

        // BC with bLazy, native/IO doesn't need to revalidate, bLazy does.
        // Scenarios: long horizontal containers, Slick carousel slidesToShow >
        // 3. If any issue, add a class `blazy--revalidate` manually to .blazy.
        if (!me.isNativeLazy() && (me.isBlazy() || me.revalidate)) {
          me.init.revalidate(true);
        }

        if (shouldLoop) {
          _db.forEach(entries, function (entry) {
            me.updateRatio('target' in entry ? entry.target : entry);
          }, doc);
        }

        _resizeTick++;
        return false;
      };

      var checkRatio = function () {
        return me.isRo() ? new ResizeObserver(loopRatio) : loopRatio(ratioItems);
      };

      // Checks for aspect ratio, onload event is a bit later.
      // Uses ResizeObserver for modern browsers, else degrades.
      rObserver = checkRatio();
      if (rObserver) {
        if (shouldLoop) {
          _db.forEach(ratioItems, function (entry) {
            rObserver.observe(entry);
          }, doc);
        }
      }
      else {
        _db.bindEvent(window, 'resize', Drupal.debounce(checkRatio, 200, true));
      }
    }
  };

  /**
   * Initialize the blazy instance, either basic, advanced, or native.
   *
   * @param {HTMLElement} context
   *   This can be document, or anything weird.
   */
  var initBlazy = function (context) {
    var me = Drupal.blazy;
    var documentElement = context instanceof HTMLDocument ? context : _db.closest(context, 'html');
    var opts = {};

    opts.mobileFirst = opts.mobileFirst || false;

    // Weirdo: documentElement is null after colorbox cbox_close event.
    documentElement = documentElement || document;

    // Set docroot in case we are in an iframe.
    if (!document.documentElement.isSameNode(documentElement)) {
      opts.root = documentElement;
    }

    me.options = _db.extend({}, me.globals(), opts);
    me.context = documentElement;

    // Old bLazy, not IO, might need scrolling CSS selector like Modal library.
    // A scrolling modal with an iframe like Entity Browser has no issue since
    // the scrolling container is the entire DOM. Another use case is parallax.
    var scrollElms = '#drupal-modal, .is-b-scroll';
    if (me.options.container) {
      scrollElms += ', ' + me.options.container.trim();
    }
    me.options.container = scrollElms;

    // Attempts to fix for Views rewrite stripping out data URI causing 404.
    me.fixMissingDataUri();

    // Swap lazy attributes to let supportive browsers lazy load them.
    me.doNativeLazy();

    // Put the blazy/IO instance into a public object for references/ overrides.
    // If native lazy load is supported, the following will skip internally.
    me.init = me.run(me.options);

    // Runs after init.
    me.afterInit();
  };

  /**
   * Blazy utility functions.
   *
   * @param {HTMLElement} elm
   *   The .blazy/[data-blazy] container, not the lazyloaded .b-lazy element.
   *
   * @todo reenable initBlazy here if any issue with the following:
   *   Each [data-blazy] may or may not:
   *     - be ajaxified, be lightboxed, have uniform or different sizes, and
   *       have few more unique features per instance, etc.
   */
  function doBlazy(elm) {
    var me = Drupal.blazy;
    var dataAttr = elm.getAttribute('data-blazy');
    var opts = (!dataAttr || dataAttr === '1') ? {} : (_db.parse(dataAttr) || {});
    var isUniform = me.contains(elm, 'blazy--field') || me.contains(elm, 'block-grid') || me.contains(elm, 'blazy--uniform');
    var instance = (Math.random() * 10000).toFixed(0);
    var eventId = 'blazy.uniform.' + instance;
    var localItems = elm.querySelector('.media--ratio') === null ? [] : elm.querySelectorAll('.media--ratio');

    me.options = _db.extend(me.options, opts);
    me.revalidate = me.revalidate || elm.classList.contains('blazy--revalidate');
    elm.classList.add('blazy--on');
    elm.blazyInstance = instance;

    if (isUniform) {
      elm.blazyUniform = true;
    }

    me.instances.push(elm);

    var swapRatio = function (e) {
      var pad = e.detail.pad || 0;

      if (pad > 10) {
        _db.forEach(localItems, function (cn) {
          cn.style.paddingBottom = pad + '%';
        }, elm);
      }
    };

    // Reduces abrupt ratio changes for the rest after the first loaded.
    // To support resizing, use debounce. To disable use {once: true}.
    if (isUniform && localItems.length > 0) {
      _db.bindEvent(elm, eventId, swapRatio);
    }
  }

  /**
   * Attaches blazy behavior to HTML element identified by .blazy/[data-blazy].
   *
   * The .blazy/[data-blazy] is the .b-lazy container, might be .field, etc.
   * The .b-lazy is the individual IMG, IFRAME, PICTURE, VIDEO, DIV, BODY, etc.
   * The lazy-loaded element is .b-lazy, not its container. Note the hypen (b-)!
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazy = {
    attach: function (context) {
      // Drupal.attachBehaviors already does this so if this is necessary,
      // someone does an invalid call. But let's be robust here.
      // Note: context can be unexpected <script> element with Media library.
      context = context || document;

      // Originally identified at D7, yet might happen at D8 with AJAX.
      // Prevents jQuery AJAX messes up where context might be an array.
      if ('length' in context) {
        context = context[0];
      }

      // Runs Blazy with multi-serving images, and aspect ratio supports.
      // W/o [data-blazy] to address various scenarios like custom simple works,
      // or within Views UI which is not easy to set [data-blazy] via UI.
      _db.once(Drupal.blazy.forEach(context));
    }
  };

}(Drupal, drupalSettings, dBlazy, this, this.document));
