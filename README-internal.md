# Pantheon release process documentation

There are some atypical development and release procedures in use with this repository:
 1. The currently released version of this repository lives in parallel in the `main` and `master` branches of
    [pantheon-upstreams/drupal-project](https://github.com/pantheon-upstreams/drupal-project).  
    `pantheon-upstreams/drupal-project` closely mirrors the development repository at [pantheon-systems/drupal-project](https://github.com/pantheon-systems/drupal-project)
    and is automatically updated by a CircleCI process.
 1. Changes are made by submitting a PR against the `default` branch of `pantheon-systems/drupal-project`.
 1. Merging a PR to `default` _does not_ create a new release of `pantheon-upstreams/drupal-project`. This allows us to
    batch more than one relatively small change into a single new "release" such that the number of separate update
    events appearing on customer dashboards is more controlled.
 1. Trigger the new release to `pantheon-upstreams` by `--ff-only`-merging `default` into `release` and pushing the 
    result.

## Differences between `pantheon-upstreams` and `pantheon-systems` repos:
 1. Commits modifying the `.circleci` directory, `devops` directory or this file are omitted from `pantheon-upstreams`.
    This prevents downstream Pantheon sites from being littered with our internal CI configuration, and allows us to
    enhance CI without generating irrelevant site updates.
    However, it means **you must not create commits that modify both .circleci and other files** in the same commit.
 2. Commit authors are rewritten to `Pantheon Automation <bot@getpantheon.com>` as a request from Product. The author
    names appear on the dashboard and this creates a more professional presentation.