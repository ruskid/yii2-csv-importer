<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-excel-importer/LICENSE
 * @link https://github.com/ruskid/yii2-excel-importer#README
 */

namespace ruskid\csvimporter;

use Exception;

/**
 * Little CSV import helper for Yii2
 * @author Victor Demin <demin@trabeja.com>
 */
class CSVImporter {

    /**
     * Limit items per single insert query. Used in multipleImport().
     * On batch insert the single query can become huge and you can get mysql exception: 
     * "Communication link failure: 1153 Got a packet bigger than 'max_allowed_packet' bytes". 
     * 
     * This parameter will divide array of values in chunks and then execute multiple 
     * insert queries. If you don't want this limit, set it to 0 (not recommended). 
     * @var integer 
     */
    public $maxItemsPerInsert = 10000;

    /**
     * Excel's parsed rows.
     * @var array
     */
    private $_rows = [];

    /**
     * Rows getter
     * @return array
     */
    public function getRows() {
        return $this->_rows;
    }

    /**
     * @param string $filename
     * @param integer $startRow Start from 1 if there is HEADER row.
     * @throws Exception
     */
    public function __construct($filename, $startRow = 1, $fieldDelimiter = ';') {
        if (!file_exists($filename)) {
            throw new Exception(__CLASS__ . ' couldn\'t find the CSV file.');
        }
        $this->_rows = $this->getAllRows($filename, $fieldDelimiter); //Read file
        $this->removeUnusedRows($this->_rows, $startRow); //Filter rows
    }

    /**
     * Will set rows reading the CSV file.
     * @param string $filename
     * @param string $fieldDelimiter
     * @return array
     */
    private function getAllRows($filename, $fieldDelimiter) {
        $allRows = [];
        if (($fp = fopen($filename, 'r')) !== FALSE) {
            while (($line = fgetcsv($fp, 0, $fieldDelimiter)) !== FALSE) {
                array_push($allRows, $line);
            }
        }
        return $allRows;
    }

    /**
     * Will remove unused rows by start row index.
     * @param array $rows
     * @param integer $start
     */
    private function removeUnusedRows(&$rows, $start) {
        for ($i = 0; $i < $start; $i++) {
            unset($rows[$i]);
        }
    }

    /**
     * Will get attribute list for multiple insert
     * @param array $configs
     * @return array
     */
    private function prepareAttributes($configs) {
        $attributes = [];
        foreach ($configs as $config) {
            $attributes[] = $config['attribute'];
        }
        return $attributes;
    }

    /**
     * Will get value list for multple insert
     * @param array $configs
     * @return array
     */
    private function prepareValues($configs) {
        $values = [];
        $rows = $this->getRows();
        foreach ($rows as $i => $row) {
            foreach ($configs as $config) {
                $values[$i][$config['attribute']] = call_user_func($config['value'], $row);
            }
        }
        return $values;
    }

    /**
     * Will filter values per unique parameters. Config array can receive 1+ unique parameters.
     * @param array $values
     * @param array $configs
     * @return array
     */
    private function filterUniqueValues($values, $configs) {
        //Get unique attributes
        $uniqueAttributes = [];
        foreach ($configs as $config) {
            if (isset($config['unique']) && $config['unique']) {
                $uniqueAttributes[] = $config['attribute'];
            }
        }

        if (empty($uniqueAttributes)) {
            return $values; //Return all values
        }

        //Filter values per unique attributes
        $uniqueValues = [];
        foreach ($values as $value) {
            $hash = ""; //generate hash per 1+ unique parameters
            foreach ($uniqueAttributes as $ua) {
                $hash = $hash . $value[$ua];
            }
            $uniqueValues[$hash] = $value;
        }
        return $uniqueValues;
    }

    /**
     * Will import from CSV. This will batch insert the rows, no validation is performed. 
     * This is the fastest way to insert big amounts of data.
     * 
     * @param string $tableName
     * @param array $configs Attribute configs on how to import data.
     * @return integer number of rows affected
     */
    public function importMultiple($tableName, $configs) {
        $attributes = $this->prepareAttributes($configs);
        $allValues = $this->prepareValues($configs);
        $uniqueValues = $this->filterUniqueValues($allValues, $configs);

        if ($this->maxItemsPerInsert && count($uniqueValues) > $this->maxItemsPerInsert) {
            //Execute multiple queries
            $countInserts = 0;
            $chunks = array_chunk($uniqueValues, $this->maxItemsPerInsert);
            foreach ($chunks as $chunk) {
                $countInserts = $countInserts + \Yii::$app->db->createCommand()
                            ->batchInsert($tableName, $attributes, $chunk)->execute();
            }
            return $countInserts;
        } else {//Execute single query
            return \Yii::$app->db->createCommand()
                            ->batchInsert($tableName, $attributes, $uniqueValues)->execute();
        }
    }

    /**
     * Import from CSV. This will create/validate/save an ActiveRecord object per excel row. 
     * This is the slowest way to insert, but most reliable. Use it with small amounts of data.
     *
     * @param string $class ActiveRecord class name
     * @param array $configs Attribute configs on how to import data.
     * @return integer number of rows affected
     */
    public function import($class, $configs) {
        $rows = $this->getRows();
        $countInserted = 0;
        foreach ($rows as $row) {
            /* @var $model \yii\db\ActiveRecord */
            $model = new $class;
            $uniqueAttributes = [];
            foreach ($configs as $config) {
                if (isset($config['attribute']) && $model->hasAttribute($config['attribute'])) {
                    $value = call_user_func($config['value'], $row);

                    //Create array of unique attributes
                    if (isset($config['unique']) && $config['unique']) {
                        $uniqueAttributes[$config['attribute']] = $value;
                    }

                    //Set value to the model
                    $model->setAttribute($config['attribute'], $value);
                }
            }
            //Check if generated Active Record is unique by query.
            if ($this->isActiveRecordUnique($class, $uniqueAttributes)) {
                $countInserted = $countInserted + $model->save();
            }
        }
        return $countInserted;
    }

    /**
     * Will check if current Active Record is unique by exists query.
     * @param string $class \yii\db\ActiveRecord class name
     * @param array $attributes
     * @return boolean
     */
    private function isActiveRecordUnique($class, $attributes) {
        return empty($attributes) ? true :
                !$class::find()->where($attributes)->exists();
    }

}
