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

$listOrder = $jinput->get('filter_order');
$listDirn  = $jinput->get('filter_order_Dir');

?>

<div class="imc_filters_buttons">
	<form class="form-search form-inline" action="<?php echo JRoute::_('index.php?option=com_imc&view=issues'); ?>" method="post" name="imc_filter_form" id="imc_filter_form">
		<?php if ($option == 'com_imc' && $view != 'issues') : ?>
			<span class="imc_btn_left">
				<a href="<?php echo JRoute::_('index.php?option=com_imc', false, 2); ?>" class="btn btn-primary"><i class="icon-arrow-left"></i> <?php echo JText::_('COM_IMC_RETURN_TO_ISSUES'); ?></a>		
			</span>				
		<?php else : ?>
			<div class="imc_btn_left">
				
				<button type="button" class="btn btn-primary"><i class="icon-filter"></i> <?php echo JText::_('COM_IMC_FILTERS'); ?></button>
				
				<div class="btn-group">
				  
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				    Ordering <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu">
				    <li><?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_TITLE', 'a.title', $listDirn, $listOrder); ?></li>
					<li><?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_STEPID', 'a.stepid', $listDirn, $listOrder); ?></li>
				  </ul>
				</div>
				
				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				    Display <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu">
				  	<?php echo ModImcfiltersHelper::createLimitBox(); ?>
				  </ul>
				</div>		
				
			</div>
		<?php endif; ?>

	</form>

	<?php $canCreate = JFactory::getUser()->authorise('core.create', 'com_imc'); ?>
	<?php if ($canCreate): ?>
		<div class="imc_btn_right">
	    	<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=0', false, 2); ?>" class="btn btn-success btn-large"><i class="icon-plus"></i> <?php echo JText::_('COM_IMC_ADD_ITEM'); ?></a>
	    </div>
	<?php endif; ?>

</div>