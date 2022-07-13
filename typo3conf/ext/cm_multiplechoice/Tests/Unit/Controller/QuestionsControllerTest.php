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
class QuestionsControllerTest extends UnitTestCase
{
    /**
     * @var \Cm\CmMultiplechoice\Controller\QuestionsController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\Cm\CmMultiplechoice\Controller\QuestionsController::class))
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
    public function listActionFetchesAllQuestionsFromRepositoryAndAssignsThemToView(): void
    {
        $allQuestions = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $questionsRepository = $this->getMockBuilder(\Cm\CmMultiplechoice\Domain\Repository\QuestionsRepository::class)
            ->onlyMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $questionsRepository->expects(self::once())->method('findAll')->will(self::returnValue($allQuestions));
        $this->subject->_set('questionsRepository', $questionsRepository);

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('questions', $allQuestions);
        $this->subject->_set('view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenQuestionsToView(): void
    {
        $questions = new \Cm\CmMultiplechoice\Domain\Model\Questions();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('questions', $questions);

        $this->subject->showAction($questions);
    }
}
