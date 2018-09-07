<?php

/**
 * @version     3.0.0
 * @package     plg_search_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

defined('_JEXEC') or die;

require_once JPATH_SITE . '/components/com_imc/router.php';

/**
 * Content search plugin.
 *
 * @package     Joomla.Plugin
 * @subpackage  Search.content
 * @since       1.6
 */
class PlgSearchImc extends JPlugin
{
    
    /**
     * Determine areas searchable by this plugin.
     *
     * @return  array  An array of search areas.
     *
     * @since   1.6
     */
    public function onContentSearchAreas() {
        static $areas = array('imc' => 'COM_IMC');
        return $areas;
    }
    
    /**
     * Search content (articles).
     * The SQL must return the following fields that are used in a common display
     * routine: href, title, section, created, text, browsernav.
     *
     * @param   string  $text      Target search string.
     * @param   string  $phrase    Matching option (possible values: exact|any|all).  Default is "any".
     * @param   string  $ordering  Ordering option (possible values: newest|oldest|popular|alpha|category).  Default is "newest".
     * @param   mixed   $areas     An array if the search it to be restricted to areas or null to search all areas.
     *
     * @return  array  Search results.
     *
     * @since   1.6
     */
    public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null) {
        $db = JFactory::getDbo();
        
        if (is_array($areas)) {
            if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
                return array();
            }
        }
        
        $limit = $this->params->def('search_limit', 50);
        
        $text = trim($text);
        
        if ($text == '') {
            return array();
        }
        
        $rows = array();
        
        //Search Issues.
        if ($limit > 0) {
            switch ($phrase) {
                case 'exact':
                    $text = $db->quote('%' . $db->escape($text, true) . '%', false);
                    $wheres2 = array();
                    $wheres2[] = 'a.title LIKE ' . $text;
                    $wheres2[] = 'a.catid LIKE ' . $text;
                    $where = '(' . implode(') OR (', $wheres2) . ')';
                    break;

                case 'all':
                case 'any':
                default:
                    $words = explode(' ', $text);
                    $wheres = array();
                    
                    foreach ($words as $word) {
                        $word = $db->quote('%' . $db->escape($word, true) . '%', false);
                        $wheres2 = array();
                        $wheres2[] = 'a.title LIKE ' . $word;
                        $wheres2[] = 'a.catid LIKE ' . $word;
                        $wheres[] = implode(' OR ', $wheres2);
                    }
                    
                    $where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
                    break;
            }
            
            switch ($ordering) {
                default:
                    $order = 'a.id DESC';
                    break;
            }
            
            $rows = array();
            $query = $db->getQuery(true);
            
            $query->clear()->select(array('a.id', 'title AS title', 'created AS created', 'title AS text', '"Issue" AS section', '1 AS browsernav'))->from('#__imc_issues AS a')->where('(' . $where . ')')->group('a.id')->order($order);
            
            $db->setQuery($query, 0, $limit);
            $list = $db->loadObjectList();
            $limit-= count($list);
            
            if (isset($list)) {
                foreach ($list as $key => $item) {
                    $list[$key]->href = JRoute::_('index.php?option=com_imc&view=issue&id=' . $item->id, false, 2);
                }
            }
            
            $rows = array_merge($list, $rows);
        }
        
        return $rows;
    }
}
