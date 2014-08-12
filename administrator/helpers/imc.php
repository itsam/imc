<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Imc helper.
 */
class ImcHelper {

    /**
     * Configure the Linkbar.
     */
    public static function addSubmenu($vName = '') {
        		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_ISSUES'),
			'index.php?option=com_imc&view=issues',
			$vName == 'issues'
		);
		JHtmlSidebar::addEntry(
			'Categories (Issues)',
			"index.php?option=com_categories&extension=com_imc",
			$vName == 'categories'
		);
		if ($vName=='categories') {
			JToolBarHelper::title('Improve My City: Categories (Issues)');
		}
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_STEPS'),
			'index.php?option=com_imc&view=steps',
			$vName == 'steps'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_KEYS'),
			'index.php?option=com_imc&view=keys',
			$vName == 'keys'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_LOGS'),
			'index.php?option=com_imc&view=logs',
			$vName == 'logs'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_VOTES'),
			'index.php?option=com_imc&view=votes',
			$vName == 'votes'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_COMMENTS'),
			'index.php?option=com_imc&view=comments',
			$vName == 'comments'
		);

    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @return	JObject
     * @since	1.6
     */
    public static function getActions() {
        $user = JFactory::getUser();
        $result = new JObject;

        $assetName = 'com_imc';

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }


}
