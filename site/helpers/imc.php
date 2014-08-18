<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;

class ImcFrontendHelper {
    
	/**
	* Get category name using category ID
	* @param integer $category_id Category ID
	* @return mixed category name if category is found, null otherwise
	*/
	public static function getCategoryNameByCategoryId($category_id) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('title')
			->from('#__categories')
			->where('id = ' . intval($category_id));

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	* Get step name using step ID
	* @param integer $stepid Step ID
	* @return step name and color if step is found, null otherwise
	*/
	public static function getStepByStepId($stepid) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('a.title AS stepid_title, a.stepcolor AS stepid_color')
			->from('#__imc_steps AS a')
			->where('a.id = ' . intval($stepid));

		$db->setQuery($query);
		return $db->loadAssoc();
	}

}
