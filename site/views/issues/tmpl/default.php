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
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');

$canEdit = $user->authorise('core.edit', 'com_imc');
$canDelete = $user->authorise('core.delete', 'com_imc');
?>
<script type="text/javascript">
    js = jQuery.noConflict();
    
    js(document).ready(function() {
        var container = document.querySelector('.masonry');
        var msnry = new Masonry( container, {
          // options
          //columnWidth: 70,
          itemSelector: '.masonry-element'
        });

        imagesLoaded( container, function() {
          msnry.layout();
        });
    });

</script>

<form action="<?php echo JRoute::_('index.php?option=com_imc&view=issues'); ?>" method="post" name="adminForm" id="adminForm">

        
      <div id="columns">
        <div class="row masonry" id="masonry-sample">
        <?php foreach ($this->items as $i => $item) : ?>

            <?php
            $canCreate = $user->authorise('core.create', 'com_imc.issue.'.$item->id);
            $canEdit = $user->authorise('core.edit', 'com_imc.issue.'.$item->id);
            $canCheckin = $user->authorise('core.manage', 'com_imc.issue.'.$item->id);
            $canChange = $user->authorise('core.edit.state', 'com_imc.issue.'.$item->id);
            $canDelete = $user->authorise('core.delete', 'com_imc.issue.'.$item->id);
            $canEditOwn = $user->authorise('core.edit.own', 'com_imc.issue.' . $item->id);

            $photos = json_decode($item->photo);
            ?>


            <?php if (!$canEdit && $user->authorise('core.edit.own', 'com_imc.issue.'.$item->id)): ?>
                <?php $canEdit = JFactory::getUser()->id == $item->created_by; ?>
            <?php endif; ?>
        
      <div class="col-sm-6 col-md-4 col-xs-12 masonry-element">
        <div id="imc-panel-<?php echo $item->id;?>" class="panel panel-default">
          <?php if (JFactory::getUser()->id == $item->created_by) : ?>  
          <div class="ribbon-wrapper-green"><div class="ribbon-green"><?php echo JText::_('COM_IMC_ISSUES_MY_ISSUE');?></div></div>
          <?php endif; ?>

          <?php $domain = ''; ?>


            <?php if(empty($photos->files) || !file_exists($photos->imagedir .'/'. $photos->id . '/medium/' . (@$photos->files[0]->name))) : ?>
                <!-- <img src="//placehold.it/450X300/OO77BB/ffffff" class="img-responsive"> -->
            <?php else : ?>
                <div class="panel-thumbnail">
                    <img src="<?php echo $domain . $photos->imagedir .'/'. $photos->id . '/medium/' . ($photos->files[0]->name) ;?>" alt="issue photo" class="img-responsive" />
                </div>
            <?php endif; ?>

          
          <div class="<?php echo ($item->state == 0 ? 'issue-unpublished ' : ''); ?>panel-body">
            <p class="lead">
                <?php if (isset($item->checked_out) && $item->checked_out) : ?>
                    <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'issues.', $canCheckin); ?>
                <?php endif; ?>

                <?php if($item->category_image != '') : ?>
                <img src="<?php echo $item->category_image; ?>" alt="category image" />
                <?php endif; ?>

                <?php if ($canEdit) : ?>
                    <a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.(int) $item->id); ?>">
                    <i class="icon-edit"></i> <?php echo $this->escape($item->title); ?></a>
                <?php else : ?>
                    <?php echo $this->escape($item->title); ?>
                <?php endif; ?>
            </p>

            <span class="label label-default"><?php echo ImcFrontendHelper::getRelativeTime($item->created); ?></span><br />
            <span class="label label-info"><?php echo $item->stepid_title; ?></span><br />
            <span class="label label-primary"><?php echo $item->catid_title; ?></span>

            <p><?php echo $item->description; ?></p>

            <p><a href="<?php echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id); ?>"><?php echo JText::_('COM_IMC_ISSUES_MORE');?></a></p>
            <?php if($item->state == 0) : ?>
                <hr />
                <p class="imc-warning"><i class="icon-info-sign"></i> <?php echo JText::_('COM_IMC_ISSUES_NOT_YET_PUBLISHED');?></p>
            <?php endif; ?>
          </div>
        </div>
      </div><!--/col-->                

<?php /*
<!--
                <tr class="row<?php echo $i % 2; ?>">

                    <?php if (isset($this->items[0]->state)): ?>
                        <?php $class = ($canChange) ? 'active' : 'disabled'; ?>
                        <td class="center">
                            <a class="btn btn-micro <?php echo $class; ?>" href="<?php echo ($canChange) ? JRoute::_('index.php?option=com_imc&task=issue.publish&id=' . $item->id . '&state=' . (($item->state + 1) % 2), false, 2) : '#'; ?>">
                                <?php if ($item->state == 1): ?>
                                    <i class="icon-ok"></i>
                                <?php else: ?>
                                    <i class="icon-remove"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                    <?php endif; ?>

                <td>
                <?php if (isset($item->checked_out) && $item->checked_out) : ?>
                    <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'issues.', $canCheckin); ?>
                <?php endif; ?>
                <?php if ($canEdit) : ?>
                    <a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id='.(int) $item->id); ?>">
                    <?php echo $this->escape($item->title); ?></a>
                <?php else : ?>
                    <?php echo $this->escape($item->title); ?>
                <?php endif; ?>
                <a href="<?php echo JRoute::_('index.php?option=com_imc&view=issue&id='.(int) $item->id); ?>">view details</a>
                </td>
                <td>

                    <?php echo $item->stepid_title; ?>
                </td>
                <td>

                    <?php echo $item->catid_title; ?>
                </td>
                <td>

                    <?php echo JFactory::getUser($item->created_by)->name; ?>
                <td>

                    <?php echo $item->language; ?>
                </td>


                <?php if (isset($this->items[0]->id)): ?>
                    <td class="center hidden-phone">
                        <?php echo (int) $item->id; ?>
                    </td>
                <?php endif; ?>

                <?php if ($canEdit || $canDelete): ?>
                    <td class="center">
                        <?php if ($canEdit): ?>
                            <a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <button data-item-id="<?php echo $item->id; ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></button>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>

                </tr>
-->
*/ ?>
            <?php endforeach; ?>
            </div>
            </div>

    <?php /*
    <?php $canCreate = $user->authorise('core.create', 'com_imc'); ?>
    <?php if ($canCreate): ?>
        <a href="<?php echo JRoute::_('index.php?option=com_imc&task=issue.edit&id=0', false, 2); ?>" class="btn btn-success btn-small"><i class="icon-plus"></i> <?php echo JText::_('COM_IMC_ADD_ITEM'); ?></a>
    <?php endif; ?>
    */ ?>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    
    <?php         
        $app = JFactory::getApplication();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));  
        $limitstart = JFactory::getApplication()->input->getInt('limitstart', 0);      
    ?>
    <input type="hidden" name="limit" value="<?php echo $limit;?>" /> 
    <input type="hidden" name="limitstart" value="<?php echo $limitstart;?>" />

    <?php echo JHtml::_('form.token'); ?>
</form>

<script type="text/javascript">

    jQuery(document).ready(function() {
        jQuery('.delete-button').click(deleteItem);
    });

    function deleteItem() {
        var item_id = jQuery(this).attr('data-item-id');
        if (confirm("<?php echo JText::_('COM_IMC_DELETE_MESSAGE'); ?>")) {
            window.location.href = '<?php echo JRoute::_('index.php?option=com_imc&task=issue.remove&id=', false, 2) ?>' + item_id;
        }
    }
</script>


