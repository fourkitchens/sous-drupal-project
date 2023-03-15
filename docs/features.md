[![Sous](https://circleci.com/gh/fourkitchens/sous-drupal-project.svg?style=svg)](https://app.circleci.com/github/fourkitchens/sous-drupal-project/pipelines)

# Features and Configurations

## Themes

The installation process creates a starter [Emulsify](https://emulsify.info/) theme with your project name. This theme will
be installed and enabled. It won't look like much at first, but it's ready for your delightful designs!

The Gin Admin theme will be installed for your admin pages.

## Content types

- **Frontpage**: A single use content type for creating a front page / home page of site.
- **Page**: A general content type for all standard pages.

There are three pieces of default content: a default homepage, an access denied page, and a page not found page.

## Media

All media types have fields for descriptions and tags.

- **File**: For documents. Allowed file extensions are txt, doc, docx, pdf.
- **Image**: Allowed file extensions are png, gif, jpg, jpeg. Has caption and copyright fields.
- **Video**: For videos hosted on YouTube or Vimeo. Has caption, copyright, and transcript fields.

### Paragraphs

The following paragraph types are available:

- Image
- Text
- Text with Media
- Video

There are also some utility modules to make paragraph widgets more user-friendly.

### Entity Browsers

Several entity browsers and associated views are set up, and used on fields where appropriate:

- All Content Browser
- All Media Browser
- File Media Browser
- Image Media Browser
- Image Media Browser (Embedded)
- Image/Video Media Browser
- Video Media Browser

### Text Formatting

The following text formats come bundled with Drupal's default installation profile. Sous builds upon these defaults and gives them distinct configuration for specific purposes.

- **Full HTML Filter**: An unrestricted filter that only corrects broken html and removes empty `p` tags when rendered.
- **Basic HTML Filter**: General use filter that offers a broad range of formatting options. Ideal for most instances of formatted text.
- **Restricted HTML Filter**: Limited access filter best used for specific scenarios where some formatting is appropriate.
- **Plain Text Filter**: Rendered as a raw string.

## User Roles and Permissions

There is only one custom role: Super User. The installation process will block the regular user 1 account and create
a new "sous_chef" user with this role. This improves security for the site.

The Role Delegation module is enabled so that when you create custom roles, you can easily assign permissions for other
roles to manage users without allowing them to create high level admins.

## Contributed Modules

Several contributed modules are installed with basic configuration. These add commonly used features to the site and
improve the administrative experience.

### Admin and authoring experience
- Admin Toolbar
- Allowed Formats
- CKEditor Browser Context Menu
- Embed
- Enhanced Entity Browser
- Entity Browser
- Entity Browser IEF
- Entity Embed
- Entity Reference Revisions
- Entity Usage
- Field Group
- Improve Line Breaks Filter
- Inline Entity Form
- Linkit
- Taxonomy Manager

### Theme related
- Components
- Emulsify Twig Extensions Module
- jQuery UI
- Twig Tweak

### Media related
- Blazy
- Crop API
- Dropzone JS
- DropzoneJS Entity Browser Widget
- Focal Point

### Paragraphs related
- Paragraphs
- Paragraphs Collapsible
- Paragraphs Editor Enhancements
- Paragraphs Features

### User related
- Login History
- Role Delegation

### Other common utilities
- Chaos Tools
- Easy Breadcrumb
- Google Tag Manager
- Libraries
- Menu Block
- Metatag
- Metatag: Open Graph
- Metatag: Twitter Cards
- Pathauto
- Redirect
- Token
