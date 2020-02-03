<?php

use Bitrix\Main\Page\Asset;

CJSCore::RegisterExt(
	'doc_tags',
	array(
		'js' => '/local/js/doc_tags.js',
		'lang' => '/local/lang/' . LANGUAGE_ID . '/doc_tags.js.php',
		'css' => '/local/css/doc_tags.css',
		'rel' => ['ajax']
	)
);

CJSCore::Init('doc_tags');

$asset = Asset::getInstance();

$asset->addString('<script>BX.ready(function () { BX.SilverDocTags.createAddTagButton(); });</script>');
