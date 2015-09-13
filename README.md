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
There are 2 import functions:
<p><b>importMultiple()</b> - is for big amount of data, it is batch insert as is without any validation.</p>
<p><b>import()</b> - is for small amount of data, it recreates Active Record and performs validation.</p>

Both accept array of attributes with their configs:
<p><b>attribute</b> is the attribute of the ActiveRecord</p>
<p><b>value</b> \Closure an anonymous function that is used to determine the value to insert. Accepts 1 parameter
that points to the line of the excel file.</p>
<p><b>unique</b> boolean, if to perform unique check for the attribute.</p>

```php
$importer = new CSVImporter($this->file->tempName, 1);
//Fast but not reliable
$importer->importMultiple(OperationSystem::tableName(), [
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

//Slow but reliable
$importer->import(OperationSystem::className(), [
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
```
