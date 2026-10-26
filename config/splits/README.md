# Optional environment splits

The complete production-safe site export remains in `config/default`. These
tracked directories are available only for projects that explicitly adopt
`web/recipes/sous-config-split` and configure its local/nonproduction differences.
No Config Split dependency or development module is enabled by default.

Use normal runtime overrides for simple environment values. Use these native
Config Split directories only when modules or stored configuration must differ.
Keep the complete export, split definitions, and relevant split contents under
project version control. Do not add an unconditional partial import of these
directories; native Config Split transforms ordinary export/import operations.
