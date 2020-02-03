<?php

use Bitrix\Main\Context;
use Bitrix\Main\EventManager;

//require(__DIR__ . '/test01/disk.php'); // пока отключил облако тегов ошибка с объектом $USER
require(__DIR__ . '/test01/js.php');
require(__DIR__ . '/test01/lib/Ref.php');
require(__DIR__ . '/test01/lib/DocumentTags.php');
require(__DIR__ . '/test01/lib/Ajax.php');

$eventManager = EventManager::getInstance();
// $eventManager->addEventHandlerCompatible('main', 'onAfterEpilog', 'dumpViews');
// $eventManager->addEventHandlerCompatible('search', "BeforeIndex", "addTagToIndex");

/**
 * Форматированный print_r
 * @param $data
 */
function dump($data) {
	 print "<pre>";
	print_r($data);
	 print "</pre>";
}