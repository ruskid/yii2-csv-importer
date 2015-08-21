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
     * Excel's parsed rows. Arrays of arrays
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
     * @param integer $start Start from 1 if there is HEADER row.
     * @param integer $expectedColsCount Validate import by counting columns and expected number of columns.
     * @throws Exception
     */
    public function __construct($filename, $startRow = 1) {
        if (!file_exists($filename)) {
            throw new Exception(__CLASS__ . ' couldn\'t find the CSV file.');
        }
        $allRows = $this->getAllRows($filename); //Read file
        $this->_rows = $this->removeUnusedRows($allRows, $startRow); //Filter rows
    }

    /**
     * Will set rows reading the CSV file.
     * @param string $filename
     * @param integer $start
     * @return array
     */
    private function getAllRows($filename) {
        $allRows = [];
        if (($fp = fopen($filename, 'r')) !== FALSE) {
            while (($line = fgetcsv($fp, 0, ";")) !== FALSE) {
                array_push($allRows, $line);
            }
        }
        return $allRows;
    }

    /**
     * Will remove unused rows by start row index.
     * @param array $rows
     * @param integer $start
     * @return array
     */
    private function removeUnusedRows($rows, $start) {
        for ($i = 0; $i < $start; $i++) {
            unset($rows[$i]);
        }
        return $rows;
    }

    /**
     * Import from CSV. This will create/save an ActiveRecord object per excel row.
     *
     * - <b>attribute</b> is the attribute of the ActiveRecord
     * - <b>value</b> \Closure an anonymous function that is used to determine the value to insert. Excepts 1 parameter
     * that points to the line of the excel file.
     * - <b>unique</b> boolean, if to perform unique check for the attribute.
     * 
     * @param string $class ActiveRecord class name
     * @param array $configs Attribute config on how to import data.
     * @return integer Number of successful inserts
     */
    public function import($class, $configs) {
        $rows = $this->getRows();
        $countInserted = 0;
        foreach ($rows as $line) {
            /* @var $model \yii\db\ActiveRecord */
            $model = new $class;
            $uniqueAttributes = [];
            foreach ($configs as $config) {
                if (isset($config['attribute']) && $model->hasAttribute($config['attribute'])) {
                    //Get value by calling anonymous function
                    $value = call_user_func($config['value'], $line);

                    //Create array of unique attributes and the values to insert for later check
                    if (isset($config['unique']) && $config['unique']) {
                        $uniqueAttributes[$config['attribute']] = $value;
                    }
                    
                    //Set value to the model
                    $model->setAttribute($config['attribute'], $value);
                }
            }
            //Save model if passes unique check
            if ($this->notExists($class, $uniqueAttributes)) {
                $countInserted = $countInserted + $model->save();
            }
        }
        return $countInserted;
    }

    /**
     * Will validate model for unique before the insert.
     * 
     * @param string $class \yii\db\ActiveRecord class name
     * @param array $attributes
     * @return boolean
     */
    private function notExists($class, $attributes) {
        if (empty($attributes)) {
            return true;
        }
       return !$class::find()->where($attributes)->exists();
    }

}
