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

jimport('joomla.application.component.modeladmin');

/**
 * Imc model.
 */
class ImcModelIssue extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_IMC';


	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Issue', $prefix = 'ImcTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		An optional array of data for the form to interogate.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	JForm	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app	= JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_imc.issue', 'issue', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}

		



		$jinput = JFactory::getApplication()->input;

		// The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		if ($jinput->get('a_id'))
		{
			$id = $jinput->get('a_id', 0);
		}
		// The back end uses id so we use that the rest of the time and set it to 0 by default.
		else
		{
			$id = $jinput->get('id', 0);
		}

		$user = JFactory::getUser();

		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_imc.issue.' . (int) $id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_imc'))
		)
		{
			//TODO: This alert to be moved on view
			echo '<div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">×</button>'.JText::_('COM_IMC_ACTION_ALERT').'</div>';
			// Disable fields for display.
			//$form->setFieldAttribute('stepid', 'readonly', 'true');
			//$form->setFieldAttribute('featured', 'disabled', 'true');
			//$form->setFieldAttribute('ordering', 'disabled', 'true');
			//$form->setFieldAttribute('publish_up', 'disabled', 'true');
			//$form->setFieldAttribute('publish_down', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');
			//$form->setFieldAttribute('description', 'disabled', 'true');
			//$form->setFieldAttribute('address', 'disabled', 'true');

			
			// Disable fields while saving.
			// The controller has already verified this is an article you can edit.
			//$form->setFieldAttribute('stepid', 'filter', 'unset');
			//$form->setFieldAttribute('featured', 'filter', 'unset');
			//$form->setFieldAttribute('ordering', 'filter', 'unset');
			//$form->setFieldAttribute('publish_up', 'filter', 'unset');
			//$form->setFieldAttribute('publish_down', 'filter', 'unset');
			//$form->setFieldAttribute('state', 'filter', 'unset');
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
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_imc.edit.issue.data', array());

		if (empty($data)) {
			$data = $this->getItem();
            

			//Support for multiple or not foreign key field: stepid
			/* itsam
			$array = array();
			foreach((array)$data->stepid as $value): 
				if(!is_array($value)):
					$array[] = $value;
				endif;
			endforeach;
			$data->stepid = implode(',',$array);
			*/
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 * @since	1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			//Do any procesing on fields here if needed
	        $category = JCategories::getInstance('Imc')->get($item->catid);
	        $params = json_decode($category->params);
	        if(isset($params->imc_category_emails)){
	        	//$item->notification_emails = explode("\n", $params->imc_category_emails);
	        	$category_emails = explode("\n", $params->imc_category_emails);
	        	$notification_emails = array();
	        	foreach ($category_emails as $email) {
	        		$line = explode(':', $email);
	        		if($line[0] != '')
	        			array_push($notification_emails, $line[0]);
	        	}
	        	$item->notification_emails = $notification_emails;
	        }
	        else{
	        	$item->notification_emails = array();
	        }

	        if(isset($params->imc_category_usergroup))
	        	$item->imc_category_usergroup = $params->imc_category_usergroup;
	        else
	        	$item->imc_category_usergroup = array();

	        if(isset($params->image))
	        	$item->category_image = $params->image;
	        else
	        	$item->category_image = '';

	        $user = JFactory::getUser($item->created_by);
			$userProfile = JUserHelper::getProfile( $item->created_by );
			$item->creatorDetails = array(
	        	'name'=>$user->name,
	        	'username'=>$user->username,
	        	'email'=>$user->email
	        );
			$item->userProfile = $userProfile;

		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id)) {

			// Set ordering to the last item if not set
			if (@$table->ordering === '') {
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__imc_issues');
				$max = $db->loadResult();
				$table->ordering = $max+1;
			}

		}
	}

	public function batch($commands, $pks, $contexts)
	{

		$app = JFactory::getApplication();

		parent::batch($commands, $pks, $contexts);

		$cmd = JArrayHelper::getValue($commands, 'move_copy', 'c');

		if ($cmd == 'c')
		{
			$app->enqueueMessage('Copy is under construction. No images, logs and notifications are created', 'info');
		}
		elseif ($cmd == 'm')
		{
			//$app->enqueueMessage('After move do the magic', 'info');
		}

		return true;

	}

}