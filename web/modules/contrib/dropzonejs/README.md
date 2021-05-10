# About DropzoneJS

This is the Drupal integration for [DropzoneJS](http://www.dropzonejs.com/).

### How to install

#### The non-composer way

1. Download this module
2. [Download DropzoneJS](https://github.com/enyo/dropzone) and place it in the
   libraries folder
3. Install dropzonejs the [usual way](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
4. Remove "test" folder from libraries folder as it could constitute a
   security risk to your site. See http://drupal.org/node/1189632 for more info.

You will now have a dropzonejs element at your disposal.

#### The composer way 1

Run `composer require wikimedia/composer-merge-plugin`

Update the root `composer.json` file. For example:

```
    "extra": {
        "merge-plugin": {
            "include": [
                "web/modules/contrib/dropzonejs/composer.libraries.json"
            ]
        }
    }
```

Run `composer require drupal/dropzonejs enyo/dropzone`, the DropzoneJS library will be
installed to the `libraries` folder automatically.

#### The composer way 2

Copy the following into the root `composer.json` file's `repository` key

```
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "enyo/dropzone",
                "version": "5.7.1",
                "type": "drupal-library",
                "dist": {
                    "url": "https://github.com/enyo/dropzone/archive/v5.7.1.zip",
                    "type": "zip"
                }
            }
        }
    ]
```

Run `composer require drupal/dropzonejs enyo/dropzone`, the DropzoneJS library
will be installed to the `libraries` folder automatically as well.

### Future plans:
- A dropzonejs field widget.
- Handling already uploaded files.
- Handling other types of upload validations (min/max resolution, min size,...)
- Removing files that were removed by the user on first upload from temp storage.

### Project page:
[drupal.org project page](https://www.drupal.org/project/dropzonejs)

### Maintainers:
+ Janez Urevc (@slashrsm) drupal.org/u/slashrsm
+ John McCormick (@neardark) drupal.org/u/neardark
+ Primoz Hmeljak (@primsi) drupal.org/u/Primsi
+ Qiangjun Ran (@jungle) drupal.org/u/jungle

### Get in touch:
 - http://groups.drupal.org/media
 - **#media**: http://drupal.slack.com

### Thanks:
 The development of this module is sponsored by [Examiner.com](http://www.examiner.com)
 Thanks also to [NYC CAMP](http://nyccamp.org/) that hosted media sprints.
