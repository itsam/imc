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
class ImcModelIssues extends JModelList {

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
                'title', 'a.title',
                'stepid', 'a.stepid',
                'catid', 'a.catid',
                'description', 'a.description',
                'address', 'a.address',
                'latitude', 'a.latitude',
                'longitude', 'a.longitude',
                'photo', 'a.photo',
                'access', 'a.access', 'access_level',
                'ordering', 'a.ordering',
                'state', 'a.state',
                'created', 'a.created',
                'updated', 'a.updated',
                'created_by', 'a.created_by',
                'language', 'a.language',
                'hits', 'a.hits',
                'note', 'a.note',
                'votes', 'a.votes',
                'modality', 'a.modality',

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
    protected function populateState($ordering = 'a.updated', $direction = 'desc')
    {

        $app = JFactory::getApplication();
        $orderCol = $app->input->get('filter_order', 'a.updated');

        if (!in_array($orderCol, $this->filter_fields))
        {
            $orderCol = 'a.ordering';
        }

        $this->setState('list.ordering', $orderCol);

        $listOrder = $app->input->get('filter_order_Dir', 'DESC');

        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
        {
            $listOrder = 'DESC';
        }

        $this->setState('list.direction', $listOrder);

        // List state information
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
        $this->setState('list.limit', $limit);

        $limitstart = JFactory::getApplication()->input->getInt('limitstart', 0);
        $this->setState('list.start', $limitstart);

        // Load the filter state.
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        //$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
        $published = 1; //always fetch published issues only
        $this->setState('filter.state', $published);

        $access = $app->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

        $category = $app->getUserStateFromRequest($this->context . '.filter.category', 'cat', array());
        $this->setState('filter.category', $category);
        //Filtering catid
        //$this->setState('filter.catid', $app->getUserStateFromRequest($this->context.'.filter.catid', 'filter_catid', '', 'string'));

        $steps = $app->getUserStateFromRequest($this->context . '.filter.steps', 'steps', array());
        $this->setState('filter.steps', $steps);
        //Filtering stepid
        //$this->setState('filter.stepid', $app->getUserStateFromRequest($this->context.'.filter.stepid', 'filter_stepid', '', 'string'));

        //Filtering owned
        $this->setState('filter.owned', $app->getUserStateFromRequest($this->context.'.filter.owned', 'filter_owned', 'no', 'string'));

        $this->setState('filter.language', JLanguageMultilang::isEnabled());

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
        //check API request
        $userid = $this->state->get('filter.imcapi.userid', 0);
        $guest = $this->state->get('filter.imcapi.guest', false);
        if($userid > 0)
        {
            $user = JFactory::getUser($userid);
        }
        elseif ($guest)
        {
            $user = JFactory::getUser(0);
        }
        else
        {
            $user = JFactory::getUser();
        }

        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select', 'DISTINCT a.*'
                )
        );

        $query->from('`#__imc_issues` AS a');

        // Join over the users for the checked out user.
        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
		// Join over the category 'catid'
		$query->select('catid.params AS catid_params, catid.title AS catid_title');
		$query->join('LEFT', '#__categories AS catid ON catid.id = a.catid');
		// Join over the created by field 'created_by'
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');
        // Join over the asset groups.
        $query->select('ag.title AS access_level')
            ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
        // Join over the imc steps.
        $query->select('st.title AS stepid_title, st.stepcolor AS stepid_color')
            ->join('LEFT', '#__imc_steps AS st ON st.id = a.stepid');


	    // Filter by published state
        $imc_raw = $this->state->get('filter.imcapi.raw', false);
	    $published = $this->getState('filter.state');

	    if($imc_raw)
	    {
		    $query->where('(a.state IN (0, 1, -2))');
	    }
	    elseif(!$imc_raw || $published == 1) {
            if (is_numeric($published)) {
                $query->where('a.state = ' . (int)$published);
            } else if ($published === '' || is_null($published)) {
                $query->where('(a.state IN (0, 1))');
            }
        }
        //$query->where('a.state = 1');



        // Filter by moderation
	    if(!$imc_raw)
	    {
		    $query->where('
            (
            (a.created_by > 0 AND a.created_by  =' . $user->id . ' AND a.moderation IN (0,1)) OR
            (a.created_by > 0 AND a.created_by !=' . $user->id . ' AND a.moderation = 0)
            )
	        ');
	    }

        // Filter by search in title
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('( a.title LIKE '.$search.' )');
            }
        }


        // Filter by access level.
        if ($access = $this->getState('filter.access'))
        {
            $query->where('a.access = ' . (int) $access);
        }

        // Implement View Level Access
        if (!$user->authorise('core.admin'))
        {
            $groups = implode(',', $user->getAuthorisedViewLevels());
            $query->where('a.access IN (' . $groups . ')');
        }        

        //Filtering stepid
        // if ($stepid = $this->getState('filter.stepid'))
        // {
        //     $query->where('a.stepid = ' . (int) $stepid);
        // }

        $filter_steps = $this->state->get('filter.steps');
        if(!empty($filter_steps)){
            if(!in_array(0, $filter_steps)){
                $filter_steps = implode(',', $filter_steps);
                $query->where('a.stepid IN ('.$filter_steps.')');
            }
        }        

        // Filter by category
        $filter_category = $this->state->get('filter.category');
        if(!empty($filter_category)){
            if(!in_array(0, $filter_category)){
                $filter_category = implode(',', $filter_category);
                $query->where('a.catid IN ('.$filter_category.')');
            }
        } 

        //Filtering owned
	    $filter_owned = $this->state->get("filter.owned");
	    if ($filter_owned == 'yes' && $user->id > 0) {
		    $query->where("a.created_by = '".$user->id."'");
	    }

	    // Filter by language
	    if ($this->getState('filter.language'))
	    {
		    $query->where('a.language IN (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
	    }

	    // Filter by geo-boundaries (Currently used only by API requests)
	    $minLat = $this->state->get('filter.imcapi.minLat');
	    $maxLat = $this->state->get('filter.imcapi.maxLat');
	    $minLng = $this->state->get('filter.imcapi.minLng');
	    $maxLng = $this->state->get('filter.imcapi.maxLng');

	    if(!is_null($minLat) && !is_null($maxLat) && !is_null($minLng) && !is_null($maxLng))
	    {
		    $query->where('a.latitude BETWEEN ' . $minLat . ' AND ' . $maxLat );
		    $query->where('a.longitude BETWEEN ' . $minLng . ' AND ' . $maxLng );
	    }

        // Filter by timestamp/prior to (Currently used only by API requests)
        $ts = $this->state->get('filter.imcapi.ts');
        $prior_to = $this->state->get('filter.imcapi.priorto');
        if(!is_null($ts))
        {
	        //$query->where('UNIX_TIMESTAMP(a.updated) >=' . $ts);
	        $query->where('a.updated >= "' . $ts .'"');

        }
	    if(!is_null($prior_to))
	    {
		    //$query->where('UNIX_TIMESTAMP(a.updated) <=' . $prior_to);
		    $query->where('a.updated <= "' . $prior_to .'"');
	    }

	    //check also for raw issues (API)
	    $ts = $this->state->get('filter.imcapi.created.ts');
	    $prior_to = $this->state->get('filter.imcapi.created.priorto');
	    if(!is_null($ts))
	    {
		    $query->where('a.created >= "' . $ts .'"');
	    }
	    if(!is_null($prior_to))
	    {
		    $query->where('a.created <= "' . $prior_to .'"');
	    }

	    // Add the list ordering clause.
	    $orderCol = $this->state->get('list.ordering');
	    $orderDirn = $this->state->get('list.direction');

        if($orderCol == 'a.votes')
        {
            $orderDirn = 'desc';
        }

	    if(!$orderCol && !$orderDirn)
	    {
		    $orderCol = $this->state->get('filter.imcapi.ordering', '');
		    $orderDirn = $this->state->get('filter.imcapi.direction', '');
	    }

	    if ($orderCol == 'access_level')
        {
            $orderCol = 'ag.title';
        }

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }
        else {
            //set default ordering
            $query->order($db->escape('a.updated' . ' ' . 'DESC'));       
        }

        $imc_limit = $this->state->get('filter.imcapi.limit', null);
        if(!is_null($imc_limit)) {
            $query->setlimit($imc_limit);
        }

        return $query;
    }

    public function getItems() {
        $items = parent::getItems();

        if (JFactory::getApplication()->isSite()) {

            $user = JFactory::getUser();
            $canChange = $user->authorise('core.edit.state', 'com_imc');
            $groups = $user->getAuthorisedViewLevels();
            $categories = JCategories::getInstance('imc');
	        $commentsModel = JModelLegacy::getInstance( 'Comments', 'ImcModel', array('ignore_request' => true) );

            $imc_raw = $this->state->get('filter.imcapi.raw', false);
            for ($x = 0, $count = count($items); $x < $count; $x++)
            {
                $items[$x]->created_by_name = JFactory::getUser($items[$x]->created_by)->name;

                // Set category image (for marker icon)...avoid using JCategories, just get category params in the main query
                $prms = json_decode($items[$x]->catid_params);
                unset($items[$x]->catid_params);
                // if(isset($prms->imc_category_emails))
                //     $items[$x]->notification_emails = explode("\n", $prms->imc_category_emails);
                // else
                //     $items[$x]->notification_emails = array();

                if (isset($prms->image))
                    $items[$x]->category_image = $prms->image;
                else
                    $items[$x]->category_image = '';


                // Check the access level. Remove issues the user shouldn't see
                if (!in_array($items[$x]->access, $groups)) {
                    unset($items[$x]);
                    continue;
                }

                //Remove according to category access level
                //if not privileged user Jcategory->get returns nothing... actually the whole object is not even set
                if (!isset($categories->get($items[$x]->catid)->access)) {
                    unset($items[$x]);
                    continue;
                }

                if (!$imc_raw) {
                    //Check the state. Remove issues that are not not published but keep own (unless is privileged)
                    if (($items[$x]->created_by != $user->id) && ($items[$x]->state != 1) && (!$canChange)) {
                        unset($items[$x]);
                        continue;
                    }

                    //Remove anonymous unpublished
                    if (($items[$x]->created_by == 0) && ($items[$x]->state != 1)) {
                        unset($items[$x]);
                        continue;
                    }
                }

	            //TODO: that's maybe not so efficient
	            $items[$x]->comments = $commentsModel->count($items[$x]->id, $items[$x]->created_by);
            }

            //avoid using model limit ($query->setlimit(x)) at getListQuery() when called from API
            /*
            $imc_limit =  $this->state->get('filter.imcapi.limit');
            if(!is_null($imc_limit) && $imc_limit > 0)
            {
                $items = array_slice($items, 0, $imc_limit);
            }
            */

        }
        return $items;
    }

}
