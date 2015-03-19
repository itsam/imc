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

jimport('joomla.application.component.view');

/**
 * View class for a list of Imc.
 */
class ImcViewSteps extends JViewLegacy {

    protected $items;
    protected $pagination;
    protected $state;
    protected $canManageSteps;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        $user = JFactory::getUser();
        $this->canManageKeys = $user->authorise('imc.manage.steps');        

        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        ImcHelper::addSubmenu('steps');

        $this->addToolbar();
        
        if(!$this->canManageKeys){
            JFactory::getApplication()->enqueueMessage(JText::_('COM_IMC_ACTION_NOT_ALLOWED'), 'error');
            return;
        }

        $this->sidebar = JHtmlSidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since	1.6
     */
    protected function addToolbar() {
        if(!$this->canManageKeys){
            JToolBarHelper::title(JText::_('COM_IMC_TITLE_STEPS'), 'steps.png');
            //JToolBarHelper::back();
            $bar = JToolBar::getInstance('toolbar');
            $bar->appendButton('Link', 'leftarrow', 'COM_IMC_BACK', JRoute::_('index.php?option=com_imc', false));
            return;
        }
                
        require_once JPATH_COMPONENT . '/helpers/imc.php';

        $state = $this->get('State');
        $canDo = ImcHelper::getActions($state->get('filter.category_id'));

        JToolBarHelper::title(JText::_('COM_IMC_TITLE_STEPS'), 'steps.png');

        //Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/step';
        if (file_exists($formPath)) {

            if ($canDo->get('core.create')) {
                JToolBarHelper::addNew('step.add', 'JTOOLBAR_NEW');
            }

            if ($canDo->get('core.edit') && isset($this->items[0])) {
                JToolBarHelper::editList('step.edit', 'JTOOLBAR_EDIT');
            }
        }

        if ($canDo->get('core.edit.state')) {

            if (isset($this->items[0]->state)) {
                JToolBarHelper::divider();
                JToolBarHelper::custom('steps.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
                JToolBarHelper::custom('steps.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            } else if (isset($this->items[0])) {
                //If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'steps.delete', 'JTOOLBAR_DELETE');
            }

            if (isset($this->items[0]->state)) {
                JToolBarHelper::divider();
                JToolBarHelper::archiveList('steps.archive', 'JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out)) {
                JToolBarHelper::custom('steps.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
        }

        //Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state)) {
            if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
                JToolBarHelper::deleteList('', 'steps.delete', 'JTOOLBAR_EMPTY_TRASH');
                JToolBarHelper::divider();
            } else if ($canDo->get('core.edit.state')) {
                JToolBarHelper::trash('steps.trash', 'JTOOLBAR_TRASH');
                JToolBarHelper::divider();
            }
        }

        if ($canDo->get('core.admin')) {
            JToolBarHelper::preferences('com_imc');
        }

        //Set sidebar action - New in 3.0
        JHtmlSidebar::setAction('index.php?option=com_imc&view=steps');

        $this->extra_sidebar = '';
        
		JHtmlSidebar::addFilter(

			JText::_('JOPTION_SELECT_PUBLISHED'),

			'filter_published',

			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), "value", "text", $this->state->get('filter.state'), true)

		);

    }

	protected function getSortFields()
	{
		return array(
		'a.id' => JText::_('JGRID_HEADING_ID'),
		'a.title' => JText::_('COM_IMC_STEPS_TITLE'),
		'a.description' => JText::_('COM_IMC_STEPS_DESCRIPTION'),
		'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
		'a.state' => JText::_('JSTATUS'),
		'a.created_by' => JText::_('COM_IMC_STEPS_CREATED_BY'),
		'a.language' => JText::_('JGRID_HEADING_LANGUAGE'),
		);
	}

}
