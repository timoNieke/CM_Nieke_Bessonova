<?php

$langGeneralPath = 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:';

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'type' => 'rendertype',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:ke_search/Resources/Public/Icons/table_icons/icon_tx_kesearch_filters.gif',
        'searchFields' => 'title'
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => $langGeneralPath . 'LGL.language',
            'config' => array(
                'default' => 0,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array($langGeneralPath . 'LGL.allLanguages', -1),
                    array($langGeneralPath . 'LGL.default_value', 0)
                )
            )
        ),
        'l10n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => $langGeneralPath . 'LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_kesearch_filters',
                'foreign_table_where' => 'AND tx_kesearch_filters.pid=###CURRENT_PID###'
                    . ' AND tx_kesearch_filters.sys_language_uid IN (-1,0)',
            )
        ),
        'l10n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'hidden' => array(
            'exclude' => 1,
            'label' => $langGeneralPath . 'LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'rendertype' => array(
            'exclude' => 0,
            'l10n_display' => 'defaultAsReadonly',
            'displayCond' => 'FIELD:l10n_parent:<:1',
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.rendertype',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.rendertype.I.0', 'select'),
                    array('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.rendertype.I.1', 'list'),
                    array('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.rendertype.I.2', 'checkbox'),
                    array('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.rendertype.I.3', 'dateRange'),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => 'select',
            )
        ),

        'markAllCheckboxes' => array(
            'exclude' => 0,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.markAllCheckboxes',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'options' => array(
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.options',
            'l10n_mode' => 'exclude',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_kesearch_filteroptions',
                'maxitems' => 500,
                'appearance' => array(
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showAllSynchronizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => array(
                        'info' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    )
                )
            ),
        ),
        'target_pid' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.target_pid',
            'config' => array(
                'default' => 0,
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        'amount' => array(
            'exclude' => 0,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.amount',
            'config' => array(
                'type' => 'input',
                'default' => '10',
                'size' => '30',
                'eval' => 'trim,int',
            )
        ),
        'shownumberofresults' => array(
            'exclude' => 0,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.shownumberofresults',
            'config' => array(
                'type' => 'check',
                'default' => '1'
            )
        ),
        'alphabeticalsorting' => array(
            'exclude' => 0,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xlf:tx_kesearch_filters.alphabeticalsorting',
            'config' => array(
                'type' => 'check',
                'default' => '1',
            )
        ),
    ),
    'types' => array(
        'select' => array('showitem' => 'sys_language_uid,l10n_parent, l10n_diffsource, hidden,'
            . ' title,rendertype, options, shownumberofresults, alphabeticalsorting'),
        'list' => array('showitem' => 'sys_language_uid,l10n_parent, l10n_diffsource, hidden,'
            . ' title,rendertype, options, shownumberofresults, alphabeticalsorting'),
        'checkbox' => array('showitem' => 'sys_language_uid,l10n_parent, l10n_diffsource, hidden,'
            . ' title,rendertype, markAllCheckboxes, options, shownumberofresults,'
            . ' alphabeticalsorting'),
        'dateRange' => array('showitem' => 'sys_language_uid,l10n_parent, l10n_diffsource, hidden,'
            . ' title,rendertype'),
    )
);
