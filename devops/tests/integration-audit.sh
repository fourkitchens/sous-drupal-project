#!/usr/bin/env bash
# Test the real fail-closed audit policy with synthetic reports, not a registry.
source "$(dirname "$0")/_test.sh"
POLICY="$SOURCE/devops/integration/audit-policy.json"
AUDIT="$SOURCE/devops/integration/audit.sh"
make_npm() {
  jq '{auditReportVersion:2,vulnerabilities:(.npm.exceptions|with_entries(.value as $entry|.value={name:.key,severity:$entry.severity,isDirect:false,nodes:[$entry.node],via:($entry.advisories|to_entries|map({url:.key,severity:.value}))})),metadata:{vulnerabilities:{total:2}}}' "$POLICY" > "$TEST_ROOT/npm-report.json"
  jq '{packages:({"node_modules/npm":{version:.npm.bundle_version}}+(.npm.exceptions|to_entries|map({key:.value.node,value:{version:.value.version,inBundle:true}})|from_entries))}' "$POLICY" > "$TEST_ROOT/npm-lock.json"
}
gate() { bash "$AUDIT" "$1" "$TEST_ROOT/$1-report.json" "$TEST_ROOT/$1-lock.json" --exit-code "$2"; }
for flag in '' 1; do
  if [ "$flag" = 1 ]; then theme='bad/name'; else theme=integration; fi
  expect_status 1 env SOUS_DISPOSABLE_TEST="$flag" PATH=/nonexistent /bin/sh "$SOURCE/devops/integration/theme.sh" "$theme"
done
pass 'theme integration guard runs before any DDEV command'
make_npm; expect_status 0 gate npm 1
json_assert "$TEST_ROOT/output" '.accepted_exceptions|length==2'
printf '{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{"total":0}}}\n' > "$TEST_ROOT/npm-report.json"
expect_status 0 gate npm 0
json_assert "$TEST_ROOT/output" '.accepted_exceptions|length==0'
pass 'only exact reviewed npm exceptions or a clean report are accepted'
for mutation in \
  '.vulnerabilities.new={}' \
  '.vulnerabilities.undici.severity="critical"' \
  '.vulnerabilities.undici.via += [{url:"https://github.com/advisories/GHSA-new",severity:"high"}]' \
  '.vulnerabilities.undici.nodes=["node_modules/undici"]' \
  '.vulnerabilities.undici.isDirect=true' \
  '.error={code:"ENETUNREACH"}' \
  '.auditReportVersion=3'; do
  make_npm; jq "$mutation" "$TEST_ROOT/npm-report.json" > "$TEST_ROOT/change.json"; mv "$TEST_ROOT/change.json" "$TEST_ROOT/npm-report.json"
  expect_failure gate npm 1
done
for mutation in \
  '.packages["node_modules/npm/node_modules/undici"].version="6.28.0"' \
  '.packages["node_modules/npm"].version="12.2.1"' \
  '.packages["node_modules/npm/node_modules/undici"].inBundle=false'; do
  make_npm; jq "$mutation" "$TEST_ROOT/npm-lock.json" > "$TEST_ROOT/change.json"; mv "$TEST_ROOT/change.json" "$TEST_ROOT/npm-lock.json"
  expect_failure gate npm 1
done
for status in 0 2 127; do make_npm; expect_failure gate npm "$status"; done
pass 'npm severity/advisory/path/version/schema/network/status regressions fail closed'
printf '{"advisories":[],"abandoned":{"oomphinc/composer-installers-extender":null},"filter":[]}\n' > "$TEST_ROOT/composer-original.json"
printf '{"packages":[{"name":"oomphinc/composer-installers-extender","version":"2.0.1"}]}\n' > "$TEST_ROOT/composer-lock.json"
cp "$TEST_ROOT/composer-original.json" "$TEST_ROOT/composer-report.json"
expect_status 0 gate composer 1
json_assert "$TEST_ROOT/output" '.accepted_exceptions|length==1'
printf '{"advisories":[],"abandoned":[]}\n' > "$TEST_ROOT/composer-report.json"
expect_status 0 gate composer 0
for mutation in \
  '.advisories={"drupal/core":[{advisoryId:"new"}]}' \
  '.advisories=null' \
  '.["ignored-advisories"]={"drupal/core":[{advisoryId:"suppressed"}]}' \
  '.["unreachable-repositories"]=["https://packages.drupal.org/8"]' \
  '.filter=["ignored advisory"]' \
  '.abandoned={"new/package":null}'; do
  jq "$mutation" "$TEST_ROOT/composer-original.json" > "$TEST_ROOT/composer-report.json"
  expect_failure gate composer 1
done
cp "$TEST_ROOT/composer-original.json" "$TEST_ROOT/composer-report.json"
jq '.packages[0].version="2.0.0"' "$TEST_ROOT/composer-lock.json" > "$TEST_ROOT/change.json"; mv "$TEST_ROOT/change.json" "$TEST_ROOT/composer-lock.json"
expect_failure gate composer 1
pass 'Composer advisories, suppression, unreachable repositories and changed abandonment fail closed'
echo "PASS: $TEST_COUNT audit-policy check groups."
