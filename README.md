# form-bundle 

## Installation

``` bash
composer require agentur22/form-bundle
```

Add Bundle to `bundles.php`:
```php
use Xxii\FormBundle\XxiiFormBundle;

return [
   XxiiFormBundle::class => ['all' => true],
];
```

- Execute: `$ bin/console pimcore:bundle:install XxiiFormBundle`


- Include: `xxiicontact` in Areablock, where you want to allow Contact Brick