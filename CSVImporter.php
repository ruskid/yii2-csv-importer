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
     * @var string the path of the uploaded CSV file on the server.
     */
    public $filename;

    /**
     * FGETCSV() options: length, delimiter, enclosure, escape.
     * @var array 
     */
    public $fgetcsvOptions = ['length' => 0, 'delimiter' => ',', 'enclosure' => '"', 'escape' => "\\"];

    /**
     * Start insert from line number. Set 1 if CSV file has header.
     * @var integer
     */
    public $startFromLine = 1;

    /**
     * CSV data
     * @var array
     */
    protected $_rows = null;
    
    /**
     * Will get CSV data
     * @return array
     */
    public function getRows() {
        if($this->_rows === null){
            $this->_rows = $this->readFile();
        }
        return $this->_rows;
    }

    /**
     * @param string $filename the path of the uploaded CSV file on the server.
     */
    public function __construct($filename) {
        $this->filename = $filename;
    }
    
    /**
     * Will read CSV file into array
     * @throws Exception
     * @return $array csv filtered data 
     */
    private function readFile() {
        if (!file_exists($this->filename)) {
            throw new Exception(__CLASS__ . ' couldn\'t find the CSV file.');
        }
        //Prepare fgetcsv parameters
        $length = isset($this->fgetcsvOptions['length']) ? $this->fgetcsvOptions['length'] : 0;
        $delimiter = isset($this->fgetcsvOptions['delimiter']) ? $this->fgetcsvOptions['delimiter'] : ',';
        $enclosure = isset($this->fgetcsvOptions['enclosure']) ? $this->fgetcsvOptions['enclosure'] : '"';
        $escape = isset($this->fgetcsvOptions['escape']) ? $this->fgetcsvOptions['escape'] : "\\";
        
        $lines = [];//Clear and set rows
        if (($fp = fopen($this->filename, 'r')) !== FALSE) {
            while (($line = fgetcsv($fp, $length, $delimiter, $enclosure, $escape)) !== FALSE) {
                array_push($lines, $line);
            }
        }
        //Remove unused lines from all lines
        for ($i = 0; $i < $this->startFromLine; $i++) {
            unset($lines[$i]);
        }
        return $lines;
    }
}
