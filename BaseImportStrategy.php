<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-csv-importer/LICENSE
 * @link https://github.com/ruskid/yii2-csv-importer#README
 */

namespace ruskid\csvimporter;

/**
 * Base Strategy
 * @author Victor Demin <demin@trabeja.com>
 */
class BaseImportStrategy {

    /**
     * Attribute configs on how to import data.
     * @var array
     */
    public $configs;

    /**
     * If value is empty and config array has empty parameter set to false. Then it should throw exception
     * and stop import. 
     * @param string $value
     * @param array $config
     * @throws Exception
     */
    protected function checkValueForEmpty($value, $config) {
        if (empty($value) && isset($config['empty']) && $config['empty'] == false) {
            throw new Exception(__CLASS__ . ' "' . $config['attribute'] . '" has empty rows.');
        }
    }

}
