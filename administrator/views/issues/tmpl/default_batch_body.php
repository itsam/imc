<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
$published = $this->state->get('filter.published');
?>
<div class="row-fluid">

	<div class="control-group span6">
		<div class="controls">
			<h3 style="color: red;">Copy functionality is under development</h3>
			<h4>Currently, no log record is created and no automatic notification is sent and no images are copied</h4>
		</div>
	</div>

	<!--
	<div class="control-group span6">
		<div class="controls">
			<?php /*echo JHtml::_('batch.tag'); */?>
		</div>
	</div>
-->
</div>

<div class="row-fluid">
	<div class="control-group span6">
		<div class="controls">
			<?php echo JHtml::_('batch.language'); ?>
		</div>
	</div>
	<?php if ($published >= 0) : ?>
		<div class="control-group span6">
			<div class="controls">
				<?php echo JHtml::_('batch.item', 'com_imc'); ?>
			</div>
		</div>
	<?php endif; ?>

<!--
	<div class="control-group span6">
		<div class="controls">
			<?php /*echo JHtml::_('batch.access'); */?>
		</div>
	</div>

-->
</div>


<div class="row-fluid">
	<div class="control-group span6">
		<div class="controls">

		</div>
	</div>
	<?php if ($published >= 0) : ?>
		<div class="control-group span6">
			<div class="controls">

			</div>
		</div>
	<?php endif; ?>

</div>

