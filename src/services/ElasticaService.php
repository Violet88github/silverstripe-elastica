<?php

namespace Violet88\Elastica\Services;

use Elastica\Client;
use Elastica\Query;
use SilverStripe\Core\ClassInfo;
use Violet88\Elastica\model\ResultList;

/**
 * A service used to interact with elastic search.
 */
class ElasticaService {

	/**
	 * @var \Elastica\Document[]
	 */
	protected $buffer = array();

	/**
	 * @var bool controls whether indexing operations are buffered or not
	 */
	protected $buffered = false;

	private $client;
	private $index;

	/**
	 * @param \Elastica\Client $client
	 * @param string           $index
	 * @param null             $config
	 */
	public function __construct(Client $client, $index, $config = null) {
		$this->client = $client;
		$this->index = $index;

		if ($config && ($connection = $this->client->getConnection())) {
			foreach ($config as $key => $value) {
				$connection->setParam($key, $value);
			}
		}
	}

	/**
	 * @return \Elastica\Client
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * @return \Elastica\Index
	 */
	public function getIndex() {
		return $this->getClient()->getIndex($this->index);
	}

	/**
	 * Performs a search query and returns a result list.
	 *
	 * @param \Elastica\Query|string|array $query
	 * @return ResultList
	 */
	public function search($query, $type = NULL, array $sort = NULL) {
		$searchQuery = Query::create($query);

		if($sort) {
			$searchQuery->setSort($sort);
		}

		return new ResultList($this->getIndex(), $searchQuery, $type);
	}

	/**
	 * Either creates or updates a record in the index.
	 *
	 * @param Searchable $record
	 */
	public function index($record) {
		$document = $record->getElasticaDocument();
		$type = $record->getElasticaType();

		/**
		 * if the object has property ShowInSearch and !ShowInSearch --> unpublish
		 * OR if the object has method isPublished() and !$record->isPublished() --> unindex
		 * (suppress errors, we are just making sure we are removing changed objects)
		 */
		if($document && (isset($record->ShowInSearch) && !$record->ShowInSearch) || (method_exists($record, 'isPublished') && !$record->isPublished())) {
			try {
				$this->remove($record);
			} catch (\Elastica\Exception\NotFoundException $e) { }
		} else if($document) {
			if ($this->buffered) {
				if (array_key_exists($type, $this->buffer)) {
					$this->buffer[$type][] = $document;
				} else {
					$this->buffer[$type] = array($document);
				}
			} else {
				$index = $this->getIndex();
				if (!$index->exists()) {
					$index->create();
				}
				$index->addDocument($document);
				$index->refresh();
			}
		}
	}

	/**
	 * Begins a bulk indexing operation where documents are buffered rather than
	 * indexed immediately.
	 */
	public function startBulkIndex() {
		$this->buffered = true;
	}

	/**
	 * Ends the current bulk index operation and indexes the buffered documents.
	 */
	public function endBulkIndex() {
		$index = $this->getIndex();
		foreach ($this->buffer as $type => $documents) {
			$index->addDocuments($documents);
			$index->refresh();
		}

		$this->buffered = false;
		$this->buffer = array();
	}

	/**
	 * Deletes a record from the index.
	 *
	 * @param Searchable $record
	 */
	public function remove($record) {
		$index = $this->getIndex();
		//$type = $index->getType($record->getElasticaType());

		if($doc = $record->getElasticaDocument()) {
			$index->deleteDocuments([$doc]);
		}
	}

	/**
	 * Deletes the index.
	 */
	public function delete() {
		$index = $this->getIndex();

		try {
			$index->delete();
		} catch(\Elastica\Exception\ResponseException $e) {}
	}

	public function create() {
		$index = $this->getIndex();

		if (!$index->exists()) {
			try {
				//$this->getIndex()->create();
				$index->create(array('index' => array('number_of_shards' => 5, 'number_of_replicas' => 1)));
			} catch(\Elastica\Exception\ResponseException $e) {}
		}
		//array('index' => array('number_of_shards' => $shards, 'number_of_replicas' => 0)
	}

	/**
	 * Creates the index and the type mappings.
	 */
	public function define() {
        $index = $this->getIndex();

        if (!$index->exists()) {
            $index->create();
        }

        foreach ($this->getIndexedClasses() as $class) {
            /** @var $sng Searchable */
            $sng = singleton($class);

            $mapping = $sng->getElasticaMapping();
            //$mapping->setType($index->getType($sng->getElasticaType()));
            $mapping->send($index);
        }
	}

	/**
	 * Re-indexes each record in the index.
	 */
	public function refresh() {
		$this->getIndex()->refresh();
		$this->startBulkIndex();

		foreach ($this->getIndexedClasses() as $class) {
			foreach ($class::get() as $record) {
				$this->index($record);
			}
		}

		$this->endBulkIndex();
	}

	/**
	 * Gets the classes which are indexed (i.e. have the extension applied).
	 *
	 * @return array
	 */
	public function getIndexedClasses() {
		$classes = array();
        foreach (ClassInfo::subclassesFor('Silverstripe\ORM\DataObject') as $candidate) {
            if (singleton($candidate)->hasExtension('Violet88\\Elastica\\Extensions\\Searchable')) {
				$classes[] = $candidate;
			}
		}

		return $classes;
	}

}
