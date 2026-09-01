<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Plugin\Framework\Data\Form\FormKey;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;

class Validator
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var array
     */
    protected $handlePath;
    /**
     * @var State
     */
    private $state;

    /**
     * Validator constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param State $state
     * @param array $handlePath
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        State $state,
        $handlePath = []
    ) {
        $this->state = $state;
        $this->handlePath = $handlePath;
        $this->request = $request;
    }

    /**
     * @param \Magento\Framework\Data\Form\FormKey\Validator $object
     * @param $result
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterValidate(\Magento\Framework\Data\Form\FormKey\Validator $object, $result)
    {
        $areaCode = $this->state->getAreaCode();
        if ($areaCode == Area::AREA_FRONTEND) {
            if (in_array(trim($this->request->getPathInfo(), '/'), $this->handlePath)) {
                return true;
            }
        }
        return $result;
    }
}
