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
     * Can be used for skipping CSV row/ActiveRecord imports. Anonymous function can accept $line array. 
     * Should always return boolean
     * @var callable|Expression 
     */
    public $skipImport;

}
