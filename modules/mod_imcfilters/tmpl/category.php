<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @subpackage  mod_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;
$elements = ModImcHelper::getList($params);
?>

<?php if (!empty($elements)) : ?>
    <table class="table">
        <?php foreach ($elements as $element): ?>
            <tr>
                <th><?php echo ModImcHelper::renderTranslatableHeader($params, $params->get('field')); ?></th>
                <td><?php echo ModImcHelper::renderElement($params->get('table'), $params->get('field'), $element->{$params->get('field')}); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>