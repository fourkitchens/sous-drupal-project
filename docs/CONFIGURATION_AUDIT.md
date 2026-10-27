# Starter configuration audit

PR #130 removed 129 YAML files from `config/default`. Most were copies of
defaults already supplied by recipes or Drupal modules, but **Frontpage and the
Page/Frontpage alias patterns had no replacement**. Those starter defaults are
restored through independently distributed recipes rather than another partial
site export. Their source now lives together in
[Sous Drupal Packages](https://github.com/fourkitchens/sous-drupal-packages).

The [Content Types recipe](https://github.com/fourkitchens/sous-drupal-packages/blob/main/packages/sous-content-types/recipe.yml)
supplies the common Page/Frontpage composition for installation options 0, 1,
and 2. The [Paragraphs composition](https://github.com/fourkitchens/sous-drupal-packages/blob/main/packages/sous-paragraphs/recipe.yml)
adds the shared `field_content` to Frontpage, and the
[Core Layout Builder composition](https://github.com/fourkitchens/sous-drupal-packages/blob/main/packages/sous-core-layout-builder/recipe.yml)
enables native layouts for both content types. The explicit admin-only option
remains available without content types.

Frontpage is a preconfigured content type. The starter does not seed a homepage
node, assign a fixed node ID to `system.site`, or enforce a singleton. Projects
create their content and select the homepage after installation.

[Generated-site configuration](../config/default/README.md) still belongs in a
complete, project-owned `config/default` export. Reusable recipe defaults omit
exported UUIDs and `_core.default_config_hash`; a site's own export retains its
UUIDs. See the [maintenance policy](MAINTENANCE.md) for existing-site adoption.

## Complete accounting

Each row below accounts for a disjoint set of the original 129 files. Names in
the detailed tables are Drupal configuration names: append `.yml` to obtain
the original filename under `config/default/`. Braced alternatives denote all
listed names, not a wildcard selection.

| Disposition | Files |
| --- | ---: |
| Sous Media defaults already owned by its recipe | 41 |
| Page defaults already owned by Sous Content Types | 13 |
| Paragraph component defaults already owned by Sous Paragraph Types | 25 |
| Superuser role owned by Sous Admin | 1 |
| Native Drupal core defaults | 24 |
| Native Login History view | 1 |
| Page field names replaced by the current recipe model | 4 |
| Shared Paragraph preview mode imported from its module | 1 |
| Ancillary view modes without current starter consumers | 9 |
| Missing Frontpage and alias defaults restored in Sous Content Types | 10 |
| **Total** | **129** |

### Media: 41 files

Provider: [`sous-media/config`](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-media/config).
These remain reusable media defaults rather than site-specific exports.

| Original configuration names | Count |
| --- | ---: |
| `core.entity_form_display.media.{file,image,video}.default` | 3 |
| `core.entity_view_display.media.{file,image,video}.{default,media_library,preview}` | 9 |
| `core.entity_view_mode.file.token`, `core.entity_view_mode.media.{preview,token}` | 3 |
| `field.field.media.file.{field_description,field_media_file,field_tags}` | 3 |
| `field.field.media.image.{field_caption,field_copyright,field_description,field_media_image,field_tags}` | 5 |
| `field.field.media.video.{field_caption,field_copyright,field_description,field_media_oembed_video,field_tags,field_transcript}` | 6 |
| `field.storage.media.{field_caption,field_copyright,field_description,field_media_file,field_media_image,field_media_oembed_video,field_tags,field_transcript}` | 8 |
| `media.type.{file,image,video}` | 3 |
| `taxonomy.vocabulary.media_tags` | 1 |

### Page: 13 files

Provider: [`sous-content-types/config`](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-content-types/config).
Its repaired Page model also supplies `body` and `field_teaser_image`, covered
by the replacement accounting below.

| Original configuration names | Count |
| --- | ---: |
| `node.type.page` | 1 |
| `core.entity_form_display.node.page.default` | 1 |
| `core.entity_view_display.node.page.{default,teaser}` | 2 |
| `field.field.node.page.{field_metadata,field_seo_title,field_tags,field_teaser_text}` | 4 |
| `field.storage.node.{field_metadata,field_seo_title,field_tags,field_teaser_text}` | 4 |
| `taxonomy.vocabulary.tags` | 1 |

### Paragraph components: 25 files

Provider: [`sous-paragraph-types/config`](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-paragraph-types/config).
The component types remain optional and are applied by the Paragraphs
installation choice.

| Original configuration names | Count |
| --- | ---: |
| `core.entity_form_display.paragraph.{image,text,text_with_media,video}.default` | 4 |
| `core.entity_view_display.paragraph.{image,text,text_with_media,video}.default` | 4 |
| `field.field.paragraph.image.field_image` | 1 |
| `field.field.paragraph.text.field_text` | 1 |
| `field.field.paragraph.text_with_media.{field_media,field_media_alignment,field_text}` | 3 |
| `field.field.paragraph.video.field_video` | 1 |
| `field.storage.paragraph.{field_image,field_media,field_media_alignment,field_text,field_video}` | 5 |
| `paragraphs.paragraphs_type.{image,text,text_with_media,video}` | 4 |
| `paragraphs_ee.paragraphs_category.{media_components,text_components}` | 2 |

### Authorization: 1 file

`user.role.superuser` is supplied by
[`sous-admin/config/user.role.superuser.yml`](https://github.com/fourkitchens/sous-drupal-packages/blob/main/packages/sous-admin/config/user.role.superuser.yml).
The role remains an administrator role with `is_admin: true`. It belongs to
the administration layer; the Emulsify recipe does not own authorization.
Fresh setup applies Admin before assigning the role to its authoring account.
The Admin package also supplies an explicit existing-site migration for a
missing role, preserving existing roles and assignments. Restoring a second
role definition in the starter would duplicate this responsibility.

### Native core defaults: 24 files

These are provided by the selected Drupal version under
`web/core/modules/<module>/config/install` or `config/optional`. Recipe imports
use those current providers rather than frozen copies from an older export.

| Original configuration names | Provider | Count |
| --- | --- | ---: |
| `core.entity_form_mode.media.media_library` | `media_library`, install | 1 |
| `core.entity_form_mode.user.register` | `user`, install | 1 |
| `core.entity_view_mode.block_content.full` | `block_content`, install | 1 |
| `core.entity_view_mode.media.full` | `media`, install | 1 |
| `core.entity_view_mode.media.media_library` | `media_library`, install | 1 |
| `core.entity_view_mode.node.{full,rss,search_index,search_result,teaser}` | `node`, install | 5 |
| `core.entity_view_mode.taxonomy_term.full` | `taxonomy`, install | 1 |
| `core.entity_view_mode.user.{compact,full}` | `user`, install | 2 |
| `image.style.media_library`, `media_library.settings` | `media_library`, install | 2 |
| `views.view.block_content` | `block_content`, optional | 1 |
| `views.view.{content,glossary}` | `node`, optional | 2 |
| `views.view.files` | `file`, optional | 1 |
| `views.view.media` | `media`, optional | 1 |
| `views.view.media_library` | `media_library`, install | 1 |
| `views.view.taxonomy_term` | `taxonomy`, optional | 1 |
| `views.view.{who_s_new,who_s_online}` | `user`, optional | 2 |

`views.view.login_history` is the separate **one-file** contrib category,
provided by `web/modules/contrib/login_history/config/install`.

Sous Admin explicitly imports these native Views. They are outside its strict
Gin/login whitelist, preserving preexisting customized Views while retaining
strict validation for Admin-owned imports. Media also preserves these Views
when applied afterward while keeping its own bundles/fields/displays strict.
Imports require their provider modules and dependencies to be
installed. Glossary remains disabled by its native default; the user-listing
Views supply block displays without placing blocks in a theme.

The legacy copies did not contain a necessary Sous-specific Views feature:

- Block content used the obsolete `admin/structure/block/block-content` route
  and `administer blocks` permission. Current core uses `admin/content/block`
  and `access block library`.
- The old Files view lacked current core's operations column.
- The old taxonomy feed used the default node display; current core selects
  the RSS display and declares that dependency.
- The old Login History view lacked its current empty-state handler.
- Most other changes were export/schema noise. The Who's New view matched
  its native definition after removing export metadata.

### Page field replacements: 4 files

| Original configuration | Current provider/replacement |
| --- | --- |
| `field.storage.node.field_content_body` | Sous Paragraphs: `field.storage.node.field_content` |
| `field.field.node.page.field_content_body` | Sous Paragraphs: `field.field.node.page.field_content` |
| `field.storage.node.field_teaser_media` | Sous Content Types: `field.storage.node.field_teaser_image` |
| `field.field.node.page.field_teaser_media` | Sous Content Types: `field.field.node.page.field_teaser_image` |

The current Paragraphs source is
[`sous-paragraphs/config`](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-paragraphs/config).
`field_content` is an entity-reference-revisions field for Paragraphs.
`field_teaser_image` is still an image-media reference labelled “Teaser Media”.
The default Page model uses its formatted Body; installing Paragraphs adds the
component field. These template replacements are **not an existing-content
data migration**. An existing site retains its old fields and data until its
maintainers explicitly adopt a reviewed migration.

### Shared and ancillary modes: 10 files

`core.entity_view_mode.paragraph.preview` is the **one-file** shared-mode
category. Sous Paragraph Types imports it from the Paragraphs module rather
than maintaining another local definition.

The following **nine** removed modes have no current starter configuration
consumer and are not required to create Page, Frontpage, or the offered
composition options:

- `core.entity_view_mode.{block,block_content,menu_link_content,node,path_alias,shortcut,taxonomy_term,user}.token` (eight files).
- `core.entity_view_mode.node.preview` (one file).

This does not remove the `file.token`, `media.token`, or `media.preview` modes
that Sous Media supplies, nor Drupal's built-in preview capability. Existing
sites with custom displays using these ancillary modes keep their own exports.

### Restored starter defaults: 10 files

Provider: [Sous Content Types configuration](https://github.com/fourkitchens/sous-drupal-packages/tree/main/packages/sous-content-types/config).
The repaired Page recipe supplies the shared storages; Frontpage supplies its
own attachments and displays.

| Removed configuration | Restored starter default |
| --- | --- |
| `node.type.frontpage` | Frontpage content type |
| `core.entity_form_display.node.frontpage.default` | Frontpage form with Body, SEO, metadata and teaser controls |
| `core.entity_view_display.node.frontpage.default` | Frontpage body rendering |
| `field.field.node.frontpage.field_content_body` | `field.field.node.frontpage.body`; the Paragraphs choice separately adds shared `field_content` |
| `field.field.node.frontpage.field_metadata` | Frontpage attachment to shared metadata storage |
| `field.field.node.frontpage.field_seo_title` | Frontpage attachment to shared SEO title storage |
| `field.field.node.frontpage.field_teaser_media` | `field.field.node.frontpage.field_teaser_image`, using the current shared image-media storage |
| `field.field.node.frontpage.field_teaser_text` | Frontpage attachment to shared teaser-text storage |
| `pathauto.pattern.frontpage` | Frontpage alias pattern with declared Pathauto support |
| `pathauto.pattern.page` | Page alias pattern with declared Pathauto support |

The old Frontpage form depended on Paragraphs-specific widgets and fields.
Restoring it unchanged would make Paragraphs mandatory for the default and
Layout Builder choices. The optional packages now extend the common
Frontpage model only when their installation option is selected.

This accounting comes from source comparisons. Runtime verification of the
resulting installation choices is reported separately in the
[integration guide](INTEGRATION.md).
