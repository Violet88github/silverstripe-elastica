# Silverstripe Elastica Module

## Installation
Run the following command to install the module:
```bash
composer require violet88/silverstripe-elastica
```


## Configuration
Add the following to a file called dependencies.yml
```yaml
SilverStripe\Core\Injector\Injector:
  Elastica\Client:
    constructor:
      - host: '`ELASTICA_HOST`'
        port: '`ELASTICA_PORT`'
        transport: '<TRANSPORT TYPE>'
        username: '<USERNAME>'
        password: '<PASSWORD>>'
        auth_type: '<AUTH TYPE>>'
  Violet88\Elastica\Tasks\ReindexTask:
    constructor:
      - '%$Violet88\Elastica\Services\ElasticaService'
  Violet88\Elastica\Extensions\Searchable:
    constructor:
      - '%$Violet88\Elastica\Services\ElasticaService'
  Violet88\Elastica\Services\ElasticaService:
    constructor:
      - '%$Elastica\Client'
      - '<INDEX NAME>'
```
And add the following to your _config.php:
```php
if (!empty(Environment::getEnv('ELASTICASERVICE_INDEX'))) {
    Config::modify()->set('Injector', 'Violet88\Elastica\Services\ElasticaService', [
        'constructor' => [
            '%$Elastica\Client',
            Environment::getEnv('ELASTICASERVICE_INDEX'),
            isset($env['ElasticaService']['config']) ? $env['ElasticaService']['config'] : null
        ]
    ]);

    // register Searchables
    if (!empty(Environment::getEnv('ELASTICASERVICE_SEARCHABLE'))) {
        foreach (unserialize(Environment::getEnv('ELASTICASERVICE_SEARCHABLE')) as $class) {
            $class::add_extension('Violet88\Elastica\Extensions\Searchable');
        }
    }
}
```
Add the following to your environment variables:
```dotenv
ELASTICASERVICE_INDEX='<NAME OF INDEX>'
ELASTICASERVICE_SEARCHABLE='<SERIALIZED ARRAY OF SEARCHABLE CLASSES>'
ELASTICA_HOST='<ELASTICSEARCH HOST'
ELASTICA_PORT='<ELASTICSEARCH PORT>'
```
