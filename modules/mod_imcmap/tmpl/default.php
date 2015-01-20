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
if ($option == 'com_imc' && $view != 'issues') {
	//TODO: get the following from module settings	
	$s = "
	    jQuery(document).ready(function() {
	 		jQuery('#map-sidebar').remove();
	 		jQuery('#imc-left').removeClass().addClass('col-xs-12');
	    });
	";
	JFactory::getDocument()->addScriptDeclaration($s);

	$module->showtitle = false;
	return;
}
?>

<div id="imc-mod-map-canvas"></div>