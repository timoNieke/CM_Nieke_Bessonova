<?php
defined('TYPO3') || die();

(static function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cmmultiplechoice_domain_model_questions', 'EXT:cm_multiplechoice/Resources/Private/Language/locallang_csh_tx_cmmultiplechoice_domain_model_questions.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cmmultiplechoice_domain_model_questions');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cmmultiplechoice_domain_model_answers', 'EXT:cm_multiplechoice/Resources/Private/Language/locallang_csh_tx_cmmultiplechoice_domain_model_answers.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cmmultiplechoice_domain_model_answers');
})();
