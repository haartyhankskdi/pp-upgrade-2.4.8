<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

class Logger extends \Monolog\Logger
{
    /**
     * enable_logging configuration path
     */
    const ENABLE_LOGGING_SAGEPAY = 'payment/magenest_sagmagenest_sagepay_formepay/enable_logging';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var bool
     */
    protected $isEnabledLogging;

    /**
     * Logger constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->scopeConfig = $scopeConfig;
        $this->isEnabledLogging = $this->scopeConfig->isSetFlag(self::ENABLE_LOGGING_SAGEPAY);
    }

    /**
     * @param string|\Stringable $message
     * @param array $context
     */
    public function debug(
        string|\Stringable $message,
        array $context = []
    ): void {
        if (!$this->isEnabledLogging) {
            return;
        }
        parent::debug($message, $context);
    }

    /**
     * @param string|\Stringable $message
     * @param array $context
     */
    public function critical(
        string|\Stringable $message,
        array $context = []
    ): void {
        if (!$this->isEnabledLogging) {
            return;
        }
        parent::critical($message, $context);
    }
}
