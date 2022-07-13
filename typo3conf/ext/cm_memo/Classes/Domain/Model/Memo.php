<?php

declare(strict_types=1);

namespace Cm\CmMemo\Domain\Model;


/**
 * This file is part of the "memo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Timo Nieke <s4tiniek@uni-trier.de>, Trier University
 */

/**
 * Memo
 */
class Memo extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * timestamp
     *
     * @var int
     */
    protected $timestamp = 0;

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * text
     *
     * @var string
     */
    protected $text = '';

    /**
     * Returns the timestamp
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Sets the timestamp
     *
     * @param int $timestamp
     * @return void
     */
    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns the text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text
     *
     * @param string $text
     * @return void
     */
    public function setText(string $text)
    {
        $this->text = $text;
    }
}
