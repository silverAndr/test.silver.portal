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
	 * получаем поля элемента highload блока
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function GetById()
	{
		return $this->entity::getRowById($this->id); // получаем поля
	}

	/**
	 * Сохрагяем $message в журнал Битрикс
	 * @param $message - текст, который надо добавить
	 * @param $severity - степень важности записи
	 * @param $type - собственный ID типа события
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
	 * При реализации метода можно использовать статический метод toLog, для этого надо передать в него степень важности
	 * записи и собственный ID типа события, либо можно использовать свое журналирование
	 * @param $message
	 * @param $severity
	 * @return mixed
	 */
	protected abstract function log($message, $severity);

	/**
	 * Кидает ошибку в журнал
	 * @param $message
	 */
	public function error($message)
	{
		$this->log($message, 'ERROR');
	}

	/**
	 * Любое сообщение может быть добавлено в журнал
	 * ID типа события и степень важности будет зависеть от реализации метода log в конкректном потомке
	 * @param $message
	 */
	public function addToLog($message)
	{
		$this->log($message);
	}

	/**
	 * Сохранение данных в базу
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
	 * Обновить значения полей
	 * @param $arFields
	 * @throws \Exception
	 */
	public function Update($arFields)
	{
		$this->SetParams($arFields);
		$this->save();
	}

	/**
	 * Устанавливает свойство объекта при работе с ним , как с ассациотивным массивом
	 * После установки свойства, необходимо вызвать метод save, для сохранения изменений в БД
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->{$offset} = $value;
	}

	/**
	 * Проверяет установленно ли свойство объекта при работе с объектом, как с ассациотивным массивом
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->{$offset});
	}

	/**
	 * Удаляет свойство объекта при работе с ним , как с ассациотивным массивом
	 * После удаления свойства, необходимо вызвать метод save, для сохранения изменений в БД
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->{$offset});
	}

	/**
	 * Получает значения свойства объекта при работе с ним , как с ассациотивным массивом
	 * @param mixed $offset
	 * @return mixed|null
	 */
	public function offsetGet($offset)
	{
		return $this->{$offset} ?? null;
	}

	/**
	 * Получаем свойства объекта из data
	 * @param $name string - имя свойства
	 * @return mixed - значение свойства или false
	 */
	public function __get($name)
	{
		return $this->GetParams()[$name] ?? false;
	}

	/**
	 * Устанавливаем свйоство, записывая в data
	 * @param $name string - имя свойства
	 * @param $value mixed - новое значение свойства
	 * @return boolean - флаг
	 */
	public function __set($name, $value)
	{
		$arData = $this->GetParams();
		$arData[$name] = $value;
		$this->SetParams($arData);
	}

	/**
	 * Установлено ли свойство
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$arData = $this->GetParams();
		return isset($arData[$name]);
	}

	/**
	 * Уничтожение свойства
	 * @param $name string - имя свойства
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
	 * Реализация получения несуществующий свойств объекта, используется для перезагрузки свойств
	 * @return mixed
	 */
	protected abstract function GetParams();

	/**
	 * Реализация создания несуществующий свойств объекта, используется для перезагрузки свойств
	 * @param $arData - массив свойств для записи
	 * @return mixed
	 */
	protected abstract function SetParams($arData);

}