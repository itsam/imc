<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @subpackage  mod_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;

// Check for component
if (!JComponentHelper::getComponent('com_imc', true)->enabled)
{
	echo '<div class="alert alert-danger">Improve My City component is not enabled</div>';
	return;
}

$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', null);
$view = $jinput->get('view', null);

if ($option == 'com_imc' && $view != 'issues'){
	$module->showtitle = false;
	return;
}
?>

<div id="imc-map-canvas"></div>

<?php 
		//http://stackoverflow.com/questions/25209463/asynchronous-loading-of-google-map-markers-from-json-data
		//initialize map
		//$script = array();
		//$script[] = "google.maps.event.addDomListener(window, 'load', initialize);";
		//JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
?>