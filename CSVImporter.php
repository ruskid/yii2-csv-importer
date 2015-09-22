<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-csv-importer/LICENSE
 * @link https://github.com/ruskid/yii2-csv-importer#README
 */

namespace ruskid\csvimporter;

use ruskid\csvimporter\CSVReader;
use ruskid\csvimporter\ImportInterface;

/**
 * Little CSV import helper for Yii2
 * @author Victor Demin <demin@trabeja.com>
 */
class CSVImporter {

    private $_data;

    /**
     * @param CSVReader $reader
     */
    public function setData(CSVReader $reader) {
        $this->_data = $reader->readFile();
    }

    /**
     * Will get CSV data
     * @return array
     */
    public function getData() {
        return $this->_data;
    }

    /**
     * Will import csv file using strategy.
     * @param ImportInterface $strategy
     */
    public function import(ImportInterface $strategy) {
        return $strategy->import($this->_data);
    }

}
