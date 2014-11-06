<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgImcmail_notifier extends JPlugin
{

	function onAfterNewIssueAdded($model, $validData, $id = null)
	{
		
		$emails = $model->getItem($id)->get('notification_emails');
		$recipients = implode(',', $emails);
		if(empty($emails)) $recipients = "mayor@municipality.gr"; //TEMPORARY FOR DEMO
		JFactory::getApplication()->enqueueMessage('Notification mail sent to '.$recipients, 'Info');
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
