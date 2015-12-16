<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/imc.php';
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
        $userid = $this->getState('filter.imcapi.userid', 0);
        $guest = $this->getState('filter.imcapi.guest', false);
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

        $query->from('`#__imc_comments` AS a');

        // Join over the users for the checked out user.
        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

        // Join over the foreign key 'issueid'
        $query->select('b.title AS issue_title');
        $query->join('LEFT', '#__imc_issues AS b ON b.id = a.issueid');

        $query->join('LEFT', '#__users AS u ON u.id = a.created_by');
        $query->select('u.name AS fullname');


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
		$filter_issueid = $this->getState('imc.filter.issueid', null);
		if (!is_null($filter_issueid)) {
			$query->where("a.issueid = ".$filter_issueid);
		}

        //Filtering state
        $filter_state = $this->getState('imc.filter.state', null);
        if (!is_null($filter_state)) {
            $query->where('a.state = '.$filter_state);
        }

        // Filter by moderation (for non-comment-admin users)
	    if(!ImcHelper::getActions(JFactory::getUser($userid))->get('imc.manage.comments'))
	    {
		    $query->where('
	            (
	            (a.created_by > 0 AND a.created_by  =' . $user->id . ' AND a.moderation IN (0,1)) OR
	            (a.created_by > 0 AND a.created_by !=' . $user->id . ' AND a.moderation = 0)
	            )
            ');
	    }

        return $query;
    }

    public function getItems() {
        $items = parent::getItems();
        return $items;
    }

    public function count($issueid, $userid = null)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)');
        $query->from($db->quoteName('#__imc_comments'));
        $query->where($db->quoteName('issueid')." = ".$db->quote($issueid));
        if(!is_null($userid))
        {
            // Filter by moderation (for non-comments-admin users)
            if(!ImcHelper::getActions(JFactory::getUser($userid))->get('imc.manage.comments'))
            {
                $query->where('
	            (
	            (created_by > 0 AND created_by  =' . $userid . ' AND moderation IN (0,1)) OR
	            (created_by > 0 AND created_by !=' . $userid . ' AND moderation = 0)
	            )
            ');
            }
        }
        $db->setQuery($query);
        $count = $db->loadResult();
        return $count;
    }

    public function add($commentObj)
    {
        $db = JFactory::getDBO();
        $result = $db->insertObject('#__imc_comments', $commentObj);
        if(!$result)
        {
            throw new Exception('Cannot store new comment');
        }
        $lastinsertid = $db->insertid();
        return $lastinsertid;

    }

    public function getIds($issueid)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from($db->quoteName('#__imc_comments'));
        $query->where($db->quoteName('issueid')." = ".$issueid);
        $db->setQuery($query);
        $result = $db->loadColumn();
        return $result;
    }
}
