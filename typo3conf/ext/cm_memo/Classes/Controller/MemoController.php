<?php

declare(strict_types=1);

namespace Cm\CmMemo\Controller;


/**
 * This file is part of the "memo" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Timo Nieke <s4tiniek@uni-trier.de>, Trier University
 */

/**
 * MemoController
 */
class MemoController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * memoRepository
     *
     * @var \Cm\CmMemo\Domain\Repository\MemoRepository
     */
    protected $memoRepository = null;

    /**
     * action list
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        $memos = $this->memoRepository->findAll();
        $this->view->assign('memos', $memos);
        return $this->htmlResponse();
    }

    /**
     * action show
     *
     * @param \Cm\CmMemo\Domain\Model\Memo $memo
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function showAction(\Cm\CmMemo\Domain\Model\Memo $memo): \Psr\Http\Message\ResponseInterface
    {
        $this->view->assign('memo', $memo);
        return $this->htmlResponse();
    }

    /**
     * action new
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function newAction(): \Psr\Http\Message\ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * action create
     *
     * @param \Cm\CmMemo\Domain\Model\Memo $newMemo
     */
    public function createAction(\Cm\CmMemo\Domain\Model\Memo $newMemo)
    {
        $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See https://docs.typo3.org/p/friendsoftypo3/extension-builder/master/en-us/User/Index.html', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        $this->memoRepository->add($newMemo);
        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param \Cm\CmMemo\Domain\Model\Memo $memo
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("memo")
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editAction(\Cm\CmMemo\Domain\Model\Memo $memo): \Psr\Http\Message\ResponseInterface
    {
        $this->view->assign('memo', $memo);
        return $this->htmlResponse();
    }

    /**
     * action update
     *
     * @param \Cm\CmMemo\Domain\Model\Memo $memo
     */
    public function updateAction(\Cm\CmMemo\Domain\Model\Memo $memo)
    {
        $this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. See https://docs.typo3.org/p/friendsoftypo3/extension-builder/master/en-us/User/Index.html', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        $this->memoRepository->update($memo);
        $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param \Cm\CmMemo\Domain\Model\Memo $memo
     */
    public function deleteAction(\Cm\CmMemo\Domain\Model\Memo $memo)
    {
        $this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See https://docs.typo3.org/p/friendsoftypo3/extension-builder/master/en-us/User/Index.html', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        $this->memoRepository->remove($memo);
        $this->redirect('list');
    }

    /**
     * @param \Cm\CmMemo\Domain\Repository\MemoRepository $memoRepository
     */
    public function injectMemoRepository(\Cm\CmMemo\Domain\Repository\MemoRepository $memoRepository)
    {
        $this->memoRepository = $memoRepository;
    }
}
