<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgImcmail_notifier extends JPlugin
{


	function onAfterNewIssueAdded($model, $validData)
	{
		//print_r($validData);
		//die;
		JFactory::getApplication()->enqueueMessage('Notification mail sent', 'info');
	}	

	function onAfterStepModified($model, $validData)
	{
		//print_r($validData);
		//print_r($model);die;
		JFactory::getApplication()->enqueueMessage('Notification mail sent because step is modified', 'info');
	}	
}
