#!/usr/bin/env bash
# Check local Markdown links/case/anchors and package licenses. No HTTP requests.
# PHP handles Markdown/URI strings; the entry point and file inventory are shell.
set -euo pipefail
SOURCE="$(CDPATH='' cd "$(dirname "$0")/../.." && pwd -P)"
roots=("$SOURCE"); output=''; self_test=0
while [ "$#" -gt 0 ]; do
  case "$1" in
    --sources)
      [ "$#" -ge 2 ] || exit 2; parent="$2"; roots=()
      roots=("$parent/sous-drupal-project" "$parent/sous-drupal-packages")
      for directory in "$parent"/sous-drupal-packages/packages/*; do roots+=("$directory"); done
      shift 2 ;;
    --json-output) [ "$#" -ge 2 ] || exit 2; output="$2"; shift 2 ;;
    --self-test) self_test=1; shift ;;
    *) echo 'Usage: documentation.sh [--sources PARENT] [--json-output FILE] [--self-test]' >&2; exit 2 ;;
  esac
done
work="$(mktemp -d "${TMPDIR:-/tmp}/sous-doc-check.XXXXXX")"
trap 'rm -rf -- "$work"' EXIT
inventory="$work/inventory.jsonl"; : > "$inventory"
for root in "${roots[@]}"; do
  [ -f "$root/composer.json" ] || [ -f "$root/package.json" ] || { echo "Missing Sous source: $root" >&2; exit 2; }
  root="$(CDPATH='' cd "$root" && pwd -P)"
  if [ "$(git -C "$root" rev-parse --show-toplevel 2>/dev/null || :)" = "$root" ]; then
    git -C "$root" ls-files -z --cached --others --exclude-standard > "$work/files"
  else
    (cd "$root"; find . -type d \( -name .git -o -name node_modules -o -name vendor -o -name reference -o -name .integration -o -name .integration-results -o -name .sfdx \) -prune -o -type f -print0) > "$work/files"
  fi
  while IFS= read -r -d '' file; do
    case "/$file/" in */node_modules/*|*/vendor/*|*/reference/*|*/.integration/*|*/.integration-results/*|*/.sfdx/*|*/.git/*) continue;; esac
    case "$file" in *.md|*.MD) [ -f "$root/$file" ] && jq -cn --arg root "$root" --arg file "$file" '{root:$root,file:$file}' >> "$inventory";; esac
  done < "$work/files"
done
if php /dev/stdin "$inventory" "$self_test" "${roots[@]}" > "$work/result.json" <<'PHP'
<?php
// Check exact spelling even when the host filesystem ignores filename case.
function exactPath(string $path): bool  {
  $parts=[];
  foreach(explode('/', $path) as $part) {
    if($part===''||$part==='.')continue;
    if($part==='..') {
      array_pop($parts);
    }
    else {
      $parts[]=$part;
    }
  }
  $current='/';
  foreach($parts as $part) {
    if(!is_dir($current)||!in_array($part,scandir($current),TRUE))return FALSE;
    $current=rtrim($current,'/').'/'.$part;
  }
  return file_exists($current);
}
function prose(string $text): array  {
  $result=[];
  $fence=NULL;
  foreach(explode("\n",$text) as $number=>$line) {
    if(preg_match('/^\s*(`{3,}|~{3,})/',$line,$m)) {
      $char=$m[1][0];
      $fence=$fence===$char?NULL:($fence===NULL?$char:$fence);
      continue;
    }
    if($fence===NULL)$result[$number+1]=$line;
  }
  return $result;
}
function anchors(string $text): array  {
  $counts=[];
  $result=[];
  foreach(prose($text) as $line) {
    if(!preg_match('/^ {0,3}#{1,6}\s+(.+?)\s*#*\s*$/',$line,$m))continue;
    $title=preg_replace('/\[([^\]]*)\]\([^)]*\)/','$1',$m[1]);
    $title=preg_replace('/<[^>]*>|[*~`]/','',$title);
    $slug=preg_replace('/\s/u','-',preg_replace('/[^\p{L}\p{N}_\s-]/u','',strtolower($title)));
    $count=$counts[$slug]??0;
    $result[]=$slug.($count?'-'.$count:'');
    $counts[$slug]=$count+1;
  }
  return $result;
}
if($argv[2]==='1') {
  if(anchors("# A heading\n# A heading\n````md\n[example](missing.md)\n````")!==['a-heading','a-heading-1']||anchors('## A `code` heading')!==['a-code-heading'])throw new RuntimeException('Documentation self-test failed.');
  print json_encode(['status'=>'pass','self_test'=>TRUE],JSON_PRETTY_PRINT)."\n";
  exit;
}
$roots=array_slice($argv,3);
$repositories=[];
$errors=[];
foreach($roots as $root) {
  $manifest_file=is_file($root.'/composer.json')?'composer.json':'package.json';
  $manifest=json_decode(file_get_contents($root.'/'.$manifest_file),TRUE,512,JSON_THROW_ON_ERROR);
  $repositories[basename($manifest['name'])]=realpath($root);
  foreach(['composer.json','package.json'] as $file) {
    if(!is_file($root.'/'.$file)||($manifest_file==='package.json'&&$file==='package.json'))continue;
    $data=json_decode(file_get_contents($root.'/'.$file),TRUE,512,JSON_THROW_ON_ERROR);
    if(($data['license']??NULL)!=='GPL-2.0-or-later')$errors[]="$root/$file: license differs from GPL-2.0-or-later";
  }
  if(!exactPath($root.'/LICENSE')||!str_starts_with(file_get_contents($root.'/LICENSE'),'SPDX-License-Identifier: GPL-2.0-or-later'))$errors[]="$root/LICENSE: missing/case-mismatched license or SPDX grant";
  if(is_file($root.'/package-lock.json')) {
    $lock=json_decode(file_get_contents($root.'/package-lock.json'),TRUE,512,JSON_THROW_ON_ERROR);
    if(($lock['packages']['']['license']??NULL)!=='GPL-2.0-or-later')$errors[]="$root/package-lock.json: inconsistent root license";
  }
}
$files=0;
$targets=0;
$seen=[];
foreach(file($argv[1],FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $record) {
  $item=json_decode($record,TRUE,512,JSON_THROW_ON_ERROR);
  $document=$item['root'].'/'.$item['file'];
  if(isset($seen[$document]))continue;
  $seen[$document]=TRUE;
  ++$files;
  if(!exactPath($document))$errors[]="$document: source filename case differs";
  foreach(prose(file_get_contents($document)) as $number=>$line) {
    $line=preg_replace('/`+[^`]*`+/','',$line);
    preg_match_all('/\[[^\]]*\]\((<[^>]*>|[^)]*)\)/',$line,$links);
    $references=$links[1];
    if(preg_match('/^\s*\[[^\]]+\]:\s*(<[^>]*>|\S+)/',$line,$m))$references[]=$m[1];
    foreach($references as $raw) {
      $target=trim($raw);
      $target=str_starts_with($target,'<')&&str_ends_with($target,'>')?substr($target,1,-1):(preg_split('/\s+/',$target)[0]??'');
      $url=parse_url($target);
      if($url===FALSE)continue;
      $fragment=rawurldecode($url['fragment']??'');
      if(isset($url['scheme'])||isset($url['host'])) {
        $parts=explode('/',trim($url['path']??'','/'));
        if(strtolower($url['host']??'')!=='github.com'||($parts[0]??'')!=='fourkitchens'||!isset($repositories[$parts[1]??'']))continue;
        $base=$repositories[$parts[1]];
        if(count($parts)===2) {
          $path=$base.'/README.md';
        }
        elseif(count($parts)>=4&&in_array($parts[2],['blob','tree'],TRUE)&&in_array($parts[3],['main','HEAD'],TRUE)) {
          $path=$base.'/'.rawurldecode(implode('/',array_slice($parts,4)));
        }
        else {
          continue;
        }
      }
      else {
        $path=isset($url['path'])&&$url['path']!==''?dirname($document).'/'.rawurldecode($url['path']):$document;
      }
      ++$targets;
      if(!exactPath($path)) {
        $errors[]="$document:$number: missing or case-mismatched target: $raw";
        continue;
      }
      if(is_dir($path))$path.='/README.md';
      if($fragment!==''&&strtolower(pathinfo($path,PATHINFO_EXTENSION))==='md'&&(!is_file($path)||!in_array($fragment,anchors(file_get_contents($path)),TRUE)))$errors[]="$document:$number: missing heading anchor: $raw";
    }
  }
}
print json_encode(['status'=>$errors?'fail':'pass','repositories'=>count($roots),'markdown_files'=>$files,'local_targets'=>$targets,'errors'=>$errors,'scope'=>'Local Markdown paths, exact filename case, heading anchors, supplied Sous GitHub main/HEAD targets and package licenses; no HTTP requests.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
exit($errors?1:0);

PHP
then status=0; else status=$?; fi
cat "$work/result.json"
if [ -n "$output" ]; then mkdir -p "$(dirname "$output")"; cp "$work/result.json" "$output"; fi
exit "$status"
