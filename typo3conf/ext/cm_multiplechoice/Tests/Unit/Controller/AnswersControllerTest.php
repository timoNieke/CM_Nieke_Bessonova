<?php

declare(strict_types=1);

namespace Cm\CmMultiplechoice\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Test case
 *
 * @author Timo Nieke <s4tiniek@uni-trier.de>
 */
class AnswersControllerTest extends UnitTestCase
{
    /**
     * @var \Cm\CmMultiplechoice\Controller\AnswersController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\Cm\CmMultiplechoice\Controller\AnswersController::class))
            ->onlyMethods(['redirect', 'forward', 'addFlashMessage'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function listActionFetchesAllAnswersFromRepositoryAndAssignsThemToView(): void
    {
        $allAnswers = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $answersRepository = $this->getMockBuilder(\::class)
            ->onlyMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $answersRepository->expects(self::once())->method('findAll')->will(self::returnValue($allAnswers));
        $this->subject->_set('answersRepository', $answersRepository);

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('answers', $allAnswers);
        $this->subject->_set('view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenAnswersToView(): void
    {
        $answers = new \Cm\CmMultiplechoice\Domain\Model\Answers();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('answers', $answers);

        $this->subject->showAction($answers);
    }
}
