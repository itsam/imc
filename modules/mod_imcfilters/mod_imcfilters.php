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
	if($params->get('show_on_details') == 0){
		$module->showtitle = false;
		return;
	}
}

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$doc = JFactory::getDocument();
$doc->addStyleSheet(JURI::base() . '/modules/mod_imcfilters/assets/css/style.css');
$doc->addScript(JURI::base() . '/modules/mod_imcfilters/assets/js/script.js');
require JModuleHelper::getLayoutPath('mod_imcfilters', $params->get('layout_type', 'default'));
