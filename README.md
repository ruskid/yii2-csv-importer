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

```php
$importer = new CSVImporter;
$importer->setData(new CSVReader([
    'filename' => $this->file->tempName,
    'fgetcsvOptions' => [
        'delimiter' => ';'
    ]
]));
//Import multiple of Vendor types (Fast but not reliable)
$importer->import(new MultipleImportStrategy([
    'tableName' => VendorSwType::tableName(),
    'configs' => [
        [
            'attribute' => 'name',
            'value' => function($line) {
                return $line[1];
            },
            'unique' => true,//optional
        ]
    ],
]));
//Import Active Records (Slow, but more reliable)
$importer->import(new ARImportStrategy([
    'className' => BusinessType::className(),
    'configs' => [
        [
            'attribute' => 'name',
            'value' => function($line) {
                return $line[2];
            },
            'unique' => true,//optional
        ]
    ],
]));
```
