<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */

namespace Buckaroo\Magento2\Gateway\Http\TransactionBuilder;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\UrlInterface;
use Magento\Framework\Encryption\Encryptor;
use Buckaroo\Magento2\Gateway\Http\Transaction;
use Buckaroo\Magento2\Model\ConfigProvider\Account;
use Buckaroo\Magento2\Model\ConfigProvider\Method\Factory;
use Buckaroo\Magento2\Service\Software\Data as SoftwareData;

class Refund extends AbstractTransactionBuilder
{
    /** @var RemoteAddress */
    protected $remoteAddress;

    /** @var Factory */
    protected $configProviderMethodFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SoftwareData         $softwareData
     * @param Account              $configProviderAccount
     * @param Transaction          $transaction
     * @param UrlInterface         $urlBuilder
     * @param RemoteAddress        $remoteAddress
     * @param Factory              $configProviderMethodFactory
     * @param FormKey              $formKey
     * @param Encryptor            $encryptor
     * @param null                 $amount
     * @param null                 $currency
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SoftwareData $softwareData,
        Account $configProviderAccount,
        Transaction $transaction,
        UrlInterface $urlBuilder,
        FormKey $formKey,
        Encryptor $encryptor,
        RemoteAddress $remoteAddress,
        Factory $configProviderMethodFactory,
        $amount = null,
        $currency = null
    ) {
        parent::__construct($scopeConfig, $softwareData, $configProviderAccount, $transaction, $urlBuilder, $formKey, $encryptor, $amount, $currency);

        $this->remoteAddress = $remoteAddress;
        $this->configProviderMethodFactory = $configProviderMethodFactory;
    }

    /**
     * @throws \Buckaroo\Magento2\Exception
     */
    protected function setRefundCurrencyAndAmount()
    {
        /**
         * @var \Buckaroo\Magento2\Model\Method\AbstractMethod $methodInstance
         */
        $methodInstance = $this->order->getPayment()->getMethodInstance();
        $method = $methodInstance->buckarooPaymentMethodCode;

        $configProvider = $this->configProviderMethodFactory->get($method);
        $allowedCurrencies = $configProvider->getAllowedCurrencies();

        if (in_array($this->order->getOrderCurrencyCode(), $allowedCurrencies)) {
            /**
             * @todo find a way to fix the cumulative rounding issue that occurs in creditmemos.
             *       This problem occurs when the creditmemo is being refunded in the order's currency, rather than the
             *       store's base currency.
             */
            $this->setCurrency($this->order->getOrderCurrencyCode());
            $this->setAmount(round($this->getAmount() * $this->order->getBaseToOrderRate(), 2));
        } elseif (in_array($this->order->getBaseCurrencyCode(), $allowedCurrencies)) {
            $this->setCurrency($this->order->getBaseCurrencyCode());
        } else {
            throw new \Buckaroo\Magento2\Exception(
                __("The selected payment method does not support the selected currency or the store's base currency.")
            );
        }
    }

    /**
     * @return array
     */
    public function getBody()
    {
        if (!$this->getCurrency()) {
            $this->setRefundCurrencyAndAmount();
        }

        $order = $this->getOrder();
        $store = $order->getStore();

        $ip = $order->getRemoteIp();
        if (!$ip) {
            $ip = $this->remoteAddress->getRemoteAddress();
        }

        $body = [
            'Currency' => $this->getCurrency(),
            'AmountDebit' => 0,
            'AmountCredit' => $this->getAmount(),
            'Invoice' => $this->getInvoiceId(),
            'Order' => $order->getIncrementId(),
            'Description' => $this->configProviderAccount->getTransactionLabel($store),
            'ClientIP' => (object)[
                '_' => $ip,
                'Type' => strpos($ip, ':') === false ? 'IPv4' : 'IPv6',
            ],
            'ReturnURL' => $this->getReturnUrl(),
            'ReturnURLCancel' => $this->getReturnUrl(),
            'ReturnURLError' => $this->getReturnUrl(),
            'ReturnURLReject' => $this->getReturnUrl(),
            'OriginalTransactionKey' => $this->originalTransactionKey,
            'StartRecurrent' => $this->startRecurrent,
            'PushURL' => $this->urlBuilder->getDirectUrl('rest/V1/buckaroo/push'),
            'Services' => (object)[
                'Service' => $this->getServices()
            ],
            'AdditionalParameters' => (object)[
                'AdditionalParameter' => $this->getAdditionalParameters()
            ],
        ];

        return $body;
    }


    /**
     * @return array
     */
    private function getAdditionalParameters()
    {
        $parameterLine = [];
        if (isset($this->getServices()['Action'])) {
            $parameterLine[] = $this->getParameterLine('service_action_from_magento', strtolower($this->getServices()['Action']));
        }

        $parameterLine[] = $this->getParameterLine('initiated_by_magento', 1);

        return $parameterLine;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return array
     */
    private function getParameterLine($name, $value)
    {
        $line = [
            '_'    => $value,
            'Name' => $name,
        ];

        return $line;
    }
}
