<?php

/**
 * @link https://github.com/ruskid/yii2-csv-importer#README
 */

namespace ruskid\csvimporter;

use Yii;

/**
 * Update from CSV. This will create|update instances of ActiveRecord using its validation.
 * A csv line is considered a new record if its key does not match any AR table row key.
 */
class ARUpdateStrategy extends BaseUpdateStrategy{

	/**
	 * @inheritdoc
	 */
	protected function importNewRecords(&$data) {
		$strategy = new ARImportStrategy([
			'className' => $this->className,
			'configs' => $this->configs,
			'skipImport' => $this->skipImport,
		]);
		return count($strategy->import($data));
	}

	/**
	 * @inheritdoc
	 */
	protected function updateRecord($row, $values) {
		$model = $this->className::find()->where($row)->one();
		if ($model) {
			$model->setAttributes($values, false);
			return $model->save();
		} else {
			return false;
		}
	}
}
