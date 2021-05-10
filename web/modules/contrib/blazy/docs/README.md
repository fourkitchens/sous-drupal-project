
# <a name="top"> </a>CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Features](#features)
 * [Updating](#updating)
 * [Troubleshooting](#troubleshooting)
 * [Roadmap](#roadmap)
 * [FAQ](#faq)
 * [Aspect ratio template](#aspect-ratio-template)
 * [Contribution](#contribution)
 * [Maintainers](#maintainers)


.
***
***
.
# <a name="introduction"></a>INTRODUCTION
Provides integration with bLazy and or Intersection Observer API, or browser
native lazy loading to lazy load and multi-serve images to save bandwidth and
server requests. The user will have faster load times and save data usage if
they don't browse the whole page.


.
***
***
.
# <a name="requirements"> </a>REQUIREMENTS
1. bLazy library:
   * [Download bLazy](https://github.com/dinbror/blazy)
   * Extract it as is, rename **blazy-master** to **blazy**, so the assets are:

      + **/libraries/blazy/blazy.js**

2. Media and Filter module in core.


.
***
***
.
# <a name="recommended-modules"> </a>RECOMMENDED MODULES
* [Markdown](https://www.drupal.org/project/markdown)

  To make reading this README a breeze at [Blazy help](/admin/help/blazy_ui)


## MODULES THAT INTEGRATE WITH OR REQUIRE BLAZY
* [Ajaxin](https://www.drupal.org/project/ajaxin)
* [Intersection Observer](https://www.drupal.org/project/io)
* [Blazy PhotoSwipe](https://www.drupal.org/project/blazy_photoswipe)
* [GridStack](https://www.drupal.org/project/gridstack)
* [Outlayer](https://www.drupal.org/project/outlayer)
* [Intense](https://www.drupal.org/project/intense)
* [Mason](https://www.drupal.org/project/mason)
* [Slick](https://www.drupal.org/project/slick)
* [Slick Lightbox](https://www.drupal.org/project/slick_lightbox)
* [Slick Views](https://www.drupal.org/project/slick_views)
* [Slick Paragraphs](https://www.drupal.org/project/slick_paragraphs)
* [Slick Browser](https://www.drupal.org/project/slick_browser)
* [Jumper](https://www.drupal.org/project/jumper)
* [Zooming](https://www.drupal.org/project/zooming)
* [ElevateZoom Plus](https://www.drupal.org/project/elevatezoomplus)


Most duplication efforts from the above modules will be merged into
`\Drupal\blazy\Dejavu`, or anywhere else namespace.


**What dups?**

The most obvious is the removal of formatters from Intense, Zooming,
Slick Lightbox, Blazy PhotoSwipe, and other (quasi-)lightboxes. Any lightbox
supported by Blazy can use Blazy, or Slick formatters if applicable instead.
We do not have separate formatters when its prime functionality is embedding
a lightbox, or superceded by Blazy.

Blazy provides a versatile and reusable formatter for a few known lightboxes
with extra advantages:

lazyloading, grid, multi-serving images, Responsive image,
CSS background, captioning, etc.

Including making those lightboxes available for free at Views Field for
File entity, Media and Blazy Filter for inline images.

If you are developing lightboxes and using Blazy, I would humbly invite you
to give Blazy a try, and consider joining forces with Blazy, and help improve it
for the above-mentioned advantages. We are also continuously improving and
solidifying the API to make advanced usages a lot easier, and DX friendly.
Currently, of course, not perfect, but have been proven to play nice with at
least 7 lightboxes, and likely more.


## SIMILAR MODULES
[Lazyloader](https://www.drupal.org/project/lazyloader)


.
***
***
.
# <a name="installation"> </a>INSTALLATION
1. **MANUAL:**

   Install the module as usual, more info can be found on:

   [Installing Drupal 8 Modules](https://drupal.org/node/1897420)

2. **COMPOSER:**

   There are various ways to install third party bower/npm asset libraries.
   Check out any below suitable to your workflow:

     + [#3021902](https://www.drupal.org/project/blazy/issues/3021902)
     + [#2907371](https://www.drupal.org/project/slick/issues/2907371)
     + [#2907371](https://www.drupal.org/project/slick/issues/2907371#comment-12882235)  

   It is up to you to decide which works best. Composer is not designed to
   manage JS, CSS or HTML framework assets. It is for PHP. Then come Composer
   plugins, and other workarounds to make Composer workflow easier. As many
   alternatives, it is not covered here. Please find more info on the
   above-mentioned issues.


.
***
***
.
# <a name="configuration"> </a>CONFIGURATION
Visit the following to configure and make use of Blazy:

1. `/admin/config/media/blazy`

   Enable Blazy UI sub-module first, otherwise regular **Access denied**.
   Contains few global options, including enabling support to bring core
   Responsive image into blazy-related formatters.
   Blazy UI can be uninstalled at production later without problems.

2. Visit any entity types:

  + `/admin/structure/types`
  + `/admin/structure/block/block-content/types`
  + `/admin/structure/paragraphs_type`
  + etc.

   Use Blazy as a formatter under **Manage display** for the supported fields:
   Image, Media, Entity reference, or even Text.

3. `/admin/structure/views`

   Use Blazy Grid as standalone blocks, or pages.


### USAGES: BLAZY FOR MULTIMEDIA GALLERY VIA VIEWS UI
#### Using **Blazy Grid**
1. Add a Views style **Blazy Grid** for entities containing Media or Image.
2. Add a Blazy formatter for the Media or Image field.
3. Add any lightbox under **Media switcher** option.
4. Limit the values to 1 under **Multiple field settings** > **Display**.

#### Without **Blazy Grid**
If you can't use **Blazy Grid** for a reason, maybe having a table, HTML list,
etc., try the following:

1. Add a CSS class under **Advanced > CSS class** for any reasonable supported/
  supportive lightbox in the format **blazy--LIGHTBOX-gallery**, e.g.:
  + **blazy--colorbox-gallery**
  + **blazy--intense-gallery**
  + **blazy--photobox-gallery**
  + **blazy--photoswipe-gallery**
  + **blazy--slick-lightbox-gallery**
  + **blazy--zooming-gallery**

  Note the double dashes BEM modifier "**--**", just to make sure we are on the
  same page that you are intentionally creating a blazy LIGHTBOX gallery.
  The View container will then have the following attributes:

  `class="blazy blazy--LIGHTBOX-gallery ..." data-blazy data-LIGHTBOX-gallery`

2. Add a Blazy formatter for the Media or Image field.
3. Add the relevant lightbox under **Media switcher** option based on the given
   CSS class at #1.


**Important!**

Be sure to leave **Use field template** under **Style settings** unchecked.
If checked, the gallery is locked to a single entity, that is no Views gallery,
but gallery per field. The same applies when using Blazy formatter with VIS
pager, alike, or inside Slick Carousel, GridStack, etc. If confusing, just
toggle this option, and you'll know which works. Only checked if Blazy formatter
is a standalone output from Views so to use field template in this case.

Check out the relevant sub-module docs for details.

.
***
***
.
# <a name="features"> </a>FEATURES
* Supports core Image.
* Supports core Responsive image.
* Supports Colorbox/ Photobox/ PhotoSwipe, also multimedia lightboxes.
* Multi-serving lazyloaded images, including multi-breakpoint CSS backgrounds.
* Lazyload video iframe urls via custom coded, or core Media.
* Supports inline images and iframes with lightboxes, and grid or CSS3 Masonry
  via Blazy Filter. Enable Blazy Filter at **/admin/config/content/formats**,
  and check out instructions at **/filter/tips**.
* Field formatters: Blazy with Media integration.
* Blazy Grid formatter for Image, Media and Text with multi-value.
* Delay loading for below-fold images until 100px (configurable) before they are
  visible at viewport.
* A simple effortless CSS loading indicator.
* It doesn't take over all images, so it can be enabled as needed via Blazy
  formatter, or its supporting modules.


## OPTIONAL FEATURES
* Views fields for File Entity and Media integration, see
  [Slick Browser](https://www.drupal.org/project/slick_browser).
* Views style plugin `Blazy Grid` for Grid Foundation or pure CSS3 Masonry.



.
***
***
.
# <a name="maintainers"> </a>MAINTAINERS/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* [geek-merlin](https://www.drupal.org/u/geek-merlin)
* [Contributors](https://www.drupal.org/node/2663268/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

* [Blazy module](https://www.drupal.org/project/blazy)

See the bLazy docs at:

* [Blazy library](https://github.com/dinbror/blazy)
* [Blazy website](http://dinbror.dk/blazy/)
