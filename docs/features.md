# Features and Configurations

The complete export in `config/default` records this site's enabled content model and configuration.

## Themes

Emulsify supplies the project theme scaffold; add a component library when ready. Gin supplies the administrative interface.
See [Build Tools](BUILD_TOOLS.md#theme) for development commands.

## Content types

Review available types under Drupal's Structure → Content types screen.
Page and Frontpage share titles, metadata, teaser fields, URL aliases and tags.
The default model adds Body; Layout Paragraphs uses components without Body.
Both component setups offer text/media, Accordion, Tabs, Quote, Embed and Table.
Layout Paragraphs adds a field-free one–four column Layout and a library picker.
Tabs Item accepts all components except Layout. Layout Builder uses equivalent
custom blocks, Inline Entity Form for nesting, and native column sections.
Navigation requires the Layout Builder module; content layouts are enabled only for that option.

## Media

Media Library manages the site's configured file, image and video types.
Available fields, focal-point cropping and upload widgets are recorded in configuration.

## User Roles and Permissions

The local `sous_chef` account uses the Superuser role; its random password appears in the initial order summary.
UID 1 (`superuser_1`) remains blocked.
Review role permissions and account assignments when adding integrations or content workflows.
Record site-specific extensions, content types and feature decisions here.
