# Choose your site's features

Choose a content setup during [fresh installation](SETUP.md). Every option
includes Gin administration, Navigation and a generated Emulsify theme.

| Option | Content setup |
| --- | --- |
| `0` Default | Page and Frontpage with Body, metadata, SEO, teasers and title-based aliases; Page also has tags |
| `1` Layout Builder | Page and Frontpage with field blocks, Basic inline blocks and per-node layout overrides |
| `2` Paragraphs | Page and Frontpage with Body and Image, Text, Text with Media and Video components |
| `3` Admin only | Administration and theme without the Page or Frontpage recipes |

Layout Builder and Paragraphs are separate packages. Installation downloads only
the selected composition and its dependencies. Layout Builder does not add
Paragraphs; Paragraphs does not configure Layout Builder on the content types.
Navigation enables Drupal's `layout_builder` module in every option, so check
the Page display settings when identifying the chosen composition.

Config Split is a separate yes/no choice and defaults to no. When selected, it
adds `_splits`, local and staging definitions. Follow
[Environments](ENVIRONMENTS.md) to control their activation.

## Add reusable configuration

The [Sous packages repository](https://github.com/fourkitchens/sous-drupal-packages)
contains independent Composer packages. Each package has its own README,
requirements and recipe. Their shared version does not require installing all
of them.

| Composer name (`fourkitchens/` prefix) | Configuration supplied |
| --- | --- |
| `sous-admin` | Gin, Navigation, administration Views, conveniences and the `superuser` role; uses the shared Linkit defaults from Formats |
| `sous-emulsify` | Frontend theme dependencies and selection |
| `sous-formats` | Basic, Full, Limited and Restricted HTML formats, editors and shared Linkit defaults |
| `sous-media` | File, Image and remote Video media types with Media Library forms and displays |
| `sous-content-types` | Page and Frontpage; includes Formats and Media |
| `sous-paragraph-types` | Four component types and their icons; includes Formats and Media |
| `sous-paragraphs` | Paragraphs fields and displays on Page and Frontpage; includes Content Types and Paragraph Types |
| `sous-core-layout-builder` | Layout Builder on Page and Frontpage; includes Content Types |
| `sous-config-split` | Config Split definitions for local and staging |

Composer installs recipes under `web/recipes`. Use the starter's
[recipe command](SETUP.md) when applying a recipe. Review the package README
before adding it to an existing site; later downloads do not apply configuration
changes automatically. See [Maintenance](MAINTENANCE.md).

## Finish site configuration

Create homepage and error-page content, then choose their paths in Drupal.
Frontpage is a content type; it does not create content or limit the site to one
Frontpage node. Configure Entity Browser and Role Delegation if the site needs
them.

Administration Views come from Drupal and module defaults. Existing customized
Views and Linkit profiles are preserved. Glossary starts disabled; user-listing
blocks are not placed automatically.

Fresh setup creates `sous_chef`, assigns the administrator role `superuser`, and
blocks `superuser_1`. Set your site's account and credential policy. Accounts
and role assignments are database data; role definitions are configuration.
Commit the complete export in `config/default` and customize it for your site.
