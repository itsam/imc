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

//Edit Own only if issue status is the initial one
$firstStep = ImcFrontendHelper::getStepByStepId($this->item->stepid);
$canEditOnStatus = true;
if ($firstStep['ordering'] != 1){
    $canEditOnStatus = false;
}

//issue statuses
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
		};

		js( "#timeline" ).click(function() {
		  js('#cd-timeline').toggle();
		  js('#cd-timeline')[0].scrollIntoView( true );
		});

		js('.delete-button').click(deleteItem);

		js('#new-comment').click(function() {
			alert('<?php echo JText::_('COM_IMC_COMMENTS_NOT_ALLOWED'); ?>');
		});
    });

    function deleteItem() {
        if (confirm("<?php echo JText::_('COM_IMC_DELETE_MESSAGE'); ?>")) {
            window.location.href = '<?php echo JRoute::_('index.php?option=com_imc&task=issue.remove&id=' . $this->item->id, false, 2); ?>'
        }
    }

</script>

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

	    <div class="col-lg-6 col-sm-12 col-xs-12 col-lg-push-6">
	    	<div style="padding-bottom: 40px;">
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
	    <div class="col-lg-6 col-sm-12 col-xs-12 col-lg-pull-6">
			<div class="imc-info-wrapper">
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
				        <a href="<?php echo $photos->imagedir .'/'. $photos->id . '/' . ($photo->name) ;?>">
				        	<?php if($count == 1) : ?>
					        	<img src="<?php echo $photos->imagedir .'/'. $photos->id . '/medium/' . ($photo->name) ;?>" alt="<?php echo JText::_('COM_IMC_ISSUES_PHOTO') . ' '. $count;?>" class="img-responsive" /><br />
				        	<?php else :?>
				        		<img src="<?php echo $photos->imagedir .'/'. $photos->id . '/thumbnail/' . ($photo->name) ;?>" alt="<?php echo JText::_('COM_IMC_ISSUES_PHOTO') . ' '. $count;?>" class="img-responsive" />
				        	<?php endif; ?>
				        </a>
				        <?php $count++;?>
					<?php endforeach; ?>
					</div>        
				<?php endif; ?>
				</p>
				<hr />
				<?php if (JFactory::getUser()->guest) : ?>
					<p><button id="new-comment" class="btn btn-success disabled"><i class="icon-comment"></i> <?php echo JText::_('COM_IMC_COMMENTS_ADD'); ?></button></p>
				<?php else : ?>
					<p><button id="new-comment" class="btn btn-success"><i class="icon-comment"></i> <?php echo JText::_('COM_IMC_COMMENTS_ADD'); ?></button></p>
				<?php endif;?>

	    	</div>
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
						<!-- <h3><?php echo $log['action']; ?></h3> -->
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
