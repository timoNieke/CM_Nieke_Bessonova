<?php
namespace Tpwd\KeSearch\Plugins;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Andreas Kiefer
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Tpwd\KeSearch\Lib\Pluginbase;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Plugin 'Faceted search - resultlist plugin' for the 'ke_search' extension.
 * @author    Andreas Kiefer
 * @author    Christian Bülter
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class ResultlistPlugin extends Pluginbase
{

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $resultListView;

    /**
     * The main method of the PlugIn
     * @param    string $content : The PlugIn content
     * @param    array $conf : The PlugIn configuration
     * @return    string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->ms = round(microtime(true) * 1000);

        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL('EXT:ke_search/Resources/Private/Language/locallang_searchbox.xlf');
        $this->conf = $typoScriptService->convertTypoScriptArrayToPlainArray($conf);

        // Configuring so caching is not expected. This value means that no cHash params are ever set.
        // We do this, because it's a USER_INT object!
        $this->pi_USER_INT_obj = 1;

        // initializes plugin configuration
        $this->init();

        if ($this->conf['resultPage'] != $GLOBALS['TSFE']->id) {
            $content = '<div id="textmessage">' . $this->pi_getLL('error_resultPage') . '</div>';
            return $this->pi_wrapInBaseClass($content);
        }

        if (empty($this->conf['view'])) {
            $content = '<div id="textmessage">' . $this->pi_getLL('error_templatePaths') . '</div>';
            return $this->pi_wrapInBaseClass($content);
        }

        // init template
        $this->initFluidTemplate();

        // hook for initials
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->addInitials($this);
            }
        }

        // assign isEmptySearch to fluid templates
        $this->fluidTemplateVariables['isEmptySearch'] = $this->isEmptySearch;

        // render "no results"-message, "too short words"-message and finally the result list
        $this->getSearchResults();

        // number of results
        $this->fluidTemplateVariables['numberofresults'] = $this->numberOfResults;

        // render links for sorting, fluid template variables are filled in class Sorting
        $this->renderOrdering();

        // process query time
        $queryTime = (round(microtime(true) * 1000) - $GLOBALS['TSFE']->register['ke_search_queryStartTime']);
        $this->fluidTemplateVariables['queryTime'] = $queryTime;
        $this->fluidTemplateVariables['queryTimeText'] = sprintf($this->pi_getLL('query_time'), $queryTime);

        // render pagebrowser
        if ($GLOBALS['TSFE']->id == $this->conf['resultPage']) {
            if (($this->conf['pagebrowserOnTop'] ?? false) || ($this->conf['pagebrowserAtBottom'] ?? false)) {
                $this->renderPagebrowser();
            }
        }

        // hook: modifyResultList
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyResultList'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyResultList'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyResultList($this->fluidTemplateVariables, $this);
            }
        }

        if (class_exists('\Tpwd\KeSearchPremium\Headless\HeadlessApi')) {
            /** @var \Tpwd\KeSearchPremium\Headless\HeadlessApi $headlessApi */
            $headlessApi = GeneralUtility::makeInstance(\Tpwd\KeSearchPremium\Headless\HeadlessApi::class);
            if ($headlessApi->getHeadlessMode()) {
                return json_encode($this->fluidTemplateVariables);
            }
        }

        // generate HTML output
        $this->resultListView->assignMultiple($this->fluidTemplateVariables);
        $htmlOutput = $this->resultListView->render();

        return $htmlOutput;
    }

    /**
     * inits the standalone fluid template
     */
    public function initFluidTemplate()
    {
        $this->resultListView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $this->resultListView->setTemplateRootPaths($this->conf['view']['templateRootPaths']);
        $this->resultListView->setPartialRootPaths($this->conf['view']['partialRootPaths']);
        $this->resultListView->setLayoutRootPaths($this->conf['view']['layoutRootPaths']);
        $this->resultListView->setTemplate('ResultList');

        // make settings available in fluid template
        $this->resultListView->assign('conf', $this->conf);
        $this->resultListView->assign('extConf', $this->extConf);
        $this->resultListView->assign('extConfPremium', $this->extConfPremium);
    }
}
