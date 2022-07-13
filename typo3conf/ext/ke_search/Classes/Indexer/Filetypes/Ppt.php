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
class Ppt extends File implements FileIndexerInterface
{

    public array $extConf = array();
    public array $app = array(); // saves the path to the executables
    public bool $isAppArraySet = false;

    /**
     * class constructor
     *
     * @param \Tpwd\KeSearch\Indexer\IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        // get extension configuration of ke_search
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ke_search');

        // check if path to catppt is correct
        if ($this->extConf['pathCatdoc']) {
            $pathCatdoc = rtrim($this->extConf['pathCatdoc'], '/') . '/';

            $exe = Environment::isWindows() ? '.exe' : '';
            if (is_executable($pathCatdoc . 'catppt' . $exe)) {
                $this->app['catppt'] = $pathCatdoc . 'catppt' . $exe;
                $this->isAppArraySet = true;
            } else {
                $this->isAppArraySet = false;
            }
        } else {
            $this->isAppArraySet = false;
        }

        if (!$this->isAppArraySet) {
            $errorMessage = 'The path to catppttools is not correctly set in '
                . 'extension configuration. You can get the path with "which catppt".';
            $pObj->logger->error($errorMessage);
            $this->addError($errorMessage);
        }
    }

    /**
     * get Content of PPT file
     * @param string $file
     * @return string The extracted content of the file
     */
    public function getContent($file)
    {
        // create the tempfile which will contain the content
        $tempFileName = GeneralUtility::tempnam('ppt_files-Indexer');

        // Delete if exists, just to be safe.
        @unlink($tempFileName);

        // generate and execute the pdftotext commandline tool
        $fileEscaped = CommandUtility::escapeShellArgument($file);
        $cmd = "{$this->app['catppt']} -s8859-1 -dutf-8 $fileEscaped > $tempFileName";
        CommandUtility::exec($cmd);

        // check if the tempFile was successfully created
        if (@is_file($tempFileName)) {
            $content = GeneralUtility::getUrl($tempFileName);
            unlink($tempFileName);
        } else {
            return false;
        }

        // check if content was found
        if (strlen($content)) {
            return $content;
        } else {
            return false;
        }
    }
}
