<?php

namespace Violet88\Elastica\model;


use Elastica\Index;
use Elastica\Query;
use Elastica\Search;
use SilverStripe\ORM\SS_List;
use  SilverStripe\ORM\Limitable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

/**
 * A list wrapper around the results from a query. Note that not all operations are implemented.
 */
class ResultList extends ViewableData implements SS_List, Limitable {

	private $index;
	private $query;
	private $search = null;
	private $type;

	public function __construct(Index $index, Query $query, $type = NULL) {
		$this->index = $index;
		$this->query = $query;
		$this->type  = $type;
	}

	public function __clone() {
		$this->query = clone $this->query;
	}

	/**
	 * @return \Elastica\Index
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * @return \Elastica\Query
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * @return Elastica search result (cached)
	 */
	public function getSearch() {
		if(!$this->search) {
			// $this->search = $this->index->search($this->query);

			$search = new Search($this->index->getClient());
			$search->addIndex($this->index);
			$search->setOptionsAndQuery(NULL, $this->query);

			if($this->type) {
				$search->addType($this->type);
			}

			$this->search = $search->search();
		}

		return $this->search;
	}

	/**
	 * @return array
	 */
	public function getResults() {
		return $this->getSearch()->getResults();
	}

	public function getIterator() {
		return new \ArrayIterator($this->toArray());
	}

	public function limit($limit, $offset = 0) {
		$list = clone $this;

		$list->getQuery()->setSize($limit);
		$list->getQuery()->setFrom($offset);

		return $list;
	}

	/**
	 * Converts results of type {@link \Elastica\Result}
	 * into their respective {@link DataObject} counterparts.
	 *
	 * @return array DataObject[]
	 */
	public function toArray() {
		$result = array();

		/** @var $found \Elastica\Result[] */
		$found = $this->getResults();
		$needed = array();
		$retrieved = array();

		foreach ($found as $item) {

			$type = $item->getSource()['type'];

			if (!array_key_exists($type, $needed)) {
				$needed[$type] = array($item->getId());
				$retrieved[$type] = array();
			} else {
				$needed[$type][] = $item->getId();
			}
		}

		foreach ($needed as $class => $ids) {
			foreach ($class::get()->byIDs($ids) as $record) {
				$retrieved[$class][$record->ID] = $record;
			}
		}

		foreach ($found as $item) {
			// Safeguards against indexed items which might no longer be in the DB
			if(array_key_exists($item->getId(), $retrieved[$item->getSource()['type']])) {
                $retrieved[$item->getSource()['type']][$item->getId()]->Score = $item->getScore();
				$result[] = $retrieved[$item->getSource()['type']][$item->getId()];
			}
		}

		return $result;
	}

	public function toArrayList() {
		return new ArrayList($this->toArray());
	}

	public function toNestedArray() {
		$result = array();

		foreach ($this as $record) {
			$result[] = $record->toMap();
		}

		return $result;
	}

	public function first() {
		$found = $this->getResults();
		if(isset($found[0]) && $item = $found[0]) {
			$class = $item->getType();

			return $class::get()->byID($item->getId());
		}
	}

	public function last() {
		// TODO: Implement last() method.
	}

	public function map($key = 'ID', $title = 'Title') {
		return $this->toArrayList()->map($key, $title);
	}

	public function column($col = 'ID') {
		if($col == 'ID') {
			$ids = array();

			foreach ($this->getResults() as $result) {
				$ids[] = $result->getId();
			}

			return $ids;
		} else {
			return $this->toArrayList()->column($col);
		}
	}

	public function each($callback) {
		return $this->toArrayList()->each($callback);
	}

	public function count() {
		return count($this->toArray());
	}

	public function getTotalHits() {
		return $this->getSearch()->getTotalHits();
	}

	/**
	 * Returns all aggregation results.
	 *
	 * @return array
	 */
	public function getAggregations() {
		return $this->getSearch()->getAggregations();
	}

	/**
	 * Retrieve a specific aggregation from this result set.
	 *
	 * @param string $name the name of the desired aggregation
	 *
	 * @throws Exception\InvalidException if an aggregation by the given name cannot be found
	 *
	 * @return array
	 */
	public function getAggregation($name) {
		return $this->getSearch()->getAggregation($name);
	}

	/**
	 * @ignore
	 */
	public function offsetExists($offset) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function offsetGet($offset) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function add($item) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function remove($item) {
		throw new \Exception();
	}

	/**
	 * @ignore
	 */
	public function find($key, $value) {
		throw new \Exception();
	}

}
