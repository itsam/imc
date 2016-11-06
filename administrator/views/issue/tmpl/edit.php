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

///JHtml::_('jquery.framework');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_imc/assets/css/imc.css');
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';

//$document->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js');
$document->addScript(JURI::root(true).'/components/com_imc/assets/js/jquery-comments.min.js');
$document->addStyleSheet(JURI::root(true).'/components/com_imc/assets/css/jquery-comments.css');

$user = JFactory::getUser();
$canDo = ImcHelper::getActions();
$canManageModeration = $canDo->get('imc.manage.moderation');
$canManageComments = $canDo->get('imc.manage.comments');
$canCreate = true;
?>

<script type="text/javascript">

	function setProfile(userid, key, value, token) {
		jQuery.ajax({
			'async': true,
			'global': false,
			'url': "index.php?option=com_imc&task=users.setprofile&format=json&userid=" + userid + "&key=" + key + "&value=" + value + "&" + token + "=1",
			'dataType': "json",
			'success': function (data) {
				var json = data;

			},
			'error': function (error) {
				alert('Set profile failed - See console for more information');
				console.log(error);
			}
		});
	}
	function getProfile(userid, key, token) {
		jQuery.ajax({
			'async': true,
			'global': false,
			'url': "index.php?option=com_imc&task=users.getProfile&format=json&userid=" + userid + "&key=" + key + "&" + token + "=1",
			'dataType': "json",
			'success': function (data) {
				var json = data;
				json.data = (json.data == 1 ? true : false);
				jQuery('input[name="jform[is_citizen]"]').prop('checked', json.data);
			},
			'error': function (error) {
				alert('Get profile failed - See console for more information');
				console.log(error);
			}
		});
	}
    js = jQuery.noConflict();

    js(document).ready(function() {

		var token = '<?php echo JSession::getFormToken();?>';
		var userid = '<?php echo $this->item->created_by;?>';

		getProfile(userid, 'imcprofile.is_citizen', token);

		var init_moderation = js('input[name="jform[moderation]"]:checked').val();
	    js('input[name="jform[moderation]"]').change(function () {
		    if(this.value == init_moderation)
		    {
			    js('#jform_is_moderation_modified').val(false);
		    }
		    else
		    {
			    js('#jform_is_moderation_modified').val(true);
		    }
	    });

    });

	function profile_change()
	{
		var token = '<?php echo JSession::getFormToken();?>';
		var userid = '<?php echo $this->item->created_by;?>';
		var sel = jQuery('input[name="jform[is_citizen]"]:checked').val();
		sel = (sel ? 1 : 0);

		setProfile(userid, 'imcprofile.is_citizen', sel, token);
	}

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
					<?php if($canManageModeration) : ?>
	                <div class="control-group">
	                	<div class="control-label"><?php echo $this->form->getLabel('moderation'); ?></div>
	                	<div class="controls"><?php echo $this->form->getInput('moderation'); ?></div>
		                <div class="control-label"><?php echo $this->form->getLabel('is_moderation_modified'); ?></div>
		                <div class="controls"><?php echo $this->form->getInput('is_moderation_modified'); ?></div>
	                </div>
					<?php else: ?>
						<div class="alert alert-info"><?php echo JText::_('COM_IMC_MODERATION_INFO_ALERT'); ?></div>
					<?php endif ?>
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
								<p style="height: 50px;"></p>
								<p><strong><?php echo JText::_('COM_IMC_USER_DETAILS');?>:</strong></p>
								<?php 
								foreach ($this->item->creatorDetails as $key => $value) {
									echo $key.': <strong>'.$value . '</strong><br />';
								}
								// get default profile
								if(isset($this->item->userProfile->profile) ) {
									foreach ($this->item->userProfile->profile as $key => $value) {
										echo $key.': <strong>'.$value . '</strong><br />';
									}
								}
								// get imc profile
								if(isset($this->item->userProfile->imcprofile) ) {
									foreach ($this->item->userProfile->imcprofile as $key => $value)
									{
										if($key != 'is_citizen')
										{
											echo $key.': <strong>'.$value . '</strong><br />';
										}
									}
								}

								?>
								<br />
								<?php echo $this->form->getLabel('extra'); ?>
								<?php echo $this->form->getInput('extra'); ?>

								<?php
								// show is_citizen
								if(isset($this->item->userProfile->imcprofile) ) {
									echo $this->form->getLabel('is_citizen');
									echo $this->form->getInput('is_citizen');
								}
								?>
							</div>
						</div>
					</div>
                </fieldset>	
			</div>
        </div>
        <?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php if ($canManageComments) : ?>
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'comments', JText::_('COM_IMC_TITLE_COMMENTS', true)); ?>

			<script type="text/javascript">
				js = jQuery.noConflict();
				js(document).ready(function() {

					var token = '<?php echo JSession::getFormToken();?>';
					var issueid = '<?php echo $this->item->id;?>';
					var userid = '<?php echo $user->id;?>';
					var picURL = '<?php echo JURI::root().'components/com_imc/assets/images/user-icon.png';?>';
					<?php if(ImcHelper::getActions()->get('imc.manage.comments')) :?>
					picURL = '<?php echo JURI::root().'components/com_imc/assets/images/admin-user-icon.png';?>';
					<?php endif; ?>
					js('#comments-container').comments({
						profilePictureURL: picURL,
						spinnerIconURL: '<?php echo JURI::root().'components/com_imc/assets/images/spinner.gif';?>',
						upvoteIconURL: '<?php echo JURI::root().'components/com_imc/assets/images/upvote-icon.png';?>',
						replyIconURL: '<?php echo JURI::root().'components/com_imc/assets/images/reply-icon.png';?>',
						noCommentsIconURL: '<?php echo JURI::root().'components/com_imc/assets/images/no-comments-icon.png';?>',
						textareaPlaceholderText: '<?php echo JText::_('COM_IMC_COMMENTS_LEAVE_COMMENT');?>',
						popularText: '<?php echo JText::_('COM_IMC_COMMENTS_MOST_POPULAR');?>',
						newestText: '<?php echo JText::_('COM_IMC_COMMENTS_NEWEST');?>',
						oldestText: '<?php echo JText::_('COM_IMC_COMMENTS_OLDEST');?>',
						sendText: '<?php echo JText::_('COM_IMC_COMMENTS_SEND');?>',
						replyText: '<?php echo JText::_('COM_IMC_COMMENTS_REPLY');?>',
						editText: '<?php echo JText::_('COM_IMC_COMMENTS_EDIT');?>',
						saveText: '<?php echo JText::_('COM_IMC_COMMENTS_SAVE');?>',
						deleteText: '<?php echo JText::_('COM_IMC_COMMENTS_DELETE');?>',
						editedText: '<?php echo JText::_('COM_IMC_COMMENTS_EDITED');?>',
						youText: '<?php echo JText::_('COM_IMC_COMMENTS_YOU');?>',
						viewAllRepliesText: '<?php echo JText::_('COM_IMC_COMMENTS_VIEW_ALL_REPLIES');?> (__replyCount__)',
						hideRepliesText: '<?php echo JText::_('COM_IMC_COMMENTS_HIDE');?>',
						noCommentsText: '<?php echo JText::_('COM_IMC_COMMENTS_NO_COMMENTS');?>',
						enableReplying: true,
						enableEditing: false,
						enableUpvoting: false,
						enableDeleting: false,
						enableDeletingCommentWithReplies: false,
						timeFormatter: function(time) {
							return new Date(time).toLocaleString();
							//return time;
						},
						fieldMappings: {
							id: 'id',
							parent: 'parentid',
							created: 'created',
							modified: 'updated',
							content: 'description',
							fullname: 'fullname',
							profilePictureURL: 'profile_picture_url',
							createdByAdmin: 'created_by_admin',
							createdByCurrentUser: 'created_by_current_user',
							upvoteCount: 'upvote_count',
							userHasUpvoted: 'user_has_upvoted'
						},
						getComments: function(success, error) {
							js.ajax({
								type: 'get',
								'url': "<?php echo JURI::root()?>administrator/index.php?option=com_imc&task=comments.comments&format=json&userid="+userid+"&issueid=" + issueid + "&" + token + "=1",
								success: function(commentsArray) {
									success(commentsArray.data)
								},
								error: error
							});
						}
						<?php if (!$canCreate) : ?>
						,
						refresh: function() {
							js('div.commenting-field').hide();

						},
						enableReplying: false
						<?php endif; ?>

						<?php if($canCreate) : ?>
						,
						postComment: function(commentJSON, success, error) {
							//console.log(commentJSON);
							js.ajax({
								type: 'post',
								'url': "<?php echo JURI::root()?>administrator/index.php?option=com_imc&task=comments.postComment&format=json&userid="+userid+"&issueid=" + issueid + "&" + token + "=1",
								data: commentJSON,
								success: function(comment) {
									success(comment.data);
								},
								error: error

							});
						}
						<?php endif; ?>
					});


				});


			</script>


			<div class="span12">
				<div id="comments-container"></div>
			</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif ?>

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

	        				<td><?php echo ImcFrontendHelper::convertFromUTC($log['created']); ?></td>
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
<?php echo $this->loadTemplate('mail'); ?>
