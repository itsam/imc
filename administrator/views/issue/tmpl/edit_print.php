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
?>
<style type="text/css">

    @media screen {
        #modal-imc-print .modal-body {
            overflow-y: auto;
        }
    }

    @media all {
        /*table {width: 100%;}*/
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
        body {
            /*margin: 0;*/
            /*padding:0;*/
        }
        body * {
            visibility: hidden;
            height: 0;
            /*display: none;*/
        }
        .modal-header,
        .modal-footer {
            display: none;
        }

        #section-to-print {
            overflow: visible;
        }
        #section-to-print,
        #section-to-print * {
            visibility: visible;
            height: auto;
            /*display: block;*/
        }
        #section-to-print {
            /*position: absolute;*/
            /*left: 0;*/
            /*top: 0;*/
        }

        img {
            max-height: 300px;
            max-width: 300px;
        }

        table {
            /*width: 99%;*/
            margin: 0 auto;
        }
        /*div.screen {*/
        /*display: none;*/
        /*height: 0;*/
        /*padding:0;*/
        /*}*/
        table {
            font-size: 11px;
        }
        .page-break {
            display: block;
            page-break-before: always;
        }

        .navbar,
        .subhead,
        .header {
            display: none;
            height: 0;
        }
        td {
            padding: 2px;
        }

        #modal-imc-print{
            /*width: 100%;*/
            /*height: 100%;*/
            position: absolute;
            left: 0;
            top: 0;
            margin: 0;
            padding: 0;
            visibility: visible;
            /**Remove scrollbar for printing.**/
            overflow-y: visible !important;
            border: none;
        }
        #modal-imc-print .modal-body {
            width: 100%;
            height: 100%;
            padding: 20px 0;
            overflow-y: visible !important;
        }
        /*#modal-imc-print h4 {margin-top: -30px;}*/

        @page { margin: 1.5cm 0.7cm;}
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
                <strong><?php echo ImcFrontendHelper::convertFromUTC($this->item->created); ?></strong></p>
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

        <p><strong><?php echo JText::_('COM_IMC_FORM_LBL_ISSUE_PHOTOS'); ?></strong>:
            <br />

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
		<?php if(!empty($photos->files) && file_exists(JPATH_ROOT . '/' . $photos->imagedir .'/'. $photos->id . '/thumbnail/' . (@$photos->files[0]->name))) : ?>

			<?php foreach ($photos->files as $photo) : ?>
				<?php
				$src = JURI::root() . '/'. $photos->imagedir .'/'. $photos->id . '/medium/' . ($photo->name);
				?>
                <img src="<?php echo $src ;?>" alt="" style="max-width: 350px;" /><br />
			<?php endforeach; ?>

		<?php endif; ?>

        </p>

        <p><strong><?php echo JText::_('COM_IMC_SETTINGS_GOOGLE_MAP_LABEL'); ?></strong>:
            <br />
			<?php
			$api_key = JComponentHelper::getParams('com_imc')->get('api_key');
			$center = $this->item->latitude . ',' . $this->item->longitude;

			$map_src  = 'https://maps.googleapis.com/maps/api/staticmap';
			$map_src .=	'?center=' . $center;
			$map_src .=	'&markers=color:red%7Clabel:C%7C'. $center;
			$map_src .=	'&zoom=17&size=400x400';
			if($api_key != '')
			{
				$map_src .=	'&key='.$api_key;
			}

			?>
            <img src="<?php echo $map_src;?>"/>
        </p>

        <p><strong><?php echo JText::_('COM_IMC_TITLE_LOG'); ?></strong></p>
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

        <p><strong><?php echo JText::_('COM_IMC_TITLE_COMMENTS'); ?></strong></p>
        <table class="table table-striped">
            <thead>
            <tr>
                <th><?php echo JText::_("JDATE");?></th>
                <th><?php echo JText::_("COM_IMC_LOGS_CREATED_BY");?></th>
                <!--<th><?php /*echo JText::_("JADMIN");*/?></th>-->
                <th><?php echo JText::_("COM_IMC_TITLE_COMMENT");?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ($this->comments as $comment) : ?>
                <tr>
                    <td><?php echo $comment->created; ?></td>
                    <td><?php echo $comment->fullname; ?></td>
                    <!--<td><?php /*echo $comment->isAdmin; */?></td>-->
                    <td><?php echo $comment->description; ?></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

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
