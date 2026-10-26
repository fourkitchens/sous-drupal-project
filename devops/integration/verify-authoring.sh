#!/usr/bin/env bash

# Exercise real Page forms in the owned disposable DDEV site. curl owns HTTP
# and cookies; Drupal's installed PHP DOM/URI libraries parse forms reliably.
# Usage: bash verify-authoring.sh BASE_URL [--uid N] [--ca-file CERTIFICATE]
# PHP, not the shell, expands variables in the two literal PHP programs below.
# shellcheck disable=SC2016
set -euo pipefail
[ "${SOUS_DISPOSABLE_TEST:-}" = 1 ] || { echo 'Set SOUS_DISPOSABLE_TEST=1 only for a disposable site.' >&2; exit 1; }
[ "$#" -ge 1 ] || { echo 'Expected a disposable base URL.' >&2; exit 2; }
base=${1%/}
shift
[[ "$base" =~ ^https?://[^/]+$ ]] || { echo 'Expected an HTTP(S) site origin.' >&2; exit 2; }
uid=1
certificate=()
while [ "$#" -gt 0 ]; do
  case "$1" in
    --uid) [ "$#" -ge 2 ] || exit 2; uid=$2; shift 2 ;;
    --ca-file) [ "$#" -ge 2 ] || exit 2; certificate=(--cacert "$2"); shift 2 ;;
    *) echo "Unknown authoring option: $1" >&2; exit 2 ;;
  esac
done
[[ "$uid" =~ ^[0-9]+$ ]] || exit 2
umask 077
mkdir -p .integration
temporary=$(mktemp -d "$PWD/.integration/sous-authoring.XXXXXX")
container_response="/var/www/html/.integration/${temporary##*/}/response.html"
suffix=$(od -An -tx1 -N12 /dev/urandom | tr -d ' \n')
marker="Sous HTTP authoring $suffix"
edited="Edited $marker"
cleanup() {
  status=$?
  trap - EXIT
  # Match this run's unique titles only, including any partially saved Page.
  if ddev exec --raw env "SOUS_AUTHORING_MARKER=$marker" vendor/bin/drush php:eval '
    $marker = getenv("SOUS_AUTHORING_MARKER");
    $ids = \Drupal::entityQuery("node")->accessCheck(FALSE)->condition("type", "page")
      ->condition("title", [$marker, "Edited " . $marker], "IN")->execute();
    $storage = \Drupal::entityTypeManager()->getStorage("node");
    $storage->delete($storage->loadMultiple($ids));
  ' > /dev/null; then :; else
    cleanup_status=$?
    [ "$status" -ne 0 ] || status=$cleanup_status
  fi
  if rm -rf -- "$temporary"; then :; else
    cleanup_status=$?
    [ "$status" -ne 0 ] || status=$cleanup_status
  fi
  exit "$status"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

request() {
  # TLS verification stays enabled. Never print the login URL or cookie file.
  # The conditional array expansion also supports empty arrays under macOS
  # Bash 3.2 with nounset, without passing curl a spurious empty argument.
  effective=$(curl --fail --silent --show-error --location --noproxy '*' \
    --connect-timeout 15 --max-time 60 ${certificate[@]+"${certificate[@]}"} \
    --cookie "$temporary/cookies" --cookie-jar "$temporary/cookies" \
    --output "$temporary/response.html" --write-out '%{url_effective}' "$@")
  case "$effective" in "$base"/*) ;; *) echo 'Unexpected authoring redirect origin.' >&2; return 1 ;; esac
}

save_page() {
  title=$1
  # Native DOM parsing retains Drupal session/CSRF hidden fields. Read the
  # private mounted response file: DDEV exec does not forward redirected stdin.
  # URI resolution avoids parsing HTML or URLs with shell regular expressions.
  ddev exec --raw php -r '
    require "vendor/autoload.php";
    libxml_use_internal_errors(TRUE);
    $document = new DOMDocument();
    $document->loadHTML(file_get_contents($argv[2]));
    foreach ($document->getElementsByTagName("form") as $form) {
      $values = [];
      foreach ($form->getElementsByTagName("input") as $input) {
        if ($input->getAttribute("type") === "hidden" && $input->hasAttribute("name") && !$input->hasAttribute("disabled")) {
          $values[$input->getAttribute("name")] = $input->getAttribute("value");
        }
      }
      if (str_starts_with($values["form_id"] ?? "", "node_page_")) {
        foreach (["form_id", "form_token", "form_build_id"] as $required) {
          if (empty($values[$required])) { throw new RuntimeException("Missing Page CSRF/session field: " . $required); }
        }
        $action = GuzzleHttp\Psr7\UriResolver::resolve(new GuzzleHttp\Psr7\Uri($argv[1]), new GuzzleHttp\Psr7\Uri($form->getAttribute("action")));
        echo json_encode(["action" => (string) $action, "values" => $values], JSON_THROW_ON_ERROR);
        exit;
      }
    }
    throw new RuntimeException("No accessible Page form was found.");
  ' "$effective" "$container_response" > "$temporary/form.json"
  action=$(jq -er '.action' "$temporary/form.json")
  case "$action" in "$base"/*) ;; *) echo 'Unexpected Page form origin.' >&2; return 1 ;; esac
  fields=()
  while IFS= read -r -d '' field; do fields+=(--data-urlencode "$field"); done < <(
    jq -j '.values | to_entries[] | .key, "=", .value, "\u0000"' "$temporary/form.json"
  )
  request "${fields[@]}" --data-urlencode "title[0][value]=$title" \
    --data-urlencode "field_seo_title[0][value]=$title" \
    --data-urlencode "body[0][value]=<p>$title</p>" --data-urlencode 'body[0][format]=basic_html' \
    --data-urlencode 'field_teaser_text[0][value]=A temporary authoring regression Page.' \
    --data-urlencode 'status[value]=1' --data-urlencode 'op=Save' "$action"
  grep -Fq "<p>$title</p>" "$temporary/response.html" || { echo 'Saved Page Body did not render.' >&2; return 1; }
}

login=$(ddev drush --no-ansi --uri="$base" user:login --uid="$uid" --no-browser | tail -n 1)
case "$login" in "$base"/*) ;; *) echo 'Drush returned a different login origin.' >&2; exit 1 ;; esac
request "$login"
request "$base/node/add/page"
save_page "$marker"
[[ "$effective" =~ /node/([0-9]+)(/|$) ]] || { echo 'Page creation did not return a node route.' >&2; exit 1; }
node=${BASH_REMATCH[1]}
request "$base/node/$node/edit"
save_page "$edited"
if grep -Fq "<p>$marker</p>" "$temporary/response.html"; then
  echo 'The original Page Body remains after editing.' >&2
  exit 1
fi
echo 'PASS: HTTP login, Page creation, editing, saving, and Body rendering.'
