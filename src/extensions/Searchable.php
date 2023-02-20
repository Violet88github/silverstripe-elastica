<?php

namespace Violet88\Elastica\Extensions;

use Elastica\Document;
use Elastica\Mapping;
use SilverStripe\ORM\DataExtension;
use Violet88\Elastica\Services\ElasticaService;

/**
 * Adds elastic search integration to a data object.
 */
class Searchable extends DataExtension {

	public static $mappings = array(
		'Boolean'     => 'boolean',
		'Decimal'     => 'double',
		'Double'      => 'double',
		'Enum'        => 'string',
		'Float'       => 'float',
		'HTMLText'    => 'string',
		'HTMLVarchar' => 'string',
		'Int'         => 'integer',
		'SS_Datetime' => 'date',
		'Text'        => 'string',
		'Varchar'     => 'string',
		'Year'        => 'integer'
	);

	private $service;

	public function __construct(ElasticaService $service) {
		$this->service = $service;
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getElasticaType() {
		return $this->owner->baseClass();
	}

	/**
	 * Gets an array of elastic field definitions.
	 *
	 * @return array
	 */
	public function getElasticaFields() {
        //$db = DataObject::database_fields(get_class($this->owner));
        $db = $this->owner->toMap();
		$fields = $this->owner->searchableFields();
		$result = array();

		foreach ($fields as $name => $params) {
			$type = null;
			$spec = array();

			if (array_key_exists($name, $db)) {
				$class = $db[$name];

				if (($pos = strpos($class, '('))) {
					$class = substr($class, 0, $pos);
				}

				if (array_key_exists($class, self::$mappings)) {
					$spec['type'] = self::$mappings[$class];
				}
			}

			$result[$name] = $spec;
		}
        $result["type"] = array();
        $result['id'] = array();

		return $result;
	}

	/**
	 * @return Mapping
     */
	public function getElasticaMapping() {
		$mapping = new Mapping();
		$mapping->setProperties($this->getElasticaFields());

		return $mapping;
	}

	public function getElasticaDocument() {
		$fields = array();

		foreach ($this->owner->getElasticaFields() as $field => $config) {
			$fields[$field] = $this->owner->$field;
		}
        $fields['type'] = $this->owner->getClassName();
        $fields['id'] = $this->owner->ID;

		return new Document(null, $fields);
	}

	/**
	 * Updates the record in the search index.
	 */
	public function onAfterWrite() {
		$this->service->index($this->owner);
	}

	/**
	 * Removes the record from the search index.
	 */
	public function onAfterDelete() {
		try {
			$this->service->remove($this->owner);
		} catch (\Elastica\Exception\NotFoundException $e) { }
	}
}
