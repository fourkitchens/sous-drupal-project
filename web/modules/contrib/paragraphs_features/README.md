CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Available Features
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Paragraphs Features module provides a few additional features for paragraphs
module.

 * For a full description of the module visit:
   https://www.drupal.org/project/paragraphs_features

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/paragraphs_features

 * For code contributions please provide pull request on GitHub. It's easier to
   review it there and we already have a testing infrastructure in place.
   https://github.com/thunder/paragraphs_features


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * Paragraphs - https://www.drupal.org/project/paragraphs


INSTALLATION
------------

 * Install the Paragraphs Features module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Content authoring >
       Paragraphs features for configuration.
    3. Select whether you want to reduce the actions dropdown to a button when
       there is only one option. Save.
    4. Select whether you want to display in between button.
    5. Select whether you want to confirm paragraph deletion.
    6. Select whether you want to the display the Drag and drop button in the actions menu.

AVAILABLE FEATURES
------------------

##### Add In Between:

This feature provides additional buttons between paragraphs for paragraphs
experimental widget UI. That allows easier adding of a new paragraph to the
specific position in the list of paragraphs.

##### Split Text for Paragraphs text field:

Split Text feature for paragraphs is available for all direct text fields of a
paragraph where CKEditor is used. It's sufficient to enable the feature for
paragraphs field experimental widget UI. Modal add mode is required for split
text feature to work.

##### Reduce actions drop-down to a button:

Paragraphs actions are rendered as drop-down also when there is only one option.
Now it's possible to enable globally for the backend when there is only one
option in the actions drop-down to display that as a single button. That
improves UX since one additional click to expand drop-down is removed.

##### Delete confirmation:

This feature provides a delete confirmation form for the paragraphs experimental
widget UI.

##### Display Drag and drop button

Paragraphs adds a drag & drop button to the action menu, that initiates an advanced drag & drop ui.
You can select if you want this. This feature is only available, if the core/sortable library is
loaded.

MAINTAINERS
-----------

 * Mladen Todorovic (mtodor) - https://www.drupal.org/u/mtodor
 * Volker Killesreiter (volkerk) - https://www.drupal.org/u/volkerk

Supporting organization:

 * Thunder - https://www.drupal.org/thunder
