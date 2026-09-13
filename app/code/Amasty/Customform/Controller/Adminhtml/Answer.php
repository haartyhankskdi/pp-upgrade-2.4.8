<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml;

use Amasty\Customform\Model\AnswerRepository;
use Amasty\Customform\Model\FormRegistry;
use Amasty\Customform\Model\Grid\Bookmark;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

abstract class Answer extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_Customform::data';

    /**
     * @var AnswerRepository
     */
    protected $answerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Bookmark
     */
    protected $bookmark;

    /**
     * @var FormRegistry
     */
    protected $formRegistry;

    public function __construct(
        Context $context,
        AnswerRepository $answerRepository,
        FormRegistry $formRegistry,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        Bookmark $bookmark
    ) {
        $this->answerRepository = $answerRepository;
        $this->logger = $logger;
        $this->resultPageFactory = $resultPageFactory;
        $this->bookmark = $bookmark;
        $this->formRegistry = $formRegistry;

        parent::__construct($context);
    }
}
