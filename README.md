workbenchfacade
===============

Laravel 4 command for adding facade after an artisan workbench command.

php artisan workbench:facade vendor/package

##### how to install

in composer.json

```php
"require": {
		...
		"bespired/workbenchfacade": "0.9.1"
	},

	"repositories": [
	    {
			"type": "vcs",
			"url": "https://github.com/bespired/workbenchfacade.git"
	    },
	],
```

##### how to activate

Add in app/config/app.php

```php

	'providers' => array(
    ...
    
		'Bespired\Workbenchfacade\WorkbenchFacadeServiceProvider',
	),
	```

##### how to use

```php
php artisan workbench vendor/package
php artisan workbench:facade vendor/package
```




