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

<form action="<?php echo JRoute::_('index.php?option=com_imc&view=issues'); ?>" method="post" name="adminForm" id="adminForm">


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

                    <?php 
                        $obj = json_decode($item->photo); 
                        foreach ($obj->files as $file) {
                            echo '<img src="http://localhost/joomla3/'. $file->thumbnailUrl . '" />';
                        }
                    ?>                

					<p><?php echo $item->stepid_title; ?></p>
					<p><?php echo $item->catid_title; ?></p>

					<?php echo JFactory::getUser($item->created_by)->name; ?>

					

                <?php if ($canEdit || $canDelete): ?>
						<?php if ($canEdit): ?>
							<a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
						<?php endif; ?>
						<?php if ($canDelete): ?>
							<button data-item-id="<?php echo $item->id; ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></button>
						<?php endif; ?>
				<?php endif; ?>

            <?php endforeach; ?>

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


