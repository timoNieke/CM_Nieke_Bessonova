<?php

declare(strict_types=1);

namespace Cm\CmMemo\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Test case
 *
 * @author Timo Nieke <s4tiniek@uni-trier.de>
 */
class MemoControllerTest extends UnitTestCase
{
    /**
     * @var \Cm\CmMemo\Controller\MemoController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\Cm\CmMemo\Controller\MemoController::class))
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
    public function listActionFetchesAllMemosFromRepositoryAndAssignsThemToView(): void
    {
        $allMemos = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $memoRepository = $this->getMockBuilder(\Cm\CmMemo\Domain\Repository\MemoRepository::class)
            ->onlyMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $memoRepository->expects(self::once())->method('findAll')->will(self::returnValue($allMemos));
        $this->subject->_set('memoRepository', $memoRepository);

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('memos', $allMemos);
        $this->subject->_set('view', $view);

        $this->subject->listAction();
    }

    /**
     * @test
     */
    public function showActionAssignsTheGivenMemoToView(): void
    {
        $memo = new \Cm\CmMemo\Domain\Model\Memo();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('memo', $memo);

        $this->subject->showAction($memo);
    }

    /**
     * @test
     */
    public function createActionAddsTheGivenMemoToMemoRepository(): void
    {
        $memo = new \Cm\CmMemo\Domain\Model\Memo();

        $memoRepository = $this->getMockBuilder(\Cm\CmMemo\Domain\Repository\MemoRepository::class)
            ->onlyMethods(['add'])
            ->disableOriginalConstructor()
            ->getMock();

        $memoRepository->expects(self::once())->method('add')->with($memo);
        $this->subject->_set('memoRepository', $memoRepository);

        $this->subject->createAction($memo);
    }

    /**
     * @test
     */
    public function editActionAssignsTheGivenMemoToView(): void
    {
        $memo = new \Cm\CmMemo\Domain\Model\Memo();

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $this->subject->_set('view', $view);
        $view->expects(self::once())->method('assign')->with('memo', $memo);

        $this->subject->editAction($memo);
    }

    /**
     * @test
     */
    public function updateActionUpdatesTheGivenMemoInMemoRepository(): void
    {
        $memo = new \Cm\CmMemo\Domain\Model\Memo();

        $memoRepository = $this->getMockBuilder(\Cm\CmMemo\Domain\Repository\MemoRepository::class)
            ->onlyMethods(['update'])
            ->disableOriginalConstructor()
            ->getMock();

        $memoRepository->expects(self::once())->method('update')->with($memo);
        $this->subject->_set('memoRepository', $memoRepository);

        $this->subject->updateAction($memo);
    }

    /**
     * @test
     */
    public function deleteActionRemovesTheGivenMemoFromMemoRepository(): void
    {
        $memo = new \Cm\CmMemo\Domain\Model\Memo();

        $memoRepository = $this->getMockBuilder(\Cm\CmMemo\Domain\Repository\MemoRepository::class)
            ->onlyMethods(['remove'])
            ->disableOriginalConstructor()
            ->getMock();

        $memoRepository->expects(self::once())->method('remove')->with($memo);
        $this->subject->_set('memoRepository', $memoRepository);

        $this->subject->deleteAction($memo);
    }
}
