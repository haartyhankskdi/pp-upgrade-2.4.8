<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Plugin;

use /** @noinspection PhpUndefinedNamespaceInspection */
    Mirasvit\FormBuilder\Model\Answer;
use /** @noinspection PhpUndefinedNamespaceInspection */
    Mirasvit\FormBuilder\Service\AnswerService;
use Mirasvit\Core\Service\DegradationReporter;
use Mirasvit\Helpdesk\Helper\Customer;
use Mirasvit\Helpdesk\Helper\Process;
use Mirasvit\Helpdesk\Model\Config;

/**
 * @see AnswerService::saveNewAnswer()
 */
class FormBuilderSaveAnswer
{
    private $customerHelper;

    private $processHelper;

    private $degradationReporter;

    public function __construct(
        Customer $customerHelper,
        Process $processHelper,
        DegradationReporter $degradationReporter
    ) {
        $this->customerHelper      = $customerHelper;
        $this->processHelper       = $processHelper;
        $this->degradationReporter = $degradationReporter;
    }

    /**
     * @param AnswerService $service
     * @param Answer        $result
     *
     * @return Answer
     */
    public function afterSaveNewAnswer($service, $result)
    {
        $form = $service->getForm($result);
        if (!$form->isConvertToTicket()) {
            return $result;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            /** @var \Mirasvit\FormBuilder\Service\EmailService $emailService */
            $emailService = $objectManager->get('Mirasvit\FormBuilder\Service\EmailService');
        } catch (\Exception $e) {
            // intentional no-op: FormBuilder EmailService is an optional cross-module service; skip ticket conversion when unavailable
            return $result;
        }

        $customerEmail = $emailService->getCustomerEmail($form, $result);
        if (!$customerEmail) {
            return $result;
        }

        $data = [
            'subject'        => $form->getName(),
            'customer_email' => $customerEmail,
            'customer_name'  => '',
            'channel_data'   => [
                'form_id'   => $form->getId(),
                'answer_id' => $result->getId(),
            ],
        ];

        try {
            $ticket   = $this->processHelper->createFromPost($data, Config::CHANNEL_FORM_BUILDER);
            $customer = $this->customerHelper->getCustomerByPost($data);

            $rows    = [];
            $answers = $result->getAnswers();
            foreach ($answers as $row) {
                $rows[] = '<strong>' . $row['label'] . '</strong>' . ': ' . $row['value'];
            }

            $ticket->addMessage(
                implode('<br>', $rows),
                $customer,
                false,
                Config::FORM_BUILDER,
                Config::MESSAGE_INTERNAL,
                false,
                Config::FORMAT_HTML
            );
        } catch (\Exception $e) {
            $this->degradationReporter->report('helpdesk', 'form_builder.convert_to_ticket', $e->getMessage());
        }

        return $result;
    }
}
