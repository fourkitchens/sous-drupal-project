# Features and Configurations

The complete export in `config/default` records this site's enabled content model and configuration.

## Themes

Emulsify supplies the project theme and component workflow; Gin supplies the administrative interface.
See [Build Tools](BUILD_TOOLS.md#theme) for development commands.

## Content types

Review available types under Drupal's Structure → Content types screen.
Page and Frontpage models can use Body, metadata, teaser fields, URL aliases and tags.
Where enabled, Layout Builder supplies per-page layouts or Paragraphs supplies reusable components.

## Media

Media Library manages the site's configured file, image and video types.
Available fields, focal-point cropping and upload widgets are recorded in configuration.

## User Roles and Permissions

The local `admin` account uses the Superuser role; UID 1 is blocked during initial installation.
Review role permissions and account assignments when adding integrations or content workflows.
Record site-specific extensions, content types and feature decisions here.
