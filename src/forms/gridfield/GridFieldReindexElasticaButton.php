<?php

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_ActionProvider;

class GridFieldReindexElasticaButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{

    protected $targetFragment;

    protected $buttonName;

    public function setButtonName($name)
    {
        $this->buttonName = $name;

        return $this;
    }

    public function __construct($targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
    }

    public function getHTMLFragments($gridField)
    {
        $forTemplate = new ArrayData([ ]);
        $forTemplate->Fields = new ArrayList();

        $reindexAction = new GridField_FormAction(
            $gridField, 'gridfield_reindex',
            _t('GridField.ReindexElasticSearch', 'Reindex ElasticSearch'),
            'reindex',
            'reindex'
        );
        $reindexAction->setAttribute('data-icon', 'arrow-circle-double');

        $forTemplate->Fields->push($reindexAction);

        return [
            $this->targetFragment => $forTemplate->renderWith('GridFieldReindexElasticaButton'),
        ];
    }

    public function getActions($gridField)
    {
        return [ 'reindex' ];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($service = Injector::inst()->get('SilverStripe\Elastica\ElasticaService')) {
            // if (class_exists('Translatable')) {
            //     Translatable::disable_locale_filter();
            // }

            // delete the index'
            $service->delete();

            // recreate the index
            $service->create();

            // define the mappings
            $service->define();

            // refresh the index
            $service->refresh();

            // if (class_exists('Translatable')) {
            //     Translatable::enable_locale_filter();
            // }

            Controller::curr()->response->addHeader('X-Status', rawurlencode('Reindex completed'));

            return;
        }

        Controller::curr()->response->addHeader('X-Status', rawurlencode('Reindex failed.'));

        return;
    }

    /**
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'reindex' => 'doReindex',
        ];
    }
}
