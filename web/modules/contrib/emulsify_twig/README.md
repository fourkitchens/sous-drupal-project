# Emulsify Twig Extensions module

This module provides two Twig extensions used in the [Emulsify Design System](https://github.com/emulsify-ds/).

## Usage

### BEM Twig Extension

Twig function that inserts static classes into Pattern Lab and adds them to the Attributes object in Drupal

#### Simple block name (required argument):
`<h1 {{ bem('title') }}>`

This creates:

`<h1 class="title">`

#### Block with modifiers (optional array allowing multiple modifiers):
`<h1 {{ bem('title', ['small', 'red']) }}>`

This creates:

`<h1 class="title title--small title--red">`

#### Element with modifiers and blockname (optional):
`<h1 {{ bem('title', ['small', 'red'], 'card') }}>`

This creates:

`<h1 class="card__title card__title--small card__title--red">`

#### Element with blockname, but no modifiers (optional):

`<h1 {{ bem('title', '', 'card') }}>`

This creates:

`<h1 class="card__title">`

#### Element with modifiers, blockname and extra classes (optional - in case you need non-BEM classes):

`<h1 {{ bem('title', ['small', 'red'], 'card', ['js-click', 'something-else']) }}>`

This creates:

`<h1 class="card__title card__title--small card__title--red js-click something-else">`

#### Element with extra classes only (optional):

`<h1 {{ bem('title', '', '', ['js-click']) }}>`

This creates:

`<h1 class="title js-click">`

### Add Attributes Twig Extension

Twig function that merges with template level attributes in Drupal and prevents them from trickling down into includes.

```
{% set additional_attributes = {
  "class": ["foo", "bar"],
  "baz": ["foobar", "goobar"],
  "foobaz": "goobaz",
} %}

<div {{ add_attributes(additional_attributes) }}></div>
```

Can also be used with the BEM Function:
```
{% set additional_attributes = {
  "class": bem("foo", ["bar", "baz"], "foobar"),
} %}

<div {{ add_attributes(additional_attributes) }}></div>
```

## Development

-------------

### Requires

- [Node.js v12+](http://nodejs.org/)
- [Yarn Package Manager](https://yarnpkg.com/)
- [Commitizen](https://github.com/commitizen/cz-cli) for commit standardization, included in install

### Initial Setup

1. Run `yarn` to install dependencies. You're done!

### Committing Changes

To facilitate automatic semantic release versioning, we utilize the [Conventional Changelog](https://github.com/conventional-changelog/conventional-changelog) standard through Commitizen. Follow these steps when commiting your work to ensure a better tomorrow.

1. Stage your changes, ensuring they encompass exactly what you wish to change, no more.
2. Run `yarn commit` and follow the prompts to craft the perfect commit message.
3. _Rejoice!_ For now your commit message will be used to create the changelog for the next version that includes that commit.
