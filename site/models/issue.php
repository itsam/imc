<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modelitem');
jimport('joomla.event.dispatcher');

/**
 * Imc model.
 */
class ImcModelIssue extends JModelItem {

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since	1.6
     */
    protected function populateState() {
        $app = JFactory::getApplication('com_imc');

        // Load state from the request userState on edit or from the passed variable on default
        if (JFactory::getApplication()->input->get('layout') == 'edit') {
            $id = JFactory::getApplication()->getUserState('com_imc.edit.issue.id');
        } else {
            $id = JFactory::getApplication()->input->get('id');
            JFactory::getApplication()->setUserState('com_imc.edit.issue.id', $id);
        }
        $this->setState('issue.id', $id);

        // Load the parameters.
        $params = $app->getParams();
        $params_array = $params->toArray();
        if (isset($params_array['item_id'])) {
            $this->setState('issue.id', $params_array['item_id']);
        }
        $this->setState('params', $params);
    }

    /**
     * Method to get an ojbect.
     *
     * @param	integer	The id of the object to get.
     *
     * @return	mixed	Object on success, false on failure.
     */
    public function &getData($id = null) {
        if ($this->_item === null) {
            $this->_item = false;

            if (empty($id)) {
                $id = $this->getState('issue.id');
            }

            // Get a level row instance.
            $table = $this->getTable();

            // Attempt to load the row.
            if ($table->load($id)) {
                // Check published state.
                if ($published = $this->getState('filter.published')) {
                    if ($table->state != $published) {
                        return $this->_item;
                    }
                }

                // Convert the JTable to a clean JObject.
                $properties = $table->getProperties(1);
                $this->_item = JArrayHelper::toObject($properties, 'JObject');
            } elseif ($error = $table->getError()) {
                $this->setError($error);
            }
        }

        
/*
			if (isset($this->_item->stepid) && $this->_item->stepid != '') {
				if(is_object($this->_item->stepid)){
					$this->_item->stepid = JArrayHelper::fromObject($this->_item->stepid);
				}
				$values = (is_array($this->_item->stepid)) ? $this->_item->stepid : explode(',',$this->_item->stepid);

				$textValue = array();
				foreach ($values as $value){
					$db = JFactory::getDbo();
					$query = "SELECT id, title AS value FROM #__imc_steps  WHERE id LIKE '" . $value . "'";
					$db->setQuery($query);
					$results = $db->loadObject();
					if ($results) {
						$textValue[] = $results->value;
					}
				}

			$this->_item->stepid = !empty($textValue) ? implode(', ', $textValue) : $this->_item->stepid;

			}
*/

        $step = ImcFrontendHelper::getStepByStepId($this->_item->stepid);
        if($step){
            $this->_item->stepid_title = $step['stepid_title'];
            $this->_item->stepid_color = $step['stepid_color'];
        }
        

		if ( isset($this->_item->created_by) ) {
			$this->_item->created_by_name = JFactory::getUser($this->_item->created_by)->name;
		}

        $category = JCategories::getInstance('Imc')->get($this->_item->catid);
        $prms = json_decode($category->params);
        if(isset($prms->imc_category_emails))
            $this->_item->notification_emails = explode("\n", $prms->imc_category_emails);
        else
            $this->_item->notification_emails = array();
        if(isset($prms->image))
            $this->_item->category_image = $prms->image;
        else
            $this->_item->category_image = '';

        $this->_item->catid_title = $category->title;









        return $this->_item;
    }

    public function getTable($type = 'Issue', $prefix = 'ImcTable', $config = array()) {
        $this->addTablePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to check in an item.
     *
     * @param	integer		The id of the row to check out.
     * @return	boolean		True on success, false on failure.
     * @since	1.6
     */
    public function checkin($id = null) {
        // Get the id.
        $id = (!empty($id)) ? $id : (int) $this->getState('issue.id');

        if ($id) {

            // Initialise the table
            $table = $this->getTable();

            // Attempt to check the row in.
            if (method_exists($table, 'checkin')) {
                if (!$table->checkin($id)) {
                    $this->setError($table->getError());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Method to check out an item for editing.
     *
     * @param	integer		The id of the row to check out.
     * @return	boolean		True on success, false on failure.
     * @since	1.6
     */
    public function checkout($id = null) {
        // Get the user id.
        $id = (!empty($id)) ? $id : (int) $this->getState('issue.id');

        if ($id) {

            // Initialise the table
            $table = $this->getTable();

            // Get the current user object.
            $user = JFactory::getUser();

            // Attempt to check the row out.
            if (method_exists($table, 'checkout')) {
                if (!$table->checkout($user->get('id'), $id)) {
                    $this->setError($table->getError());
                    return false;
                }
            }
        }

        return true;
    }

    public function getLogs($id = null) {
        if($id == null){
            $jinput = JFactory::getApplication()->input;
            if ($jinput->get('a_id'))
                $id = $jinput->get('a_id', 0);
            else
                $id = $jinput->get('id', 0);            
        }
        
        JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
        $logsModel = JModelLegacy::getInstance( 'Logs', 'ImcModel' );
        $results = $logsModel->getItemsByIssue($id);
        return $results;
    }

    public function publish($id, $state) {
        $table = $this->getTable();
        $table->load($id);
        $table->state = $state;
        return $table->store();
    }

    public function delete($id) {
        $table = $this->getTable();
        return $table->delete($id);
    }

}
