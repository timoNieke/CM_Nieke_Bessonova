<?php

declare(strict_types=1);

namespace Cm\CmMultiplechoice\Domain\Model;


/**
 * This file is part of the "CM Multiple Choice" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Timo Nieke <s4tiniek@uni-trier.de>, Uni Trier
 */


/**
 * Answers
 */
class Answers extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * answer
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $answer = '';

    /**
     * correct
     *
     * @var bool
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $correct = false;

    /**
     * Returns the answer
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Sets the answer
     *
     * @param string $answer
     * @return void
     */
    public function setAnswer(string $answer)
    {
        $this->answer = $answer;
    }

    /**
     * Returns the correct
     *
     * @return bool
     */
    public function getCorrect()
    {
        return $this->correct;
    }

    /**
     * Sets the correct
     *
     * @param bool $correct
     * @return void
     */
    public function setCorrect(bool $correct)
    {
        $this->correct = $correct;
    }

    /**
     * Returns the boolean state of correct
     *
     * @return bool
     */
    public function isCorrect()
    {
        return $this->correct;
    }
}
