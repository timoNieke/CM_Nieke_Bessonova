<?php

namespace Tpwd\KeSearch\Indexer\Types;

/* ***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Andreas Kiefer
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
 * *************************************************************
 *
 * @author Andreas Kiefer
 * @author Christian Bülter
 */

use Tpwd\KeSearch\Domain\Repository\ContentRepository;
use Tpwd\KeSearch\Domain\Repository\IndexRepository;
use Tpwd\KeSearch\Domain\Repository\PageRepository;
use Exception;
use Tpwd\KeSearch\Indexer\IndexerBase;
use Tpwd\KeSearch\Lib\SearchHelper;
use Tpwd\KeSearch\Lib\Db;
use TYPO3\CMS\Core\Domain\Repository\PageRepository as CorePageRepository;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use \TYPO3\CMS\Frontend\DataProcessing\FilesProcessor;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;

define('DONOTINDEX', -3);


/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer
 * @author    Stefan Froemken
 * @author    Christian Bülter
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Page extends IndexerBase
{

    /**
     * this array contains all data of all pages in the default language
     * @var array
     */
    public $pageRecords = array();

    /**
     * this array contains all data of all pages, but additionally with all available languages
     * @var array
     */
    public $cachedPageRecords = array(); //

    /**
     * this array contains the system languages
     * @var array
     */
    public $sysLanguages = array();

    /**
     * this array contains the definition of which content element types should be indexed
     * @var array
     */
    public $defaultIndexCTypes = array(
        'text',
        'textmedia',
        'textpic',
        'bullets',
        'table',
        'html',
        'header',
        'uploads',
        'shortcut'
    );

    /**
     * this array contains the definition of which file content element types should be indexed
     * @var array
     */
    public $fileCTypes = array('uploads');

    /**
     * this array contains the definition of which page
     * types (field doktype in pages table) should be indexed.
     * @var array
     * @see https://github.com/TYPO3/typo3/blob/10.4/typo3/sysext/core/Classes/Domain/Repository/PageRepository.php#L106
     */
    public $indexDokTypes = array(CorePageRepository::DOKTYPE_DEFAULT);

    /*
     * Name of indexed elements. Will be overwritten in content element indexer.
     */
    public $indexedElementsName = 'pages';

    /* @var $fileRepository \TYPO3\CMS\Core\Resource\FileRepository */
    public $fileRepository;

    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var FilesProcessor
     */
    public $filesProcessor;

    /**
     * Files Processor configuration
     * @var array
     */
    public $filesProcessorConfiguration = [
        'references.' => [
            'fieldName' => 'media',
            'table' => 'tt_content'
        ],
        'collections.' => [
            'field' => 'file_collections'
        ],
        'sorting.' => [
            'field ' => 'filelink_sorting'
        ],
        'as' => 'files'
    ];

    /**
     * counter for how many pages we have indexed
     * @var integer
     */
    public $counter = 0;

    /**
     * counter for how many pages without content we found
     * @var integer
     */
    public $counterWithoutContent = 0;

    /**
     * counter for how many files we have indexed
     * @var integer
     */
    public $fileCounter = 0;

    /**
     * sql query for content types
     * @var string
     */
    public $whereClauseForCType = '';

    /**
     * tx_kesearch_indexer_types_page constructor.
     * @param \Tpwd\KeSearch\Indexer\IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        parent::__construct($pObj);

        // set content types which should be index, fall back to default if not defined
        if (empty($this->indexerConfig['contenttypes'])) {
            $content_types_temp = $this->defaultIndexCTypes;
        } else {
            $content_types_temp = GeneralUtility::trimExplode(
                ',',
                $this->indexerConfig['contenttypes']
            );
        }

        if (!empty($this->indexerConfig['index_page_doctypes'])) {
            $this->indexDokTypes = GeneralUtility::trimExplode(
                ',',
                $this->indexerConfig['index_page_doctypes']
            );
        }

        // create a mysql WHERE clause for the content element types
        $cTypes = array();
        foreach ($content_types_temp as $value) {
            $cTypes[] = 'CType="' . $value . '"';
        }
        $this->whereClauseForCType = implode(' OR ', $cTypes);

        // get all available sys_language_uid records
        /** @var TranslationConfigurationProvider $translationProvider */
        $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $startingPoints = [];
        $startingPoints += GeneralUtility::trimExplode(',', $this->indexerConfig['startingpoints_recursive'], true);
        $startingPoints += GeneralUtility::trimExplode(',', $this->indexerConfig['single_pages'], true);
        foreach ($startingPoints as $startingPoint) {
            foreach ($translationProvider->getSystemLanguages($startingPoint) as $key => $lang) {
                $this->sysLanguages[$key] = $lang;
            }
        }

        // make file repository
        /* @var $this ->fileRepository \TYPO3\CMS\Core\Resource\FileRepository */
        $this->fileRepository = GeneralUtility::makeInstance(FileRepository::class);

        // make cObj
        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        // make filesProcessor
        $this->filesProcessor = GeneralUtility::makeInstance(FilesProcessor::class);
    }

    /**
     * This function was called from indexer object and saves content to index table
     * @return string content which will be displayed in backend
     */
    public function startIndexing()
    {
        // get all pages. Regardless if they are shortcut, sysfolder or external link
        $indexPids = $this->getPagelist(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['single_pages']
        );

        // add complete page record to list of pids in $indexPids
        $this->pageRecords = $this->getPageRecords($indexPids);

        // create an array of cached page records which contains pages in
        // default and all other languages registered in the system
        foreach ($this->pageRecords as $pageRecord) {
            $this->addLocalizedPagesToCache($pageRecord);
        }

        // create a new list of allowed pids
        $indexPids = array_keys($this->pageRecords);

        // Remove unmodified pages in incremental mode
        if ($this->indexingMode == self::INDEXING_MODE_INCREMENTAL) {
            $this->removeUnmodifiedPageRecords($indexPids, $this->pageRecords, $this->cachedPageRecords);
        }

        // Stop if no pages for indexing have been found. Proceeding here would result in an error because we cannot
        // fetch an empty list of pages.
        if ($this->indexingMode == self::INDEXING_MODE_INCREMENTAL && empty($indexPids)) {
            $logMessage = 'No modified pages have been found, no indexing needed.';
            $this->pObj->logger->info($logMessage);
            return $logMessage;
        }

        // add tags to pages of doktype standard, advanced, shortcut and "not in menu"
        // add tags also to subpages of sysfolders (254), since we don't want them to be
        // excluded (see: http://forge.typo3.org/issues/49435)
        $where = ' (doktype = 1 OR doktype = 2 OR doktype = 4 OR doktype = 5 OR doktype = 254) ';

        // add the tags of each page to the global page array
        $this->addTagsToRecords($indexPids, $where);

        // loop through pids and collect page content and tags
        foreach ($indexPids as $uid) {
            $this->getPageContent($uid);
        }

        $logMessage = 'Indexer "' . $this->indexerConfig['title'] . '" finished'
            . ' (' . count($indexPids) . ' records processed)';
        $this->pObj->logger->info($logMessage);

        // compile title of languages
        $languageTitles = '';
        foreach ($this->sysLanguages as $language) {
            if (strlen($languageTitles)) $languageTitles .= ', ';
            $languageTitles .= $language['title'];
        }

        // show indexer content
        return
            count($indexPids) . ' ' . $this->indexedElementsName . ' have been selected for indexing in the main language.' . LF
            . count($this->sysLanguages) . ' languages (' . $languageTitles . ') have been found.' . LF
            . $this->counter . ' ' . $this->indexedElementsName . ' have been indexed. ' . LF
            . $this->counterWithoutContent . ' had no content or the content was not indexable.' . LF
            . $this->fileCounter . ' files have been indexed.';
    }

    /**
     * @return string
     */
    public function startIncrementalIndexing(): string
    {
        $this->indexingMode = self::INDEXING_MODE_INCREMENTAL;
        $content = $this->startIndexing();
        $content .= $this->removeDeleted();
        return $content;
    }

    /**
     * Removes index records for the page records which have been deleted since the last indexing.
     * Only needed in incremental indexing mode since there is a dedicated "cleanup" step in full indexing mode.
     *
     * @return string
     */
    public function removeDeleted(): string
    {
        /** @var IndexRepository $indexRepository */
        $indexRepository = GeneralUtility::makeInstance(IndexRepository::class);

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        // get all pages (including deleted)
        $indexPids = $this->getPagelist(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['single_pages'],
            true
        );

        // Fetch all pages which have been deleted since the last indexing
        $records = $pageRepository->findAllDeletedAndHiddenByUidListAndTimestampInAllLanguages($indexPids, $this->lastRunStartTime);

        // and remove the corresponding index entries
        $count = $indexRepository->deleteCorrespondingIndexRecords('page', $records, $this->indexerConfig);
        $message = LF . 'Found ' . $count . ' deleted or hidden page(s).';

        return $message;
    }

    /**
     * get array with all pages
     * but remove all pages we don't want to have
     * @param array $uids Array with all page uids
     * @param string $whereClause Additional where clause for the query
     * @param string $table The table to select the fields from
     * @param string $fields The requested fields
     * @return array Array containing page records with all available fields
     */
    public function getPageRecords(array $uids, $whereClause = '', $table = 'pages', $fields = 'pages.*')
    {
        $databaseConnection = Db::getDatabaseConnection('tx_kesearch_index');
        $queryBuilder = Db::getQueryBuilder($table);
        $queryBuilder->getRestrictions()->removeAll();
        $pageQuery = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->in(
                    'uid', implode(',', $uids)
                )
            )
            ->execute();

        $pageRows = [];
        while ($row = $pageQuery->fetch()) {
            $pageRows[$row['uid']] = $row;
        }

        return $pageRows;
    }

    /**
     * add localized page records to a cache/globalArray
     * This is much faster than requesting the DB for each tt_content-record
     * @param array $pageRow
     * @param bool $removeRestrictions
     * @return void
     */
    public function addLocalizedPagesToCache($pageRow, $removeRestrictions = false)
    {
        // create entry in cachedPageRecods for default language
        $this->cachedPageRecords[0][$pageRow['uid']] = $pageRow;

        // create entry in cachedPageRecods for additional languages, skip default language 0
        foreach ($this->sysLanguages as $sysLang) {
            if ($sysLang['uid'] != 0) {

                // get translations from "pages" not from "pages_language_overlay" if on TYPO3 9 or higher
                // see https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.0/Breaking-82445-PagesAndPageTranslations.html
                $queryBuilder = Db::getQueryBuilder('pages');
                if ($removeRestrictions) {
                    $queryBuilder->getRestrictions()->removeAll();
                }
                $results = $queryBuilder
                    ->select('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'l10n_parent',
                            $queryBuilder->quote($pageRow['uid'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->quote($sysLang['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->execute()
                    ->fetchAll();

                $pageOverlay = $results[0] ?? false;
                if ($pageOverlay) {
                    $this->cachedPageRecords[$sysLang['uid']][$pageRow['uid']] = $pageOverlay + $pageRow;
                }
            }
        }
    }


    /**
     * Remove page records from $indexPids, $pageRecords and $cachedPageRecords which have not been modified since
     * last index run.
     *
     * @param array $indexPids
     * @param array $pageRecords
     * @param array $cachedPageRecords
     */
    public function removeUnmodifiedPageRecords(array & $indexPids, & $pageRecords = [], & $cachedPageRecords = [])
    {
        foreach ($indexPids as $uid) {
            $modified = false;

            // check page timestamp
            foreach ($this->sysLanguages as $sysLang) {
                if (
                    !empty($cachedPageRecords[$sysLang['uid']][$uid])
                    && $cachedPageRecords[$sysLang['uid']][$uid]['tstamp'] > $this->lastRunStartTime
                ) {
                    $modified = true;
                }
            }

            // check content elements timestamp
            /** @var ContentRepository $contentRepository */
            $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
            $newestContentElement = $contentRepository->findNewestByPid($uid, true);
            if ( !empty($newestContentElement) && $newestContentElement['tstamp'] > $this->lastRunStartTime) {
                $modified = true;
            }

            // remove unmodified pages
            if (!$modified) {
                unset($pageRecords[$uid]);
                foreach ($this->sysLanguages as $sysLang) {
                    unset($cachedPageRecords[$sysLang['uid']][$uid]);
                }
                $key = array_search($uid, $indexPids);
                if (false !== $key) {
                    unset($indexPids[$key]);
                }
            }
        }
    }

    /**
     * creates a rootline and searches for valid access restrictions
     * returns the access restrictions for the given page as an array:
     *    $accessRestrictions = array(
     *        'hidden' => 0,
     *        'fe_group' => ',
     *        'starttime' => 0,
     *        'endtime' => 0
     *    );
     * @param integer $currentPageUid
     * @return array
     */
    public function getInheritedAccessRestrictions($currentPageUid)
    {

        // get the rootline, start with the current page and go up
        $pageUid = $currentPageUid;
        $tempRootline = array(intval($this->cachedPageRecords[0][$currentPageUid]['pageUid'] ?? 0));
        while (($this->cachedPageRecords[0][$pageUid]['pid'] ?? 0) > 0) {
            $pageUid = intval($this->cachedPageRecords[0][$pageUid]['pid']);
            if (is_array($this->cachedPageRecords[0][$pageUid] ?? null)) {
                $tempRootline[] = $pageUid;
            }
        }

        // revert the ordering of the rootline so it starts with the
        // page at the top of the tree
        krsort($tempRootline);
        $rootline = array();
        foreach ($tempRootline as $pageUid) {
            $rootline[] = $pageUid;
        }

        // access restrictions:
        // a) hidden field
        // b) frontend groups
        // c) publishing and expiration date
        $inheritedAccessRestrictions = array(
            'hidden' => 0,
            'fe_group' => '',
            'starttime' => 0,
            'endtime' => 0
        );

        // collect inherited access restrictions
        // since now we have a full rootline of the current page
        // (0 = level 0, 1 = level 1 and so on),
        // we can fetch the access restrictions from pages above
        foreach ($rootline as $pageUid) {
            if ($this->cachedPageRecords[0][$pageUid]['extendToSubpages'] ?? false) {
                $inheritedAccessRestrictions['hidden'] = $this->cachedPageRecords[0][$pageUid]['hidden'];
                $inheritedAccessRestrictions['fe_group'] = $this->cachedPageRecords[0][$pageUid]['fe_group'];
                $inheritedAccessRestrictions['starttime'] = $this->cachedPageRecords[0][$pageUid]['starttime'];
                $inheritedAccessRestrictions['endtime'] = $this->cachedPageRecords[0][$pageUid]['endtime'];
            }
        }

        // use access restrictions of current page if set otherwise use
        // inherited access restrictions
        $accessRestrictions = array(
            'hidden' => $this->cachedPageRecords[0][$currentPageUid]['hidden']
                ? $this->cachedPageRecords[0][$currentPageUid]['hidden'] : $inheritedAccessRestrictions['hidden'],
            'fe_group' => $this->cachedPageRecords[0][$currentPageUid]['fe_group']
                ? $this->cachedPageRecords[0][$currentPageUid]['fe_group'] : $inheritedAccessRestrictions['fe_group'],
            'starttime' => $this->cachedPageRecords[0][$currentPageUid]['starttime']
                ? $this->cachedPageRecords[0][$currentPageUid]['starttime'] : $inheritedAccessRestrictions['starttime'],
            'endtime' => $this->cachedPageRecords[0][$currentPageUid]['endtime']
                ? $this->cachedPageRecords[0][$currentPageUid]['endtime'] : $inheritedAccessRestrictions['endtime'],
        );

        return $accessRestrictions;
    }

    private function processShortcuts($rows, $fields, $depth = 99)
    {
        if (--$depth === 0) {
            return $rows;
        }
        $processedRows = [];
        foreach ($rows as $row) {
            if ($row['CType'] !== 'shortcut') {
                $processedRows[] = $row;
                continue;
            }

            $recordList = GeneralUtility::trimExplode(',', $row['records'], true);
            foreach ($recordList as $recordIdentifier) {
                $split = BackendUtility::splitTable_Uid($recordIdentifier);
                $tableName = empty($split[0]) ? 'tt_content' : $split[0];
                $uid = (int)($split[1] ?? 0);

                if ($tableName !== 'tt_content' || $uid === 0) {
                    continue;
                }

                $queryBuilder = Db::getQueryBuilder($tableName);
                $where = [];
                $where[] = $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                );
                $where[] = $this->whereClauseForCType;

                $fieldArray = GeneralUtility::trimExplode(',', $fields);
                $referencedRow = $queryBuilder
                    ->select(...$fieldArray)
                    ->from($tableName)
                    ->where(...$where)
                    ->execute()
                    ->fetch();

	        if ($referencedRow) {
                    array_push($processedRows, ...$this->processShortcuts([$referencedRow], $fields, $depth));
                }
            }
        }
        return $processedRows;
    }

    /**
     * get content of current page and save data to db
     *
     * @param integer $uid page-UID that has to be indexed
     */
    public function getPageContent($uid)
    {
        // get content elements for this page
        $fields = 'uid, pid, header, bodytext, CType, sys_language_uid, header_layout, fe_group, file_collections, filelink_sorting, records';

        // If EXT:gridelements is installed, add the field containing the gridelement to the list
        if (ExtensionManagementUtility::isLoaded('gridelements')) {
            $fields .= ', tt_content.tx_gridelements_container';
        }

        // If EXT:container is installed, add the field containing the container id to the list
        if (ExtensionManagementUtility::isLoaded('container')) {
            $fields .= ', tt_content.tx_container_parent';
        }

        // hook to modify the page content fields
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPageContentFields'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPageContentFields'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyPageContentFields(
                    $fields,
                    $this
                );
            }
        }

        $table = 'tt_content';
        $queryBuilder = Db::getQueryBuilder($table);
        $where = [];
        $where[] = $queryBuilder->expr()->eq(
            'pid',
            $queryBuilder->createNamedParameter(
                $uid,
                \PDO::PARAM_INT
            )
        );
        $where[] = $this->whereClauseForCType;

        // Get access restrictions for this page, this access restrictions apply to all
        // content elements of this pages. Individual access restrictions
        // set for the content elements will be ignored. Use the content
        // element indexer if you need that feature!
        $pageAccessRestrictions = $this->getInheritedAccessRestrictions($uid);

        // add ke_search tags current page
        $tags = $this->pageRecords[intval($uid)]['tags'];

        // Compile content for this page from individual content elements with
        // respect to the language.
        // While doing so, fetch also content from attached files and write
        // their content directly to the index.
        $fieldArray = GeneralUtility::trimExplode(',', $fields);
        $ttContentRows = $queryBuilder
            ->select(...$fieldArray)
            ->from($table)
            ->where(...$where)
            ->execute()
            ->fetchAll();

        $pageContent = array();
        if (count($ttContentRows)) {
            $ttContentRows = $this->processShortcuts($ttContentRows, $fields);
            foreach ($ttContentRows as $ttContentRow) {

                // Skip content elements inside hidden containers and for other (custom) reasons
                if (!$this->contentElementShouldBeIndexed($ttContentRow)) {
                    continue;
                }

                $content = '';

                // index header
                // add header only if not set to "hidden", do not add header of html element
                if ($ttContentRow['header_layout'] != 100 && $ttContentRow['CType'] != 'html') {
                    $content .= strip_tags($ttContentRow['header']) . "\n";
                }

                // index content of this content element and find attached or linked files.
                // Attached files are saved as file references, the RTE links directly to
                // a file, thus we get file objects.
                // Files go into the index no matter if "index_content_with_restrictions" is set
                // or not, that means even if protected content elements do not go into the index,
                // files do. Since each file gets it's own index entry with correct access
                // restrictons, that's no problem from a access permission perspective (in fact, it's a feature).
                if (in_array($ttContentRow['CType'], $this->fileCTypes)) {
                    $fileObjects = $this->findAttachedFiles($ttContentRow);
                } else {
                    $fileObjects = $this->findLinkedFilesInRte($ttContentRow);
                    $content .= $this->getContentFromContentElement($ttContentRow) . "\n";
                }

                // index the files found
                if (!$pageAccessRestrictions['hidden'] && $this->checkIfpageShouldBeIndexed($uid, $this->pageRecords[intval($uid)]['sys_language_uid'])) {
                    $this->indexFiles($fileObjects, $ttContentRow, $pageAccessRestrictions['fe_group'], $tags);
                }

                // add content from this content element to page content
                // ONLY if this content element is not access protected
                // or protected content elements should go into the index
                // by configuration.
                if ($this->indexerConfig['index_content_with_restrictions'] == 'yes'
                    || $ttContentRow['fe_group'] == ''
                    || $ttContentRow['fe_group'] == '0'
                ) {
                    if (!isset($pageContent[$ttContentRow['sys_language_uid']])) {
                        $pageContent[$ttContentRow['sys_language_uid']] = '';
                    }
                    $pageContent[$ttContentRow['sys_language_uid']] .= $content;

                    // add content elements with sys_language_uid = -1 to all language versions of this page
                    if ($ttContentRow['sys_language_uid'] == -1) {
                        foreach ($this->sysLanguages as $sysLang) {
                            if ($sysLang['uid'] != -1) {
                                $pageContent[$sysLang['uid']] .= $content;
                            }
                        }
                    }
                }
            }
        } else {
            $this->counterWithoutContent++;
        }

        // make it possible to modify the indexerConfig via hook
        $additionalFields = array();
        $indexerConfig = $this->indexerConfig;

        // make it possible to modify the default values via hook
        $indexEntryDefaultValues = array(
            'type' => 'page',
            'uid' => $uid,
            'params' => '',
            'feGroupsPages' => $pageAccessRestrictions['fe_group'],
            'debugOnly' => false
        );

        // hook for custom modifications of the indexed data, e. g. the tags
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyPagesIndexEntry(
                    $uid,
                    $pageContent,
                    $tags,
                    $this->cachedPageRecords,
                    $additionalFields,
                    $indexerConfig,
                    $indexEntryDefaultValues,
                    $this
                );
            }
        }


        // store record in index table
        if (count($pageContent)) {
            foreach ($pageContent as $language_uid => $content) {
                $pageTitle = $this->cachedPageRecords[$language_uid][$uid]['title'] ?? '[empty title]';
                if (!$pageAccessRestrictions['hidden'] && $this->checkIfpageShouldBeIndexed($uid, $language_uid)) {
                    $this->pObj->logger->debug('Indexing page ' . $pageTitle . ' (UID ' . $uid . ', L ' . $language_uid . ')');
                    // overwrite access restrictions with language overlay values
                    $accessRestrictionsLanguageOverlay = $pageAccessRestrictions;
                    $pageAccessRestrictions['fe_group'] = $indexEntryDefaultValues['feGroupsPages'];
                    if ($language_uid > 0) {
                        if ($this->cachedPageRecords[$language_uid][$uid]['fe_group']) {
                            $accessRestrictionsLanguageOverlay['fe_group'] =
                                $this->cachedPageRecords[$language_uid][$uid]['fe_group'];
                        }
                        if ($this->cachedPageRecords[$language_uid][$uid]['starttime']) {
                            $accessRestrictionsLanguageOverlay['starttime'] =
                                $this->cachedPageRecords[$language_uid][$uid]['starttime'];
                        }
                        if ($this->cachedPageRecords[$language_uid][$uid]['endtime']) {
                            $accessRestrictionsLanguageOverlay['endtime'] =
                                $this->cachedPageRecords[$language_uid][$uid]['endtime'];
                        }
                    }

                    // use tx_kesearch_abstract instead of "abstract" if set
                    $abstract = (string)($this->cachedPageRecords[$language_uid][$uid]['tx_kesearch_abstract']
                        ?: $this->cachedPageRecords[$language_uid][$uid]['abstract']);

                    $this->pObj->storeInIndex(
                        $indexerConfig['storagepid'],                               // storage PID
                        $this->cachedPageRecords[$language_uid][$uid]['title'],     // page title
                        $indexEntryDefaultValues['type'],                           // content type
                        $indexEntryDefaultValues['uid'],                            // target PID / single view
                        $content,                        // indexed content, includes the title (linebreak after title)
                        $tags,                                                      // tags
                        $indexEntryDefaultValues['params'],                         // typolink params for singleview
                        $abstract,                                                  // abstract
                        $language_uid,                                              // language uid
                        $accessRestrictionsLanguageOverlay['starttime'],            // starttime
                        $accessRestrictionsLanguageOverlay['endtime'],              // endtime
                        $accessRestrictionsLanguageOverlay['fe_group'],             // fe_group
                        $indexEntryDefaultValues['debugOnly'],                      // debug only?
                        $additionalFields                                           // additional fields added by hooks
                    );
                    $this->counter++;
                } else {
                    $this->pObj->logger->debug('Skipping page ' . $pageTitle . ' (UID ' . $uid . ', L ' . $language_uid . ')');
                }
            }
        }
    }

    /**
     * Checks if the given row from tt_content should really be indexed by checking if the content element
     * sits inside a container (EXT:gridelements, EXT:container) and if this container is visible.
     *
     * @param $ttContentRow
     * @return bool
     */
    public function contentElementShouldBeIndexed($ttContentRow)
    {
        $contentElementShouldBeIndexed = true;

        // If gridelements is installed, check if the content element sits inside a gridelements container.
        // If yes, check if the container is hidden or placed outside the page (colPos: -2).
        // This adds a query for each content element which may result in slow indexing. But simply
        // joining the tt_content table to itself does not work either, since then all content elements which
        // are not located inside a gridelement won't be indexed then.
        if (ExtensionManagementUtility::isLoaded('gridelements') && $ttContentRow['tx_gridelements_container']) {
            $queryBuilder = Db::getQueryBuilder('tt_content');
            $gridelementsContainer = $queryBuilder
                ->select(...['colPos','hidden'])
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($ttContentRow['tx_gridelements_container'])
                    )
                )
                ->execute()
                ->fetch();

            // If there's no gridelement container found, it means it is hidden or deleted or time restricted.
            // In this case, skip the content element.
            if ($gridelementsContainer === FALSE) {
                $contentElementShouldBeIndexed = false;
            } else {

                // If the colPos of the gridelement container is -2, it is not on the page, so skip it.
                if ($gridelementsContainer['colPos'] === -2) {
                    $contentElementShouldBeIndexed = false;
                }

            }
        }

        // If EXT:container is installed, check if the content element sits inside a container element
        if (ExtensionManagementUtility::isLoaded('container') && $ttContentRow['tx_container_parent']) {
            $queryBuilder = Db::getQueryBuilder('tt_content');
            $container = $queryBuilder
                ->select('uid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($ttContentRow['tx_container_parent'])
                    )
                )
                ->execute()
                ->fetch();

            // If there's no container found, it means it is hidden or deleted or time restricted.
            // In this case, skip the content element.
            $contentElementShouldBeIndexed = !($container === FALSE);
        }

        // hook to add custom check if this content element should be indexed
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['contentElementShouldBeIndexed'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['contentElementShouldBeIndexed'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $contentElementShouldBeIndexed = $_procObj->contentElementShouldBeIndexed(
                    $ttContentRow,
                    $contentElementShouldBeIndexed,
                    $this
                );
            }
        }

        return $contentElementShouldBeIndexed;
    }

    /**
     * Checks if the given page should go to the index.
     * Checks the doktype and flags like "hidden", "no_index" and versioning.
     *
     * are set.
     *
     * @param integer $uid
     * @param integer $language_uid
     * @return boolean
     */
    public function checkIfpageShouldBeIndexed($uid, $language_uid)
    {
        $index = true;

        if ($this->cachedPageRecords[$language_uid][$uid]['hidden'] ?? false) {
            $index = false;
        }

        if ($this->cachedPageRecords[$language_uid][$uid]['no_search'] ?? false) {
            $index = false;
        }

        if (!in_array($this->cachedPageRecords[$language_uid][$uid]['doktype'] ?? 0, $this->indexDokTypes)) {
            $index = false;
        }

        if ((int) $language_uid === 0 && GeneralUtility::hideIfDefaultLanguage($this->cachedPageRecords[$language_uid][$uid]['l18n_cfg'])) {
            $index = false;
        }

        if (!empty($this->cachedPageRecords[$language_uid][$uid]) && !$this->recordIsLive($this->cachedPageRecords[$language_uid][$uid])) {
            $index = false;
        }

        return $index;
    }

    /**
     * combine group access restrictons from page(s) and content element
     * @param string $feGroupsPages comma list
     * @param string $feGroupsContentElement comma list
     * @return string
     * @author Christian Bülter
     * @since 26.09.13
     */
    public function getCombinedFeGroupsForContentElement($feGroupsPages, $feGroupsContentElement)
    {
        // combine frontend groups from page(s) and content element as follows
        // 1. if page has no groups, but ce has groups, use ce groups
        // 2. if ce has no groups, but page has groups, use page groups
        // 3. if page has "show at any login" (-2) and ce has groups, use ce groups
        // 4. if ce has "show at any login" (-2) and page has groups, use page groups
        // 5. if page and ce have explicit groups (not "hide at login" (-1), merge them (use only groups both have)
        // 6. if page or ce has "hide at login" and the other
        // has an explicit group the element will never be shown and we must not index it.
        // So which group do we set here? Let's use a constant for that and check in the calling function for that.

        $feGroups = '';

        if (!$feGroupsPages && $feGroupsContentElement) {
            $feGroups = $feGroupsContentElement;
        }

        if ($feGroupsPages && !$feGroupsContentElement) {
            $feGroups = $feGroupsPages;
        }

        if ($feGroupsPages == '-2' && $feGroupsContentElement) {
            $feGroups = $feGroupsContentElement;
        }

        if ($feGroupsPages && $feGroupsContentElement == '-2') {
            $feGroups = $feGroupsPages;
        }

        if ($feGroupsPages && $feGroupsContentElement && $feGroupsPages != '-1' && $feGroupsContentElement != '-1') {
            $feGroupsContentElementArray = GeneralUtility::intExplode(
                ',',
                $feGroupsContentElement
            );
            $feGroupsPagesArray = GeneralUtility::intExplode(',', $feGroupsPages);
            $feGroups = implode(',', array_intersect($feGroupsContentElementArray, $feGroupsPagesArray));
        }

        if (($feGroupsContentElement
                && $feGroupsContentElement != '-1'
                && $feGroupsContentElement != -2
                && $feGroupsPages == '-1')
            ||
            ($feGroupsPages && $feGroupsPages != '-1' && $feGroupsPages != -2 && $feGroupsContentElement == '-1')
        ) {
            $feGroups = DONOTINDEX;
        }

        return $feGroups;
    }

    /**
     * Extracts content from files given (as array of file objects or file reference objects)
     * and writes the content to the index
     * @param array $fileObjects
     * @param array $ttContentRow
     * @param string $feGroupsPages comma list
     * @param string $tags string
     * @author Christian Bülter
     * @since 25.09.13
     */
    public function indexFiles($fileObjects, $ttContentRow, $feGroupsPages, $tags)
    {
        // combine group access restrictions from page(s) and content element
        $feGroups = $this->getCombinedFeGroupsForContentElement($feGroupsPages, $ttContentRow['fe_group']);

        if (count($fileObjects) && $feGroups != DONOTINDEX) {
            // loop through files
            foreach ($fileObjects as $fileObject) {
                $isHidden = false;
                $isInList = false;
                if ($fileObject instanceof FileInterface) {
                    $isHidden = $fileObject->hasProperty('hidden') && $fileObject->getProperty('hidden') === 1;
                    $isInList = GeneralUtility::inList(
                        $this->indexerConfig['fileext'],
                        $fileObject->getExtension()
                    );
                } else {
                    $errorMessage = 'Could not index file in content element #' . $ttContentRow['uid'] . ' (no file object).';
                    $this->pObj->logger->warning($errorMessage);
                    $this->addError($errorMessage);
                }

                // check if the file extension fits in the list of extensions
                // to index defined in the indexer configuration
                if (!$isHidden && $isInList) {
                    // get file path and URI
                    $filePath = $fileObject->getForLocalProcessing(false);

                    /* @var $fileIndexerObject File */
                    $fileIndexerObject = GeneralUtility::makeInstance(File::class, $this->pObj);

                    // add tags from linking page to this index record?
                    if (!$this->indexerConfig['index_use_page_tags_for_files']) {
                        $tags = '';
                    }

                    // add tag to identify this index record as file
                    SearchHelper::makeTags($tags, array('file'));

                    // get file information and  file content (using external tools)
                    // write file data to the index as a seperate index entry
                    // count indexed files, add it to the indexer output
                    if (!file_exists($filePath)) {
                        $errorMessage = 'Could not index file ' . $filePath . ' in content element #' . $ttContentRow['uid'] . ' (file does not exist).';
                        $this->pObj->logger->warning($errorMessage);
                        $this->addError($errorMessage);
                    } else {
                        if ($fileIndexerObject->fileInfo->setFile($fileObject)) {
                            if (($content = $fileIndexerObject->getFileContent($filePath))) {
                                $this->storeFileContentToIndex(
                                    $fileObject,
                                    $content,
                                    $fileIndexerObject,
                                    $feGroups,
                                    $tags,
                                    $ttContentRow
                                );
                                $this->fileCounter++;
                            } else {
                                $this->addError($fileIndexerObject->getErrors());
                                $errorMessage = 'Could not index file ' . $filePath . '.';
                                $this->pObj->logger->warning($errorMessage);
                                $this->addError($errorMessage);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Finds files attached to "uploads" content elements
     * returns them as file reference objects array
     * @author Christian Bülter
     * @since 24.09.13
     * @param array $ttContentRow content element
     * @return array
     */
    public function findAttachedFiles($ttContentRow)
    {
        // Set current data
        $this->cObj->data = $ttContentRow;

        // Get files by filesProcessor
        $processedData = [];
        $processedData = $this->filesProcessor->process($this->cObj, [], $this->filesProcessorConfiguration, $processedData);
        $fileReferenceObjects = $processedData['files'];

        return $fileReferenceObjects;
    }


    /**
     * Finds files linked in rte text
     * returns them as array of file objects
     * @param array $ttContentRow content element
     * @return array
     * @author Christian Bülter
     * @since 24.09.13
     */
    public function findLinkedFilesInRte($ttContentRow)
    {
        $fileObjects = array();
        // check if there are links to files in the rte text
        /* @var $rteHtmlParser RteHtmlParser */
        $rteHtmlParser = GeneralUtility::makeInstance(RteHtmlParser::class);

        /** @var LinkService $linkService */
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $blockSplit = $rteHtmlParser->splitIntoBlock('A', (string)$ttContentRow['bodytext'], 1);
        foreach ($blockSplit as $k => $v) {
            list($attributes) = $rteHtmlParser->get_tag_attributes($rteHtmlParser->getFirstTag($v), true);
            if (!empty($attributes['href'])) {
                try {
                    $hrefInformation = $linkService->resolve($attributes['href']);
                    if ($hrefInformation['type'] === LinkService::TYPE_FILE) {
                        $fileObjects[] = $hrefInformation['file'];
                    }
                } catch (Exception $exception) {
                    $this->pObj->logger->error($exception->getMessage());
                }
            }
        }

        return $fileObjects;
    }


    /**
     * Store the file content and additional information to the index
     * @param $fileObject File reference object or file object
     * @param string $content file text content
     * @param File $fileIndexerObject
     * @param string $feGroups comma list of groups to assign
     * @param array $ttContentRow tt_content element the file was assigned to
     * @author Christian Bülter
     * @since 25.09.13
     */
    public function storeFileContentToIndex($fileObject, $content, $fileIndexerObject, $feGroups, $tags, $ttContentRow)
    {
        // get metadata
        if ($fileObject instanceof FileReference) {
            $orig_uid = $fileObject->getOriginalFile()->getUid();
            $metadata = $fileObject->getOriginalFile()->getMetaData()->get();
        } else {
            $orig_uid = $fileObject->getUid();
            $metadata = $fileObject->getMetaData()->get();
        }

        if (isset($metadata['fe_groups']) && !empty($metadata['fe_groups'])) {
            if ($feGroups) {
                $feGroupsContentArray = GeneralUtility::intExplode(',', $feGroups);
                $feGroupsFileArray = GeneralUtility::intExplode(',', $metadata['fe_groups']);
                $feGroups = implode(',', array_intersect($feGroupsContentArray, $feGroupsFileArray));
            } else {
                $feGroups = $metadata['fe_groups'];
            }
        }

        // assign categories as tags (as cleartext, eg. "colorblue")
        $categories = SearchHelper::getCategories($metadata['uid'], 'sys_file_metadata');
        SearchHelper::makeTags($tags, $categories['title_list']);

        // assign categories as generic tags (eg. "syscat123")
        SearchHelper::makeSystemCategoryTags($tags, $metadata['uid'], 'sys_file_metadata');

        if ($metadata['title']) {
            $content = $metadata['title'] . "\n" . $content;
        }

        $abstract = '';
        if ($metadata['description']) {
            $abstract = $metadata['description'];
            $content = $metadata['description'] . "\n" . $content;
        }

        if ($metadata['alternative']) {
            $content .= "\n" . $metadata['alternative'];
        }

        $title = $fileIndexerObject->fileInfo->getName();
        $storagePid = $this->indexerConfig['storagepid'];
        $type = 'file:' . $fileObject->getExtension();

        $additionalFields = array(
            'sortdate' => $fileIndexerObject->fileInfo->getModificationTime(),
            'orig_uid' => $orig_uid,
            'orig_pid' => 0,
            'directory' => $fileIndexerObject->fileInfo->getAbsolutePath(),
            'hash' => $fileIndexerObject->getUniqueHashForFile()
        );

        //hook for custom modifications of the indexed data, e. g. the tags
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFileIndexEntryFromContentIndexer'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFileIndexEntryFromContentIndexer'] as
                     $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyFileIndexEntryFromContentIndexer(
                    $fileObject,
                    $content,
                    $fileIndexerObject,
                    $feGroups,
                    $ttContentRow,
                    $storagePid,
                    $title,
                    $tags,
                    $abstract,
                    $additionalFields
                );
            }
        }

        // Store record in index table:
        // Add usergroup restrictions of the page and the
        // content element to the index data.
        // Add time restrictions to the index data.
        $this->pObj->storeInIndex(
            $storagePid,                             // storage PID
            $title,                                  // file name
            $type,                                   // content type
            $ttContentRow['pid'],                    // target PID: where is the single view?
            $content,                                // indexed content
            $tags,                                   // tags
            '',                                      // typolink params for singleview
            $abstract,                               // abstract
            $ttContentRow['sys_language_uid'],       // language uid
            $ttContentRow['starttime'] ?? 0,              // starttime
            $ttContentRow['endtime'] ?? 0,                // endtime
            $feGroups,                               // fe_group
            false,                                   // debug only?
            $additionalFields                        // additional fields added by hooks
        );
    }

    /**
     * Extracts content from content element and returns it as plain text
     * for writing it directly to the index
     * @author Christian Bülter
     * @since 24.09.13
     * @param array $ttContentRow content element
     * @return string
     */
    public function getContentFromContentElement($ttContentRow)
    {
        // bodytext
        $bodytext = (string)$ttContentRow['bodytext'];

        // following lines prevents having words one after the other like: HelloAllTogether
        $bodytext = str_replace('<td', ' <td', $bodytext);
        $bodytext = str_replace('<br', ' <br', $bodytext);
        $bodytext = str_replace('<p', ' <p', $bodytext);
        $bodytext = str_replace('<li', ' <li', $bodytext);

        if ($ttContentRow['CType'] == 'table') {
            // replace table dividers with whitespace
            $bodytext = str_replace('|', ' ', $bodytext);
        }

        // remove script and style tags
        // thanks to the wordpress project
        // https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/formatting.php#L5178
        $bodytext = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $bodytext );

        // remove other tags
        $bodytext = strip_tags($bodytext);

        // hook for modifiying a content elements content
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentFromContentElement'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentFromContentElement'] as
                     $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyContentFromContentElement(
                    $bodytext,
                    $ttContentRow,
                    $this
                );
            }
        }

        return $bodytext;
    }
}
