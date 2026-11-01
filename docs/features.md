# Features and Configurations

The complete export in `config/default` records this site's enabled content model and configuration.

## Themes

Emulsify supplies the project theme scaffold; add a component library when ready. Gin supplies the administrative interface.
See [Build Tools](BUILD_TOOLS.md#theme) for development commands.

## Content types

Review available types under Drupal's Structure → Content types screen.
Page and Frontpage share titles, metadata, teaser fields, URL aliases and tags.
Layout Paragraphs has no Body field; both component setups offer text/media, Accordion, Tabs, Quote, Embed and Table.
Layout Paragraphs adds a field-free one–four column Layout and a library picker.
Tabs Item accepts all components except Layout. Layout Builder uses equivalent blocks, Inline Entity Form for nesting, and native column sections.
Navigation requires the Layout Builder module; content layouts are enabled only for that option.

## Media

Every setup includes file, image and video Media Library types, focal-point cropping and upload widgets.

## Events and News

When selected, Event adds Smart Date with all-day and recurring schedules and an Event Type selector using Tagify.
Events share Page-style teasers and use month–year, event type and title in URL aliases.
Upcoming and Past Views provide embeddable listings; ongoing events stay Upcoming until their end.
A recurring series appears once per listing when different occurrences qualify for each.
News Post adds a plain-text byline, multi-value News Categories and Page-style teasers.
News aliases use `news/first-category/title`.
Both use rendered teasers with exposed taxonomy and title/teaser-text search filters.
Both use components with Paragraphs and layouts with Layout Builder.

## User Roles and Permissions

The local `sous_chef` account uses the Superuser role; its random password appears in the initial order summary.
UID 1 (`superuser_1`) remains blocked.
Review role permissions and account assignments when adding integrations or content workflows.
Record site-specific extensions, content types and feature decisions here.
