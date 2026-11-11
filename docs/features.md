# Features and Configurations

The complete export in `config/default` records this site's enabled content model and configuration.

## Themes

Emulsify supplies the project theme; optionally install and compile a component library during creation, or add one later. Gin supplies the administrative interface. See [Build Tools](BUILD_TOOLS.md#theme) for development commands.

## Content types

Review available types under Drupal's Structure → Content types screen.
Page and Frontpage share titles, metadata, teaser fields and URL aliases.
Each has its own content browser display in the shared Pages/Frontpage View.
Packaged content types provide Full, Teaser and Admin Preview displays. The content browsers use Admin Preview.
Content starts in Draft and can become Published or Archived; the sidebar schedules workflow changes through Drupal cron.
Layout Paragraphs has no Body field; both component setups offer text/media, Accordion, Tabs, Quote, Embed and Table.
Layout Paragraphs adds a field-free one–four column Layout and a library picker.
Tabs Item accepts all components except Layout. Layout Builder uses equivalent blocks, Inline Entity Form for nesting, and native column sections.
Navigation requires the Layout Builder module; content layouts are enabled only for that option.

## Media

Every setup includes File, Image, Video and Video File media types. Use Video File for approved MP4 or WebM uploads and Video for YouTube or Vimeo embeds.
Image editing includes Focal Point. Teasers use the landscape responsive image style, with portrait and square styles also available.

## Optional content types

When selected, Event adds Smart Date with all-day and recurring schedules and an Event Type selector using Tagify.
Events share Page-style teasers and month–year/type/title aliases; Upcoming and Past embeds keep ongoing events Upcoming until their end.
A recurring series appears once per listing when different occurrences qualify for each.
News Post adds a plain-text byline, multi-value News Categories, Page-style teasers and a Recent News embed.
News aliases use `news/first-category/title`.
Placeable Event and News listing blocks offer taxonomy and title or teaser-text filters.
Both use components with Paragraphs and layouts with Layout Builder.
Profile adds required first/last names, a hidden automatic title, one Profile Image, a Basic HTML Bio and teasers. The Profiles embed sorts by last name. The placeable listing block searches names and Bio. Profile aliases use `profile/title`.
Event, News Post and Profile each add a content browser display to their existing listing View.

## SEO

Every setup includes redirects for changed aliases, an XML sitemap at `/sitemap.xml`, and breadcrumbs that use page titles. It also includes Google Tag and Schema.org metadata integrations.
Redirect 404 logging records missing pages, and Superusers can review Top missing pages on the Site admin dashboard.

## Optional site features

Alerts displays site-wide banners at the top of every public page. Editors can schedule alerts and make them dismissible.

Search provides a placeable results block with a keyword field and a Content type filter.

Content List combines hand-picked teasers with a View listing. Each Content List Item can reference existing content or use a custom image, heading, text, and link.
**Filter this list** lets editors narrow the bundled Events listings by event type and Recent News by news category. The Profiles listing offers an item limit.
Content List requires Paragraphs or Layout Builder.

Gallery adds a Paragraphs or Layout Builder component for any number of images and hosted videos, with an optional heading and introduction.

## User Roles and Permissions

The local `sous_chef` uses the Superuser role and a random password shown in the order summary; UID 1 (`superuser_1`) stays blocked.
Site Editors can manage and publish site content and delegate Site Editor from the Roles tab at `/user/{uid}/roles`.
Superusers can assign any custom role on the account edit form. Drupal assigns Authenticated automatically.

After signing in, Superusers land on Site admin with site status, recent errors, sign-ins and new accounts, and can switch to the Editor tab; Site Editors land on Editor with drafts, scheduled changes, recently updated content and accessibility alerts.
Only Superusers can rearrange dashboard blocks at Structure → Dashboard. Other roles land on their account page.

Review role permissions and account assignments when adding integrations or content workflows.
Record site-specific extensions, content types and feature decisions here.
