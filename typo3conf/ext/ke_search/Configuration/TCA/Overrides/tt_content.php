<?php
defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ke_search',
    'Configuration/TypoScript',
    'Faceted Search'
);

// show FlexForm field in plugin configuration
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ke_search_pi1'] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ke_search_pi2'] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ke_search_pi3'] = 'pi_flexform';

// remove the old "plugin mode" configuration field
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ke_search_pi1'] = 'select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ke_search_pi2'] = 'select_key,recursive,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ke_search_pi3'] = 'select_key,recursive';

// add plugins
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'list_type',
    'ke_search',
    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab',
    'after:default'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'list_type',
    [
        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
        'ke_search_pi1',
        'EXT:ke_search/Resources/Public/Icons/Extension.svg',
        'ke_search'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'list_type',
    [
        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi2',
        'ke_search_pi2',
        'EXT:ke_search/Resources/Public/Icons/Extension.svg',
        'ke_search'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'list_type',
    [
        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi3',
        'ke_search_pi3',
        'EXT:ke_search/Resources/Public/Icons/Extension.svg',
        'ke_search'
    ]
);

// Configure FlexForm field
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ke_search_pi1',
    'FILE:EXT:ke_search/Configuration/FlexForms/flexform_searchbox.xml'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ke_search_pi2',
    'FILE:EXT:ke_search/Configuration/FlexForms/flexform_resultlist.xml'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ke_search_pi3',
    'FILE:EXT:ke_search/Configuration/FlexForms/flexform_searchbox.xml'
);
