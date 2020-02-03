<?php

namespace Silver;

use Bitrix\Main\Loader;

class DocumentTags extends Ref
{
	protected $_data;
	/**
	 * DocumentTags constructor.
	 * ѕри создании объекта, можно указать id тега, тогда будет произведена выборка данных из соответствующего HL блока
	 * ¬ противном случае создаетс€ новый тег
	 * @param bool $tag_id
	 */
	public function __construct($tag_id = false)
	{
		parent::__construct($tag_id, "DocumentTags");
	}

	/**
	 * ѕолучает список тегов файла
	 * @param int $fileId - id файла в модуле disk
	 * @param string $glue - разделитель тегов. ≈сли не false, то массив будет преобразован в строку с этим разделетилем
	 * @return array|bool|string массив тегов или строка из тегов или false
	 */
	public static function getTagsByFile(int $fileId, $glue = ",", $limit=10)
	{
		$docTag = new self();
		$res = $docTag->entity::GetList([
			'filter' => [
				'UF_FILE_ID' => $fileId
			],
			'limit' => $limit // по умолчанию работает только первые 10 тегов, потом высока веро€тность уперетьс€ в ограничени€ по величине индекса
		]);
		// TODO: add page nav!
		$arTags = [];
		while ($ar = $res->fetch()) {
			if(!empty($ar["UF_TAG"])){
				$arTags[] = $ar["UF_TAG"];
			}
		}
		if (count($arTags)) {
			if($glue !== false)
				return implode($glue,$arTags);
			return $arTags;
		}
		return false;
	}

	/**
	 * ƒобавл€ет тег к документу
	 * @param $strTag
	 * @param $userId
	 * @param $fileId
	 */
	public static function addTag($strTag, $userId, $fileId)
	{
		$docTag = new self();
		if($docTag->findTag($strTag)){
			if(!in_array($fileId, $docTag->UF_FILE_ID)) {
				$arFiles = $docTag['UF_FILE_ID'];
				$arFiles[] = $fileId;
				$docTag['UF_FILE_ID'] = $arFiles;
			}
		}
		else {
			$docTag->UF_FILE_ID = [$fileId];
			$docTag->UF_USER_ID = $userId;
			$docTag->UF_TAG = $strTag;
		}
		return $docTag->save();
	}

	/**
	 * Ќаходит файлы по тегу
	 * @param $tag - тег
	 * @return bool|mixed
	 */
	public static function findFilesByTag($tag)
	{
		$docTag = new self();
		if($docTag->findTag($tag)){
			return $docTag->UF_FILE_ID;
		}
		return false;
	}

	/**
	 * ѕоиск тегов по подстроке
	 * @param $string - часть тега
	 * @return array - массив тегов, если не найдено - пустой массив
	 */
	public static function searchTags($string) {
		$docTag = new self();
		$arTags = [];
		$res = $docTag->entity::GetList([
			'filter' => [
				'%UF_TAG' => $string
			]
		]);
		while ($arTag = $res->fetch()) {
			$arTags[] = $arTag["UF_TAG"];
		}
		return $arTags;
	}

	/**
	 * »щет тег в Ѕƒ и загружает свойства объекта в случае успеха
	 * @param $strTag
	 * @return bool
	 */
	public function findTag($strTag)
	{
		$arData = $this->entity::GetRow([
			'filter' => [
				'UF_TAG' => $strTag
			]
		]);
		if(!empty($arData)) {
			$this->SetParams($arData);
			$this->id = $arData["ID"];
			return true;
		}
		return false;
	}

	/**
	 * ќпред€лет способ получени€ свойств дл€ перезагрузки
	 * @return mixed
	 */
	protected function GetParams()
	{
		return $this->_data;
	}

	/**
	 * ќпредел€ет способ сохранени€ свойств дл€ перезагрузки
	 * @param $arData
	 * @return mixed|void
	 */
	protected function SetParams($arData)
	{
		$this->_data = $arData;
	}

	/**
	 * ќпредел€ет способ записи в журнал
	 * @param $message
	 * @param string $severity
	 * @return mixed
	 */
	protected function log($message, $severity = 'INFO')
	{
		return parent::toLog($message, $severity, 'doc_tag_' . (string)($this->id ?? '0'));
	}

	/**
	 * «аписываем теги в специальную таблицу b_disk_object_head_index
	 * *** расковыр€л методы модул€, чтобы пон€ть как это работает ***
	 * @param $fileId - id файла
	 */
	public static function reindexWithTags($fileId) {
		if (!$fileId) {
			return false;
		}
		if(Loader::IncludeModule('search') && Loader::IncludeModule('disk')){
			// $fileId = 50;
			$tags = self::getTagsByFile((integer)$fileId, ' '); // получаем теги файла, через пробел
			$file = \Bitrix\Disk\File::loadById($fileId); // получаем объект файла

			$textBuilder = \Bitrix\Disk\Search\FullTextBuilder::create() // создаем полнотекстовый индекс
				->addText($file->getName()) // добавл€ем в него им€ файла
				->addText($tags) // добавл€ем в него теги
				->addUser($file->getCreatedBy()); // добавл€ем в него пользовател€

			\Bitrix\Disk\Internals\Index\ObjectHeadIndexTable::upsert( // записываем индекс в Ѕƒ
				$fileId, // id файла
				$textBuilder->getSearchValue() // обработана€ строка индекса
			);
			if($textBuilder->getSearchValue()) return true;
		}
		return false;
	}
}