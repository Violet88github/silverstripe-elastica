<?php

namespace SilverStripe\Elastica\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use SilverStripe\Elastica\Services\ElasticaService;

/**
 * Defines and refreshes the elastic search index.
 */
class ReindexTask extends BuildTask
{

	protected $title = 'Elastic Search Reindex';

	protected $description = 'Refreshes the elastic search index';

	/**
	 * @var ElasticaService
	 */
	private $service;

	public function __construct(ElasticaService $service)
	{
		$this->service = $service;
	}

	public function run($request)
	{
		$message = function ($content) {
			print(Director::is_cli() ? "$content\n" : "<p>$content</p>");
		};

		$message('Delete the index');
		$this->service->delete();

		$message('Re-create the index');
		$this->service->create();

		$message('Defining the mappings');
		$this->service->define();

		$message('Refreshing the index: You should see a FINISHED msg soon....');
		$this->service->refresh();
		$message('Refreshing the index FINISHED!!!');
	}

}
