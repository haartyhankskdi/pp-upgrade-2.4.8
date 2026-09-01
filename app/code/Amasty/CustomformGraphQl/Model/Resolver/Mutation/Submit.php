<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Amasty Custom Forms GraphQl for Magento 2 (System)
*/

declare(strict_types=1);

namespace Amasty\CustomformGraphQl\Model\Resolver\Mutation;

use Amasty\Base\Model\Serializer;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Oauth\Helper\Request;

class Submit implements ResolverInterface
{
    public const FORM_DATA_KEY = 'form_data';

    /**
     * @var \Amasty\Customform\Model\Submit
     */
    private $submit;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $session;

    public function __construct(
        \Amasty\Customform\Model\Submit $submit,
        Serializer $serializer,
        \Magento\Customer\Model\Session $session
    ) {
        $this->submit = $submit;
        $this->serializer = $serializer;
        $this->session = $session;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws \Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input'][self::FORM_DATA_KEY])) {
            throw new GraphQlInputException(__('Required parameter "%1" is missing', self::FORM_DATA_KEY));
        }

        try {
            $params = $this->serializer->unserialize($args['input'][self::FORM_DATA_KEY]);
            $this->session->setCustomerId($context->getUserId());
            $this->submit->process($params);
            $result = ['status' => Request::HTTP_OK];
        } catch (\Exception $e) {
            $result = ['status' => Request::HTTP_INTERNAL_ERROR];
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $result;
    }
}
