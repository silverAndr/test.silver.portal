<?php

namespace Silver;

use Bitrix\Main\Loader;

class DocumentTags extends Ref
{
	protected $_data;
	/**
	 * DocumentTags constructor.
	 * ��� �������� �������, ����� ������� id ����, ����� ����� ����������� ������� ������ �� ���������������� HL �����
	 * � ��������� ������ ��������� ����� ���
	 * @param bool $tag_id
	 */
	public function __construct($tag_id = false)
	{
		parent::__construct($tag_id, "DocumentTags");
	}

	/**
	 * �������� ������ ����� �����
	 * @param int $fileId - id ����� � ������ disk
	 * @param string $glue - ����������� �����. ���� �� false, �� ������ ����� ������������ � ������ � ���� ������������
	 * @return array|bool|string ������ ����� ��� ������ �� ����� ��� false
	 */
	public static function getTagsByFile(int $fileId, $glue = ",", $limit=10)
	{
		$docTag = new self();
		$res = $docTag->entity::GetList([
			'filter' => [
				'UF_FILE_ID' => $fileId
			],
			'limit' => $limit // �� ��������� �������� ������ ������ 10 �����, ����� ������ ����������� ��������� � ����������� �� �������� �������
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
	 * ��������� ��� � ���������
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
	 * ������� ����� �� ����
	 * @param $tag - ���
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
	 * ����� ����� �� ���������
	 * @param $string - ����� ����
	 * @return array - ������ �����, ���� �� ������� - ������ ������
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
	 * ���� ��� � �� � ��������� �������� ������� � ������ ������
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
	 * ��������� ������ ��������� ������� ��� ������������
	 * @return mixed
	 */
	protected function GetParams()
	{
		return $this->_data;
	}

	/**
	 * ���������� ������ ���������� ������� ��� ������������
	 * @param $arData
	 * @return mixed|void
	 */
	protected function SetParams($arData)
	{
		$this->_data = $arData;
	}

	/**
	 * ���������� ������ ������ � ������
	 * @param $message
	 * @param string $severity
	 * @return mixed
	 */
	protected function log($message, $severity = 'INFO')
	{
		return parent::toLog($message, $severity, 'doc_tag_' . (string)($this->id ?? '0'));
	}

	/**
	 * ���������� ���� � ����������� ������� b_disk_object_head_index
	 * *** ���������� ������ ������, ����� ������ ��� ��� �������� ***
	 * @param $fileId - id �����
	 */
	public static function reindexWithTags($fileId) {
		if (!$fileId) {
			return false;
		}
		if(Loader::IncludeModule('search') && Loader::IncludeModule('disk')){
			// $fileId = 50;
			$tags = self::getTagsByFile((integer)$fileId, ' '); // �������� ���� �����, ����� ������
			$file = \Bitrix\Disk\File::loadById($fileId); // �������� ������ �����

			$textBuilder = \Bitrix\Disk\Search\FullTextBuilder::create() // ������� �������������� ������
				->addText($file->getName()) // ��������� � ���� ��� �����
				->addText($tags) // ��������� � ���� ����
				->addUser($file->getCreatedBy()); // ��������� � ���� ������������

			\Bitrix\Disk\Internals\Index\ObjectHeadIndexTable::upsert( // ���������� ������ � ��
				$fileId, // id �����
				$textBuilder->getSearchValue() // ����������� ������ �������
			);
			if($textBuilder->getSearchValue()) return true;
		}
		return false;
	}
}