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

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';
JHtml::_('jquery.framework');
$doc = JFactory::getDocument();
$doc->addStyleSheet(JURI::base() . '/modules/mod_imcmap/assets/css/style.css');

$params = JComponentHelper::getParams('com_imc');
$api_key = $params->get('api_key');
if($api_key == '')
	echo '<strong>Module IMC Map :: Google Maps API KEY missing</strong>';
else
	$doc->addScript('https://maps.googleapis.com/maps/api/js?key='.$api_key);

$doc->addScript(JURI::base() . '/modules/mod_imcmap/assets/js/script.js');
require JModuleHelper::getLayoutPath('mod_imcmap', $params->get('layout_type', 'default'));
