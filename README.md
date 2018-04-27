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
"ruskid/yii2-csv-importer": "dev-master"
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
                //You cand perform your filters and excludes here. Empty exclude example:
                return $line[7] != "" AppHelper::importStringFromCSV($line[7]) : null;
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
//Get primary key list of new imported items for later use.
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

//You can use the primary key list for the next import of related data.
//The order of primary key items will be the same as in csv file.
$importer->import(new MultipleImportStrategy([
    'tableName' => Product::tableName(),
    'configs' => [
        [
            'attribute' => 'id_fabrica',
            'value' => function($line) use (&$primaryKeys) {
                return array_shift($primaryKeys);
            },
            'unique' => true,
        ],     
    ],
]));


//You can skip CSV Row / Active Record import if some csv row doesn't meet the requirements.
$importer->import(new MultipleImportStrategy([
    'tableName' => ProductCategory::tableName(),
    'configs' => [
        [
            'attribute' => 'name',
            'value' => function($line) {
                return $line[0];
            },
        ],
        [
            'attribute' => 'description',
            'value' => function($line) {
                return $line[1];
            },
        ],
        [
            'attribute' => 'estado',
            'value' => function($line) {
                return $line[2];
            },
        ]
    ],
    //All ACTIVE categories that don't have name set to 'MS_SQLSERVER' or empty will be imported.
    'skipImport' => function($line){
        if($line[0] == 'MS_SQLSERVER' || $line[0] == ""){
            return true;
        }
        if($line[2] == 'NOT ACTIVE'){
            return true;
        }
    }           
]));

//Import or update multiple (Fast but not reliable). Will return number of inserted, updated and unchanged rows
//ARUpdateStrategy can be used instead of MultipleUpdateStrategy if you want to use AR validation.
//The returned value will be the number of inserted, updated and unchanged rows in both cases.
$records = $importer->import(new MultipleUpdateStrategy([
	'className' => Customer::className(),
	'csvKey' => function ($line) {
		return $line[0];
	},
	'rowKey' => function ($row) {
		return $row['gecom_id'];
	},
	'skipImport' => function ($line) {
		return !$line[0];
	},
	'configs' => [
		[
			'attribute' => 'customer_id',
			'value' => function($line) {
				return $line[0];
			},
		],
	],
]));
```
