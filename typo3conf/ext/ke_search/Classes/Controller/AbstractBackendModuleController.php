<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Bernhard Berger <bernhard.berger@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Tpwd\KeSearch\Controller;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;

/**
 * Class AbstractBackendModuleController
 * @package Tpwd\KeSearch\Controller
 */
abstract class AbstractBackendModuleController extends ActionController
{

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var array
     */
    protected $excludedArguments = array();

    protected $argumentsKey = 'tx_kesearch_web_kesearchbackendmodule';

    /**
     * Forwards the request to the last active action.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    protected function forwardToLastModule()
    {
        $moduleData = BackendUtility::getModuleData(
            array('controller' => ''),
            array(),
            $this->argumentsKey
        );

        if (($moduleData['action'] ?? '') === 'alert') {
            return;
        }

        if (!empty($moduleData['action']) && $moduleData['action'] !== $this->getActionName()) {
            $this->forward($moduleData['action'], $moduleData['controller']);
        }
    }

    /**
     * Makes action name from the current action method name.
     *
     * @return string
     */
    protected function getActionName()
    {
        return substr($this->actionMethodName, 0, -6);
    }

    /**
     * Makes controller name from the controller class name.
     *
     * @return string
     */
    protected function getControllerName()
    {
        return (string)preg_replace('/^.*\\\([^\\\]+)Controller$/', '\1', get_class($this));
    }

    /**
     * Initializes all actions.
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->id = (int)GeneralUtility::_GET('id');

        // Fix pagers
        $arguments = GeneralUtility::_GPmerged($this->argumentsKey);

        if ($arguments && is_array($arguments)) {
            foreach ($arguments as $argumentKey => $argumentValue) {
                if ($argumentValue) {
                    if (!in_array($argumentKey, $this->excludedArguments)) {
                        $this->request->setArgument($this->argumentsKey . '|' . $argumentKey, $argumentValue);
                    } else {
                        $this->request->setArgument($this->argumentsKey . '|' . $argumentKey, '');
                    }
                }
            }
        } else {
            $this->forwardToLastModule();
        }

        parent::initializeAction();
    }

    /**
     * Stores information about the last action of the module.
     */
    protected function storeLastModuleInformation()
    {
        // Probably should store also arguments (except pager?)
        BackendUtility::getModuleData(
            array('controller' => '', 'action' => ''),
            array('controller' => $this->getControllerName(), 'action' => $this->getActionName()),
            $this->argumentsKey
        );
    }
}
