.
***
***
.
# <a name="faq"></a>FAQ

## CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedback from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.


## PROGRAMATICALLY
[blazy.api.php](https://git.drupalcode.org/project/blazy/blob/8.x-2.x/blazy.api.php)


## BLAZY VS. B-LAZY
`blazy` is the module namespace. `b-lazy` is the default CSS class to lazy load.

* The `blazy` class is applied to the **top level container**, e,g.. `.field`,
  `.view`, `.item-list`, etc., those which normally contain item collection.
  In this container, you can feed any `bLazy` script options into `[data-blazy]`
  attribute to override existing behaviors per particular page, only if needed.
* The `b-lazy` class is applied to the **target item** to lazy load, normally
  the children of `.blazy`, but not always. This can be IMG, VIDEO, DIV, etc.

## WHAT `BLAZY` CSS CLASS IS FOR?
Aside from the fact that a module must reserve its namespace including for CSS
classes, the `blazy` is actually used to limit the scope to scan document.
Rather than scanning the entire DOM, you limit your work to a particular
`.blazy` container, and these can be many, no problem. This also allows each
`.blazy` container to have unique features, such as ones with multi-breakpoint
images, others with regular images; ones with a lightbox, others with
image to iframe; ones with CSS background, others with regular images; etc.
right on the same page. This is only possible and efficient within the `.blazy`
scope.

## WHY NOT `BLAZY__LAZY` FOR `B-LAZY`?
`b-lazy` is the default CSS class reserved by JS script. Rather than recreating
a new one, respecting the defaults is better. Following BEM standard is not
crucial for most JS generated CSS classes. Uniqueness matters.

## NATIVE LAZY LOADING
Native lazy loading is supported by Chrome 76+ as of 01/2019. Blazy or IO will
be used as fallback for other browsers instead. Currently the offset/ threshold
before loading is hard-coded to [800px at Chrome](https://cs.chromium.org/chromium/src/third_party/blink/renderer/core/frame/settings.json5?l=971-1003&rcl=e8f3cf0bbe085fee0d1b468e84395aad3ebb2cad),
so it might only be good for super tall pages for now, be aware.
[Read more](https://web.dev/native-lazy-loading/)

This also may trick us to think lazy load not work, check out browsers' Network
tab to verify that it still does work.

**UPDATE 2020-04-24**: Added a delay to only lazy load once the first found is
  loaded, see [#3120696](https://drupal.org/node/3120696)

## ANIMATE.CSS INTEGRATION
Blazy container (`.media`) can be animated using
[animate.css](https://github.com/daneden/animate.css). The container is chosen
to be the animated element so to support various use cases:
CSS background, picture, image, or rich media contents.

See [GridStack](https://drupal.org/project/gridstack) 2.6+ for the `animate.css`
samples at Layout Builder pages.

To replace **Blur** effect with `animate.css` thingies, implements two things:  
1. **Globally**: `hook_blazy_image_effects_alter` and add `animate.css` classes
   to make them available for select options at Blazy UI.  
2. **Fine grained**: `hook_blazy_settings_alter`, and replace a setting named
   `fx` with one of `animate.css` CSS classes, adjust conditions based settings.

### Requirements:

* The `animate.css` library included in your theme, or via `animate_css` module.
* Data attributes: `data-animation`, with optional: `data-animation-duration`,
  `data-animation-delay` and `data-animation-iteration-count`, as seen below.

```
function MYTHEME_preprocess_blazy(&$variables) {
  $settings = &$variables['settings'];
  $attributes = &$variables['attributes'];

  // Be sure to limit the scope, only animate for particular conditions.
  if ($settings['entity_id'] == 123
    && $settings['field_name'] == 'field_media_animated')  {

    // This was taken care of by feeding $settings['fx'], or hard-coded here.
    $attributes['data-animation'] = 'wobble';

    // The following can be defined manually.
    $attributes['data-animation-duration'] = '3s';
    $attributes['data-animation-delay'] = '.3s';
    // Iteration can be any number, or infinite.
    $attributes['data-animation-iteration-count'] = 'infinite';
  }
}
```
