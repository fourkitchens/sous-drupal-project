# Features and configuration ownership

Reusable defaults live in the Sous recipes. A generated site owns the complete
export produced after installation, and any later customization. See
[ownership and maintenance](MAINTENANCE.md) for the supported update workflow.

## Installation choices

Every starter choice includes Gin administration and a generated Emulsify theme.

| Choice | Content authoring defaults |
| --- | --- |
| Default | Page and Frontpage with visible Body, metadata, SEO and teasers; Page also has tags; both have title-based URL aliases |
| Paragraphs | Both content types plus Image, Text, Text with Media, and Video components through the starter's extension of the Sous Paragraphs host |
| Native Layout Builder | Both content types with their visible field formatters retained as blocks, native Basic inline blocks, and per-node layout overrides |
| Admin only | Administration and theme setup without the optional Page/content recipe |

The bundled `sous-starter-content` recipe restores Frontpage and both alias
patterns without requiring Paragraphs in the default composition. The original
type neither created a homepage node nor enforced a singleton; those remain
project decisions, as do homepage/error-page content, Entity Browser and Role
Delegation. The [configuration audit](CONFIGURATION_AUDIT.md) accounts for every
removed file. The complete generated-site export remains in `config/default`.

Full content installations also import the ten historic Views from current
core/module defaults, preserving existing customized Views. Management screens
are restored without copying obsolete routes/permissions from old exports.
Glossary remains disabled, and user-listing blocks remain unplaced by default.

## Media and editing

Sous Media supplies File, Image, and Video bundles with their fields, forms,
displays, and native Media Library integration. Video uses Drupal's remote
video source. Sous Formats supplies Basic, Full, Limited, and Restricted HTML
editor/filter configurations, plus native Plain Text. The shared Linkit
subrecipe supplies node, media, and external matchers when its default profile
is missing. Existing profile customization is retained.

## Administration and authorization

Sous Admin owns Gin, core Navigation, the administration defaults, and the
`superuser` administrator role. Emulsify owns only frontend theme configuration.
Fresh starter bootstrap creates `sous_chef`, assigns that role, and blocks the
initial `superuser_1` account. Accounts and role assignments are site database
data; the role's configuration is included in the site's export. Projects own
their account and credential policies.

All offered administration conveniences remain enabled. They are not all
authorization requirements. The [Sous Admin module classification](https://github.com/fourkitchens/sous-admin#administration-defaults-and-optional-conveniences)
distinguishes essential administration, content-model dependencies, editorial
tools, and operational conveniences. Existing sites must validate dependencies
and uninstall modules through Drupal before removing their Composer packages.
