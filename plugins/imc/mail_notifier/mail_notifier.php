<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgImcmail_notifier extends JPlugin
{

	public function onAfterNewIssueAdded($model, $validData, $id = null)
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

		$userid = $issueModel->getItem($issueid)->get('created_by');
		$username = JFactory::getUser($userid)->name;
		$useremail = JFactory::getUser($userid)->email;
		
		//Prepare email for admins
		if(empty($emails) || $emails[0] == ''){
			JFactory::getApplication()->enqueueMessage('Admin notifications for this category are not set', 'Info');
		}
		else {
			$recipients = implode(',', $emails);
			JFactory::getApplication()->enqueueMessage('Admin notification mail is sent to '.$recipients, 'Info');
		}

		//Prepare email for user
		JFactory::getApplication()->enqueueMessage('User notification mail is sent to '.$username.' at '.$useremail, 'Info');

	}	

	public function onAfterStepModified($model, $validData, $id = null)
	{
		//check if issue added from frontend
		if($id == null){
			$issueid = $model->getItem()->get('id');
		} 
		else {
			$issueid = $id;
		}

		//$emails = $model->getItem()->get('notification_emails');
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel' );
		$emails = $issueModel->getItem($issueid)->get('notification_emails');

		$userid = $issueModel->getItem($issueid)->get('created_by');
		$username = JFactory::getUser($userid)->name;
		$useremail = JFactory::getUser($userid)->email;
		
		//Prepare email for admins
		//TODO: Do we really need to notify admins to every issue status modification? Set this on settings
		if(empty($emails) || $emails[0] == ''){
			JFactory::getApplication()->enqueueMessage('Admin notifications when issue status modified are not set', 'Info');
		}
		else {
			$recipients = implode(',', $emails);
			JFactory::getApplication()->enqueueMessage('Admin notification mail due to issue status modification is sent to '.$recipients, 'Info');
		}

		//Prepare email for user
		JFactory::getApplication()->enqueueMessage('User notification mail (because issue status has modified) sent to '.$username.' at '.$useremail, 'Info');
	}	

	public function onAfterCategoryModified($model, $validData, $id = null)
	{
		//check if issue added from frontend
		if($id == null){
			$issueid = $model->getItem()->get('id');
		} 
		else {
			$issueid = $id;
		}

		//$emails = $model->getItem()->get('notification_emails');
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		$issueModel = JModelLegacy::getInstance( 'Issue', 'ImcModel' );
		$emails = $issueModel->getItem($issueid)->get('notification_emails');

		$userid = $issueModel->getItem($issueid)->get('created_by');
		$username = JFactory::getUser($userid)->name;
		$useremail = JFactory::getUser($userid)->email;
		
		//Prepare email for admins
		if(empty($emails) || $emails[0] == ''){
			JFactory::getApplication()->enqueueMessage('Admin notifications when category has modified are not set', 'Info');
		}
		else {
			$recipients = implode(',', $emails);
			JFactory::getApplication()->enqueueMessage('Notification mail due to category modification is sent to '.$recipients, 'Info');
		}

		//Prepare email for user
		//TODO: Do we really need to notify user for categeory modification? Set this on settings
		JFactory::getApplication()->enqueueMessage('Notification mail (because category has modified) sent to '.$username.' at '.$useremail, 'Info');

	}	
}
