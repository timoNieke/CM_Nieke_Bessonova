<?php
defined('TYPO3') || die();

(static function() {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CmMultiplechoice',
        'Questsionsfrontend',
        [
            \Cm\CmMultiplechoice\Controller\QuestionsController::class => 'list, show'
        ],
        // non-cacheable actions
        [
            \Cm\CmMultiplechoice\Controller\QuestionsController::class => '',
            \Cm\CmMultiplechoice\Controller\AnswersController::class => ''
        ]
    );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    questsionsfrontend {
                        iconIdentifier = cm_multiplechoice-plugin-questsionsfrontend
                        title = LLL:EXT:cm_multiplechoice/Resources/Private/Language/locallang_db.xlf:tx_cm_multiplechoice_questsionsfrontend.name
                        description = LLL:EXT:cm_multiplechoice/Resources/Private/Language/locallang_db.xlf:tx_cm_multiplechoice_questsionsfrontend.description
                        tt_content_defValues {
                            CType = list
                            list_type = cmmultiplechoice_questsionsfrontend
                        }
                    }
                }
                show = *
            }
       }'
    );
})();
