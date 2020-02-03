<?php


namespace Silver;


use Bitrix\Main\Localization\Loc;

class Ajax
{
	protected $request;
	public function __construct($request)
	{
		$this->request = $request;
	}

	/**
	 * ����� ����� �� ����� ����
	 * @return false|string
	 */
	public function findTags() {
		$data = $this->request["data"];
		return $this->result(DocumentTags::searchTags($data));
	}

	/**
	 * �������� ������ ����� ����� �������� �� id �����
	 * @return false|string
	 */
	public function getFileTags() {
		$file = (integer)$this->request["file"];
		return$this->result(DocumentTags::getTagsByFile($file,', '));
	}

	/**
	 * ���������� ���� � �������
	 * @return false|string
	 */
	public function addTag() {
		$tag = $this->request["tag"];
		$file = $this->request["file"];
		global $USER;
		$result = DocumentTags::addTag($tag, $USER->GetID(), $file);
		DocumentTags::reindexWithTags($file);
		return $this->result($result);
	}

	/**
	 * ���� ������� ������� �������������� �������, ������ ������� ���������
	 * @param $name
	 * @param $args
	 * @return false|string
	 */
	public function __call($name, $args)
	{
		$errors = [Loc::getMessage("SILVER_NO_METHOD", ["#name#"=>$name])];
		return $this->result($errors);
	}

	/**
	 * ��������� ��������� � json ����� ���������
	 * @param bool $arData
	 * @return false|string
	 */
	protected function result($arData = false)
	{
		return json_encode($arData);
	}
}