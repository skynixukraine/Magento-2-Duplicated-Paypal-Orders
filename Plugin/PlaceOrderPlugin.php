<?php
/**
 * Create By Skynix Team
 * Author: oleksii
 * Date: 11/16/18
 * Time: 9:12 PM
 */

namespace Skynix\PaypalDuplicatedOrders\Plugin;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\Generic;
use Magento\Paypal\Controller\Express\AbstractExpress\PlaceOrder;

class PlaceOrderPlugin
{
    private const ORDER_CREATED = 'skynix_paypal_order_created';
    /**
     * @var Generic
     */
    private Generic $session;
    /**
     * @var Http
     */
    private Http $response;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;
    /**
     * @var Session
     */
    private Session $checkoutSession;

    public function __construct(
        Generic $session,
        Http $response,
        ManagerInterface $messageManager,
        Session $checkoutSession
    ) {
        $this->session = $session;
        $this->response = $response;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
    }

    private function isProcessingPaypalExpressOrder(): bool
    {
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote ? $quote->getPayment() : false;
        $order = $this->checkoutSession->getLastRealOrder();

        return $quote &&
               $quote->getId() &&
               $quote->hasItems() &&
               $quote->getHasError() === null &&
               $payment &&
               $payment->getMethod() === "paypal_express" &&
               $order &&
               $order->getStatus() === "processing";
    }

    public function aroundExecute(PlaceOrder $subject, callable $proceed): void
    {
        if ($this->isProcessingPaypalExpressOrder()) {
            if ($this->session->getData(self::ORDER_CREATED)) {
                $this->session->destroy(['send_expire_cookie' => true]);
                $this->messageManager->addNoticeMessage(
                    __('Your order was already processed, please check your email inbox. If your orders was not created try again.')
                );

                $this->response->setRedirect( "customer/account/login" );
                return;
            }

            $this->session->setData(
                self::ORDER_CREATED,
                $this->checkoutSession->getLastRealOrder()->getId()
            );
        }

        $proceed();

        if ($this->isProcessingPaypalExpressOrder()) {
            $this->session->setData(
                self::ORDER_CREATED,
                $this->checkoutSession->getLastRealOrder()->getId()
            );
        }
    }
}