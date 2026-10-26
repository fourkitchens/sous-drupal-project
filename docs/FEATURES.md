# Features and configuration ownership

Reusable defaults live in the Sous recipes. A generated site owns the complete
export produced after installation, and any later customization. See
[ownership and maintenance](MAINTENANCE.md) for the supported update workflow.

## Installation choices

Every starter choice includes Gin administration and a generated Emulsify theme.

| Choice | Content authoring defaults |
| --- | --- |
| Default | Page with visible Body, metadata, tags, teaser text and media support |
| Paragraphs | Page plus Image, Text, Text with Media, and Video components through the companion Sous Paragraphs host |
| Native Layout Builder | Page with its visible field formatters retained as blocks, native Basic inline blocks, and per-Page layout overrides |
| Admin only | Administration and theme setup without the optional Page/content recipe |

The historic Frontpage type, seeded homepage/access-denied/not-found content,
Entity Browser collection, and Role Delegation defaults are not supplied by the
current recipes. Their presence in the old partial `config/default` directory
did not make them part of fresh recipe installation. Projects can add these
features deliberately; the legacy partial export is not an installation input.

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
