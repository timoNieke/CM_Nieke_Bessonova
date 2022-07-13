<?php
defined('TYPO3') || die();

(static function() {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CmMemo',
        'Frontend',
        [
            \Cm\CmMemo\Controller\MemoController::class => 'list, show, new, create, edit, update, delete'
        ],
        // non-cacheable actions
        [
            \Cm\CmMemo\Controller\MemoController::class => 'create, update, delete'
        ]
    );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    frontend {
                        iconIdentifier = cm_memo-plugin-frontend
                        title = LLL:EXT:cm_memo/Resources/Private/Language/locallang_db.xlf:tx_cm_memo_frontend.name
                        description = LLL:EXT:cm_memo/Resources/Private/Language/locallang_db.xlf:tx_cm_memo_frontend.description
                        tt_content_defValues {
                            CType = list
                            list_type = cmmemo_frontend
                        }
                    }
                }
                show = *
            }
       }'
    );
})();
