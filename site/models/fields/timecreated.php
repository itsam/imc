<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');
require_once JPATH_COMPONENT_SITE . '/helpers/imc.php';

/**
 * Supports an HTML select list of categories
 */
class JFormFieldTimecreated extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'timecreated';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput() {
        // Initialize variables.
        $html = array();

        $time_created = $this->value;
        if (!strtotime($time_created)) {
            $time_created = ImcFrontendHelper::convert2UTC( date("Y-m-d H:i:s") );
        }

        $hidden = (boolean) $this->element['hidden'];
        if ($hidden == null || !$hidden) {
            $jdate = new JDate( ImcFrontendHelper::convertFromUTC($time_created) );
            $pretty_date = $jdate->format(JText::_('DATE_FORMAT_LC2'));
            $html[] = "<span>" . $pretty_date . "</span>";
        }
        $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $time_created . '" />';
        return implode("\n", $html);
    }
}