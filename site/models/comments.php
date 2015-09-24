<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Imc records.
 */
class ImcModelComments extends JModelList {

    /**
     * Constructor.
     *
     * @param    array    An optional associative array of configuration settings.
     * @see        JController
     * @since    1.6
     */
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'issueid', 'a.issueid',
                'parentid', 'a.parentid',
                'description', 'a.description',
                'photo', 'a.photo',
                'created', 'a.created',
                'updated', 'a.updated',
                'ordering', 'a.ordering',
                'state', 'a.state',
                'created_by', 'a.created_by',
                'language', 'a.language',

            );
        }
        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since	1.6
     */
    protected function populateState($ordering = null, $direction = null) {

        // Initialise variables.
        $app = JFactory::getApplication();

        // List state information
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
        $this->setState('list.limit', $limit);

        $limitstart = JFactory::getApplication()->input->getInt('limitstart', 0);
        $this->setState('list.start', $limitstart);

        
		if(empty($ordering)) {
			$ordering = 'a.ordering';
		}

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return	JDatabaseQuery
     * @since	1.6
     */
    protected function getListQuery() {
        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select', 'DISTINCT a.*'
                )
        );

        $query->from('`#__imc_comments` AS a');

        
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
    
		// Join over the foreign key 'issueid'
		$query->select('#__imc_issues_1382371.title AS issues_title_1382371');
		$query->join('LEFT', '#__imc_issues AS #__imc_issues_1382371 ON #__imc_issues_1382371.id = a.issueid');
		// Join over the created by field 'created_by'
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');
        

        // Filter by search in title
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                
            }
        }

        

		//Filtering issueid
		$filter_issueid = $this->state->get("filter.issueid");
		if ($filter_issueid) {
			$query->where("a.issueid = '".$filter_issueid."'");
		}

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }

        return $query;
    }

    public function getItems() {
        $items = parent::getItems();
        foreach($items as $item){
	

			if (isset($item->issueid) && $item->issueid != '') {
				if(is_object($item->issueid)){
					$item->issueid = JArrayHelper::fromObject($item->issueid);
				}
				$values = (is_array($item->issueid)) ? $item->issueid : explode(',',$item->issueid);

				$textValue = array();
				foreach ($values as $value){
					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query
							->select('title')
							->from('`#__imc_issues`')
							->where('id = ' . $db->quote($db->escape($value)));
					$db->setQuery($query);
					$results = $db->loadObject();
					if ($results) {
						$textValue[] = $results->title;
					}
				}

			$item->issueid = !empty($textValue) ? implode(', ', $textValue) : $item->issueid;

			}
}
        return $items;
    }

    public function count($issueid)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)');
        $query->from($db->quoteName('#__imc_comments'));
        $query->where($db->quoteName('issueid')." = ".$db->quote($issueid));

        $db->setQuery($query);
        $count = $db->loadResult();
        return $count;
    }
}
