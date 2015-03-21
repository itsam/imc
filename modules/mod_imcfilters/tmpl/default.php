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

//get state of Issues model
$issuesModel = JModelLegacy::getInstance( 'Issues', 'ImcModel', array('ignore_request' => false) );
$state = $issuesModel->getState();

$listOrder = $state->get('list.ordering');
$listDirn  = $state->get('list.direction');

$app = JFactory::getApplication();
$search = $app->getUserStateFromRequest('com_imc.issues.filter.search', 'filter_search');
$owned = $app->getUserStateFromRequest('com_imc.issues.filter.owned', 'filter_owned');
$cat = $app->getUserStateFromRequest('com_imc.issues.filter.category', 'cat', array()); 

$jinput = $app->input;
$option = $jinput->get('option', null);
$view = $jinput->get('view', null);
$id = $jinput->get('id', null);
?>

<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function() {
		js('#new-vote').click(function(e) {
			e.preventDefault();
			vote('<?php echo $id;?>', '<?php echo JFactory::getUser()->id; ?>', '<?php echo JSession::getFormToken(); ?>');
		});
	});
</script>
<div class="imc-mod-imcfilters-wrapper<?php echo $moduleclass_sfx ?>">
<form class="form-search form-inline" action="<?php echo JRoute::_('index.php?option=com_imc&view=issues'); ?>" method="post" name="imc_filter_form" id="adminForm">
	<div class="imc_filters_buttons">
		<?php if ($option == 'com_imc' && $view != 'issues') : ?>
			<span class="imc_btn_left">
				<a href="<?php echo JRoute::_('index.php?option=com_imc', false, 2); ?>" class="btn btn-info"><i class="icon-arrow-left"></i> <?php echo JText::_('MOD_IMCFILTERS_RETURN_TO_ISSUES'); ?></a>		
			</span>				
		<?php else : ?>
			<div class="imc_btn_left">
				<a id="filters_btn" href="#filtersModal" role="button" class="btn btn-primary" data-toggle="modal"><i class="icon-filter"></i> <?php echo JText::_('MOD_IMCFILTERS_FILTERS'); ?></a>
				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				    <?php echo JText::_('MOD_IMCFILTERS_ORDERING'); ?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu">
				    <li><?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_TITLE', 'a.title', $listDirn, $listOrder); ?></li>
					<li><?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_STEPID', 'a.stepid', $listDirn, $listOrder); ?></li>
					<li><?php echo JHtml::_('grid.sort',  'JDATE', 'a.updated', $listDirn, $listOrder); ?></li>
				  </ul>
				</div>
				
				
				<div class="btn-group">
				  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				    <?php echo JText::_('MOD_IMCFILTERS_DISPLAY'); ?> <span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu">
				  	<?php echo ModImcfiltersHelper::createLimitBox($state->get('list.limit')); ?>
				  </ul>
				</div>		
				

				<?php /*
				TODO: Set layout state
				<div class="btn-group">
					<span class="imc_btn_left">
						<a title="list layout" href="<?php echo JRoute::_('index.php?option=com_imc&layout=default', false, 2); ?>" class="btn btn-default"><i class="icon-align-justify"></i></a>		
					</span>	
					<span class="imc_btn_left">
						<a title="tabular layout" href="<?php echo JRoute::_('index.php?option=com_imc&layout=tabular', false, 2); ?>" class="btn btn-default"><i class="icon-list"></i></a>		
					</span>	
					<span class="imc_btn_left">
						<a title="media layout" href="<?php echo JRoute::_('index.php?option=com_imc&layout=media', false, 2); ?>" class="btn btn-default"><i class="icon-th"></i></a>		
					</span>	
				</div>					
				*/ ?>
			</div>
		<?php endif; ?>

		<?php $canCreate = JFactory::getUser()->authorise('core.create', 'com_imc'); ?>
		
		<?php if ($canCreate && $option == 'com_imc' && $view == 'issues'): ?>
			<div class="imc_btn_right">
		    	<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=0', false, 2); ?>" class="btn btn-success btn-large btn-lg"><i class="icon-plus"></i> <?php echo JText::_('MOD_IMCFILTERS_ADD_ITEM'); ?></a>
		    </div>
		<?php endif; ?>
		<?php if ($canCreate && $option == 'com_imc' && $view == 'issue'): ?>
			<div class="imc_btn_right">
				<div class="btn-group btn-group-lg" role="group" aria-label="">
		    	<button id="new-vote" class="btn btn-success btn-large btn-lg"><i class="icon-thumbs-up"></i> +1 <?php echo JText::_('MOD_IMCFILTERS_VOTE'); ?></button>
		    	<span id="votes-counter" class="btn btn-success btn-large btn-lg disabled"><?php echo ModImcfiltersHelper::getVotes($id); ?></span>
		    	</div>
		    	<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=0', false, 2); ?>" class="btn btn-default btn-large btn-lg"><i class="icon-plus"></i> <?php echo JText::_('MOD_IMCFILTERS_ADD_ITEM'); ?></a>
		    </div>
		<?php elseif(JFactory::getUser()->guest && $option == 'com_imc' && $view == 'issue') : ?>
			<div class="imc_btn_right">
				<div class="btn-group btn-group-lg" role="group" aria-label="">
		    	<button class="btn btn-success btn-large btn-lg disabled"><i class="icon-thumbs-up"></i> +1 <?php echo JText::_('MOD_IMCFILTERS_VOTE'); ?></button>
		    	<span class="btn btn-success btn-large btn-lg disabled"><?php echo ModImcfiltersHelper::getVotes($id); ?></span>
		    	</div>
		    </div>
		<?php endif; ?>

	</div>

	<!-- Modal -->
	<div id="filtersModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="filtersModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h3 id="filtersModalLabel"><?php echo JText::_('MOD_IMCFILTERS_FILTERS'); ?></h3>
				</div>
				<div class="modal-body">
					<p id="filtersBody">
						<?php $category_filters = ModImcfiltersHelper::getCategoryFilters();?>
						<?php foreach ($category_filters as $filter) : ?>
							<?php echo $filter; ?>
						<?php endforeach; ?>
					</p>
				</div>
				<div class="modal-footer">
					<button id="cancel_filters" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JCANCEL');?></button>
					<button type="submit" id="apply_filters" class="btn btn-success"><?php echo JText::_('MOD_IMCFILTERS_APPLY');?></button>
				</div>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<input type="hidden" name="limit" value="<?php echo $state->get('list.limit');?>" /> 
	<input type="hidden" name="limitstart" value="<?php echo $state->get('list.start');?>" />
	<?php echo JHtml::_('form.token'); ?>


</form>
</div>