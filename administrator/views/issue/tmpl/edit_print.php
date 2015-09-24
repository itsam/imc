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
<style type="text/css">
	
	@media screen {
		#modal-imc-print .modal-body {
			overflow-y: auto;
		}
	}

	@media all {
		table {width: 100%;}
		.page-break	{ display: none; }
		table, th, td, thead, tr {
		  border: 1px solid black;
		}
		table {
		  border-collapse: collapse;
		}
		th {
		  height: 50px;
		}
	}

	@media print {
	  body {margin: 0;padding:0;}
	  body * {
	    visibility: hidden;
	    height: 0;
	  }
	  #section-to-print, #section-to-print * {
	    visibility: visible;
	    height: auto;
	    
	  }
	  #section-to-print {
	    position: absolute;
	    left: 0;
	    top: 0;
	  }

	  table {
	    width: 99%;
	    margin: 0 auto;
	  }
	  div.screen {display: none; height: 0;padding:0;}
	  table {
	    font-size: 11px;
	  }  
	  .page-break	{ display: block; page-break-before: always; }

	  .navbar, .subhead, .header {display: none; height: 0;}
	  td {padding: 2px;}
	  @page { margin: 0.2cm;}
	  #modal-imc-print{width: 100%; height: 100%;}
	  #modal-imc-print .modal-body {overflow: visible; width: 100%; height: 100%;}
	  #modal-imc-print h4 {margin-top: -30px;}
	}

</style>

<div class="modal hide fade" id="modal-imc-print">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&#215;</button>
		<h3><?php echo JText::_('COM_IMC_PRINT'); ?></h3>
	</div>
	<div id="section-to-print" class="modal-body">
		
		<h4><?php echo $this->item->title; ?></h4>
		<div class="alert alert-info">
		<p><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY'); ?>: 
		<?php 
		foreach ($this->item->creatorDetails as $details) {
			echo $details . ' / ';
		}
		?><br />
		<strong><?php echo $this->item->created; ?></strong></p>
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
		<button class="btn btn-primary" type="submit" onclick="javascript:window.print();">
			<?php echo JText::_('COM_IMC_PRINT'); ?>
		</button>
	</div>
</div>
