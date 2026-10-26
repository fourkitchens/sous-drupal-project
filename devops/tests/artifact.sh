#!/usr/bin/env bash
# Build the actual uncommitted source archive in an owned Git tree, then use
# Composer's real local dist downloader. No source index, commit or DB is changed.
source "$(dirname "$0")/_test.sh"
for tool in composer unzip; do command -v "$tool" >/dev/null || fail "Missing $tool"; done
snapshot="$TEST_ROOT/source snapshot"; mkdir -p "$snapshot"
if [ "$(git -C "$SOURCE" rev-parse --show-toplevel 2>/dev/null || :)" = "$SOURCE" ]; then
  git -C "$SOURCE" ls-files -z --cached --others --exclude-standard > "$TEST_ROOT/files"
else
  (cd "$SOURCE"; find . -type d \( -name .git -o -name node_modules -o -name vendor -o -name reference -o -name .sfdx -o -name .integration-results \) -prune -o -type f -print0) > "$TEST_ROOT/files"
fi
while IFS= read -r -d '' relative; do
  case "/$relative/" in */node_modules/*|*/vendor/*|*/reference/*|*/.sfdx/*|*/.integration-results/*|*/__pycache__/*) continue;; esac
  [ -f "$SOURCE/$relative" ] || continue
  mkdir -p "$snapshot/$(dirname "$relative")"
  cp -p "$SOURCE/$relative" "$snapshot/$relative"
done < "$TEST_ROOT/files"
git -C "$snapshot" init --quiet
git -C "$snapshot" add --all --force
tree="$(git -C "$snapshot" write-tree)"
git -C "$snapshot" archive --worktree-attributes --format=zip --output="$TEST_ROOT/starter.zip" "$tree"
unzip -Z1 "$TEST_ROOT/starter.zip" > "$TEST_ROOT/archive-files"
excludes "$TEST_ROOT/archive-files" devops/integration/
contains "$TEST_ROOT/archive-files" devops/tests/
if grep '^devops/tests/.' "$TEST_ROOT/archive-files" >/dev/null; then fail 'maintainer tests leaked into archive'; fi
pass 'Git distribution archive omits integration and retains an empty tests directory'
mkdir "$TEST_ROOT/composer archive"
(cd "$snapshot"; composer --no-plugins --no-scripts archive --format=zip --file=starter-fixture --dir="$TEST_ROOT/composer archive") > "$TEST_ROOT/output" 2>&1 || fail 'Composer archive failed'
unzip -Z1 "$TEST_ROOT/composer archive/starter-fixture.zip" > "$TEST_ROOT/composer-archive-files"
excludes "$TEST_ROOT/composer-archive-files" devops/integration/
if grep '^devops/tests/.' "$TEST_ROOT/composer-archive-files"; then fail 'maintainer tests leaked into Composer archive'; fi
for file in patches/patches.json patches/embed-3517882.patch patches/field-group-2969051.patch; do
  contains "$TEST_ROOT/archive-files" "$file"
  contains "$TEST_ROOT/composer-archive-files" "$file"
done
pass 'Composer archive exclusions omit maintainer tools and retain the PHP patches'
repository="$(jq -cn --arg url "file://$TEST_ROOT/starter.zip" '{type:"package",package:{name:"fourkitchens/sous-drupal-project",version:"99.0.0",type:"project",dist:{type:"zip",url:$url}}}')"
export COMPOSER_HOME="$TEST_ROOT/composer" COMPOSER_CACHE_DIR="$TEST_ROOT/composer-cache" COMPOSER_DISABLE_NETWORK=1
expect_status 0 composer --no-plugins create-project --no-install --no-scripts --no-interaction --repository="$repository" fourkitchens/sous-drupal-project:99.0.0 "$TEST_ROOT/generated dist project"
dist="$TEST_ROOT/generated dist project"
[ ! -e "$dist/devops/integration" ] || fail 'Composer dist project includes integration'
[ -d "$dist/devops/tests" ] && [ -z "$(ls -A "$dist/devops/tests")" ] || fail 'Composer dist tests directory is not empty'
pass 'real Composer create-project dist contains no maintainer integration/test files'
# --prefer-source retains source files before post-create. Exercise the actual
# one-time naming/cleanup hook with those files still present, without bootstrap.
clone="$TEST_ROOT/generated source project"; cp -R "$snapshot" "$clone"
(cd "$clone"; sh devops/scripts/sous-setup.sh) > "$TEST_ROOT/output" 2>&1 || fail 'source project naming/cleanup failed'
[ ! -e "$clone/devops/integration" ] || fail 'source project retained integration'
[ -d "$clone/devops/tests" ] && [ -z "$(ls -A "$clone/devops/tests")" ] || fail 'source project tests directory is not empty'
json_assert "$clone/package.json" '.scripts|has("release:test")|not'
json_assert "$SOURCE/package.json" '.scripts|has("release:test")'
pass 'source checkout post-create cleanup removes tooling but preserves maintainer release tests'
echo "PASS: $TEST_COUNT artifact check groups."
