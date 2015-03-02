<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgImcmail_notifier extends JPlugin
{

	function onAfterNewIssueAdded($model, $validData, $id = null)
	{
		//check if issue added from frontend
		if($id == null){
			$issueid = $model->getItem()->get('id');
		} 
		else {
			$issueid = $id;
		}

		//$emails = $model->getItem($issueid)->get('notification_emails');
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel' );
		$emails = $issueModel->getItem($issueid)->get('notification_emails');

		$userid = $model->getItem($issueid)->get('created_by');
		$username = JFactory::getUser($userid)->name;
		$useremail = JFactory::getUser($userid)->email;
		
		//Prepare email for admins
		if(empty($emails) || $emails[0] == ''){
			JFactory::getApplication()->enqueueMessage('Admin notifications for this category are not set', 'Info');
		}
		else {
			$recipients = implode(',', $emails);
			JFactory::getApplication()->enqueueMessage('Notification mail sent to '.$recipients, 'Info');
		}

		//Prepare email for user
		JFactory::getApplication()->enqueueMessage('Notification mail sent to '.$username.' at '.$useremail, 'Info');

	}	

	function onAfterStepModified($model, $validData)
	{
		//print_r($validData);
		$emails = $model->getItem()->get('notification_emails');
		$recipients = implode(',', $emails);
		JFactory::getApplication()->enqueueMessage('Notification mail (because step has modified) sent to '.$recipients, 'Info');
	}	

	function onAfterCategoryModified($model, $validData)
	{
		//print_r($validData);
		$emails = $model->getItem()->get('notification_emails');
		$recipients = implode(',', $emails);
		JFactory::getApplication()->enqueueMessage('Notification mail (because category has changed) sent to '.$recipients, 'Info');
	}	
}
