<?php

declare(strict_types=1);

namespace Cm\CmMultiplechoice\Tests\Unit\Domain\Model;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 *
 * @author Timo Nieke <s4tiniek@uni-trier.de>
 */
class QuestionsTest extends UnitTestCase
{
    /**
     * @var \Cm\CmMultiplechoice\Domain\Model\Questions|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(
            \Cm\CmMultiplechoice\Domain\Model\Questions::class,
            ['dummy']
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getQuestionReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getQuestion()
        );
    }

    /**
     * @test
     */
    public function setQuestionForStringSetsQuestion(): void
    {
        $this->subject->setQuestion('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->_get('question'));
    }

    /**
     * @test
     */
    public function getQuestionanswerReturnsInitialValueForAnswers(): void
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getQuestionanswer()
        );
    }

    /**
     * @test
     */
    public function setQuestionanswerForObjectStorageContainingAnswersSetsQuestionanswer(): void
    {
        $questionanswer = new \Cm\CmMultiplechoice\Domain\Model\Answers();
        $objectStorageHoldingExactlyOneQuestionanswer = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneQuestionanswer->attach($questionanswer);
        $this->subject->setQuestionanswer($objectStorageHoldingExactlyOneQuestionanswer);

        self::assertEquals($objectStorageHoldingExactlyOneQuestionanswer, $this->subject->_get('questionanswer'));
    }

    /**
     * @test
     */
    public function addQuestionanswerToObjectStorageHoldingQuestionanswer(): void
    {
        $questionanswer = new \Cm\CmMultiplechoice\Domain\Model\Answers();
        $questionanswerObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $questionanswerObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($questionanswer));
        $this->subject->_set('questionanswer', $questionanswerObjectStorageMock);

        $this->subject->addQuestionanswer($questionanswer);
    }

    /**
     * @test
     */
    public function removeQuestionanswerFromObjectStorageHoldingQuestionanswer(): void
    {
        $questionanswer = new \Cm\CmMultiplechoice\Domain\Model\Answers();
        $questionanswerObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $questionanswerObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($questionanswer));
        $this->subject->_set('questionanswer', $questionanswerObjectStorageMock);

        $this->subject->removeQuestionanswer($questionanswer);
    }
}
