.
***
***
.
# <a name="updating"></a>UPDATE SOP
Visit any of the following URLs when updating Blazy, or its related modules.
Please ignore any documentation if already aware of Drupal site building. This
is for the sake of completed documentation for those who may need it.

1. [Performance](/admin/config/development/performance)

  Unless an update is required, clearing cache should fix most issues.
  * Hit **Clear all caches** button once the new Blazy in place.
  * Regenerate CSS and JS as the latest fixes may contain changes to the assets.
    Ignore below if you are aware, and found no asset changes from commits.
    Normally clearing cache suffices when no asset changes are found.
      * Uncheck CSS and JS aggregation options under Bandwidth optimization.
      * Save.
      * [Ignorable] See one of Blazy related pages if display is expected.
      * [Ignorable] Only clear cache if needed.
      * Check both options again.
      * Save again.
      * [Ignorable] Press F5, or CMD/ CTRL + R to refresh browser cache if
        needed.

2. [Admin status](/admin/reports/status)

   Check for any pending update, and run `/update.php` from browser address bar.

3. If Twig templates are customized, compare against the latest.

4. Always test updates at DEV or STAGING environments like a pro so nothing
   breaks your PRODUCTION site till everything is thoroughly reviewed.

5. Read more the [TROUBLESHOOTING](#troubleshooting) section for common trouble
   solutions.


## BROKEN MODULES
Alpha, Beta, DEV releases are for developers only. Beware of possible breakage.

However if it is broken, unless an update is provided, running `drush cr` during
DEV releases should fix most issues as we add new services, or change things.
If you don't drush, before any module update:

1. Always open a separate tab:

   [Performance](/admin/config/development/performance)
2. And so you are ready to hit **Clear all caches** button if any issue. Do not
   reload this page.
3. Instead view other browser tabs, and simply hit the button if any
   issue.
4. Run `/update.php` as required.
5. Only at worst case, know how to run
   [Registry Rebuild](https://www.drupal.org/project/registry_rebuild) safely.
