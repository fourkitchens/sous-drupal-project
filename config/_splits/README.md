# Config Split definitions

Generated projects that adopt `web/recipes/sous-config-split` keep environment
split definitions here. The first normal configuration export populates this
directory from the recipe-created definitions. The source of reusable defaults
is the bundled recipe; there is no second copy to maintain in the starter.

The always-active `_splits` definition stays in `config/default` as a bootstrap
entry point for fresh imports. It routes `sous_local` and `sous_staging` here.
Environment-specific module and configuration values live in `config/local`
and `config/staging`; runtime settings choose which environment split is active.

Add project-specific definitions to `_splits.complete_list` when dev, test or
multidev need distinct behavior. Keep all active directories present and outside
the web root. Native Config Split handles ordering and ordinary export/import;
do not add unconditional partial imports. See the
[bundled recipe guide](../../web/recipes/sous-config-split/README.md).
