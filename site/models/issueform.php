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

jimport('joomla.application.component.modelform');
jimport('joomla.event.dispatcher');

/**
 * Imc model.
 */
class ImcModelIssueForm extends JModelForm
{

	var $_item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState()
	{
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
	public function &getData($id = null)
	{
		if ($this->_item === null) {
			$this->_item = false;

			if (empty($id)) {
				$id = $this->getState('issue.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table->load($id)) {

				$user = JFactory::getUser();
				$id = $table->id;
				if ($id) {
					$canEdit = $user->authorise('core.edit', 'com_imc.issue.' . $id) || $user->authorise('core.create', 'com_imc.issue.' . $id);
				} else {
					$canEdit = $user->authorise('core.edit', 'com_imc') || $user->authorise('core.create', 'com_imc');
				}
				if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.' . $id)) {
					$canEdit = $user->id == $table->created_by;
				}

				if (!$canEdit) {
					JError::raiseError('500', JText::_('JERROR_ALERTNOAUTHOR'));
				}

				// Check published state.
				if ($published = $this->getState('filter.published')) {
					if ($table->state != $published) {
						return $this->_item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties = $table->getProperties(1);
				$this->_item = JArrayHelper::toObject($properties, 'JObject');


				//get category properties
				$category = JCategories::getInstance('Imc')->get($this->_item->catid);
				$params = json_decode($category->params);
				// if(isset($params->imc_category_emails))
				// 	$this->_item->notification_emails = explode("\n", $params->imc_category_emails);
				// else
				// 	$this->_item->notification_emails = array();
				if (isset($params->image))
					$this->_item->category_image = $params->image;
				else
					$this->_item->category_image = '';
			} elseif ($error = $table->getError()) {
				$this->setError($error);
			}
		}

		return $this->_item;
	}

	public function getTable($type = 'Issue', $prefix = 'ImcTable', $config = array())
	{
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
	public function checkin($id = null)
	{
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
	public function checkout($id = null)
	{
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

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML 
	 * 
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_imc.issue', 'issueform', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_imc.edit.issue.data', array());
		if (empty($data)) {
			$data = $this->getData();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param	array		The form data.
	 * @return	mixed		The user id on success, false on failure.
	 * @since	1.6
	 */
	public function save($data)
	{
		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('issue.id');
		$state = (!empty($data['state'])) ? 1 : 0;

		//$user = JFactory::getUser();
		//check API request
		$user = (isset($data['userid']) ? JFactory::getUser($data['userid']) : JFactory::getUser());

		if ($id) {
			//Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_imc.issue.' . $id) || $authorised = $user->authorise('core.edit.own', 'com_imc.issue.' . $id);
			if ($user->authorise('core.edit.state', 'com_imc.issue.' . $id) !== true && $state == 1) { //The user cannot edit the state of the item.
				return false;
				$data['state'] = 0;
			}
		} else {
			//Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_imc');
			if ($user->authorise('core.edit.state', 'com_imc.issue.' . $id) !== true && $state == 1) { //The user cannot edit the state of the item.
				$data['state'] = 0;
			}
			//for new issues the updated date is the same as the created date
			$data['updated'] = $data['created'];
		}

		//TODO: moderation check settings
		$data['state'] = 1;
		if (!$id) {
			$com_imc_params = JComponentHelper::getParams('com_imc');
			$moderation	= $com_imc_params->get('newissueneedsmoderation');
			$data['moderation'] = $moderation;
		}

		if ($authorised !== true) {
			JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			return false;
		}

		$table = $this->getTable();
		if ($table->save($data) === true) {
			//set insertid to simulate postHook in controller
			JFactory::getApplication()->setUserState('com_imc.edit.issue.insertid', $table->get('id'));
			return $id;
		} else {
			return false;
		}
	}

	function delete($data)
	{
		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('issue.id');
		if (JFactory::getUser()->authorise('core.delete', 'com_imc.issue.' . $id) !== true) {
			JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			return false;
		}
		$table = $this->getTable();
		if ($table->delete($data['id']) === true) {
			return $id;
		} else {
			return false;
		}

		return true;
	}



	public function getItem($pk = null)
	{
		return $this->getData($pk); //TODO: returns empty even with correct id...
	}
}
