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
$steps = $app->getUserStateFromRequest('com_imc.issues.filter.steps', 'steps', array()); 

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
				<?php if($params->get('show_poweredby') == 1) : ?>
					<?php $powered_by = JURI::base() . '/modules/mod_imcfilters/assets/images/powered_by_imc.png'; ?>
					<a href="http://www.improve-my-city.com" target="_blank"><img src="<?php echo $powered_by; ?>" title="http://www.improve-my-city.com" alt="Powered by Improve My City" /></a>
				<?php endif; ?>

				<a id="search_btn" href="#IMC_advancedSearchModal" role="button" class="btn btn-primary" data-toggle="modal"><i class="icon-search"></i> <?php echo JText::_('MOD_IMCFILTERS_SEARCH'); ?></a>
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
				<?php if($params->get('show_help') == 1) : ?>
					<a id="help_btn" href="<?php echo $params->get('help_link'); ?>" role="button" class="btn btn-default"><i class="icon-help"></i> <?php echo JText::_('MOD_IMCFILTERS_HELP'); ?></a>
				<?php endif; ?>
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
			<div class="imc_btn_right">
				<?php if($params->get('app_store_link') != '') : ?>
					<?php $appStore = JURI::base() . '/modules/mod_imcfilters/assets/images/Download_on_the_App_Store_Badge_US-UK_135x40.png'; ?>
					<a href="<?php echo $params->get('app_store_link'); ?>" target="_blank"><img src="<?php echo $appStore; ?>" title="<?php echo JText::_('MOD_IMCFILTERS_DOWNLOAD_APPSTORE'); ?>" alt="AppStore badge" /></a>
				<?php endif; ?>
				<?php if($params->get('google_play_link') != '') : ?>
					<?php $googlePlay = JURI::base() . '/modules/mod_imcfilters/assets/images/google-play-badge.png'; ?>
					<a href="<?php echo $params->get('google_play_link'); ?>" target="_blank"><img src="<?php echo $googlePlay; ?>" title="<?php echo JText::_('MOD_IMCFILTERS_DOWNLOAD_GOOGLEPLAY'); ?>" alt="Google Play badge" /></a>
				<?php endif; ?>
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
	<div id="IMC_advancedSearchModal" class="modal modal-wide fade" tabindex="-1" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<h3 id="searchModalLabel"><?php echo JText::_('MOD_IMCFILTERS_SEARCH'); ?></h3>
				</div>
				<div class="modal-body">
					<div class="btn-group">
					  <input id="filter_search" placeholder="<?php echo JText::_('MOD_IMCFILTERS_SEARCH_PLACEHOLDER');?>" name="filter_search" type="search" class="form-control" value="<?php echo $search; ?>">
					  <span id="searchclear" class="glyphicon glyphicon-remove-circle"></span>
					</div>
					<?php if (JFactory::getUser()->id > 0) : ?>
						<br /><br />
						<input type="hidden" id="filter_owned_hidden" name="filter_owned" value="no" />
					    <label class="checkbox inline">
							<input type="checkbox" id="filter_owned" name="filter_owned" value="yes" <?php echo ($owned == 'yes' ? 'checked="checked"' : ''); ?> > <?php echo JText::_('MOD_IMCFILTERS_SHOW_MINE');?>
						</label>
					<?php endif; ?>
					<hr />
					
					<h4>
						<input type="checkbox" checked="checked" id="selectAllCategories">
						<?php echo JText::_('MOD_IMCFILTERS_CATEGORIES');?>
					</h4>
					<?php $category_filters = ModImcfiltersHelper::getCategoryFilters(); ?>
					<div class="container-fluid">
					  <div class="row">
							<?php foreach ($category_filters as $filter) : ?>
						  		<div class="col-md-4">
									<?php echo $filter; ?>
						  		</div>
							<?php endforeach; ?>
					  </div>
					</div>
					<hr />

					<h4>
						<input type="checkbox" checked="checked" id="selectAllSteps">
						<?php echo JText::_('MOD_IMCFILTERS_ISSUE_STATUSES');?>
					</h4>
					<div class="container-fluid">
						<div class="row">
						  	<div class="col-md-12">
							<?php 
								$statuses = ModImcfiltersHelper::createStatuses();
								echo $statuses;
							?>
							</div>
						</div>
					</div>					
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