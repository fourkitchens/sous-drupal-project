<?php

$app_root = getcwd() . "/web";
$site_path = "sites/default";
define("DRUPAL_ROOT", $app_root);
include $app_root . "/" . $site_path . "/settings.php";
if (($settings["rebuild_access"] ?? FALSE) === TRUE) {
  throw new \RuntimeException("Local development settings loaded outside DDEV.");
}
echo "Local development settings stay in DDEV.\n";
