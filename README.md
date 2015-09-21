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

//More advanced example
$importer->import(new MultipleImportStrategy([
    'tableName' => ProductInventory::tableName(),
    'configs' => [
        [
            'attribute' => 'product_name',
            'value' => function($line) {
                return AppHelper::importStringFromCSV($line[7]);
            },
            'empty' => true,//will accept "" and nulls
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
        [
            'attribute' => 'id_business_type',
            'value' => function($line) {
                $name = AppHelper::importStringFromCSV($line[2]);
                $vendor = BusinessType::getDb()->cache(function ($db) use($name) {
                    return BusinessType::find()->where(['name' => $name])->one();
                });
                return isset($vendor) ? $vendor->id : null;
            },
            'empty' => true
        ],     
    ],
]));
```
