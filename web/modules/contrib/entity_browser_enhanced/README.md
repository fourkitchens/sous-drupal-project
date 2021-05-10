# Entity Browser Enhanced

This module provides behaviour and style enhancements to Entity Browsers.
Targeted to multiselect and image/media browsers.

Influenced by enhancements made on the Media Entity Browser in Lightning.

Does this module replace "Entity Browser"?

No. This module adds to Entity Browser some missing usability enhancements.
 Specifically for the "View" widget.

### Features:

Currently this module allows you to select an "Enhancer" to each "View" widget
in a given Entity Browser.
Available enhancers now is one that's called "Enhanced Multiselect".
Which does the following:

- Hides the checkboxes, allows you to select anywhere on the entity to select 
  it as an item.
- Adds some styling enahncements on selected entities.
- If an Entity Browser is used as a field widget, the Entity Browser's View
  widget would limit the number of selectable entities based on the field's
  cardinality config.
- Doubleclicking the entity means add or place.

### Roadmap:

We've seen that a lot of Entity Browsers out there have their own styles,
JavaScript and CSS enhancements. Mainly on the "View" widget.
The vision of this module to provide a set of pluggable enhancers that allows
you to choose from for each View widget in an Entity Browser.
