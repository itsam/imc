<?php

/**
 * @version     3.0.0
 * @package     plg_system_imc
 * @copyright   Copyright (C) 2016. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */

defined('_JEXEC') or die;

class PlgSystemImc extends JPlugin
{
    public function onBeforeRender()
    {
        // Get the application object
        $app = JFactory::getApplication();

        // Run in backend
        if ($app->isAdmin() === true)
        {
            // Get the input object
            $input = $app->input;

            // Append button just on Articles
            if ($input->getCmd('option') === 'com_categories' && $input->getCmd('extension', 'com_imc') === 'com_imc')
            {
                // Get an instance of the Toolbar
                $toolbar = JToolbar::getInstance('toolbar');

                // Add your custom button here
                $url = JRoute::_('index.php?option=com_example&task=massemail&format=raw');
                $toolbar->appendButton('Link', 'export', 'Renew timestamp', $url);
            }
        }
    }
}