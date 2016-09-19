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
?>

    <style>
        .col-imclist{width:100%;float:left;}
        .col-imclist:nth-child(2n+1){background-color: #f0f0f0;border: 2px solid #f0f0f0;border-bottom: 2px solid rgba(0,0,0,0.08);}
        .col-imclist:nth-child(2n){background-color: white;border: 2px solid white;border-bottom: 2px solid rgba(0,0,0,0.08);}
        .panel-list{margin-bottom:0;border:none;box-shadow:none;padding:18px;}
        .col-imclist .panel{background-color:transparent;}

        .total-imclist{background-color:#e4e4e4;height:100%;}

        .imc-column{float:left;line-height:120%;box-sizing:border-box;}
        .imc-left-col{width: 4.66666666667%;margin-left:0;text-align:center;}
        .imc-med-col{width:74.0%;margin-left:4%;}
        .imc-right-col{width:13.3333333333%;text-align:center;margin-left:4%;color:rgba(0, 0, 0, 0.23);}
        .imc-list-id{font-size:130%;line-height: 140%;}
        .imc-list-title{font-size:150%;width:100%;margin:0 0 10px;}
        .imc-list-categories{width:100%;margin-bottom:10px;}
        .imc-list-address{width:100%;margin-bottom:10px;}
        .imc-HorizontalSeparator{background:0;padding:0;background-color:#d1d1d1;border:0;height:1px;margin:0 0 10px;}
        .imc-list-content{width:100%;}
        .imc-list-info{width:100%;margin-bottom:10px;}
        .imc-list-info span{margin-right:20px;}
        .imc-list-info .icon-comment{margin-right:5px;}
        .imc-list-info .icon-thumbs-up{margin-right:5px;}
        @media screen and (max-width:619px){
            .imc-left-col{width:10%;}
            .imc-med-col{width:80%;}
            .imc-right-col{width:100%;margin: 10px 0px 20px;}
            .imc-list-info span{margin-right:0;}
        }

    </style>

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
                        <?php if($item->votes > 0) : ?>
                            <div title="<?php echo JText::_('COM_IMC_ISSUES_VOTES');?>" class="book-ribbon">
                                <div>+<?php echo $item->votes; ?></div>
                            </div>
                        <?php endif; ?>
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

                        <div class="imc-list-categories">
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_ISSUES_CATID');?>"><?php echo $item->catid_title; ?></span>
                        </div>

                        <div class="imc-list-content">
                            <p><?php echo ImcFrontendHelper::cutString($item->description, 200); ?></p>
                        </div>

                        <div class="imc-list-address">
                            <span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span> <?php echo $item->address;?>
                        </div>

                        <hr class="imc-HorizontalSeparator"></hr>

                        <div class="imc-list-info">
                            <?php if($item->updated == $item->created) : ?>
                                <span class="label label-default" title="<?php echo JText::_('COM_IMC_ISSUES_CREATED');?>"><?php echo ImcFrontendHelper::getRelativeTime($item->created); ?></span>
                            <?php else : ?>
                                <span class="label label-info" title="<?php echo JText::_('COM_IMC_ISSUES_UPDATED');?>"><?php echo ImcFrontendHelper::getRelativeTime($item->updated); ?></span>
                            <?php endif; ?>
                            <span class="label label-info" style="background-color: <?php echo $item->stepid_color;?>" title="<?php echo JText::_('COM_IMC_ISSUES_STEPID');?>"><?php echo $item->stepid_title; ?></span>
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_TITLE_COMMENTS');?>"><i class="icon-comment"></i> <?php echo $item->comments;?></span>
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_TITLE_COMMENTS');?>"><i class="icon-thumbs-up"></i> <?php echo $item->votes;?></span>

                        </div>


                    </div>


                    <div class="imc-column imc-right-col">
                        <?php //show photo if any
                        $i = 0;

                        if(isset($attachments->files)){
                            foreach ($attachments->files as $file) {
                                if (isset($file->thumbnailUrl)){
                                    echo '<div class="panel-thumbnail">'. "\n";
                                    echo '<a href="'. JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id).'">';
                                    echo '<img src="'.$attachments->imagedir .'/'. $attachments->id . '/medium/' . ($attachments->files[$i]->name) .'" alt="issue photo" class="img-responsive" />' . "\n";
                                    echo '</a>';
                                    echo '</div>'. "\n";
                                    break;
                                }
                                $i++;
                            }
                        }
                        $tempArray = $attachments->files;
                        if($tempArray==null){
                            echo '<i class="icon-picture icon-4x"></i><div style="clear:both"></div>';
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
