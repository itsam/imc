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

//$doc = JFactory::getDocument();
//$doc->addStyleSheet(JURI::base() . '/modules/mod_imc/assets/css/style.css');
//$doc->addScript(JURI::base() . '/modules/mod_imc/assets/js/script.css');
require JModuleHelper::getLayoutPath('mod_imcfilters', $params->get('content_type', 'blank'));
