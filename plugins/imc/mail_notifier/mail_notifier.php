<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgImcmail_notifier extends JPlugin
{


	function onAfterNewIssueAdded($model, $validData)
	{
		print_r($validData);
		print_r($model);
		echo 'I AM MAIL NOTIFIER PLUGIN';
		JFactory::getApplication()->enqueueMessage('Notification mail sent', 'info');
	}	

}
