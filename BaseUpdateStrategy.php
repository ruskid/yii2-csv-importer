<?php

/**
 * @link https://github.com/ruskid/yii2-csv-importer#README
 */

namespace ruskid\csvimporter;

use yii\base\Exception;
use yii\db\Query;

/**
 * Base update strategy.
 */
abstract class BaseUpdateStrategy extends BaseImportStrategy implements ImportInterface {

	/**
	 * ActiveRecord class name
	 * @var string
	 */
	public $className;

	/**
	 * Table name. It is set automatically if left blank.
	 * @var string
	 */
	public $tableName;

	/**
	 * @var \Closure a function that returns a value that will be used to index the corresponding
	 * csv file line. The signature of the function should be the following: `function ($line)`.
	 * This value must be unique for each csv line.
	 * Where `$line` is an array representing one line of the csv file.
	 */
	public $csvKey;

	/**
	 * @var \Closure a function that returns a value that will be used to index the corresponding
	 * AR table row. The signature of the function should be the following: `function ($row)`.
	 * This value must be unique for each table row.
	 * Where `$row` is an array representing one row of the AR table.
	 */
	public $rowKey;

	/**
	 * @throws Exception
	 */
	public function __construct() {
		$arguments = func_get_args();
		if (!empty($arguments)) {
			if (isset($arguments[0]['className']) && !isset($arguments[0]['tableName'])) {
				$arguments[0]['tableName'] = (new $arguments[0]['className'])->tableName();
			}
			foreach ($arguments[0] as $key => $property) {
					if (property_exists($this, $key)) {
							$this->{$key} = $property;
					}
			}
		}
		$requiredFields = ['csvKey', 'rowKey', 'className'];
		foreach ($requiredFields as $field) {
			if ($this->{$field} === null) {
				throw new Exception(__CLASS__ . " $field is required.");
			}
		}
	}

	/**
	 * Will multiple import|update data into table. WARNING: this function deletes $data content to save memory.
	 * @param array $data CSV data passed by reference to save memory.
	 * @return [integer] records affected ['new' => integer, 'updated' => integer, 'unchanged' => integer]
	 */
	public function import(&$data) {
		$records = ['new' => 0, 'updated' => 0, 'unchanged' => 0];

		// Re-index csv data
		$dataReindexed = [];
		foreach ($data as $k => $line) {
			$dataReindexed[call_user_func($this->csvKey, $line)] = $line;
			unset($data[$k]);
		}

		$query = (new Query())->from($this->tableName);

		foreach ($query->each() as $row) {
			$key = call_user_func($this->rowKey, $row);

			if (key_exists($key, $dataReindexed)) {
				// remove this line from new records to be inserted
				$line = $dataReindexed[$key];
				unset($dataReindexed[$key]);

				// table row should be updated or unchanged
				$skipImport = isset($this->skipImport) ? call_user_func($this->skipImport, $line) : false;
				if(!$skipImport) {
					$values = $this->getLineValues($line);
					if ($this->changed($row, $values)) {
						if ($this->updateRecord($row, $values)) {
							$records['updated']++;
						}
					} else {
						$records['unchanged'] ++;
					}
				} else {
					$records['unchanged'] ++;
				}
			}
		}

		// save new records
		$records['new'] = $this->importNewRecords($dataReindexed);
		return $records;
	}

	/**
	 * Will get values for csv line
	 * @param string $line csv line
	 * @return array
	 */
	public function getLineValues($line) {
		foreach ($this->configs as $config) {
			$value = call_user_func($config['value'], $line);
			$values[$config['attribute']] = $value;
		}
		return $values;
	}

	/**
	 * Returns true if $row needs to be updated
	 * @param array $row
	 * @param array $values
	 * @return boolean
	 */
	public function changed($row, $values) {
		foreach ($values as $k => $value) {
			if ($row[$k] != $value) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Updates $row with $values
	 * @param type $row
	 * @param type $values
	 * @return bool
	 */
	abstract protected function updateRecord($row, $values);

	/**
	 * Import new records
	 * @param array $data CSV data passed by reference to save memory
	 * @return integer the number saved records
	 */
	abstract protected function importNewRecords(&$data);
}
