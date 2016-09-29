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

$user = JFactory::getUser();
$userId = $user->get('id');
$this->document->addStyleSheet(JURI::root(true) . '/components/com_imc/assets/css/list.css');
?>


<script>
    js = jQuery.noConflict();
    js(document).ready(function() {
        <!--Bugfix on map vanishing when admin hovers on it.-->
        js('#map-sidebar').children().css("position", "static");

    });
</script>

<div class="container-fluid">
    <div class="row">
        <?php foreach ($this->items as $i => $item) : ?>
            <?php
            $canCreate = $user->authorise('core.create', 'com_imc.issue.'.$item->id);
            $canEdit = $user->authorise('core.edit', 'com_imc.issue.'.$item->id);
            $canCheckin = $user->authorise('core.manage', 'com_imc.issue.'.$item->id);
            $canChange = $user->authorise('core.edit.state', 'com_imc.issue.'.$item->id);
            $canDelete = $user->authorise('core.delete', 'com_imc.issue.'.$item->id);
            //$canEditOwn = $user->authorise('core.edit.own', 'com_imc.issue.' . $item->id);
            $attachments = json_decode($item->photo);

            //Edit Own only if issue status is the initial one
            $firstStep = ImcFrontendHelper::getStepByStepId($item->stepid);
            $canEditOnStatus = true;
            if ($firstStep['ordering'] != 1){
                $canEditOnStatus = false;
            }

            ?>
            <?php if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.'.$item->id)): ?>
                <?php $canEdit = JFactory::getUser()->id == $item->created_by; ?>
            <?php endif; ?>



            <div class="col-imclist">
                <div id="imc-panel-<?php echo $item->id;?>" class="panel panel-default panel-list">
                    <?php if (JFactory::getUser()->id == $item->created_by) : ?>
                        <div class="ribbon-wrapper-corner">
                            <div class="ribbon-corner"><?php echo JText::_('COM_IMC_ISSUES_MY_ISSUE');?></div>
                        </div>
                    <?php else : ?>
                        <?php /*if($item->votes > 0) : ?>
                            <div title="<?php echo JText::_('COM_IMC_ISSUES_VOTES');?>" class="book-ribbon">
                                <div>+<?php echo $item->votes; ?></div>
                            </div>
                        <?php endif; */?>
                    <?php endif; ?>


                    <div class="imc-column imc-left-col">
                        <span class="imc-list-id"><?php echo (int) $item->id; ?></span>
                        <p class="lead">
                            <?php if($item->category_image != '') : ?>
                                <img src="<?php echo $item->category_image; ?>" alt="category image" />
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="imc-column imc-med-col">
                        <div class="imc-list-title">
                            <?php if ($canEdit && $canEditOnStatus) : ?>
                                <a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.(int) $item->id); ?>">
                                    <i class="icon-edit"></i> <?php echo $this->escape($item->title); ?></a>
                            <?php else : ?>
                                <a href="<?php echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id); ?>">
                                    <?php echo $this->escape($item->title); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="imc-overview-cat-style">
                            <span class="label label-default imc-overview-label-style" title="<?php echo JText::_('COM_IMC_ISSUES_CATID');?>"><?php echo $item->catid_title; ?></span>
                        </div>

                        <div class="imc-list-content">
                            <p><?php echo ImcFrontendHelper::cutString($item->description, 200); ?></p>
                            <p><a href="<?php echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id); ?>"><?php echo JText::_('COM_IMC_ISSUES_MORE');?></a></p>
                        </div>

                        <div class="imc-list-address">
                            <span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span> <span><?php echo $item->address;?></span>
                        </div>

                        <hr class="imc-HorizontalSeparator"/>

                        <div class="imc-list-info">
                            <?php if($item->updated == $item->created) : ?>
                                <span class="label label-default" title="<?php echo JText::_('COM_IMC_ISSUES_CREATED');?>"><?php echo ImcFrontendHelper::getRelativeTime($item->created); ?></span>
                            <?php else : ?>
                                <span class="label label-info" title="<?php echo JText::_('COM_IMC_ISSUES_UPDATED');?>"><?php echo ImcFrontendHelper::getRelativeTime($item->updated); ?></span>
                            <?php endif; ?>
                            <span class="label label-info" style="background-color: <?php echo $item->stepid_color;?>" title="<?php echo JText::_('COM_IMC_ISSUES_STEPID');?>"><?php echo $item->stepid_title; ?></span>
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_TITLE_COMMENTS');?>"><i class="icon-comment"></i> <?php echo $item->comments;?></span>
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_VOTES');?>"><i class="icon-thumbs-up"></i> <?php echo $item->votes;?></span>

                        </div>


                    </div>


                    <div class="imc-column imc-right-col">
                        <?php //show photo if any
                        $i = 0;

                        if(isset($attachments->files)){
                            foreach ($attachments->files as $file) {
                                if (isset($file->thumbnailUrl)){
                                    echo '<div class="panel-thumbnail">'. "\n";
                                    echo '<a class="imc-OverviewListImageStyle" href="'. JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id).'">';
                                    echo '<img src="'.$attachments->imagedir .'/'. $attachments->id . '/medium/' . ($attachments->files[$i]->name) .'" alt="issue photo" class="img-responsive" sizes="(max-width: 200px) 85vw, 200px" width="200" height="85" />' . "\n";
                                    echo '</a>';
                                    echo '</div>'. "\n";
                                    break;
                                }
                                $i++;
                            }
                        }
                        $tempArray = $attachments->files;
                        if($tempArray==null){
                            echo '<i class="hidden-xs icon-picture icon-4x"></i>';
                            echo '<i class="visible-xs icon-picture icon-2x"></i>';
                            echo '<div style="clear:both"></div>';
                            echo '<span class="imc-right-col-noimage">'. JText::_('COM_IMC_NO_PHOTO') . '</span>';
                        }
                        ?>
                    </div>

                </div>
            </div>


        <?php endforeach; ?>

        <div style="text-align:center"><?php echo $this->pagination->getListFooter(); ?></div>
    </div>

</div>