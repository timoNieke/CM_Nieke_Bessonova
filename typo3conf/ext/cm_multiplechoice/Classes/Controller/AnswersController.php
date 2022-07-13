<?php

declare(strict_types=1);

namespace Cm\CmMultiplechoice\Controller;


/**
 * This file is part of the "CM Multiple Choice" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Timo Nieke <s4tiniek@uni-trier.de>, Uni Trier
 */


/**
 * AnswersController
 */
class AnswersController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * action list
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        $answers = $this->answersRepository->findAll();
        $this->view->assign('answers', $answers);
        return $this->htmlResponse();
    }

    /**
     * action show
     *
     * @param \Cm\CmMultiplechoice\Domain\Model\Answers $answers
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function showAction(\Cm\CmMultiplechoice\Domain\Model\Answers $answers): \Psr\Http\Message\ResponseInterface
    {
        $this->view->assign('answers', $answers);
        return $this->htmlResponse();
    }
}
