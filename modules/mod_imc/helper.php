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

/**
 * Helper for mod_imc
 *
 * @package     com_imc
 * @subpackage  mod_imc
 */
class ModImcHelper {

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
     * 
     * @param Joomla\Registry\Registry $params
     * @param string $field
     */
    public static function renderElement($table_name, $field_name, $field_value) {
        $result = '';
                $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        switch ($table_name) {
            
		case '#__imc_issues':
		switch($field_name){
		case 'id':
		$result = $field_value;
		break;
		case 'title':
		$result = $field_value;
		break;
		case 'stepid':
		$query = "SELECT id, title AS value FROM #__imc_steps HAVING id LIKE '" . $field_value . "'";
		$db->setQuery($query);
		$results = $db->loadObject();
		$result = empty($results->value) ? $field_value : $results->value;
		break;
		case 'catid':
		$result = self::loadValueFromExternalTable('#__categories', 'id', 'title', $field_value);
		break;
		case 'description':
		$result = $field_value;
		break;
		case 'address':
		$result = $field_value;
		break;
		case 'latitude':
		$result = $field_value;
		break;
		case 'longitude':
		$result = $field_value;
		break;
		case 'photo':
		$result = $field_value;
		break;
		case 'created':
		$result = $field_value;
		break;
		case 'updated':
		$result = $field_value;
		break;
		case 'created_by':
		$user = JFactory::getUser($field_value);
		$result = $user->name;
		break;
		case 'language':
		$result = JLanguage::getInstance($field_value)->getName();
		break;
		case 'hits':
		$result = $field_value;
		break;
		case 'note':
		$result = $field_value;
		break;
		case 'votes':
		$result = $field_value;
		break;
		case 'modality':
		$result = $field_value;
		break;
		}
		break;
		case '#__imc_steps':
		switch($field_name){
		case 'id':
		$result = $field_value;
		break;
		case 'title':
		$result = $field_value;
		break;
		case 'description':
		$result = $field_value;
		break;
		case 'created':
		$result = $field_value;
		break;
		case 'updated':
		$result = $field_value;
		break;
		case 'created_by':
		$user = JFactory::getUser($field_value);
		$result = $user->name;
		break;
		case 'language':
		$result = JLanguage::getInstance($field_value)->getName();
		break;
		}
		break;
		case '#__imc_keys':
		switch($field_name){
		case 'id':
		$result = $field_value;
		break;
		case 'title':
		$result = $field_value;
		break;
		case 'skey':
		$result = $field_value;
		break;
		case 'created_by':
		$user = JFactory::getUser($field_value);
		$result = $user->name;
		break;
		case 'created':
		$result = $field_value;
		break;
		case 'updated':
		$result = $field_value;
		break;
		}
		break;
		case '#__imc_evolution':
		switch($field_name){
		case 'id':
		$result = $field_value;
		break;
		case 'issueid':
		$result = self::loadValueFromExternalTable('#__imc_issues', 'id', 'title', $field_value);
		break;
		case 'stepid':
		$query = "SELECT id, title AS value FROM #__imc_steps HAVING id LIKE '" . $field_value . "'";
		$db->setQuery($query);
		$results = $db->loadObject();
		$result = empty($results->value) ? $field_value : $results->value;
		break;
		case 'description':
		$result = $field_value;
		break;
		case 'created':
		$result = $field_value;
		break;
		case 'updated':
		$result = $field_value;
		break;
		case 'created_by':
		$user = JFactory::getUser($field_value);
		$result = $user->name;
		break;
		case 'language':
		$result = JLanguage::getInstance($field_value)->getName();
		break;
		}
		break;
		case '#__imc_votes':
		switch($field_name){
		case 'id':
		$result = $field_value;
		break;
		case 'issueid':
		$result = self::loadValueFromExternalTable('#__imc_issues', 'id', 'title', $field_value);
		break;
		case 'created':
		$result = $field_value;
		break;
		case 'updated':
		$result = $field_value;
		break;
		case 'created_by':
		$user = JFactory::getUser($field_value);
		$result = $user->name;
		break;
		}
		break;
		case '#__imc_comments':
		switch($field_name){
		case 'id':
		$result = $field_value;
		break;
		case 'issueid':
		$result = self::loadValueFromExternalTable('#__imc_issues', 'id', 'title', $field_value);
		break;
		case 'parentid':
		$result = $field_value;
		break;
		case 'description':
		$result = $field_value;
		break;
		case 'photo':
		$result = $field_value;
		break;
		case 'created':
		$result = $field_value;
		break;
		case 'updated':
		$result = $field_value;
		break;
		case 'created_by':
		$user = JFactory::getUser($field_value);
		$result = $user->name;
		break;
		case 'language':
		$result = JLanguage::getInstance($field_value)->getName();
		break;
		}
		break;
        }
        return $result;
    }

    /**
     * Returns the translatable name of the element
     * @param Joomla\Registry\Registry $params
     * @param string $field Field name
     * @return string Translatable name.
     */
    public static function renderTranslatableHeader(&$params, $field) {
        return JText::_('MOD_IMC_HEADER_FIELD_' . str_replace('#__', '', strtoupper($params->get('table'))) . '_' . strtoupper($field));
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
