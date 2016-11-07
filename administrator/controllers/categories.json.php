<?php
/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

// No direct access.
defined('_JEXEC') or die;
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/imc.php';

class ImcControllerCategories extends JControllerLegacy
{
    public function renew()
    {
        if (ImcHelper::renewCategories()) {
            JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_RENEW_TS_SUCCESS'), 'info');
        }
        else
        {
            JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_RENEW_TS_FAIL'), 'error');
        }
        $this->setRedirect(JRoute::_('index.php?option=com_categories&extension=com_imc', false));
    }
}