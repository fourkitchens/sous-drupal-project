#!/usr/bin/env bash

# Validate native audit output against the explicitly reviewed tooling policy.
# Usage: bash audit.sh npm|composer REPORT LOCK --exit-code N [--policy FILE]
# Never hide the raw audit status/logs: only this narrow policy decision may pass.
set -euo pipefail

directory="$(CDPATH='' cd "$(dirname "$0")" && pwd -P)"
if [ "$#" -lt 5 ]; then
  echo 'Usage: audit.sh npm|composer REPORT LOCK --exit-code N [--policy FILE]' >&2
  exit 2
fi
kind=$1
report=$2
lock=$3
shift 3
policy="$directory/audit-policy.json"
native_status=
while [ "$#" -gt 0 ]; do
  case "$1" in
    --exit-code) [ "$#" -ge 2 ] || exit 2; native_status=$2; shift 2 ;;
    --policy) [ "$#" -ge 2 ] || exit 2; policy=$2; shift 2 ;;
    *) echo "Unknown audit option: $1" >&2; exit 2 ;;
  esac
done
case "$kind:$native_status" in npm:0|npm:1|composer:0|composer:1) ;;
  *) echo 'Audit policy failed: unsupported audit kind or native failure status.' >&2; exit 1 ;;
esac

# jq handles structured JSON; the shell owns command execution and exit codes.
# Missing fields, malformed documents, unexpected advisories and scope changes
# all fail closed instead of being interpreted as an empty audit.
jq -n --arg kind "$kind" --argjson native "$native_status" \
  --slurpfile reports "$report" --slurpfile locks "$lock" --slurpfile policies "$policy" '
  def require($condition; $message): if $condition then . else error($message) end;
  def empty_list: . == [] or . == {};
  require(($reports | length) == 1 and ($locks | length) == 1 and ($policies | length) == 1; "Expected one JSON document per input") |
  $reports[0] as $report | $locks[0] as $lock | $policies[0] as $policy |
  require(($report | type) == "object" and ($report.error | not); "Audit command returned an error") |
  if $kind == "npm" then
    require($report.auditReportVersion == 2; "Unsupported npm audit schema") |
    require(($report.vulnerabilities | type) == "object"; "Invalid npm findings") |
    require($report.metadata.vulnerabilities.total == ($report.vulnerabilities | length); "Inconsistent npm count") |
    require($native == (if ($report.vulnerabilities | length) > 0 then 1 else 0 end); "npm status contradicts findings") |
    [$report.vulnerabilities | to_entries[] |
      .key as $name | .value as $finding | $policy.npm.exceptions[$name] as $expected |
      require(($expected | type) == "object"; "Unreviewed npm finding: " + $name) |
      require($lock.packages["node_modules/npm"].version == $policy.npm.bundle_version; "npm bundle changed") |
      $lock.packages[$expected.node] as $package |
      require($package.version == $expected.version and $package.inBundle == true; "Affected bundled version changed: " + $name) |
      require($finding.name == $name and $finding.isDirect == false and $finding.nodes == [$expected.node] and $finding.severity == $expected.severity; "Finding path, scope or severity changed: " + $name) |
      require(($finding.via | type) == "array" and ($finding.via | length) > 0; "Invalid advisory chain") |
      require(all($finding.via[]; type == "object" and (.url | type) == "string" and (.severity | type) == "string" and $expected.advisories[.url] == .severity); "Unreviewed advisory: " + $name) |
      {package: $name, version: $package.version, severity: $finding.severity, advisories: [$finding.via[].url]}
    ]
  else
    require(($report | has("advisories") and has("abandoned")); "Incomplete Composer audit") |
    require(($report | if has("ignored-advisories") then .["ignored-advisories"] else [] end | empty_list) and ($report | if has("unreachable-repositories") then .["unreachable-repositories"] else [] end | empty_list) and ($report | if has("filter") then .filter else [] end | empty_list); "Composer suppressed findings or could not audit a repository") |
    require(($report.advisories | empty_list); "Composer security advisories are not permitted") |
    require(($report.abandoned | type) == "object" or $report.abandoned == []; "Invalid abandoned package list") |
    require($native == (if ($report.abandoned | length) > 0 then 1 else 0 end); "Composer status contradicts findings") |
    require(($lock.packages | type) == "array"; "Invalid Composer lock") |
    ($lock.packages | map({key: .name, value: .}) | from_entries) as $packages |
    [$report.abandoned | to_entries[] |
      .key as $name | .value as $replacement | $policy.composer.abandoned[$name] as $expected |
      require(($expected | type) == "object" and $replacement == $expected.replacement and $packages[$name].version == $expected.version; "Unreviewed abandoned package: " + $name) |
      {package: $name, version: $expected.version, abandoned: true, replacement: $replacement}
    ]
  end |
  {audit: $kind, native_exit: $native, accepted_exceptions: .}
  '
