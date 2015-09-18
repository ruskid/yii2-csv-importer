# Yii2 CSV Importer to Database
Helper for CSV imports to tables.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ruskid/yii2-excel-importer "dev-master"
```

or add

```
"ruskid/yii2-excel-importer": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Both classes accept array of attributes with their configs:
<p><b>attribute</b> is the attribute of the ActiveRecord</p>
<p><b>value</b> \Closure an anonymous function that is used to determine the value to insert. Accepts 1 parameter
that points to the line of the excel file.</p>
<p><b>unique</b> boolean, if to perform unique check for the attribute.</p>

```php
//Fast but not reliable
$importer = new \ruskid\csvimporter\MultipleImport($this->file->tempName, Pregunta::tableName(), [
    [
        'attribute' => 'name',
        'value' => function($line) {
            return $line[9];
        },
        'unique' => true
    ],
    [
        'attribute' => 'surname',
        'value' => function($line) {
            return $line[0];
        },
    ],
]);
$importer->import();

//Slow but reliable
 $importer = new \ruskid\csvimporter\ActiveRecordImport($this->file->tempName, Pregunta::className(), [
    [
        'attribute' => 'name',
        'value' => function($line) {
            return $line[9];
        },
    ],
]);
$importer->import();
```
