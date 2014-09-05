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



$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', null);
$view = $jinput->get('view', null);

//Show module only on issues list view
if ($option == 'com_imc' && $view != 'issues'){
	$module->showtitle = false;
	return;
}
?>

<div id="imc-map-canvas"></div>

<?php 
	//initialize map
	$script = array();
	$script[] = "google.maps.event.addDomListener(window, 'load', imc_mod_map_initialize);";
	JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
?>