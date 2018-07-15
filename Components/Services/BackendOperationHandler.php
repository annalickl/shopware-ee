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

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;

class BackendOperationHandler extends Handler
{
    /**
     * @param Transaction        $transaction
     * @param TransactionService $transactionService
     * @param string             $operation
     * @param string             $notificationUrl
     *
     * @return ErrorAction
     */
    public function execute(
        Transaction $transaction,
        TransactionService $transactionService,
        $operation,
        $notificationUrl
    ) {
        $transaction->setNotificationUrl($notificationUrl);

        // TODO: eventually implement payment specific backend operation processing

        try {
            $response = $transactionService->process($transaction, $operation);
        } catch (\Exception $e) {
            $this->logger->error('Transaction service process failed: ' . $e->getMessage());
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Transaction processing failed');
        }

        if ($response instanceof SuccessResponse) {
            return $this->handleSuccess($response);
        }

        if ($response instanceof FailureResponse) {
            return $this->handleFailure($response);
        }
    }

    protected function handleSuccess(SuccessResponse $response)
    {
        return new ViewAction(null, [
            'transactionId' => $response->getTransactionId()
        ]);
    }

    protected function handleFailure(FailureResponse $response)
    {
    }
}
