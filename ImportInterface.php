<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-excel-importer/LICENSE
 * @link https://github.com/ruskid/yii2-excel-importer#README
 */

namespace ruskid\csvimporter;

/**
 * @author Victor Demin <demin@trabeja.com>
 */
interface ImportInterface {

    /**
     * Data is passed by reference to save memory. CSV data can be huge
     * @param array $data
     */
    public function import(&$data);
}
