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
JHtml::_('behavior.tooltip');
JHtml::_('behavior.modal');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_imc/assets/css/imc.css');
?>
<script type="text/javascript">
    /*
    js = jQuery.noConflict();
    js(document).ready(function() {
        
	js('input:hidden.stepid').each(function(){
		var name = js(this).attr('name');
		if(name.indexOf('stepidhidden')){
			js('#jform_stepid option[value="'+js(this).val()+'"]').attr('selected',true);
		}
	});
	js("#jform_stepid").trigger("liszt:updated");
    });
    */
    Joomla.submitbutton = function(task)
    {
        if (task == 'issue.cancel') {
            Joomla.submitform(task, document.getElementById('issue-form'));
        }
        else {
            
            if (task != 'issue.cancel' && document.formvalidator.isValid(document.id('issue-form'))) {
                
                Joomla.submitform(task, document.getElementById('issue-form'));
            }
            else {
                alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
            }
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_imc&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="issue-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
    <div class="form-vertical">
        <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_IMC_TITLE_ISSUE', true)); ?>
        <div class="row-fluid">
            <div class="span6">
                <fieldset class="adminform">
	                <div class="control-group">
	                	<div class="control-label"><?php echo $this->form->getLabel('moderation'); ?></div>
	                	<div class="controls"><?php echo $this->form->getInput('moderation'); ?></div>
	                </div>                 
		            <div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
					</div>
					<div class="accordion" id="accordion2">
					  <div class="accordion-group">
					    <div class="accordion-heading">
					      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
					        <i class="icon-download"></i> <?php echo JText::_('COM_IMC_FORM_DESC_ISSUE_REGNUM');?>
					      </a>
					    </div>
					    <div id="collapseOne" class="accordion-body collapse">
					      <div class="accordion-inner">
					        
					        <div class="alert alert-warning" style="width:80%;">
					        	<div class="control-group">
					        		<div class="control-label"><?php echo $this->form->getLabel('regnum'); ?></div>
					        		<div class="controls"><?php echo $this->form->getInput('regnum'); ?></div>
					        	</div>
					        	<div class="control-group">
					        		<div class="control-label"><?php echo $this->form->getLabel('regdate'); ?></div>
					        		<div class="controls"><?php echo $this->form->getInput('regdate'); ?></div>
					        	</div>
					        	<div class="control-group">
					        		<div class="control-label"><?php echo $this->form->getLabel('responsible'); ?></div>
					        		<div class="controls"><?php echo $this->form->getInput('responsible'); ?></div>
					        	</div>
					        	<div class="control-group">
					        		<div class="control-label"><?php echo $this->form->getLabel('subgroup'); ?></div>
					        		<div class="controls"><?php echo $this->form->getInput('subgroup'); ?></div>
					        	</div>
					        </div>
					      </div>
					    </div>
					  </div>
					  
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('stepid'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('stepid'); ?></div>
					</div>

					<?php
						foreach((array)$this->item->stepid as $value): 
							if(!is_array($value)):
								echo '<input type="hidden" class="stepid" name="jform[stepidhidden]['.$value.']" value="'.$value.'" />';
							endif;
						endforeach;
					?>			
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('catid'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('catid'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('photo'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('photo'); ?></div>
					</div>
                </fieldset>
            </div>
            <div class="span6">
                <fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('address'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('address'); ?></div>
						<?php echo $this->form->getInput('latitude'); ?>
						<?php echo $this->form->getInput('longitude'); ?>
					</div>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('extra'); ?></div>
						<div class="controls">
							<div class="alert alert-warning">
								<p><strong><?php echo JText::_('COM_IMC_USER_DETAILS');?>:</strong></p>
								<?php 
								foreach ($this->item->creatorDetails as $key => $value) {
									echo $key.':'.$value . '<br />';
								}
								?>
							<?php echo $this->form->getInput('extra'); ?>
							</div>
						</div>
					</div>
                </fieldset>	
			</div>
        </div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>
        
        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'logging', JText::_('COM_IMC_TITLE_LOGS', true)); ?>
        	<div class="span12">
	        	<table class="table table-striped">
	        		<thead>
	        			<tr>
	        				<th><?php echo JText::_("JDATE");?></th>
	        				<th><?php echo JText::_("COM_IMC_FORM_LBL_LOG_ACTION");?></th>
	        				<th><?php echo JText::_("COM_IMC_LOGS_CREATED_BY");?></th>
	        				<th><?php echo JText::_("COM_IMC_TITLE_STEP");?></th>
	        				<th><?php echo JText::_("COM_IMC_LOGS_DESCRIPTION");?></th>
	        				</tr>
	        		</thead>
	        		<tbody>
	        			<?php foreach ($this->logs as $log) : ?>
	        			<tr>
	        				<td><?php echo $log['created']; ?></td>
	        				<td><?php echo $log['action']; ?></td>
	        				<td><?php echo $log['created_by']; ?></td>
	        				<td>
	        					<span style="font-size: 20px;color: <?php echo $log['stepid_color']; ?>">&marker;</span>
	        					<?php echo $log['stepid_title']; ?>
	        				</td>
	        				<td><?php echo $log['description']; ?></td>
	        			</tr>
	        			<?php endforeach; ?>
	        		</tbody>
	        	</table>

        	</div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>

        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
            <div class="span6">
                <fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('access'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('access'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('language'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('language'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('hits'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('hits'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('votes'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('votes'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('modality'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('modality'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('note'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('note'); ?></div>
					</div>
                </fieldset>	
			</div>		
            <div class="span6">
                <fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created'); ?></div>
						<div class="controls"><strong><?php echo $this->form->getInput('created'); ?></strong></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
						<div class="controls"><strong><?php echo $this->form->getInput('created_by'); ?></strong></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('updated'); ?></div>
						<div class="controls"><strong><?php echo $this->form->getInput('updated'); ?></strong></div>
					</div>

					<?php if(!empty($this->item->notification_emails)) : ?>
						<div class="alert alert-info">
							<p><strong><?php echo JText::_('COM_IMC_ADMIN_NOTIFICATIONS_RECEIVED_BY');?>:</strong></p>
							<?php 
								foreach ($this->item->notification_emails as $email) {
									echo $email.'<br />';
								}
							?>
						</div>
					<?php endif; ?>
                </fieldset>	
			</div>	
		<?php echo JHtml::_('bootstrap.endTab'); ?>

        <?php if (JFactory::getUser()->authorise('core.admin','imc')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>

        <?php echo JHtml::_('bootstrap.endTabSet'); ?>

        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>

    </div>
</form>
<?php echo $this->loadTemplate('print'); ?>
