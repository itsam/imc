<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');

$canEdit = $user->authorise('core.edit', 'com_imc');
$canDelete = $user->authorise('core.delete', 'com_imc');
?>
I AM TABULAR .PHP
<form action="<?php echo JRoute::_('index.php?option=com_imc&view=issues'); ?>" method="post" name="adminForm" id="adminForm">

    <table class="table table-striped" id="issueList">
        <thead>
            <tr>
                <?php if (isset($this->items[0]->state)): ?>
                    <th width="1%" class="nowrap center">
                        <?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                    </th>
                <?php endif; ?>

                <th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_TITLE', 'a.title', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_STEPID', 'a.stepid', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_CATID', 'a.catid', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('grid.sort',  'COM_IMC_ISSUES_CREATED', 'a.created', $listDirn, $listOrder); ?>
				</th>
                    

                <?php if (isset($this->items[0]->id)): ?>
                    <th width="1%" class="nowrap center hidden-phone">
                        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                    </th>
                <?php endif; ?>

                <?php if ($canEdit || $canDelete): ?>
					<th class="center">
				    <?php echo JText::_('COM_IMC_ISSUES_ACTIONS'); ?>
				</th>
				<?php endif; ?>

            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                    <?php echo $this->pagination->getListFooter(); ?>
                    <?php //echo $this->pagination->getLimitBox(); ?>
                </td>
            </tr>
        </tfoot>
        <tbody>

            <?php foreach ($this->items as $i => $item) : ?>

                <?php 
                $canCreate = $user->authorise('core.create', 'com_imc.issue.'.$item->id);
                $canEdit = $user->authorise('core.edit', 'com_imc.issue.'.$item->id);
                $canCheckin = $user->authorise('core.manage', 'com_imc.issue.'.$item->id);
                $canChange = $user->authorise('core.edit.state', 'com_imc.issue.'.$item->id);
                $canDelete = $user->authorise('core.delete', 'com_imc.issue.'.$item->id);
                $canEditOwn = $user->authorise('core.edit.own', 'com_imc.issue.' . $item->id);
                ?>


                <?php if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.'.$item->id)): ?>
                    <?php $canEdit = JFactory::getUser()->id == $item->created_by; ?>
				<?php endif; ?>

                <tr class="row<?php echo $i % 2; ?>">

                    <?php if (isset($this->items[0]->state)): ?>
                        <?php $class = ($canChange) ? 'active' : 'disabled'; ?>
                        <td class="center">
                            <a class="btn btn-micro <?php echo $class; ?>" href="<?php echo ($canChange) ? JRoute::_('index.php?option=com_imc&task=issue.publish&id=' . $item->id . '&state=' . (($item->state + 1) % 2), false, 2) : '#'; ?>">
                                <?php if ($item->state == 1): ?>
                                    <i class="icon-ok"></i>
                                <?php else: ?>
                                    <i class="icon-remove"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                    <?php endif; ?>

                <td>
				<?php if (isset($item->checked_out) && $item->checked_out) : ?>
					<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'issues.', $canCheckin); ?>
				<?php endif; ?>
				<?php if ($canEdit) : ?>
					<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.(int) $item->id); ?>">
					<?php echo $this->escape($item->title); ?></a>
				<?php else : ?>
					<?php echo $this->escape($item->title); ?>
				<?php endif; ?>
                <a href="<?php echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id); ?>">view details</a>
				</td>
				<td>

					<?php echo $item->stepid_title; ?>
				</td>
				<td>

					<?php echo $item->catid_title; ?>
				</td>
				<td>

					<?php echo JFactory::getUser($item->created_by)->name; ?>
				<td>

					<?php echo $item->created; ?>
				</td>


                <?php if (isset($this->items[0]->id)): ?>
                    <td class="center hidden-phone">
                        <?php echo (int) $item->id; ?>
                    </td>
                <?php endif; ?>

                <?php if ($canEdit || $canDelete): ?>
					<td class="center">
						<?php if ($canEdit): ?>
							<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
						<?php endif; ?>
						<?php if ($canDelete): ?>
							<button data-item-id="<?php echo $item->id; ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></button>
						<?php endif; ?>
					</td>
				<?php endif; ?>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php /*
    <?php $canCreate = $user->authorise('core.create', 'com_imc'); ?>
    <?php if ($canCreate): ?>
        <a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=0', false, 2); ?>" class="btn btn-success btn-small"><i class="icon-plus"></i> <?php echo JText::_('COM_IMC_ADD_ITEM'); ?></a>
    <?php endif; ?>
    */ ?>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    
    <?php         
        $app = JFactory::getApplication();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));  
        $limitstart = JFactory::getApplication()->input->getInt('limitstart', 0);      
    ?>
    <input type="hidden" name="limit" value="<?php echo $limit;?>" /> 
    <input type="hidden" name="limitstart" value="<?php echo $limitstart;?>" />

    <?php echo JHtml::_('form.token'); ?>
</form>

<script type="text/javascript">

    jQuery(document).ready(function() {
        jQuery('.delete-button').click(deleteItem);
    });

    function deleteItem() {
        var item_id = jQuery(this).attr('data-item-id');
        if (confirm("<?php echo JText::_('COM_IMC_DELETE_MESSAGE'); ?>")) {
            window.location.href = '<?php echo JRoute::_('index.php?option=com_imc&task=issue.remove&id=', false, 2) ?>' + item_id;
        }
    }
</script>


