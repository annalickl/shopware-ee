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

namespace WirecardElasticEngine\Components\Mail;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Plugin\Plugin;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * @package WirecardElasticEngine\Components\Mail
 *
 * @since   1.0.0
 */
class SupportMail
{
    const SUPPORT_MAIL = 'shop-systems-support@wirecard.com';

    /**
     * @var \Enlight_Components_Mail
     */
    private $mail;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var InstallerService
     */
    private $installerService;

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @param \Enlight_Components_Mail $mail
     * @param EntityManagerInterface   $em
     * @param InstallerService         $installerService
     * @param PaymentFactory           $paymentFactory
     *
     * @since 1.0.0
     */
    public function __construct(
        \Enlight_Components_Mail $mail,
        EntityManagerInterface $em,
        InstallerService $installerService,
        PaymentFactory $paymentFactory
    ) {
        $this->mail             = $mail;
        $this->em               = $em;
        $this->installerService = $installerService;
        $this->paymentFactory   = $paymentFactory;
    }

    /**
     * Sends Email to Wirecard Support
     *
     * @param ParameterBagInterface $parameterBag
     * @param string                $senderAddress
     * @param string                $message
     * @param string                $replyTo
     *
     * @return \Zend_Mail
     * @throws \Zend_Mail_Exception
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function send(ParameterBagInterface $parameterBag, $senderAddress, $message, $replyTo = null)
    {
        $message .= PHP_EOL . PHP_EOL . PHP_EOL;
        $message .= '*** Server Info: ***';
        $message .= $this->arrayToText($this->getServerInfo());

        $message .= '*** Shop Info: ***';
        $message .= $this->arrayToText($this->getShopInfo($parameterBag));

        $message .= '*** Plugin Info: ***';
        $message .= $this->arrayToText($this->getPluginInfo());

        $message .= '*** Plugin List: ***';
        $message .= $this->arrayToText($this->getPluginList());

        $this->mail->setFrom($senderAddress);

        $this->mail->setReplyTo($replyTo ?: $senderAddress);

        $this->mail->addTo($this->getRecipientMail());
        $this->mail->setSubject('Shopware support request');
        $this->mail->setBodyText($message);

        return $this->mail->send();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    private function getRecipientMail()
    {
        if (in_array(getenv('SHOPWARE_ENV'), ['dev', 'development', 'testing', 'test'])) {
            return 'test@example.com';
        }

        return self::SUPPORT_MAIL;
    }

    /**
     * Formats array to readable string
     *
     * @param array $array
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function arrayToText($array)
    {
        $result = PHP_EOL;
        foreach ($array as $key => $val) {
            if (is_bool($val)) {
                $result .= $key . ': ' . ($val ? 'true' : 'false') . PHP_EOL;
            } elseif (is_array($val)) {
                $result .= PHP_EOL . '[' . $key . ']';
                $result .= $this->arrayToText($val);
            } else {
                $result .= $key . ': ' . $val . PHP_EOL;
            }
        }
        $result .= PHP_EOL;

        return $result;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getServerInfo()
    {
        return [
            'os'     => php_uname(),
            'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
            'php'    => phpversion(),
        ];
    }

    /**
     * @param ParameterBagInterface $parameterBag
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function getShopInfo(ParameterBagInterface $parameterBag)
    {
        return [
            'name'        => $parameterBag->get('kernel.name'),
            'version'     => $parameterBag->get('shopware.release.version'),
            'environment' => $parameterBag->get('kernel.environment'),
        ];
    }

    /**
     * @return array
     *
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function getPluginInfo()
    {
        $plugin         = $this->installerService->getPluginByName(WirecardElasticEngine::NAME);
        $payments       = $this->paymentFactory->getSupportedPayments();
        $paymentConfigs = [];

        foreach ($payments as $payment) {
            $paymentModel = $this->em->getRepository(Payment::class)
                                     ->findOneBy(['name' => $payment->getName()]);

            if (! $paymentModel) {
                continue;
            }

            $paymentConfigs[$payment->getName()] = array_merge(
                ['active' => $paymentModel->getActive()],
                $payment->getPaymentConfig()->toArray()
            );
        }

        return [
            'name'     => WirecardElasticEngine::NAME,
            'version'  => $plugin->getVersion(),
            'payments' => $paymentConfigs,
        ];
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getPluginList()
    {
        /** @var EntityRepository $repository */
        $repository = $this->em->getRepository(Plugin::class);
        $plugins    = $repository->createQueryBuilder('plugin')
                                 ->andWhere('plugin.capabilityEnable = true')
                                 ->addOrderBy('plugin.active', 'desc')
                                 ->addOrderBy('plugin.name')
                                 ->getQuery()
                                 ->execute();

        $rows = [];

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $rows[] = [
                'name'      => $plugin->getName(),
                'label'     => $plugin->getLabel(),
                'version'   => $plugin->getVersion(),
                'author'    => $plugin->getAuthor(),
                'active'    => $plugin->getActive() ? 'Yes' : 'No',
                'installed' => $plugin->getInstalled() ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }
}
