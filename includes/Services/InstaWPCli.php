<?php

require_once dirname(dirname(__DIR__)). "/vendor/autoload.php";

use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;

function myMethod() {
  // Use functions/classes from dependencies
  $result = InstaMigrateService::InstallInstaWpConnect();
  return $result;
}

// Call your method
$result = myMethod();
echo $result;