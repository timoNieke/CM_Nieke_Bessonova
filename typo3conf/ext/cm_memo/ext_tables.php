<?php
defined('TYPO3') || die();

(static function() {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cmmemo_domain_model_memo', 'EXT:cm_memo/Resources/Private/Language/locallang_csh_tx_cmmemo_domain_model_memo.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cmmemo_domain_model_memo');
})();
