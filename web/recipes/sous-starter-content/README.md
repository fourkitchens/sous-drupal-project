# Starter content defaults

Applied over the starter's existing Admin base, this recipe composes Sous Content Types and adds the
starter-owned Frontpage type and Page/Frontpage Pathauto patterns. It reuses
Page's Body, metadata, SEO title, teaser-image and teaser-text storage rather
than keeping another copy of shared field storage or a partial site export.

Default installation applies this recipe. The bundled Paragraphs and Layout
Builder recipes extend both content types; the explicit admin-only option
continues to omit content types. Dependencies are installed by the starter's
Composer manifest. The recipe does not seed a homepage node, select a home URL,
or enforce a singleton Frontpage.

Management Views are imported from their current core/module providers with
`config.strict: false`. Existing Views are preserved. This retains the Content,
Content blocks, Files, Media, Media Library, Taxonomy and Login history screens
and native optional Glossary/user-listing defaults without freezing old routes,
permissions or plugin settings in the starter.

The [configuration audit](../../../docs/CONFIGURATION_AUDIT.md) accounts for all
129 removed export files. Page, media, Paragraph types and the administrator
role retain their existing recipe owners. Fresh setup exports the installed
composition into the site's complete `config/default` directory. Existing
sites adopt changes explicitly; package updates do not migrate fields or data.
These are one-time fresh-site compositions. Do not reapply the full recipe
stack over a customized site; its upstream recipes intentionally validate their
own defaults. Adopt later differences through reviewed site-owned migrations.
