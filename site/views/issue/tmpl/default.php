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
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/imc.php';

// Load admin language file
$lang = JFactory::getLanguage();
$lang->load('com_imc', JPATH_ADMINISTRATOR);
$user = JFactory::getUser();
$canCreate = $user->authorise('core.create', 'com_imc');
$canEdit = $user->authorise('core.edit', 'com_imc.issue.' . $this->item->id);
$canChange = $user->authorise('core.edit.state', 'com_imc.issue.' . $this->item->id);
$canEditOwn = $user->authorise('core.edit.own', 'com_imc.issue.' . $this->item->id);

if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.' . $this->item->id)) {
	$canEdit = $user->id == $this->item->created_by;
}

// Edit Own only if issue status is the initial one
$firstStep = ImcFrontendHelper::getStepByStepId($this->item->stepid);
$canEditOnStatus = true;
if ($firstStep['ordering'] != 1){
	$canEditOnStatus = false;
}

// Issue statuses
JFormHelper::addFieldPath(JPATH_ROOT . '/components/com_imc/models/fields');
$step = JFormHelper::loadFieldType('Step', false);
$statuses = $step->getOptions();

?>

	<script type="text/javascript">
		js = jQuery.noConflict();
		js(document).ready(function() {
			js('#gallery').photobox('a', { thumbs:true, loop:false }, callback);
			// using setTimeout to make sure all images were in the DOM, before the history.load() function is looking them up to match the url hash
			setTimeout(window._photobox.history.load, 2000);
			function callback(){
				//console.log('callback for loaded content:', this);
			}

			js( "#timeline" ).click(function() {
				js('#cd-timeline').toggle();
				js('#cd-timeline')[0].scrollIntoView( true );
			});

			js('.delete-button').click(deleteItem);

			<?php if($this->showComments) : ?>
			var token = '<?php echo JSession::getFormToken();?>';
			var issueid = '<?php echo $this->item->id;?>';
			var userid = '<?php echo $user->id;?>';
			var picURL = '<?php echo JURI::base().'components/com_imc/assets/images/user-icon.png';?>';
			<?php if(ImcHelper::getActions()->get('imc.manage.comments')) :?>
			picURL = '<?php echo JURI::base().'components/com_imc/assets/images/admin-user-icon.png';?>';
			<?php endif; ?>
			js('#comments-container').comments({
				profilePictureURL: picURL,
				spinnerIconURL: '<?php echo JURI::base().'components/com_imc/assets/images/spinner.gif';?>',
				upvoteIconURL: '<?php echo JURI::base().'components/com_imc/assets/images/upvote-icon.png';?>',
				replyIconURL: '<?php echo JURI::base().'components/com_imc/assets/images/reply-icon.png';?>',
				noCommentsIconURL: '<?php echo JURI::base().'components/com_imc/assets/images/no-comments-icon.png';?>',
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
				defaultNavigationSortKey: 'newest',
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
						'url': "index.php?option=com_imc&task=comments.comments&format=json&userid="+userid+"&issueid=" + issueid + "&" + token + "=1",
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
						'url': "index.php?option=com_imc&task=comments.postComment&format=json&userid="+userid+"&issueid=" + issueid + "&" + token + "=1",
						data: commentJSON,
						success: function(comment) {
							success(comment.data);
						},
						error: error

					});
				}
				<?php endif; ?>
			});

			<?php endif; ?>
		});

		function deleteItem() {
			if (confirm("<?php echo JText::_('COM_IMC_DELETE_MESSAGE'); ?>")) {
				window.location.href = '<?php echo JRoute::_('index.php?option=com_imc&task=issue.remove&id=' . $this->item->id, false, 2); ?>'
			}
		}

	</script>

<?php
//make sure you are allowed to see the issue (in case of direct link)
if($this->item->moderation == 1 && !$canEdit) : ?>

	<div class="alert alert-danger">
		<?php echo JText::_('COM_IMC_ITEM_NOT_LOADED'); ?>
	</div>

	<?php return; endif; ?>

<?php if ($this->item && ($this->item->state == 1 || $canEditOwn ) ) : ?>
	<div class="container">
		<div class="row">

			<div class="imc-issue-title">
				<?php if($this->item->category_image != '') : ?>
					<img src="<?php echo $this->item->category_image; ?>" alt="<?php echo $this->item->catid_title;?>" title="<?php echo $this->item->catid_title;?>" />
				<?php endif; ?>
				<?php echo '#'. $this->item->id . '. ' .$this->item->title; ?>
				<?php if($canEdit && $this->item->checked_out == 0 && $canEditOnStatus): ?>
					<a class="btn btn-default btn-xs" href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.$this->item->id); ?>"><?php echo JText::_("COM_IMC_EDIT_ITEM"); ?></a>
				<?php endif; ?>
				<?php /*if(JFactory::getUser()->authorise('core.delete','com_imc.issue.'.$this->item->id)):?>
				<button class="btn btn-warning delete-button"><?php echo JText::_("COM_IMC_DELETE_ITEM"); ?></button>
			<?php endif; */?>
			</div>

			<div class="imc-statuses">
				<?php foreach ($statuses as $status) : ?>
					<?php if($status->value == $this->item->stepid) : ?>
						<span style="color: <?php echo $this->logs[count($this->logs)-1]['stepid_color'];?>">
		    				<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
							<?php echo $status->text; ?>
		    			</span>
					<?php else : ?>
						<?php echo $status->text; ?>
					<?php endif; ?>
					&nbsp;&nbsp;
				<?php endforeach; ?>
			</div>


			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 pull-left">
				<div class="imc-info-wrapper" style="margin-bottom: 24px;">

					<!-- TODO - create a new label called "Description" -->
					<div class="imc-issue-subtitle"><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_DESCRIPTION'); ?></div>

					<p>
						<strong><?php echo JText::_('COM_IMC_ISSUES_CATID'); ?>: </strong>
						<?php echo $this->item->catid_title; ?><br />

						<strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED'); ?>: </strong>
						<?php echo ImcFrontendHelper::getRelativeTime($this->item->created); ?><br />

						<?php if ($this->params->get('showuserdetailstimeline')) : ?>
							<strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY'); ?>: </strong>
							<?php echo $this->item->created_by_name; ?>
						<?php endif ?>

						<?php if ($this->item->regnum != '') : ?>
							<strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_REGNUM'); ?>: </strong>
							<?php echo $this->item->regnum; ?><br />
						<?php endif; ?>

						<?php if ($this->item->regdate != '0000-00-00 00:00:00') : ?>
							<strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_REGDATE'); ?>: </strong>
							<?php echo  date("d-m-Y", strtotime($this->item->regdate)); ?><br />
						<?php endif; ?>

					</p>
					<div class="imc-issue-subtitle"><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_DESCRIPTION'); ?></div>
					<p><?php echo $this->item->description; ?></p>
					<p>
						<?php
						$photos = json_decode($this->item->photo);
						$i=0;
						foreach ($photos->files as $photo) {
							if(!isset($photo->thumbnailUrl))
								unset($photos->files[$i]);
							$i++;
						}
						$attachments = json_decode($this->item->photo);
						$i=0;
						foreach ($attachments->files as $attachment) {
							if(isset($attachment->thumbnailUrl))
								unset($attachments->files[$i]);
							$i++;
						}
						?>
						<?php if(!empty($attachments->files)) : ?>
					<div id="attachments">
						<div class="imc-issue-subtitle"><?php echo JText::_('COM_IMC_ISSUE_ATTACHMENTS'); ?></div>
						<?php foreach ($attachments->files as $attachment) : ?>
							<ul>
								<li><a href="<?php echo $attachment->url; ?>"><?php echo $attachment->name; ?></a></li>
							</ul>
						<?php endforeach ?>
					</div>
					<?php endif; ?>

					<?php if(!empty($photos->files) && file_exists($photos->imagedir .'/'. $photos->id . '/thumbnail/' . (@$photos->files[0]->name))) : ?>
						<div class="imc-issue-subtitle"><?php echo JText::_('COM_IMC_ISSUE_PHOTOS'); ?></div>
						<div id='gallery'>
							<?php $count = 1; ?>
							<?php foreach ($photos->files as $photo) : ?>
								<a style="text-decoration: none;" href="<?php echo $photos->imagedir .'/'. $photos->id . '/' . ($photo->name) ;?>">
									<?php if($count == 1) : ?>
										<img src="<?php echo $photos->imagedir .'/'. $photos->id . '/medium/' . ($photo->name) ;?>" alt="<?php echo JText::_('COM_IMC_ISSUES_PHOTO') . ' '. $count;?>" class="img-responsive" /><br />
									<?php else :?>
										<img style="display: inline-block; padding-right: 6px;" src="<?php echo $photos->imagedir .'/'. $photos->id . '/thumbnail/' . ($photo->name) ;?>" alt="<?php echo JText::_('COM_IMC_ISSUES_PHOTO') . ' '. $count;?>" class="img-responsive" />
									<?php endif; ?>
								</a>
								<?php $count++;?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<hr />
					<?php if($this->showComments) : ?>
						<div id="comments-container"></div>
					<?php endif; ?>

					<?php /*if (JFactory::getUser()->guest) : ?>
					<p><button id="new-comment" class="btn btn-success disabled"><i class="icon-comment"></i> <?php echo JText::_('COM_IMC_COMMENTS_ADD'); ?></button></p>
				<?php else : ?>
					<p><button id="new-comment" class="btn btn-success"><i class="icon-comment"></i> <?php echo JText::_('COM_IMC_COMMENTS_ADD'); ?></button></p>
				<?php endif; */?>

				</div>
			</div>

			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 pull-right">

				<div class="imc-info-wrapper"><span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span> <?php echo $this->item->address;?></div>
				<?php
				//map
				$gmap = JFormHelper::loadFieldType('GMap', false);
				$gmap->__set('mapOnly', true);
				if($this->item->category_image != ''){
					$gmap->__set('icon', JURI::base().$this->item->category_image);
				}
				echo $gmap->showField($this->item->latitude, $this->item->longitude, 18);
				?>

			</div>

		</div>
		<hr />
		<div class="row">
			<div class="col-lg-12 col-sm-12 col-xs-12">
				<div class="center">
					<button id="timeline" class="btn btn-primary btn-lg" type="button" data-target="#cd-timeline" aria-expanded="false" aria-controls="cd-timeline">
						<?php echo JText::_('COM_IMC_ISSUE_TIMELINE'); ?> <i class="icon-arrow-down"></i>
					</button>
				</div>
				<section id="cd-timeline" class="cd-container collapse in">
					<?php $first = true; ?>
					<?php foreach ($this->logs as $log) : ?>
						<div class="cd-timeline-block">
							<div class="cd-timeline-img cd-location" style="background-color: <?php echo $log['stepid_color']; ?>;">
								<img src="<?php echo JURI::base() . '/components/com_imc/assets/images/cd-icon-location.svg';?>" alt="Location">
							</div>
							<div class="cd-timeline-content">
								<h3><?php echo $log['stepid_title']; ?></h3>
								<?php if($this->params->get('showuserdetailstimeline') && $first) : ?>
									<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY'); ?>: </strong>
										<?php echo $log['created_by']; ?></p>
									<?php $first = false; ?>

								<?php elseif($this->params->get('showadmindetailstimeline') && !$first) : ?>
									<p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_CREATED_BY'); ?>: </strong>
										<?php echo $log['created_by']; ?></p>
									<?php $first = false; ?>
								<?php endif; ?>


								<p><?php echo $log['description']; ?></p>
								<span class="cd-date"><?php echo ImcFrontendHelper::getRelativeTime($log['created']); ?></span>
							</div>
						</div>
						<?php $first = false; ?>
					<?php endforeach; ?>
				</section>
			</div>
		</div>
	</div> <!-- /container -->

	<?php /*
    <div class="item_fields2">
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
			<td><?php echo $this->item->stepid_title . ' -> '. $this->item->stepid;; ?></td>
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
<!--
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_LATITUDE'); ?></th>
			<td><?php echo $this->item->latitude; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_LONGITUDE'); ?></th>
			<td><?php echo $this->item->longitude; ?></td>
			</tr>
			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_PHOTOS'); ?></th>
			<td><?php echo $this->item->photo; ?></td>
			</tr>
 -->
 			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_STATE'); ?></th>
			<td>
			<i class="icon-<?php echo ($this->item->state == 1) ? 'publish' : 'unpublish'; ?>"></i>
			<?php echo $this->item->state; ?>
			</td>
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
<!-- 			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_LANGUAGE'); ?></th>
			<td><?php echo $this->item->language; ?></td>
			</tr> -->
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
<!-- 			<tr>
			<th><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_MODALITY'); ?></th>
			<td><?php echo $this->item->modality; ?></td>
			</tr> -->
			<tr>
			<th>Logs</th>
			<td><?php print_r($this->logs); ?></td>
			</tr>
    </div>
*/ ?>

<?php else: ?>
	<div class="alert alert-danger">
		<?php echo JText::_('COM_IMC_ITEM_NOT_LOADED'); ?>
	</div>
<?php endif; ?>