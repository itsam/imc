<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class ImcController extends JControllerLegacy {

/*  // testing some practices  
    private $view;
    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->model = $this->getModel();
        $this->view = $this->getView(JFactory::getApplication()->input->get('view', 'issue'), 'html');
        $this->view->setModel($this->model, true);
        $this->view->setModel($this->getModel('Logs', 'ImcModel'), false);
        echo 'WOW';
    }
*/

    /**
     * Method to display a view.
     *
     * @param	boolean			$cachable	If true, the view output will be cached
     * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.5
     */
    public function display($cachable = false, $urlparams = false) {
        require_once JPATH_COMPONENT . '/helpers/imc.php';

        $view = JFactory::getApplication()->input->getCmd('view', 'issues');
        JFactory::getApplication()->input->set('view', $view);

        // testing some practices
        $v = &$this->getView($view, 'html');
        $v->setModel($this->getModel($view), true); //the default model (true)

        JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
        $logsModel = $this->getModel('Logs', 'ImcModel');
        $v->setModel($logsModel, false);

        //$foo = $logsModel->getItemsByIssue(1);
        //print_r($foo);

        //$issueModel = $this->getModel('Issue', 'ImcModel');
        //$v->setModel($issueModel, false);

        //$moo = $issueModel->getItem(1);
        //print_r($moo);


        //$v->display();
        parent::display($cachable, $urlparams);

        return $this;
    }

}
