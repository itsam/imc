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

//Load admin language file
$lang = JFactory::getLanguage();
$lang->load('com_imc', JPATH_ADMINISTRATOR);
$canEdit = JFactory::getUser()->authorise('core.edit', 'com_imc.' . $this->item->id);
if (!$canEdit && JFactory::getUser()->authorise('core.edit.own', 'com_imc' . $this->item->id)) {
	$canEdit = JFactory::getUser()->id == $this->item->created_by;
}
?>
<?php if ($this->item) : ?>

    <div class="item_fields">
        <table class="table">
            <tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_ID'); ?></th>
			<td><?php echo $this->item->id; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_ISSUEID'); ?></th>
			<td><?php echo $this->item->issueid; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_PARENTID'); ?></th>
			<td><?php echo $this->item->parentid; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_DESCRIPTION'); ?></th>
			<td><?php echo $this->item->description; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_PHOTO'); ?></th>
			<td><?php echo $this->item->photo; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_CREATED'); ?></th>
			<td><?php echo $this->item->created; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_UPDATED'); ?></th>
			<td><?php echo $this->item->updated; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_STATE'); ?></th>
			<td>
			<i class="icon-<?php echo ($this->item->state == 1) ? 'publish' : 'unpublish'; ?>"></i></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_CREATED_BY'); ?></th>
			<td><?php echo $this->item->created_by_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_COMMENT_LANGUAGE'); ?></th>
			<td><?php echo $this->item->language; ?></td>
</tr>

        </table>
    </div>
    <?php if($canEdit && $this->item->checked_out == 0): ?>
		<a class="btn" href="<?php echo JRoute::_('index.php?option=com_imc&task=comment.edit&id='.$this->item->id); ?>"><?php echo JText::_("COM_IMC_EDIT_ITEM"); ?></a>
	<?php endif; ?>
								<?php if(JFactory::getUser()->authorise('core.delete','com_imc.comment.'.$this->item->id)):?>
									<a class="btn" href="<?php echo JRoute::_('index.php?option=com_imc&task=comment.remove&id=' . $this->item->id, false, 2); ?>"><?php echo JText::_("COM_IMC_DELETE_ITEM"); ?></a>
								<?php endif; ?>
    <?php
else:
    echo JText::_('COM_IMC_ITEM_NOT_LOADED');
endif;
?>
