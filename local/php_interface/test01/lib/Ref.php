<?php

namespace Silver;


use Bitrix\Main\Loader;

abstract class Ref implements \ArrayAccess
{
	protected $data;
	protected $entity;
	protected $id;

	public function __construct($id = false, $name)
	{
		$this->entity = self::getEntity($name);
		if ($id > 0) {
			$this->id = $id;
			$this->SetParams($this->GetById());
		}
	}

	/**
	 *
	 * @param $tableName
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function getEntity($hlBlockName)
	{
		Loader::includeModule("highloadblock");
		$hlblock = HL\HighloadBlockTable::getRow(['filter' => ['NAME' => $hlBlockName]]);
		$entity = HL\HighloadBlockTable::compileEntity($hlblock);
		return $entity->getDataClass();
	}

	/**
	 * �������� ���� �������� highload �����
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function GetById()
	{
		return $this->entity::getRowById($this->id); // �������� ����
	}

	/**
	 * ��������� $message � ������ �������
	 * @param $message - �����, ������� ���� ��������
	 * @param $severity - ������� �������� ������
	 * @param $type - ����������� ID ���� �������
	 * @return mixed
	 */
	protected static function toLog($message, $severity, $type)
	{
		global $USER;
		$userId = $USER->GetID();
		return \CEventLog::Add(array(
				'SEVERITY' => $severity,
				'AUDIT_TYPE_ID' => $type,
				'MODULE_ID' => 'user',
				'DESCRIPTION' => $message,
				'ITEM_ID' => $userId
			)
		);
	}

	/**
	 * ��� ���������� ������ ����� ������������ ����������� ����� toLog, ��� ����� ���� �������� � ���� ������� ��������
	 * ������ � ����������� ID ���� �������, ���� ����� ������������ ���� ��������������
	 * @param $message
	 * @param $severity
	 * @return mixed
	 */
	protected abstract function log($message, $severity);

	/**
	 * ������ ������ � ������
	 * @param $message
	 */
	public function error($message)
	{
		$this->log($message, 'ERROR');
	}

	/**
	 * ����� ��������� ����� ���� ��������� � ������
	 * ID ���� ������� � ������� �������� ����� �������� �� ���������� ������ log � ����������� �������
	 * @param $message
	 */
	public function addToLog($message)
	{
		$this->log($message);
	}

	/**
	 * ���������� ������ � ����
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult
	 * @throws \Exception
	 */
	public function save()
	{
		if ($this->order_id) {
			return $this->entity::update($this->order_id, $this->GetParams());
		} else {
			return $this->entity::add($this->GetParams());
		}
	}

	/**
	 * �������� �������� �����
	 * @param $arFields
	 * @throws \Exception
	 */
	public function Update($arFields)
	{
		$this->SetParams($arFields);
		$this->save();
	}

	/**
	 * ������������� �������� ������� ��� ������ � ��� , ��� � ������������� ��������
	 * ����� ��������� ��������, ���������� ������� ����� save, ��� ���������� ��������� � ��
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->{$offset} = $value;
	}

	/**
	 * ��������� ������������ �� �������� ������� ��� ������ � ��������, ��� � ������������� ��������
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->{$offset});
	}

	/**
	 * ������� �������� ������� ��� ������ � ��� , ��� � ������������� ��������
	 * ����� �������� ��������, ���������� ������� ����� save, ��� ���������� ��������� � ��
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->{$offset});
	}

	/**
	 * �������� �������� �������� ������� ��� ������ � ��� , ��� � ������������� ��������
	 * @param mixed $offset
	 * @return mixed|null
	 */
	public function offsetGet($offset)
	{
		return $this->{$offset} ?? null;
	}

	/**
	 * �������� �������� ������� �� data
	 * @param $name string - ��� ��������
	 * @return mixed - �������� �������� ��� false
	 */
	public function __get($name)
	{
		return $this->GetParams()[$name] ?? false;
	}

	/**
	 * ������������� ��������, ��������� � data
	 * @param $name string - ��� ��������
	 * @param $value mixed - ����� �������� ��������
	 * @return boolean - ����
	 */
	public function __set($name, $value)
	{
		$arData = $this->GetParams();
		$arData[$name] = $value;
		$this->SetParams($arData);
	}

	/**
	 * ����������� �� ��������
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$arData = $this->GetParams();
		return isset($arData[$name]);
	}

	/**
	 * ����������� ��������
	 * @param $name string - ��� ��������
	 */
	public function __unset($name)
	{
		$arData = $this->GetParams();
		unset($arData[$name]);
		$this->SetParams($arData);
	}

	public function __invoke($action = 'vars')
	{
		switch ($action) {
			case 'vars':
				return $this->GetParams();
			default:
				return false;
		}
	}

	/**
	 * ���������� ��������� �������������� ������� �������, ������������ ��� ������������ �������
	 * @return mixed
	 */
	protected abstract function GetParams();

	/**
	 * ���������� �������� �������������� ������� �������, ������������ ��� ������������ �������
	 * @param $arData - ������ ������� ��� ������
	 * @return mixed
	 */
	protected abstract function SetParams($arData);

}