<?php

namespace Tpwd\KeSearch\Indexer\Filetypes;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Stefan Froemken
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
 * ************************************************************* */

use Tpwd\KeSearch\Indexer\IndexerRunner;
use Tpwd\KeSearch\Lib\Fileinfo;
use Tpwd\KeSearch\Indexer\Types\File;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Pdf extends File implements FileIndexerInterface
{

    public array $extConf = array();
    public array $app = array(); // saves the path to the executables
    public bool $isAppArraySet = false;

    /** @var IndexerRunner */
    public $pObj;

    /**
     * class constructor
     *
     * @param \Tpwd\KeSearch\Indexer\IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        $this->pObj = $pObj;

        // get extension configuration of ke_search_hooks
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ke_search');

        // check if needed system tools pdftotext and pdfinfo exist
        if ($this->extConf['pathPdftotext']) {
            $pathPdftotext = rtrim($this->extConf['pathPdftotext'], '/') . '/';
            $pathPdfinfo = rtrim($this->extConf['pathPdfinfo'], '/') . '/';

            $exe = Environment::isWindows() ? '.exe' : '';
            if ((is_executable($pathPdftotext . 'pdftotext' . $exe)
                && is_executable($pathPdfinfo . 'pdfinfo' . $exe))
            ) {
                $this->app['pdfinfo'] = $pathPdfinfo . 'pdfinfo' . $exe;
                $this->app['pdftotext'] = $pathPdftotext . 'pdftotext' . $exe;
                $this->isAppArraySet = true;
            } else {
                $this->isAppArraySet = false;
            }
        } else {
            $this->isAppArraySet = false;
        }

        if (!$this->isAppArraySet) {
            $errorMessage = 'The path to pdftools is not correctly set in the '
                . 'extension configuration. You can get the path with "which pdfinfo" or "which pdftotext".';
            $pObj->logger->error($errorMessage);
            $this->addError($errorMessage);
        }
    }

    /**
     * get Content of PDF file
     * @param string $file
     * @return string The extracted content of the file
     */
    public function getContent($file)
    {
        $this->fileInfo = GeneralUtility::makeInstance(Fileinfo::class);
        $this->fileInfo->setFile($file);

        // get PDF informations
        if (!$pdfInfo = $this->getPdfInfo($file)) {
            return false;
        }

        // proceed only of there are any pages found
        if ((int)$pdfInfo['pages'] && $this->isAppArraySet) {
            // create the tempfile which will contain the content
            $tempFileName = GeneralUtility::tempnam('pdf_files-Indexer');

            // Delete if exists, just to be safe.
            @unlink($tempFileName);

            // generate and execute the pdftotext commandline tool
            $fileEscaped = CommandUtility::escapeShellArgument($file);
            $cmd = "{$this->app['pdftotext']} -enc UTF-8 -q $fileEscaped $tempFileName";

            CommandUtility::exec($cmd);

            // check if the tempFile was successfully created
            if (@is_file($tempFileName)) {
                $content = GeneralUtility::getUrl($tempFileName);
                unlink($tempFileName);
            } else {
                $errorMessage = 'Content for file ' . $file . ' could not be extracted. Maybe it is encrypted?';
                $this->pObj->logger->warning($errorMessage);
                $this->addError($errorMessage);

                // return empty string if no content was found
                $content = '';
            }
            // sanitize content
            $content = $this->removeReplacementChar($content);

            return $this->removeEndJunk($content);
        } else {
            return false;
        }
    }

    /**
     * execute commandline tool pdfinfo to extract pdf informations from file
     * @param string $file
     * @return array The pdf informations as array
     */
    public function getPdfInfo($file)
    {
        if ($this->fileInfo->getIsFile()
            && $this->fileInfo->getExtension() == 'pdf'
            && $this->isAppArraySet
        ) {
            $fileEscaped = CommandUtility::escapeShellArgument($file);
            $cmd = "{$this->app['pdfinfo']} $fileEscaped";
            CommandUtility::exec($cmd, $pdfInfoArray);
            $pdfInfo = $this->splitPdfInfo($pdfInfoArray);

            return $pdfInfo;
        }

        return false;
    }

    /**
     * Analysing PDF info into a useable format.
     * @param array $pdfInfoArray Data of PDF content, coming from the pdfinfo tool
     * @return array The pdf informations as array in a useable format
     */
    public function splitPdfInfo($pdfInfoArray)
    {
        $res = array();
        if (is_array($pdfInfoArray)) {
            foreach ($pdfInfoArray as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) > 1 && trim($parts[0])) {
                    $res[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
            }
        }
        return $res;
    }

    /**
     * Removes some strange char(12) characters and line breaks that then to
     * occur in the end of the string from external files.
     * @param string String to clean up
     * @return string Cleaned up string
     */
    public function removeEndJunk($string)
    {
        $string = preg_replace('@\x{FFFD}@u', '', $string);
        return trim(preg_replace('/[' . LF . chr(12) . ']*$/', '', $string));
    }

    /**
     * Remove (U+FFFD)� characters due to incorrect image indexing in PDF file
     * @param string String to clean up
     * @return string Cleaned up string
     */
    public function removeReplacementChar($string)
    {
        return trim(preg_replace('@\x{FFFD}@u', '', $string));
    }
}