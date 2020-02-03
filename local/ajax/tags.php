<?php

use Bitrix\Main\Context;

require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = Context::getCurrent()->getRequest();
$sMethod = trim($request['method']);

header('Cache-Control: private, max-age=0, no-cache');
header('Content-Type: application/json');

$main = new Silver\Ajax($request);
print $main->$sMethod();
