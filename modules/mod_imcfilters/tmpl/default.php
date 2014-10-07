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



$app = JFactory::getApplication();
$search = $app->getUserStateFromRequest('com_imc.issues.filter.search', 'filter_search');
$owned = $app->getUserStateFromRequest('com_imc.issues.filter.owned', 'filter_owned');

$jinput = $app->input;
$option = $jinput->get('option', null);
$view = $jinput->get('view', null);
?>

<div class="imc_filters_buttons">

	<?php if ($option == 'com_imc' && $view != 'issues') : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_imc', false, 2); ?>" class="btn btn-primary"><i class="icon-arrow-left"></i> <?php echo JText::_('COM_IMC_RETURN_TO_ISSUES'); ?></a>		
	<?php else : ?>
		<a href="#" class="btn btn-primary"><i class="icon-filter"></i> <?php echo JText::_('COM_IMC_FILTERS'); ?></a>
	<?php endif; ?>
	
	<?php /*
	<form class="form-search form-inline" action="<?php echo JRoute::_('index.php?option=com_imc&view=issues'); ?>" method="post" name="imc_filter_form" id="imc_filter_form">
	</form>
	*/ 
	?>

	<?php $canCreate = JFactory::getUser()->authorise('core.create', 'com_imc'); ?>
	<?php if ($canCreate): ?>
		<span class="imc_btn_right">
	    	<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=0', false, 2); ?>" class="btn btn-success btn-large"><i class="icon-plus"></i> <?php echo JText::_('COM_IMC_ADD_ITEM'); ?></a>
	    </span>
	<?php endif; ?>
</div>