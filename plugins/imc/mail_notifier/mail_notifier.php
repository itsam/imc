<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgImcmail_notifier extends JPlugin
{


	function onAfterNewIssueAdded($model, $validData)
	{
		//print_r($validData);
		$emails = $model->getItem()->get('notification_emails');
		$recipients = implode(',', $emails);
		JFactory::getApplication()->enqueueMessage('Notification mail sent to '.$recipients, 'info');
	}	

	function onAfterStepModified($model, $validData)
	{
		//print_r($validData);
		$emails = $model->getItem()->get('notification_emails');
		$recipients = implode(',', $emails);
		JFactory::getApplication()->enqueueMessage('Notification mail (because step is modified) sent to '.$recipients, 'info');
	}	
}
