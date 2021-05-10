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

## Differences between `pantheon-upstreams` and `pantheon-systems` repos:
 1. Commits modifying the `.circleci` directory, `devops` directory or this file are omitted from `pantheon-upstreams`.
    This prevents downstream Pantheon sites from being littered with our internal CI configuration, and allows us to
    enhance CI without generating irrelevant site updates.
    However, it means **you must not create commits that modify both .circleci and other files** in the same commit.
 2. Commit authors are rewritten to `Pantheon Automation <bot@getpantheon.com>` as a request from Product. The author
    names appear on the dashboard and this creates a more professional presentation.

## Cutting a new release
 1. Update CHANGELOG.md. In the past, the copy has been created in consultation with Ari / product.
 1. Ensure the commit message for the last commit in this release says what we want to have appearing on the
    dashboard as an available update. See [CORE-2258](https://getpantheon.atlassian.net/browse/CORE-2258) for
    the inaugural example of such a commit message. All changes are committed to `pantheon-upstreams` as a single
    commit, and the message that is used for it is the one from the last commit.
    * Typically the CHANGELOG.md commit is the last one and so is the one whose commit message should be wordsmithed.
 1. Trigger the new release to `pantheon-upstreams` by `--ff-only`-merging `default` into `release` and pushing the 
    result:
    ```
    git fetch
    git checkout release && git pull
    git merge --ff-only origin/default
    git push origin release
    ```
    A CircleCI job causes the release to be created.

## Branch protections and their rationale

### In pantheon-systems
 1. The `default` branch does not accept merge commits. This is because this branch serves as the staging area for
    commits queued to be released to site upstreams, and the commit messages appear on customer dashboards as
    available updates. Preventing `Merged "[CORE-1234] Added widget to branch default [#62]"`-style commit messages
    enhances the user experience.

### In pantheon-upstreams
 1. All branches do not accept pushes, except by GitHub user `pantheon-circleci` and owners of the `pantheon-upstreams`
    organization, because GitHub hardcodes those users as able to push. This is just to avoid accidental direct pushes,
    because commits to the upstreams repo are supposed to be made only from CircleCI as part of an intentional release
    with the commit authors rewritten.
