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
class AnswersTest extends UnitTestCase
{
    /**
     * @var \Cm\CmMultiplechoice\Domain\Model\Answers|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(
            \Cm\CmMultiplechoice\Domain\Model\Answers::class,
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
    public function getAnswerReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getAnswer()
        );
    }

    /**
     * @test
     */
    public function setAnswerForStringSetsAnswer(): void
    {
        $this->subject->setAnswer('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->_get('answer'));
    }

    /**
     * @test
     */
    public function getCorrectReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getCorrect());
    }

    /**
     * @test
     */
    public function setCorrectForBoolSetsCorrect(): void
    {
        $this->subject->setCorrect(true);

        self::assertEquals(true, $this->subject->_get('correct'));
    }
}
