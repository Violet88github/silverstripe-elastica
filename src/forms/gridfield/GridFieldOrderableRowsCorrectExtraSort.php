<?php

use SilverStripe\Forms\GridField\GridField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\ORM\SS_List;

class GridFieldOrderableRowsCorrectExtraSort extends GridFieldOrderableRows
{
    public function getManipulatedData(GridField $grid, SS_List $list) {
        $state = $grid->getState();
        $sorted = (bool) ((string) $state->GridFieldSortableHeader->SortColumn);

        // If the data has not been sorted by the user, then sort it by the
        // sort column, otherwise disable reordering.
        $state->GridFieldOrderableRows->enabled = !$sorted;

        if(!$sorted) {
            $sortterm = '';
            if ($this->extraSortFields) {
                if (is_array($this->extraSortFields)) {
                    foreach($this->extraSortFields as $col => $dir) {
                        $sortterm .= "$col $dir, ";
                    }
                } else {
                    $sortterm = $this->extraSortFields.', ';
                }
            }
            $sortterm = '"'.$this->getSortTable($list).'"."'.$this->getSortField().'", ' . trim($sortterm, ',\ ');
            return $list->sort($sortterm);
        } else {
            return $list;
        }
    }
}
