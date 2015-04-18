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
     */
    protected function populateState($ordering = null, $direction = null) {
        // Initialise variables.
        $app = JFactory::getApplication('administrator');

        // Load the filter state.
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
        $this->setState('filter.state', $published);

        $access = $app->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
        $this->setState('filter.access', $access);

		//Filtering stepid
		$this->setState('filter.stepid', $app->getUserStateFromRequest($this->context.'.filter.stepid', 'filter_stepid', '', 'string'));

		//Filtering catid
		$this->setState('filter.catid', $app->getUserStateFromRequest($this->context.'.filter.catid', 'filter_catid', '', 'string'));

        //Filtering subgroup
        $this->setState('filter.subgroup', $app->getUserStateFromRequest($this->context.'.filter.subgroup', 'filter_subgroup', '', 'string'));

        // Load the parameters.
        $params = JComponentHelper::getParams('com_imc');
        $this->setState('params', $params);

        // List state information.
        parent::populateState('a.id', 'desc');
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param	string		$id	A prefix for the store id.
     * @return	string		A store id.
     * @since	1.6
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id.= ':' . $this->getState('filter.search');
        $id.= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.access');
        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return	JDatabaseQuery
     * @since	1.6
     */
    protected function getListQuery() {
        $user = JFactory::getUser();
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

        
		// Join over the users for the checked out user
		$query->select("uc.name AS editor");
		$query->join("LEFT", "#__users AS uc ON uc.id=a.checked_out");
		// Join over the category 'catid'
		$query->select('catid.title AS catid_title');
		$query->join('LEFT', '#__categories AS catid ON catid.id = a.catid');
		// Join over the user field 'created_by'
		$query->select('created_by.name AS created_by');
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');
        // Join over the asset groups.
        $query->select('ag.title AS access_level')
            ->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
        // Join over the imc steps.
        $query->select('st.title AS stepid_title, st.stepcolor AS stepid_color')
            ->join('LEFT', '#__imc_steps AS st ON st.id = a.stepid');
        

		// Filter by published state
        $published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int) $published);
		} else if ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}

        // Filter by search in title
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('( a.title LIKE '.$search.'  OR  a.address LIKE '.$search.' )');
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
        if ($stepid = $this->getState('filter.stepid'))
        {
            $query->where('a.stepid = ' . (int) $stepid);
        }


		//Filtering catid
		$filter_catid = $this->state->get("filter.catid");
		if ($filter_catid) {
			$query->where("a.catid = '".$db->escape($filter_catid)."'");
		}

        //Filtering by category usergroups except if access imc.showall.issues = true
        $canShowAllIssues = $user->authorise('imc.showall.issues');
        if(!$canShowAllIssues){
            require_once JPATH_COMPONENT . '/helpers/imc.php';
            $allowed_catids = ImcHelper::getCategoriesByUserGroups();
            $allowed_catids = implode(',', $allowed_catids);
            if(!empty($allowed_catids)){
                $query->where('a.catid IN (' . $allowed_catids . ')');
            }
            else {
                //show nothing
                $query->where('a.catid = -1');   
            }
        }

        //Filtering by subgroup
        $filter_subgroup = $this->state->get("filter.subgroup");
        if ($filter_subgroup) {
            $query->where("a.subgroup = '".$db->escape($filter_subgroup)."'");
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
       
        if ($orderCol == 'access_level')
        {
            $orderCol = 'ag.title';
        }

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }

        return $query;
    }

    public function getItems() {
        $items = parent::getItems();

        /* Causes pagination problem. Check: http://issues.joomla.org/tracker/joomla-cms/6645 */
/*        if (JFactory::getApplication()->isAdmin())
        {
            $user = JFactory::getUser();
            $canShowAllIssues = $user->authorise('imc.showall.issues');
            if (!$canShowAllIssues) {
                $usergroups = JAccess::getGroupsByUser($user->id);

                for ($x = 0, $count = count($items); $x < $count; $x++)
                {
                    // Check the category usergroup level. Remove issues the user shouldn't see
                    $category = JCategories::getInstance('Imc')->get($items[$x]->catid);
                    $params = json_decode($category->params);
                    if(isset($params->imc_category_usergroup))
                        $imc_category_usergroup = $params->imc_category_usergroup;
                    else
                        $imc_category_usergroup = array();

                    $group_found = false;
                    foreach ($imc_category_usergroup as $group) {
                        if (in_array($group, $usergroups))
                        {
                            $group_found = true;
                            break;
                        }
                    }
                    if($group_found == false){
                        unset($items[$x]);
                    }

                }
            }
        }*/
        


        if (JFactory::getApplication()->isSite())
        {
            $user = JFactory::getUser();
            $groups = $user->getAuthorisedViewLevels();

            for ($x = 0, $count = count($items); $x < $count; $x++)
            {
                // Check the access level. Remove articles the user shouldn't see
                if (!in_array($items[$x]->access, $groups))
                {
                    unset($items[$x]);
                }
            }
        }

        return $items;
    }

    public function isOwnIssue($issueid, $userid) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)');
        $query->from('`#__imc_issues` AS a');
        $query->where('a.id    = ' . $db->quote($db->escape($issueid)));
        $query->where('a.created_by = ' . $db->quote($db->escape($userid)));
        $db->setQuery($query);
        $results = $db->loadResult();
        
        return $results;
    }

    public function updateVotes($issueid, $userid) {
        $db = JFactory::getDbo();        
        $query = $db->getQuery(true);
        $query  ->update($db->quoteName('#__imc_issues'))
                ->set($db->quoteName('votes') .'=' . $db->quoteName('votes') . ' + 1')
                ->where($db->quoteName('id') .'='. $issueid);
        $db->setQuery($query);
        $results = $db->execute();
        return $results;
    }

    public function getVotes($issueid) {
        $db = JFactory::getDbo();  
        $query = $db->getQuery(true);
        $query->select('a.votes');
        $query->from('`#__imc_issues` AS a');
        $query->where('a.id = '. $issueid);
        $db->setQuery($query);
        $results = $db->loadResult();
        return $results;
    }


}
