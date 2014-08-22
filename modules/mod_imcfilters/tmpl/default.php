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

$safe_htmltags = array(
    'a', 'address', 'em', 'strong', 'b', 'i',
    'big', 'small', 'sub', 'sup', 'cite', 'code',
    'img', 'ul', 'ol', 'li', 'dl', 'lh', 'dt', 'dd',
    'br', 'p', 'table', 'th', 'td', 'tr', 'pre',
    'blockquote', 'nowiki', 'h1', 'h2', 'h3',
    'h4', 'h5', 'h6', 'hr');
// Check for component
if (!JComponentHelper::getComponent('com_imc', true)->enabled)
{
	echo 'Improve My City component is not enabled';
	return;
}
/* @var $params Joomla\Registry\Registry */
$filter = JFilterInput::getInstance($safe_htmltags);
echo $filter->clean($params->get('html_content'));
?>