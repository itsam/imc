<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */


defined('JPATH_BASE') or die;
/*jimport('joomla.html.html');
jimport('joomla.form.formfield');*/

JFormHelper::loadFieldClass('list');

//include('step.php');
/**
 * Supports an HTML select list of categories
 */
class JFormFieldAclcategory extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Aclcategory';

	protected $extension = 'com_imc';
	/**
	 * Cached array of the category items.
	 *
	 * @var    array
	 * @since  3.2
	 */
	protected static $options = array();

	/**
	 * Method to get the options to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.2
	 */
	protected function getOptions()
	{
		// Hash for caching
		$hash = md5($this->element);

		if (!isset(static::$options[$hash]))
		{
			static::$options[$hash] = parent::getOptions();

			$options = array();

			$db = JFactory::getDbo();
			$user = JFactory::getUser();

			$query = $db->getQuery(true)
				->select('a.id AS value')
				->select('a.title AS text')
				->select('COUNT(DISTINCT b.id) AS level')
				->from('#__categories as a')
				->where('a.extension = "'.$this->extension.'"')
				->join('LEFT', '#__categories AS b ON a.lft > b.lft AND a.rgt < b.rgt')
				->group('a.id, a.title, a.lft, a.rgt')
				->order('a.lft ASC');
			
			$isRoot = $user->authorise('core.admin');
			if(!$isRoot) {
				//require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/imc.php';
				require_once JPATH_ROOT . '/administrator/components/com_imc/helpers/imc.php';

	            $allowed_catids = ImcHelper::getCategoriesByUserGroups();
	            $allowed_catids = implode(',', $allowed_catids);
	            if(!empty($allowed_catids)){
	                $query->where('a.id IN (' . $allowed_catids . ')');
	            }
			}


			$db->setQuery($query);

			if ($options = $db->loadObjectList())
			{
				foreach ($options as &$option)
				{
					$option->text = str_repeat('- ', $option->level) . $option->text;
				}

				static::$options[$hash] = array_merge(static::$options[$hash], $options);
			}
		}

		return static::$options[$hash];
	}
}
