<?php

namespace Tpwd\KeSearch\Indexer\Filetypes;

/* * *************************************************************
 *  Copyright notice
 *
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Armin Vieweg
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Pptx extends File implements FileIndexerInterface
{
    /**
     * class constructor
     */
    public function __construct()
    {
        // without overwriting __construct, the parent class would expect one param ($pObj)
        // which occures exception in Classes/indexer/types/class.tx_kesearch_indexer_types_file.php:224 (makeInstance)
        // may break with more strict php settings
    }

    /**
     * get Content of PPTX file
     * @param string $file
     * @return string The extracted content of the file
     */
    public function getContent($file)
    {
        /** @var \Tpwd\KeSearch\Utility\OoxmlConversion $reader */
        $reader = GeneralUtility::makeInstance(\Tpwd\KeSearch\Utility\OoxmlConversion::class, $file);

        try {
            return trim($reader->convertToText());
        } catch (\Exception $e) {
            $this->pObj->logger->error($e->getMessage());
        }
    }
}
