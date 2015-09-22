# Yii2 CSV Importer to Database
Helper for CSV imports to tables.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ruskid/yii2-csv-importer "dev-master"
```

or add

```
"ruskid/yii2-csv-importer": "*"
```

to the require section of your `composer.json` file.


Usage
-----

```php
$importer = new CSVImporter;

//Will read CSV file
$importer->setData(new CSVReader([
    'filename' => $this->file->tempName,
    'fgetcsvOptions' => [
        'delimiter' => ';'
    ]
]));

//Import multiple (Fast but not reliable). Will return number of inserted rows
$numberRowsAffected = $importer->import(new MultipleImportStrategy([
    'tableName' => VendorSwType::tableName(),
    'configs' => [
        [
            'attribute' => 'name',
            'value' => function($line) {
                return $line[1];
            },
            'unique' => true, //Will filter and import unique values only. can by applied for 1+ attributes
            'empty' => false //Will throw exception if csv contains empty values
        ]
    ],
]));

//Import Active Records (Slow, but more reliable). Will return array of primary keys
$primaryKeys = $importer->import(new ARImportStrategy([
    'className' => BusinessType::className(),
    'configs' => [
        [
            'attribute' => 'name',
            'value' => function($line) {
                return $line[2];
            },
        ]
    ],
]));


// More advanced example. You can use queries to set related data.
// Use query caching for performance
$importer->import(new MultipleImportStrategy([
    'tableName' => ProductInventory::tableName(),
    'configs' => [
        [
            'attribute' => 'product_name',
            'value' => function($line) {
                return AppHelper::importStringFromCSV($line[7]);
            },
        ],
        [
            'attribute' => 'id_vendor_sw_type',
            'value' => function($line) {
                $name = AppHelper::importStringFromCSV($line[1]);
                $vendor = VendorSwType::getDb()->cache(function ($db) use($name) {
                    return VendorSwType::find()->where(['name' => $name])->one();
                });
                return isset($vendor) ? $vendor->id : null;
            },
        ],   
    ],
]));

//Special case only available with Active Record Strategy. 

$primaryKeys = $importer->import(new ARImportStrategy([
    'className' => Fabrica::className(),
    'configs' => [
        [
            'attribute' => 'name',
            'value' => function($line) {
                return $line[0];
            },
        ]
    ],
]));

//Import product after fabrica import
$importer->import(new MultipleImportStrategy([
    'tableName' => Product::tableName(),
    'configs' => [
        [
            'attribute' => 'id_fabrica',
            'value' => function($line) use (&$primaryKeys) {
                $fk = array_shift($primaryKeys);
                return $fk;
            },
            'unique' => true,
            'empty' => false
        ],     
    ],
]));

```
