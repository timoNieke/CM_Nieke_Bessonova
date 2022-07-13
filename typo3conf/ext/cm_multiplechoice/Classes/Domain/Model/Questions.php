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
 * Questions
 */
class Questions extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * question
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $question = '';

    /**
     * questionanswer
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Cm\CmMultiplechoice\Domain\Model\Answers>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $questionanswer = null;

    /**
     * __construct
     */
    public function __construct()
    {

        // Do not remove the next line: It would break the functionality
        $this->initializeObject();
    }

    /**
     * Initializes all ObjectStorage properties when model is reconstructed from DB (where __construct is not called)
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->questionanswer = $this->questionanswer ?: new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the question
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Sets the question
     *
     * @param string $question
     * @return void
     */
    public function setQuestion(string $question)
    {
        $this->question = $question;
    }

    /**
     * Adds a Answers
     *
     * @param \Cm\CmMultiplechoice\Domain\Model\Answers $questionanswer
     * @return void
     */
    public function addQuestionanswer(\Cm\CmMultiplechoice\Domain\Model\Answers $questionanswer)
    {
        $this->questionanswer->attach($questionanswer);
    }

    /**
     * Removes a Answers
     *
     * @param \Cm\CmMultiplechoice\Domain\Model\Answers $questionanswerToRemove The Answers to be removed
     * @return void
     */
    public function removeQuestionanswer(\Cm\CmMultiplechoice\Domain\Model\Answers $questionanswerToRemove)
    {
        $this->questionanswer->detach($questionanswerToRemove);
    }

    /**
     * Returns the questionanswer
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Cm\CmMultiplechoice\Domain\Model\Answers>
     */
    public function getQuestionanswer()
    {
        return $this->questionanswer;
    }

    /**
     * Sets the questionanswer
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Cm\CmMultiplechoice\Domain\Model\Answers> $questionanswer
     * @return void
     */
    public function setQuestionanswer(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $questionanswer)
    {
        $this->questionanswer = $questionanswer;
    }
}
