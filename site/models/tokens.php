<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
defined('_JEXEC') or die;


/**
 * Keep the class lightweight since it's only used by the API
 */
class ImcModelTokens
{
    public static function insertToken($objToken)
    {
        // Insert the object into the tokens table.
        $result = JFactory::getDbo()->insertObject('#__imc_tokens', $objToken);
        return $result;
    }

    public static function exists($token)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('COUNT(*)');
        $query->from($db->quoteName('#__imc_tokens'));
        $query->where($db->quoteName('token')." = ".$db->quote($token));

        // Reset the query using our newly populated query object.
        $db->setQuery($query);
        $count = $db->loadResult();
        return $count;
    }
}