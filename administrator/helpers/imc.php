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

	private static $catIds = array();
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
			JText::_('COM_IMC_TITLE_CATEGORIES'),
			"index.php?option=com_categories&extension=com_imc",
			$vName == 'categories'
		);
		if ($vName=='categories') {
			JToolBarHelper::title('Improve My City: Categories (Issues)');
		}
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_LOGS'),
			'index.php?option=com_imc&view=logs',
			$vName == 'logs'
		);
		/*
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_VOTES'),
			'index.php?option=com_imc&view=votes',
			$vName == 'votes'
		);
		*/
		JHtmlSidebar::addEntry(
			JText::_('COM_IMC_TITLE_COMMENTS'),
			'index.php?option=com_imc&view=comments',
			$vName == 'comments'
		);
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
            'core.admin', 
            'core.manage', 
            'core.create', 
            'core.edit', 
            'core.edit.own', 
            'core.edit.state', 
            'core.delete',
            'imc.manage.keys',
            'imc.manage.steps',
            'imc.manage.logs',
            'imc.showall.issues'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }

    public static function getCategoriesByUserGroups($user = null) {
    	if($user == null) {
    		$user = JFactory::getUser();
    	}

    	self::$catIds = array();
    	ImcHelper::getCategoriesUserGroups(); //populates self::catIds
    	$categories = self::$catIds;

    	$usergroups = JAccess::getGroupsByUser($user->id);
    	$allowed_catIds = array();
    	foreach ($categories as $category) {
    		foreach ($category['usergroups'] as $groupid) {
    			if (in_array($groupid, $usergroups)){
    				array_push($allowed_catIds, $category['catid']);
    			}
    		}
    	}

    	return $allowed_catIds;
    }

    private static function getCategoriesUserGroups($recursive = false)
    {
        $_categories = JCategories::getInstance('Imc');
        $_parent = $_categories->get();
        if(is_object($_parent))
        {
            $_items = $_parent->getChildren($recursive);
        }
        else
        {
            $_items = false;
        }
        return ImcHelper::loadCats($_items);
    }
        
    private static function loadCats($cats = array())
    {
        if(is_array($cats))
        {
            foreach($cats as $JCatNode)
            {
                $params = json_decode($JCatNode->params);
                if(isset($params->imc_category_usergroup))
                    $usergroups = $params->imc_category_usergroup;
                else
                    $usergroups = array();

                self::$catIds[] = array('catid'=>$JCatNode->id,'usergroups'=>$usergroups);

                if($JCatNode->hasChildren())
                    ImcHelper::loadCats($JCatNode->getChildren());
            }
        }
        return false;
    }

}
