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
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';
//print_r($this->item);
//print_r($this->logs);
?>
<script type="text/javascript">
	function isEmail(email) {
		var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		return regex.test(email);
	}

	jQuery(document).ready(function() {
		jQuery("#mail_btn").click(function(){
			var recipient = jQuery('#recipient').val();
			var content = jQuery('textarea#content').val();
			if(isEmail(recipient))
			{
				window.location="<?php echo 'index.php?option=com_imc&task=issue.mail&id='.$this->item->id.'&recipient='; ?>"+recipient+'&content='+content;
			}
			else
			{
				alert('<?php echo JText::_('COM_IMC_INVALID_MAIL');?>')
			}

		});
	});
</script>

<div class="modal hide fade" id="modal-imc-mail">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&#215;</button>
		<h3><?php echo JText::_('COM_IMC_MAIL'); ?></h3>
		<?php echo JText::_('COM_IMC_TO'); ?>: <input type="text" name="recipient" id="recipient" />
		<?php echo JText::_('COM_IMC_SETTINGS_COMMENTS_LABEL'); ?>: <textarea name="content" id="content"></textarea>
	</div>
	<div class="modal-body">
		
		<h4><?php echo $this->item->title; ?></h4>
		<div class="alert alert-info">
		<p><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY'); ?>: 
		<?php 
		foreach ($this->item->creatorDetails as $details) {
			echo $details . ' / ';
		}
		?><br />
		<strong><?php echo ImcFrontendHelper::convertFromUTC($this->item->created); ?></strong></p>
		</div>

		<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_ID'); ?></strong>: 
		<?php echo $this->item->id; ?>
		</p>
		
		<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_STEPID'); ?></strong>: 
		<?php $step = ImcFrontendHelper::getStepByStepId($this->item->stepid); ?>
		<span style="color: <?php echo $step['stepid_color']?>"><?php echo $step['stepid_title']?></span>
		</p>

		<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CATID'); ?></strong>: 
		<?php echo ImcFrontendHelper::getCategoryNameByCategoryId($this->item->catid); ?></p>
		
		<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_ADDRESS'); ?></strong>: 
		<?php echo $this->item->address; ?></p>
		
		<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_DESCRIPTION'); ?></strong>: 
		<?php echo $this->item->description; ?></p>

		<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_EXTRA'); ?></strong>:
		<?php echo $this->item->extra; ?></p>

	</div>
	<div class="modal-footer">
		<button class="btn" type="button" data-dismiss="modal">
			<?php echo JText::_('JCANCEL'); ?>
		</button>
		<button id="mail_btn" class="btn btn-primary">
			<?php echo JText::_('COM_IMC_MAIL'); ?>
		</button>
	</div>
</div>
