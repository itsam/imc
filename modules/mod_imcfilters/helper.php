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

class ModImcfiltersHelper {

    public function createLimitBox()
    {
        // Initialise variables.
        $app = JFactory::getApplication();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));

        $jinput = JFactory::getApplication()->input;
        //$selected = $jinput->get('list.limit');
        $selected = $limit;

        $html = '';
        $values = array (1, 5, 10, 20, 100, 0);
        foreach($values as $i){
            $a = $i;
            if($a == 0)
                $a = JText::_('ALL');
            if($selected == $i){
                $html .= '<li><a href="#" onclick="jQuery(\'input[name=limit]\').val('.$i.');jQuery(\'#adminForm\').submit();">'.$a.' <i class="icon-ok"></i></a></li>';
            }
            else {
                $html .= '<li><a href="#" onclick="jQuery(\'input[name=limit]\').val('.$i.');jQuery(\'#adminForm\').submit();">'.$a.'</a></li>';
            }
        }
        return $html;
    }

    /**
     * Retrieve component items
     * @param Joomla\Registry\Registry  &$params  module parameters
     * @return array Array with all the elements
     */
    public static function getList(&$params) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        /* @var $params Joomla\Registry\Registry */
        $query
                ->select('*')
                ->from($params->get('table'));

        $db->setQuery($query, $params->get('offset'), $params->get('limit'));
        $rows = $db->loadObjectList();
        return $rows;
    }

    /**
     * Retrieve component items
     * @param Joomla\Registry\Registry  &$params  module parameters
     * @return mixed stdClass object if the item was found, null otherwise
     */
    public static function getItem(&$params) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        /* @var $params Joomla\Registry\Registry */
        $query
                ->select('*')
                ->from($params->get('item_table'))
                ->where('id = ' . intval($params->get('item_id')));

        $db->setQuery($query);
        $element = $db->loadObject();
        return $element;
    }


    /**
     * Checks if an element should appear in the table/item view
     * @param string $field name of the field
     * @return boolean True if it should appear, false otherwise
     */
    public static function shouldAppear($field) {
        $noHeaderFields = array('checked_out_time', 'checked_out', 'ordering', 'state');
        return !in_array($field, $noHeaderFields);
    }

    

    /**
     * Method to get a value from a external table
     * @param string $source_table Source table name
     * @param string $key_field Source key field 
     * @param string $value_field Source value field
     * @param mixed  $key_value Value for the key field
     * @return mixed The value in the external table or null if it wasn't found
     */
    private static function loadValueFromExternalTable($source_table, $key_field, $value_field, $key_value) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
                ->select($value_field)
                ->from($source_table)
                ->where($key_field . ' = ' . $db->quote($key_value));


        $db->setQuery($query);
        return $db->loadResult();
    }
}
