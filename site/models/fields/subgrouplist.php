<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * Field to load a list of available users statuses
 *
 * @since  3.2
 */
class JFormFieldSubGroupList extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since   3.2
	 */
	protected $type = 'SubGroupList';

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
			$canDo = ImcHelper::getActions();
			$canShowAllIssues = $canDo->get('imc.showall.issues');
			if($canShowAllIssues){
				$query = $db->getQuery(true)
					->select('a.id AS value')
					->select('a.title AS text')
					->select('COUNT(DISTINCT b.id) AS level')
					->from('#__usergroups as a')
					->where('a.id > 9')
					->join('LEFT', '#__usergroups  AS b ON a.lft > b.lft AND a.rgt < b.rgt')
					->group('a.id, a.title, a.lft, a.rgt')
					->order('a.lft ASC');
			}
			else {
				//get user groups higher than 9 
				$usergroups = JAccess::getGroupsByUser($user->id, false);
				for ($i=0; $i < count($usergroups); $i++) { 
					if($usergroups[$i] <= 9){
						unset($usergroups[$i]);
					}
				}
				$ids = implode(',', $usergroups);
				//get lft, rgt for these groups
				$where = array();
				$query = $db->getQuery(true)
					->select('a.id, a.lft, a.rgt')
					->from('#__usergroups as a')
					->where('a.id IN ('.$ids.')');
				$db->setQuery($query);	
				if($grps = $db->loadAssocList()){
					
					foreach ($grps as $grp) {
						$where[] = '(a.lft >= '.$grp['lft'].' AND a.rgt <= '.$grp['rgt'].')';
						$where[] = ' OR ';
					}
					array_pop($where);
				}
				else{
					$where[] = "1=1";
				}

				$query = $db->getQuery(true)
					->select('a.id AS value')
					->select('a.title AS text')
					->select('COUNT(DISTINCT b.id) AS level')
					->from('#__usergroups as a')
					->where('a.id > 9')

					->where(implode("\n", $where))
					
					->join('LEFT', '#__usergroups  AS b ON a.lft > b.lft AND a.rgt < b.rgt')
					->group('a.id, a.title, a.lft, a.rgt')
					->order('a.lft ASC');
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
