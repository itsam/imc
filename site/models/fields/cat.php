<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */


defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldCat extends JFormFieldStep
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Cat';

	protected function getItemById($id)
	{
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('title')
              ->from('#__categories')
              ->where('id='.$id);
        $db->setQuery($query);
        $results = $db->loadRow();		
		return $results;
	}

	protected function getAccessById($id)
	{
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('access')
              ->from('#__categories')
              ->where('id='.$id);
        $db->setQuery($query);
        $results = $db->loadRow();		
		return $results;
	}

	public function getOptions()
	{
		$options = array();
		$extension = $this->element['extension'] ? (string) $this->element['extension'] : (string) $this->element['scope'];
		$published = (string) $this->element['published'];

		// Load the category options for a given extension.
		if (!empty($extension))
		{
			// Filter over published state or not depending upon if it is present.
			if ($published)
			{
				$options = JHtml::_('category.options', $extension, array('filter.published' => explode(',', $published)));
			}
			else
			{
				$options = JHtml::_('category.options', $extension);
			}

			// Verify permissions.  If the action attribute is set, then we scan the options.
			if ((string) $this->element['action'])
			{

				// Get the current user object.
				$user = JFactory::getUser();

				foreach ($options as $i => $option)
				{
					$cat = $this->getAccessById($option->value);

					/*
					 * To take save or create in a category you need to have create rights for that category
					 * unless the item is already in that category.
					 * Unset the option if the user isn't authorised for it. In this field assets are always categories.
					 */
					if ($user->authorise('core.create', $extension . '.category.' . $option->value) != true)
					{
						unset($options[$i]);
					}

					/* itsam added: check category access */
					else if(!in_array($cat[0], $user->getAuthorisedViewLevels())){
						unset($options[$i]);	
					}


				}

			}

			if (isset($this->element['show_root']))
			{
				array_unshift($options, JHtml::_('select.option', '0', JText::_('JGLOBAL_ROOT')));
			}
		}
		else
		{
			JLog::add(JText::_('JLIB_FORM_ERROR_FIELDS_CATEGORY_ERROR_EXTENSION_EMPTY'), JLog::WARNING, 'jerror');
		}

		// Merge any additional options in the XML definition.
		//$options = array_merge(parent::getOptions(), $options);

		return $options;
	}


}