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
JHtml::_('behavior.formvalidation');
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
    <div class="form-horizontal">
        <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_IMC_TITLE_ISSUE', true)); ?>
        <div class="row-fluid">
            <div class="span6 form-horizontal">
                <fieldset class="adminform">
		            <div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
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
            <div class="span6 form-horizontal">
                <fieldset class="adminform">
					<div class="control-group">
						
						<div class="controls"><?php echo $this->form->getInput('address'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('latitude'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('latitude'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('longitude'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('longitude'); ?></div>
					</div>
					<div class="control-group">
						<div class="controls">
							<legend>Progress</legend>
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Date</th>
										<th>Action</th>
										<th>By</th>
										<th>Step</th>
										<th>Description</th>
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
					</div>
                </fieldset>	
			</div>

        </div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>
        <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
            <div class="span6 form-horizontal">
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
            <div class="span6 form-horizontal">
                <fieldset class="adminform">
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('created'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
					</div>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('updated'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('updated'); ?></div>
					</div>
                </fieldset>	
			</div>	
		<?php echo JHtml::_('bootstrap.endTab'); ?>

        <?php if (JFactory::getUser()->authorise('core.admin','imc')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>

        <?php echo JHtml::_('bootstrap.endTabSet'); ?>

		<?php //echo $this->form->getInput('is_step_modified'); ?>
		<?php //echo $this->form->getInput('step_modified_description'); ?>
		<?php echo $this->form->getInput('is_category_modified'); ?>
		<?php echo $this->form->getInput('category_modified_description'); ?>


        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>

    </div>
</form>