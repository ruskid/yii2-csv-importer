<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-excel-importer/LICENSE
 * @link https://github.com/ruskid/yii2-excel-importer#README
 */

namespace ruskid\csvimporter;

use ruskid\csvimporter\ImportInterface;
use ruskid\csvimporter\CSVReader;

/**
 * Will import from CSV. This will batch insert the rows, no validation is performed. 
 * This is the fastest way to insert big amounts of data.
 * 
 * @author Victor Demin <demin@trabeja.com>
 */
class MultipleImport extends CSVReader implements ImportInterface {

    /**
     * Table name where to import data
     * @var string
     */
    public $tableName;

    /**
     * Attribute configs on how to import data.
     * @var array
     */
    public $configs;

    /**
     * Limit items per single insert query. 
     * On batch insert the single query can become huge and you can get mysql exception: 
     * "Communication link failure: 1153 Got a packet bigger than 'max_allowed_packet' bytes". 
     * 
     * This parameter will divide array of values in chunks and then execute multiple 
     * insert queries. If you don't want this limit, set it to 0 (not recommended). 
     * @var integer 
     */
    public $maxItemsPerInsert = 10000;

    /**
     * @param string $filename the path of the uploaded CSV file on the server.
     * @param string $tableName
     * @param array $configs
     */
    public function __construct($filename, $tableName, $configs) {
        parent::__construct($filename);
        $this->tableName = $tableName;
        $this->configs = $configs;
    }

    /**
     * Will multiple import data into table
     * @return integer number of rows affected
     */
    public function import() {
        $attributes = $this->getAttributes();
        $values = $this->getValues();
        
        if ($this->maxItemsPerInsert && count($values) > $this->maxItemsPerInsert) {
            //Execute multiple queries
            $countInserts = 0;
            $chunks = array_chunk($values, $this->maxItemsPerInsert);
            foreach ($chunks as $chunk) {
                $countInserts = $countInserts + \Yii::$app->db->createCommand()
                                ->batchInsert($this->tableName, $attributes, $chunk)->execute();
            }
            return $countInserts;
        } else {//Execute single query
            return \Yii::$app->db->createCommand()
                            ->batchInsert($this->tableName, $attributes, $values)->execute();
        }
    }

    /**
     * Will get attribute list from the config
     * @return array
     */
    private function getAttributes() {
        $attributes = [];
        foreach ($this->configs as $config) {
            $attributes[] = $config['attribute'];
        }
        return $attributes;
    }

    /**
     * Will get value list from the config
     * @return array
     */
    private function getValues() {
        $values = [];
        $rows = $this->getRows();
        foreach ($rows as $i => $row) {
            foreach ($this->configs as $config) {
                $values[$i][$config['attribute']] = call_user_func($config['value'], $row);
            }
        }
        $this->filterUniqueValues($values);
        return $values;
    }

    /**
     * Will filter values per unique parameters. Config array can receive 1+ unique parameters.
     * @param array $values
     */
    private function filterUniqueValues(&$values) {
        //Get unique attributes
        $uniqueAttributes = [];
        foreach ($this->configs as $config) {
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

}
