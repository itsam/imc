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
//JHtml::_('jquery.framework');
//JHtmlBootstrap::loadCSS();
//JHtml::_('bootstrap.framework');
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$user = JFactory::getUser();
$userId = $user->get('id');

$isAllowedToEdit = $user->authorise('core.edit', 'com_imc');
if(is_null($isAllowedToEdit))
{
    $isAllowedToEdit = 0;
}

$allowed_catids = ImcHelper::getCategoriesByUserGroups();

// $canEdit = $user->authorise('core.edit', 'com_imc');
// $canDelete = $user->authorise('core.delete', 'com_imc');

$this->document->addStyleSheet(JURI::root(true) . '/components/com_imc/assets/css/card.css');
?>

    <script src="https://unpkg.com/masonry-layout@4.1/dist/masonry.pkgd.min.js"></script>
    <script src="<?php echo  JURI::root(true) . '/components/com_imc/assets/js/imagesloaded.pkgd.min.js'; ?>"></script>

    <script type="text/javascript">
        js = jQuery.noConflict();
        js(document).ready(function() {

//	    var container = document.querySelector('.masonry');
//        var msnry = new Masonry( container, {
//          // options
//          //columnWidth: 70,
//          itemSelector: '.masonry-element'
//        });
//
//        imagesLoaded( container, function() {
//          msnry.layout();
//        });

            <!--Bugfix on map vanishing when admin hovers on it.-->
            js('#map-sidebar').children().css("position", "static");

            var grid = js('.grid').masonry({
                // set itemSelector so .grid-sizer is not used in layout
                itemSelector: '.grid-item',
                // use element for option
                columnWidth: '.grid-sizer',
                gutter: '.gutter-sizer',
                percentPosition: true
            });
            //grid.masonry('layout');
            grid.imagesLoaded().progress( function() {
                grid.masonry('layout');
            });

        });
    </script>

    <div id="imc-button-filters">
        <?php
        $module = &JModuleHelper::getModule('mod_imcfilters');
        $html = JModuleHelper::renderModule($module);
        echo $html;
        ?>
    </div>



    <div class="grid">
        <!-- width of .grid-sizer used for columnWidth -->
        <div class="grid-sizer"></div>
        <div class="gutter-sizer"></div>

        <div class="grid-item">
            <div id="imc-panel-0" class="panel panel-default">
                <div class="panel-thumbnail">
				    <?php
				    $module = &JModuleHelper::getModule('mod_imcmap');
				    $html = JModuleHelper::renderModule($module);
				    echo $html;
				    ?>
                </div>
                <div class="imc-panel-body">asdf</div>
                <div class="imc-card-header">asdf</div>
            </div>
        </div>
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
            if ($firstStep['ordering'] != 1 && !$isAllowedToEdit){
                $canEditOnStatus = false;
            }

            ?>
            <?php if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.'.$item->id)): ?>
                <?php $canEdit = JFactory::getUser()->id == $item->created_by; ?>
            <?php endif; ?>


            <div class="grid-item">
                <div id="imc-panel-<?php echo $item->id;?>" class="panel panel-default">

                    <?php if (JFactory::getUser()->id == $item->created_by) : ?>
                        <div class="ribbon-wrapper-corner"><div class="ribbon-corner"><?php echo JText::_('COM_IMC_ISSUES_MY_ISSUE');?></div></div>
                    <?php /*else : */?><!--
                        <?php /*if($item->votes > 0) : */?>
                            <div title="<?php /*echo JText::_('COM_IMC_ISSUES_VOTES');*/?>" class="book-ribbon">
                                <div>+<?php /*echo $item->votes; */?></div>
                            </div>
                        --><?php /*endif; */?>
                    <?php endif; ?>

                    <?php //show photo if any
                    $i = 0;
                    $thumbnailFound = false;
                    echo '<div class="panel-thumbnail">'. "\n";
                    echo '<a href="'. JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id).'">';
                    if(!empty($attachments->files)){
                        foreach ($attachments->files as $file) {
                            if (isset($file->thumbnailUrl)){

                                echo '<img src="'.$attachments->imagedir .'/'. $attachments->id . '/medium/' . ($attachments->files[$i]->name) .'" alt="issue photo" class="imc-card-img" />' . "\n";
                                $thumbnailFound = true;
                                break;
                            }
                            $i++;
                        }

                        if (!$thumbnailFound) {
                            echo '<div class="imc-no-img-grid">
                                    <i class="hidden-xs icon-picture icon-4x"></i> <i class="visible-xs icon-picture icon-2x"></i>
                                    <div style="clear:both"></div>
                                    <span class="imc-right-col-noimage">'. JText::_('COM_IMC_NO_PHOTO').'</span>
                              </div>';
                        }


                    } else {
                        echo '<div class="imc-no-img-grid">
                                    <i class="hidden-xs icon-picture icon-4x"></i> <i class="visible-xs icon-picture icon-2x"></i>
                                    <div style="clear:both"></div>
                                    <span class="imc-right-col-noimage">'. JText::_('COM_IMC_NO_PHOTO').'</span>
                              </div>';
                    }

                    echo '</a>'; ?>
                    <span class="imc-card-id"><?php echo '#'. (int) $item->id . ' '; ?></span>

                    <div class="imc-issue-msg">
                    <?php if ( $item->moderation == 1 || !$canEditOnStatus && JFactory::getUser()->id == $item->created_by ) { ?>
                        <i class="imc-card-msg-icon icon-exclamation-sign"></i>
                        <span class="imc-msg-tooltip imc-grid-msg-tooltip">
                        <?php if($item->moderation == 1) : ?>
                            <?php echo JText::_('COM_IMC_ISSUES_NOT_YET_PUBLISHED');?>
                        <?php endif; ?>
                        <?php if (!$canEditOnStatus && JFactory::getUser()->id == $item->created_by) : ?>
                            <?php echo JText::_('COM_IMC_ISSUE_CANNOT_EDIT_ANYMORE'); ?>
                        <?php endif; ?>
                        </span>
                    <?php } ?>
                    </div>

                    <?php if ( ($canEdit && $canEditOnStatus && empty($allowed_catids)) || (in_array($item->catid,$allowed_catids)) ) : ?>
                        <span class="imc-card-edit-icon">
                            <a title="<?php echo $this->escape($item->title); ?>" class="imc-grid-title" href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.(int) $item->id); ?>">
                                <i class="icon-edit"></i>
                            </a>
                        </span>
                    <?php endif;
                    echo '</div>'. "\n";
                    ?>

                    <div class="<?php echo ($item->moderation == 1 ? 'issue-unmoderated ' : ''); ?>imc-panel-body">
                        <div class="imc-card-header">
                            <?php if($item->category_image != '') { ?>
                                <img class="imc-card-cat-icon" src="<?php echo $item->category_image; ?>" alt="category image" />
                            <?php } else { ?>
                                <img class="imc-card-cat-icon" src="<?php echo JURI::base();?>modules/mod_imcmap/assets/images/marker.png" alt="category image" />
                            <?php } ?>

                            <a title="<?php echo $this->escape($item->title); ?>" class="imc-grid-title" href="<?php echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id)?>">
                                <?php echo $this->escape($item->title); ?>
                            </a>

                            <?php /*uncomment if you like to display a lock icon */
                            /*if (isset($item->checked_out) && $item->checked_out) : ?>
                            <i class="icon-lock"></i> <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'issues.', $canCheckin); ?>
                          <?php endif; */ ?>
                        </div>

                        <span class="imc-grid-cat-label" title="<?php echo JText::_('COM_IMC_ISSUES_CATID');?>"><?php echo $item->catid_title; ?></span>

                        <div class="imc-card-section-style">

                            <span class="label label-info" style="background-color: <?php echo $item->stepid_color;?>" title="<?php echo JText::_('COM_IMC_ISSUES_STEPID');?>"><?php echo $item->stepid_title; ?></span>

                            <?php if($item->updated == $item->created) : ?>
                                <span class="label label-default" title="<?php echo JText::_('COM_IMC_ISSUES_CREATED');?>"><?php echo ImcFrontendHelper::getRelativeTime($item->created); ?></span>
                            <?php else : ?>
                                <span class="label label-info" title="<?php echo JText::_('COM_IMC_ISSUES_UPDATED');?>"><?php echo ImcFrontendHelper::getRelativeTime($item->updated); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="imc-card-section-style">
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_TITLE_COMMENTS');?>"><i class="icon-comment"></i>&nbsp; <?php echo $item->comments;?></span>
                            <span class="label label-default" title="<?php echo JText::_('COM_IMC_ISSUES_VOTES');?>"><i class="icon-thumbs-up"></i>&nbsp; <?php echo $item->votes;?></span>
                            <?php if (JFactory::getUser()->id == $item->created_by && $item->votes > 0) : ?>
                                <span class="label label-default" title="<?php echo JText::_('COM_IMC_ISSUES_VOTES');?>">+<?php echo $item->votes; ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="imc-card-description"><?php echo ImcFrontendHelper::cutString($item->description, 200); ?></p>

                        <!--<p><a href="<?php /*echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id); */?>"><?php /*echo JText::_('COM_IMC_ISSUES_MORE');*/?></a></p>-->
                    </div>
                </div><!-- /imc-panel-X -->
            </div><!--/grid-item-->
        <?php endforeach; ?>
    </div>

<?php echo $this->pagination->getListFooter(); ?>