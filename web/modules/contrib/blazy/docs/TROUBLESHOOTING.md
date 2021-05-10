.
***
***
.
# <a name="troubleshooting"></a>TROUBLESHOOTING
* Blazy and its sub-modules -- Slick, GridStack, etc. are tightly coupled.
  Be sure to have the latest release date or matching versions in the least.
  DEV for DEV, Beta for Beta, etc. Mismatched versions may lead to errors
  especially before having RCs. Mismatched branches will surely be errors.
* Resizing is not supported. Just reload the page. **The main reason**:
  When being resized, the browser gave no data about pixel ratio from desktop
  to mobile, not vice versa. Unless delayed for 4s+, not less, which is of
  course unacceptable.
* Images are gone, only eternal blue loader is flipping like a drunk butterfly.
  Solution: ensures that blazy library is loaded. And temporarily switch to
  stock Bartik themes.
* Press F12 at any browser, and see the errors at the browser console. Any JS
  error will prevent Blazy from working identified by eternal blue loaders.
* Images are collapsed. Solution: choose one of the Aspect ratio.
* Images or videos aren't responsive. Solution: choose one of the Aspect ratio.
* Images are distorted. Solution: choose the correct Aspect ratio. If unsure,
  choose "fluid" to let the module calculate aspect ratio automatically.

  [Check out few aspect ratio samples](https://cgit.drupalcode.org/blazy/tree/docs/ASPECT_RATIO.md)



## 1. VIEWS GOTCHAS
Blazy provides a simple Views field for File Entity, and Media. Also a Blazy
Grid views style plugin.

When using Blazy formatter within Views, check **Use field template** under
**Style settings**, if trouble with Blazy Formatter as a stand alone Views
output.

On the contrary, uncheck **Use field template**, when Blazy formatter
is embedded inside another module such as Slick so to pass the renderable
array to work with accordingly.

This is a Views common gotcha with field formatter, so be aware of it.
If confusing, just toggle **Use field template**, and see the output. You'll
know which works.


## 2. BLAZY GRID WITH SINGLE VALUE FIELD (D7 ONLY)
This is no issue at D8. Blazy Grid formatter is designed for multi-value fields.
Unfortunately no handy way to disable formatters for single value at D7. So
the formatter is available even for single value, but not actually
functioning. Please ignore it till we can get rid of it at D7, if possible,
without extra legs.

## 3. MIN-WIDTH
If the images appear to be shrink within a **floating** container, add
some expected width or min-width to the parent container via CSS accordingly.
Non-floating image parent containers aren't affected.

## 4. MIN-HEIGHT
Add a min-height CSS to individual element to avoid layout reflow if not using
**Aspect ratio** or when **Aspect ratio** is not supported such as with
Responsive image. Otherwise some collapsed image containers will defeat the
purpose of lazyloading. When using CSS background, the container may also be
collapsed.

### SOLUTIONS
Both layout reflow and lazyloading delay issues are actually taken care of
if **Aspect ratio** option is enabled in the first place.

Adjust, and override blazy CSS/ JS files accordingly.

## 5. BLAZY FILTER
Blazy Filter must run after **Align/ Caption filters** as otherwise the required
CSS class `b-lazy` will be moved into `<figure>` elements and make Blazy fail
with JS error due to not finding the required `SRC` and `[data-src]` attributes.
**Align/ Caption filters** output are respected and moved into Blazy markups
accordingly when Blazy Filter runs after them.

Blazy Filter is useless and broken when you enable **Media embed** or
**Display embedded entities**. You can disable Blazy Filter in favor of Blazy
formatter embedded inside **Media embed** or **Display embedded entities**
instead. However it might be useful for User Generated Contents (UGC) where
Entity/Media Embed are likely more for privileged users, editors, admins, alike.
Or when Entity/Media Embed is disabled.

## 6. INTERSECTION OBSERVER API
* **IntersectionObserver API** is not loading all images, try disabling
  **Disconnect** option at Blazy UI.
* **IntersectionObserver API** is not working with Slick `slidesToShow > 1`, try
  disabling Slick `centerMode`. If still failing, choose one of the 4 lazy
  load options, except Blazy.

## 7. BLUR IMAGE EFFECT
`/admin/config/media/blazy`

The `Image effect` Blur will override `Placeholder` option.
Will use `Thumbnail style` option at Blazy formatters for the placeholder with
fallback to core `Thumbnail` image style.

**For best results:**

* Choose `Aspect ratio` option, non-fluid is better;
* Use similar aspect ratio for both `Thumbnail style` and `Image style`;
* Adjust `Offset` and or `threshold`;
* The smaller the better.

Use `hook_blazy_image_effects_alter()` to add more effects -- curtain, fractal,
slice, whatever.

**Limitations**:  
Currently only works with a proper `Aspect ratio` as otherwise collapsed image.
Be sure to add one. If not, add regular CSS `width: 100%` to the blurred
image if doable with your design.

## 8. ASPECT RATIO
**UPDATE 05/02/2020**:   
Blazy RC7+ is 99% integrated with Responsive image, including
CSS background and the notorious aspect ratio **Fluid**. The remaining 1% is
some unknown glicthes.

Aspect ratio was never supported for Responsive image till Blazy 2.rc7+, <s>not
fully though. One remaining issue is to make Aspect ratio `Fluid` work for:
CSS background + Picture element.</s>

Any **fixed** Aspect ratio (`4:3, 16:9`, etc) should immediately work as long as
you understand what it means.

Aspect ratio `Fluid` worked with
[**custom breakpoints**](https://www.drupal.org/node/3105243) (deprecated),
<s>not Responsive image, yet. If you want Aspect ratio for Responsive image,
choose anything but `Fluid`.</s>

Any **fixed** Aspect ratio (`4:3, 16:9`, etc), but `Fluid`, wants consistent
aspect ratio down to mobile, which means it won't work with art direction
technique, or Picture element. [Check out few aspect ratio samples](https://cgit.drupalcode.org/blazy/tree/docs/ASPECT_RATIO.md)

Temporary workaround is to add regular CSS `width: 100%` to the controlling
image if doable with your design. And a `min-height` per breakpoint via CSS
mediaqueries.

Aspect ratio fixes many issues with lazyloaded element -- collapsed, distorted,
excessive height, layout reflow, etc., including making iframe fully responsive.
However it doesn't fix everything. Please bear with it.

**If you have display issues, the correct Aspect ratio is your first best bet.**

Depending on your particular issue, **enable or disable**, either way, is your
potential solution. One good sample when Aspect ratio makes no sense is
GridStack gapless grids. Image sizes, hence Aspect ratio, cannot be applied
to gapless grids. Aspect ratio is based on image sizes, not grid sizes.


## 9. BLAZY WITHIN SCROLLING CONTAINER DOES NOT LOAD
`/admin/config/media/blazy`

**Note**: `IO` does not need it, old `bLazy` does.

If you put Blazy within a scrolling container, provide valid comma separated CSS
selectors, except `#drupal-modal, .is-b-scroll`, e.g.: `#my-scrolling-container,
.another-scrolling-container`.

A known scrolling container is `#drupal-modal` like seen at **Media library**.
A scrolling modal with an iframe like **Entity Browser** has no issue since the
scrolling container is the entire DOM. Must know `.blazy` parent container which
has CSS rules containing `overflow` with values anything but `hidden` such as
`auto` or `scroll`. Press `F12` at any browser to inspect elements.

Default to known `#drupal-modal, .is-b-scroll`.
The `.is-b-scroll` can be used when Blazy UI is unreachable without extra legs.

## 10. LINKED FIELD INTEGRATION
Under `Media switcher` option, only `Image to iFrame` makes sense. The rest like
`Image to lightboxes`, or `Image linked to content` will obviously be ignored
since these will output A tag just like what Linked Field does.
Alternatively leave `Media switcher` empty, if no videos are mixed with images.
With `Image to iFrame`, the good thing is video will be still playable, and the
image be linked as required. Best of Both Worlds for real.

## 11. BROKEN MODULES
Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is provided, running `drush cr` during
DEV releases should fix most issues as we add new services, or change things.
If you don't drush, before any module update, always open:

[Performance](/admin/config/development/performance)

And so you are ready to hit **Clear all caches** if any issue.
Only at worst case, know how to run
https://www.drupal.org/project/registry_rebuild safely.

Check out [Update SOP](#updating) for the non-drush users.
