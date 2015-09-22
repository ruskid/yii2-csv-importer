<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-csv-importer/LICENSE
 * @link https://github.com/ruskid/yii2-csv-importer#README
 */

namespace ruskid\csvimporter;

use Exception;

/**
 * CSV Reader
 * @author Victor Demin <demin@trabeja.com>
 */
class CSVReader {

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
     * @throws Exception
     */
    public function __construct() {
        $arguments = func_get_args();
        if (!empty($arguments)) {
            foreach ($arguments[0] as $key => $property) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $property;
                }
            }
        }

        if ($this->filename === null) {
            throw new Exception(__CLASS__ . ' filename is required.');
        }
    }

    /**
     * Will read CSV file into array
     * @throws Exception
     * @return $array csv filtered data 
     */
    public function readFile() {
        if (!file_exists($this->filename)) {
            throw new Exception(__CLASS__ . ' couldn\'t find the CSV file.');
        }
        //Prepare fgetcsv parameters
        $length = isset($this->fgetcsvOptions['length']) ? $this->fgetcsvOptions['length'] : 0;
        $delimiter = isset($this->fgetcsvOptions['delimiter']) ? $this->fgetcsvOptions['delimiter'] : ',';
        $enclosure = isset($this->fgetcsvOptions['enclosure']) ? $this->fgetcsvOptions['enclosure'] : '"';
        $escape = isset($this->fgetcsvOptions['escape']) ? $this->fgetcsvOptions['escape'] : "\\";

        $lines = []; //Clear and set rows
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
