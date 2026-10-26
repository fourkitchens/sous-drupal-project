#!/bin/sh

# Shared runtime adapters for Sous commands and the sibling devops/hooks files.
# Source this from those entry points; do not execute it as a standalone command.
# Sourcing resolves the caller's project root, changes to it, validates explicit
# environment values and defines helpers. It installs nothing or starts nothing;
# later DDEV commands can auto-start the project as part of normal DDEV behavior.
# See docs/ENVIRONMENTS.md for SOUS_RUNTIME and SOUS_ENVIRONMENT defaults.
# Helpers defined with (...) use subshells to keep directory changes, filtered
# argument lists and temporary variables from leaking into the calling script.

# $0 belongs to the calling script, whose location is two levels below the root.
# Resolve physical paths once so relative backup/theme paths use a stable base.
SOUS_PROJECT_ROOT="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
cd "$SOUS_PROJECT_ROOT"

# A real DDEV container always uses its prepared tools directly. Host scripts
# default to DDEV; native host/CI execution must be selected explicitly.
if [ "${IS_DDEV_PROJECT:-}" = true ]; then
  SOUS_RUNTIME=native
else
  SOUS_RUNTIME="${SOUS_RUNTIME:-ddev}"
fi
case "$SOUS_RUNTIME" in
  ddev|native) ;;
  *) echo 'SOUS_RUNTIME must be ddev or native.' >&2; exit 2 ;;
esac
export SOUS_RUNTIME
# Distinguish an unset value from an explicitly empty or misspelled environment.
if [ "${SOUS_ENVIRONMENT+x}" = x ]; then
  case "$SOUS_ENVIRONMENT" in
    local|nonproduction|production) ;;
    *) echo 'SOUS_ENVIRONMENT must be local, nonproduction, or production.' >&2; exit 2 ;;
  esac
fi

# Check a selected runtime executable before calling it; return the conventional
# missing-command status without installing a replacement or falling back.
runtime_require_tool() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Required tool is unavailable in the prepared runtime: $1" >&2
    return 127
  fi
}

# Run one program plus its separate arguments in the selected runtime. Preserve
# its status and argument boundaries, including paths containing whitespace.
runtime_exec() {
  if [ "$SOUS_RUNTIME" = native ]; then
    runtime_require_tool "$1" || return $?
    "$@"
  else
    runtime_require_tool ddev || return $?
    # Forward only the explicit contract and guard mode, never all host values.
    if [ "${SOUS_ENVIRONMENT+x}" = x ]; then
      if [ "${SOUS_ENVIRONMENT_CHECK+x}" = x ]; then
        ddev exec --dir=/var/www/html --raw env "SOUS_ENVIRONMENT=$SOUS_ENVIRONMENT" "SOUS_ENVIRONMENT_CHECK=$SOUS_ENVIRONMENT_CHECK" "$@"
      else
        ddev exec --dir=/var/www/html --raw env "SOUS_ENVIRONMENT=$SOUS_ENVIRONMENT" "$@"
      fi
    elif [ "${SOUS_ENVIRONMENT_CHECK+x}" = x ]; then
      ddev exec --dir=/var/www/html --raw env "SOUS_ENVIRONMENT_CHECK=$SOUS_ENVIRONMENT_CHECK" "$@"
    else
      ddev exec --dir=/var/www/html --raw "$@"
    fi
  fi
}

# Explicit bootstrap/restore startup; a native environment is already prepared.
runtime_start() {
  if [ "$SOUS_RUNTIME" = ddev ]; then
    runtime_require_tool ddev || return $?
    ddev start
  fi
}

# Always use this project's installed Drush. Supply DDEV's default site URI
# first, allowing a later explicit --uri argument to select the intended site.
runtime_drush() {
  if [ "$SOUS_RUNTIME" = native ]; then
    runtime_require_tool php || return $?
    if [ ! -x "$SOUS_PROJECT_ROOT/vendor/bin/drush" ]; then
      echo 'Project Drush is unavailable; prepare Composer dependencies first.' >&2
      return 1
    fi
    if [ -n "${DDEV_PRIMARY_URL:-}" ]; then
      set -- "--uri=$DDEV_PRIMARY_URL" "$@"
    fi
    "$SOUS_PROJECT_ROOT/vendor/bin/drush" "$@"
  else
    # DDEV normally supplies this URI through its Drush command wrapper. Keep
    # it while explicitly forwarding the environment to the project binary.
    runtime_exec sh -c 'exec vendor/bin/drush --uri="${DDEV_PRIMARY_URL:-http://localhost}" "$@"' sh "$@"
  fi
}

# Composer/npm come from the selected runtime; these wrappers neither install
# them nor change the caller's install/update arguments or failure status.
runtime_composer() {
  if [ "$SOUS_RUNTIME" = native ]; then runtime_require_tool php || return $?; fi
  runtime_exec composer "$@"
}
runtime_npm() {
  if [ "$SOUS_RUNTIME" = native ]; then runtime_require_tool node || return $?; fi
  runtime_exec npm "$@"
}

# Use the locked root CLI, or the DDEV command that delegates to that same CLI.
# A missing installation must fail instead of selecting an unrelated global CLI.
runtime_emulsify() {
  if [ "$SOUS_RUNTIME" = native ]; then
    runtime_require_tool node || return $?
    if [ ! -x "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" ]; then
      echo 'Project Emulsify CLI is unavailable; run npm ci first.' >&2
      return 1
    fi
    "$SOUS_PROJECT_ROOT/node_modules/.bin/emulsify" "$@"
  else
    runtime_require_tool ddev || return $?
    ddev emulsify "$@"
  fi
}

# Print an existing theme path relative to the project. Resolving symlinks first
# prevents build helpers from operating on a directory outside this checkout.
runtime_theme_directory() (
  directory="$(CDPATH='' cd "$1" && pwd -P)" || exit $?
  case "$directory" in
    "$SOUS_PROJECT_ROOT"/*) printf '%s\n' "${directory#"$SOUS_PROJECT_ROOT/"}" ;;
    *) echo 'The theme directory must be inside this project.' >&2; exit 2 ;;
  esac
)

# Run the bootstrapped PHP guard in one mode, forwarding only target selectors.
# The subshell keeps this invocation's guard mode out of subsequent commands.
runtime_environment_check() (
  SOUS_ENVIRONMENT_CHECK="$1"
  shift
  export SOUS_ENVIRONMENT_CHECK
  runtime_target_command check "$@"
)

# Dispatch a guard, cache rebuild or local project hook against the same target
# as export/deploy. Command-specific flags stay on the original Drush command.
runtime_target_command() (
  target_action="$1"
  shift
  # Export/deploy flags stay on their own command, but global target selectors
  # must also select the same site for the pre/post safety checks.
  # Consume only the original argument count: retained selectors are appended to
  # "$@" and retain their argument boundaries after the scan finishes.
  remaining="$#"
  while [ "$remaining" -gt 0 ]; do
    argument="$1"
    shift
    remaining=$((remaining - 1))
    case "$argument" in
      @*)
        # SSH redispatch cannot guarantee this guard's environment/script paths.
        echo 'Sous configuration helpers require a prepared local project. Run the helper on the remote host with SOUS_RUNTIME=native.' >&2
        exit 2
        ;;
      --uri=*|--root=*|--config=*|--alias-path=*|--include=*|--define=*|--local|-l?*|-r?*|-c?*|-D?*) set -- "$@" "$argument" ;;
      --uri|--root|--config|--alias-path|--include|--define|-l|-r|-c|-D)
        if [ "$remaining" -eq 0 ]; then
          echo "Missing value for $argument." >&2
          exit 2
        fi
        value="$1"
        shift
        remaining=$((remaining - 1))
        set -- "$@" "$argument" "$value"
        ;;
    esac
  done
  case "$target_action" in
    check) runtime_drush "$@" php:script devops/scripts/environment-check.php ;;
    cache) runtime_drush "$@" cache:rebuild ;;
    post-import|post-config-import)
      # Resolve the environment inside the target runtime before opening a hook.
      # Production skips local hooks entirely; other targets require the file.
      runtime_exec sh -c '
        if [ "${SOUS_ENVIRONMENT+x}" = x ]; then
          hook_environment=$SOUS_ENVIRONMENT
        elif [ "${IS_DDEV_PROJECT:-}" = true ]; then
          hook_environment=local
        else
          hook_environment=production
        fi
        case "$hook_environment" in
          production) exit 0 ;;
          local|nonproduction) ;;
          *) echo "Invalid environment for a local safety hook." >&2; exit 2 ;;
        esac
        if [ ! -f "$1" ] || [ ! -r "$1" ]; then
          echo "Required project safety hook is missing or unreadable: $1" >&2
          exit 1
        fi
        exec sh "$@"
      ' sh "devops/hooks/$target_action.sh" "$@"
      ;;
    *) echo 'Unknown target command.' >&2; exit 2 ;;
  esac
)

# All three attempts matter after a partial import. Keep the first failure;
# callers retain an earlier database/deployment failure over this cleanup.
# Clear cached pre-import state before the project hook, then verify the active
# mail/cron/environment policy even if either preceding attempt failed.
runtime_safeguards() (
  safeguard_stage="$1"
  shift
  safeguard_status=0
  runtime_target_command cache "$@" || safeguard_status=$?
  if runtime_target_command "$safeguard_stage" "$@"; then :; else
    hook_status=$?
    echo "Project $safeguard_stage safety hook failed ($hook_status)." >&2
    if [ "$safeguard_status" -eq 0 ]; then safeguard_status=$hook_status; fi
  fi
  if runtime_environment_check active "$@"; then :; else
    check_status=$?
    echo "Post-import environment safety check failed ($check_status)." >&2
    if [ "$safeguard_status" -eq 0 ]; then safeguard_status=$check_status; fi
  fi
  return "$safeguard_status"
)

# Gate destructive development workflows before database work. An explicit host
# production value fails early; fallback selection is checked in the actual
# runtime so a host shell cannot mistake itself for the DDEV container.
runtime_require_development() {
  if [ "${SOUS_ENVIRONMENT:-}" = production ]; then
    echo 'This development workflow is disabled in production.' >&2
    return 1
  fi
  # An explicit native host/CI is production-safe by default; DDEV is local.
  runtime_exec sh -c '
    environment=${SOUS_ENVIRONMENT:-}
    if [ -z "$environment" ]; then
      if [ "${IS_DDEV_PROJECT:-}" = true ]; then
        environment=local
      else
        environment=production
      fi
    fi
    case "$environment" in
      local|nonproduction) ;;
      *) echo "This development workflow requires local or nonproduction." >&2; exit 1 ;;
    esac
  '
}
