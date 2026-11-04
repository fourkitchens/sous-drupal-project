# Features and Configurations

The complete export in `config/default` records this site's enabled content model and configuration.

## Themes

Emulsify supplies the project theme; optionally install and compile a component library during creation, or add one later. Gin supplies the administrative interface. See [Build Tools](BUILD_TOOLS.md#theme) for development commands.

## Content types

Review available types under Drupal's Structure → Content types screen.
Page and Frontpage share titles, metadata, teaser fields, URL aliases and tags.
Content starts in Draft and can become Published or Archived; the sidebar schedules workflow changes through Drupal cron.
Layout Paragraphs has no Body field; both component setups offer text/media, Accordion, Tabs, Quote, Embed and Table.
Layout Paragraphs adds a field-free one–four column Layout and a library picker.
Tabs Item accepts all components except Layout. Layout Builder uses equivalent blocks, Inline Entity Form for nesting, and native column sections.
Navigation requires the Layout Builder module; content layouts are enabled only for that option.

## Media

Every setup includes File, Image and Video media types. Upload files and images through Drupal's Media Library form. Image editing includes Focal Point.

## Optional content types

When selected, Event adds Smart Date with all-day and recurring schedules and an Event Type selector using Tagify.
Events share Page-style teasers and month–year/type/title aliases; Upcoming and Past embeds keep ongoing events Upcoming until their end.
A recurring series appears once per listing when different occurrences qualify for each.
News Post adds a plain-text byline, multi-value News Categories and Page-style teasers.
News aliases use `news/first-category/title`.
Both use rendered teasers with exposed taxonomy and title/teaser-text search filters.
Both use components with Paragraphs and layouts with Layout Builder.
Profile adds required first/last names, a hidden automatic title, one Profile Image, a Basic HTML Bio and teasers; its Profiles embed sorts by last name and searches names and Bio.

## Optional site features

Alerts displays site-wide banners at the top of every public page. Editors can schedule alerts and make them dismissible.

Search adds a search box in the header and a `/search` results page with a Content type filter.

## User Roles and Permissions

The local `sous_chef` uses the Superuser role and a random password shown in the order summary; UID 1 (`superuser_1`) stays blocked.
Review role permissions and account assignments when adding integrations or content workflows.
Record site-specific extensions, content types and feature decisions here.
