<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-csv-importer/LICENSE
 * @link https://github.com/ruskid/yii2-csv-importer#README
 */

namespace ruskid\csvimporter;

use yii\base\Exception;
use ruskid\csvimporter\ImportInterface;
use ruskid\csvimporter\BaseImportStrategy;

/**
 * Import from CSV. This will create/validate/save an ActiveRecord object per excel line. 
 * This is the slowest way to insert, but most reliable. Use it with small amounts of data.
 * 
 * @author Victor Demin <demin@trabeja.com>
 */
class ARImportStrategy extends BaseImportStrategy implements ImportInterface {

    /**
     * ActiveRecord class name
     * @var string
     */
    public $className;

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

        if ($this->className === null) {
            throw new Exception(__CLASS__ . ' className is required.');
        }
        if ($this->configs === null) {
            throw new Exception(__CLASS__ . ' configs is required.');
        }
    }

    /**
     * Will multiple import data into table
     * @param array $data CSV data passed by reference to save memory.
     * @return array Primary keys of imported data
     */
    public function import(&$data) {
        $importedPks = [];
        foreach ($data as $row) {
            $skipImport = isset($this->skipImport) ? call_user_func($this->skipImport, $row) : false;
            if (!$skipImport) {
                /* @var $model \yii\db\ActiveRecord */
                $model = new $this->className;
                $uniqueAttributes = [];
                foreach ($this->configs as $config) {
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
                //Check if model is unique and saved with success
                if ($this->isActiveRecordUnique($uniqueAttributes) && $model->save()) {
                    $importedPks[] = $model->primaryKey;
                }
            }
        }
        return $importedPks;
    }

    /**
     * Will check if Active Record is unique by exists query.
     * @param array $attributes
     * @return boolean
     */
    private function isActiveRecordUnique($attributes) {
        /* @var $class \yii\db\ActiveRecord */
        $class = $this->className;
        return empty($attributes) ? true :
                !$class::find()->where($attributes)->exists();
    }

}
