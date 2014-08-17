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
$user = JFactory::getUser();
$canEdit = $user->authorise('core.edit', 'com_imc.issue.' . $this->item->id);
$canChange = $user->authorise('core.edit.state', 'com_imc.issue.' . $this->item->id);
$canEditOwn = $user->authorise('core.edit.own', 'com_imc.issue.' . $this->item->id);

if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.' . $this->item->id)) {
	$canEdit = $user->id == $this->item->created_by;
}
?>


<?php if ($this->item && ($this->item->state == 1 || $canEditOwn ) ) : ?>

    <div class="item_fields">
        <table class="table">
            <tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_ID'); ?></th>
			<td><?php echo $this->item->id; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_TITLE'); ?></th>
			<td><?php echo $this->item->title; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_STEPID'); ?></th>
			<td><?php echo $this->item->stepid; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CATID'); ?></th>
			<td><?php echo $this->item->catid_title; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_DESCRIPTION'); ?></th>
			<td><?php echo $this->item->description; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_ADDRESS'); ?></th>
			<td><?php echo $this->item->address; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_LATITUDE'); ?></th>
			<td><?php echo $this->item->latitude; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_LONGITUDE'); ?></th>
			<td><?php echo $this->item->longitude; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_PHOTO'); ?></th>
			<td><?php echo $this->item->photo; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_STATE'); ?></th>
			<td>
			<i class="icon-<?php echo ($this->item->state == 1) ? 'publish' : 'unpublish'; ?>"></i></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED'); ?></th>
			<td><?php echo $this->item->created; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_UPDATED'); ?></th>
			<td><?php echo $this->item->updated; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY'); ?></th>
			<td><?php echo $this->item->created_by_name; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_LANGUAGE'); ?></th>
			<td><?php echo $this->item->language; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_HITS'); ?></th>
			<td><?php echo $this->item->hits; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_NOTE'); ?></th>
			<td><?php echo $this->item->note; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_VOTES'); ?></th>
			<td><?php echo $this->item->votes; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_MODALITY'); ?></th>
			<td><?php echo $this->item->modality; ?></td>
			</tr>
        </table>
        
        
    </div>
    <?php if($canEdit && $this->item->checked_out == 0): ?>
		<a class="btn" href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.$this->item->id); ?>"><?php echo JText::_("COM_IMC_EDIT_ITEM"); ?></a>
	<?php endif; ?>
	<?php if(JFactory::getUser()->authorise('core.delete','com_imc.issue.'.$this->item->id)):?>
		<a class="btn" href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.remove&id=' . $this->item->id, false, 2); ?>"><?php echo JText::_("COM_IMC_DELETE_ITEM"); ?></a>
	<?php endif; ?>

<?php else: ?>
	    <?php echo JText::_('COM_IMC_ITEM_NOT_LOADED'); ?>
<?php endif; ?>