<?php

/**
 * We need to denormalize data:
 * For aggrgations/relations data needs to be denormalised into flat data
 * This class is a set of functions to make this possible whereever it is needed.
 * See: https://www.elastic.co/guide/en/elasticsearch/guide/current/denormalization.html
 *
 * Keep in mind, we might need to use/index raw data (not analyzed) as well @ the object:
 * https://www.elastic.co/guide/en/elasticsearch/guide/current/top-hits.html
 */

class SSDataMap
{

	/**
	 * Add Object fields to index
	 * @param [type] $relation Object relation to index
	 * @param array  $fieldmap Field map of relation fields (what to index)
	 */
	public static function DenormalizeRelation($relation, array $fieldmap) {
		// check if wee need to loop the relation for has_many/many_many relations
		// otherwise check if the relation (has_one) exists
		if(get_class($relation) === 'ManyManyList') {
			$map = array();
			foreach ($relation as $rel) {
				foreach ($fieldmap as $key => $field) {
					$map[$key][] = $rel->$field;
				}
			}

			return $map;
		} else if(get_class($relation) === 'HasManyList') {
			// TODO: add logic for has_many relations (perhaps the same as many_many?)
		} else if($relation->exists()) {
			// has_one found, and has_one exists.
			$map = array();
			foreach ($fieldmap as $key => $field) {
				$map[$key] = $relation->$field;
			}

			return $map;
		}
	}

	/**
	 * Fieldmap of object or relation attributes.
	 * @param string  $type       Field type (e.d. string, long, geo_point, nested, ...)
	 * @param boolean $analyzed   To analyze or not to analyze ([not_]analyzed)
	 * @param string  $analyzer   If analyzed: override default (standard) analyzer, e.g. 'dutch'
	 * @param array   $properties Nested (iterative) mapping setup for nested object relation
	 */
	public static function FieldMap($type, $analyzed = true, $analyzer = null, $properties = null) {
		$map = ['type' => $type];

		switch ($type) {
			case 'date':
				$map['format'] = 'yyyy-MM-dd';
				break;

			case 'datetime':
				$map['type']   = 'date';
				$map['format'] = 'yyyy-MM-dd HH:mm:ss';
				break;

			case 'nested':
				$map['include_in_parent'] = true;
		}

		if($analyzed === false) {
			$map['index'] = 'not_analyzed';
		}

		if($analyzer) {
			$map['analyzer'] = $analyzer;
		}

		if($properties) {
			$map['properties'] = $properties;
		}

		return $map;
	}
}