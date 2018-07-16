<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace WirecardShopwareElasticEngine\Components\Services;

use Shopware\Models\Order\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Exception\ParentTransactionNotFoundException;
use WirecardShopwareElasticEngine\Models\Transaction;

class SessionHandler
{
    const ORDER = 'WirecardElasticEngineOrder';
    const PAYMENT_DATA = 'WirecardElasticEnginePaymentData';

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $orderNumber
     * @param string $basketSignature
     */
    public function storeOrder($orderNumber, $basketSignature)
    {
        $this->session->offsetSet(self::ORDER, [
            'orderNumber'     => $orderNumber,
            'basketSignature' => $basketSignature,
        ]);
    }

    /**
     * @return string|null
     */
    public function getOrderNumber()
    {
        if (! $this->session->offsetExists(self::ORDER)) {
            return null;
        }
        $store = $this->session->offsetGet(self::ORDER);
        return isset($store['orderNumber']) ? $store['orderNumber'] : null;
    }

    /**
     * @return string|null
     */
    public function getBasketSignature()
    {
        if (! $this->session->offsetExists(self::ORDER)) {
            return null;
        }
        $store = $this->session->offsetGet(self::ORDER);
        return isset($store['basketSignature']) ? $store['basketSignature'] : null;
    }

    public function clearOrder()
    {
        $this->session->offsetSet(self::ORDER, []);
    }

    /**
     * @param array $additionalData
     */
    public function storeAdditionalPaymentData(array $additionalData)
    {
        $this->session->offsetSet(self::PAYMENT_DATA, [
            'additionalData' => $additionalData
        ]);
    }

    /**
     * @return array|null
     */
    public function getAdditionalPaymentData()
    {
        if (! $this->session->offsetExists(self::PAYMENT_DATA)) {
            return null;
        }

        $store = $this->session->offsetGet(self::PAYMENT_DATA);
        return isset($store['additionalData']) ? $store['additionalData'] : null;
    }
}
