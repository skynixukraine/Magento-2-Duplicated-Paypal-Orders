<?php
/**
 * Create By Skynix Team
 * Author: oleksii
 * Date: 11/16/18
 * Time: 9:12 PM
 */

namespace Skynix\PaypalDuplicatedOrders\Plugin;

use Magento\Framework\App\ObjectManager;

class PlaceOrder
{
    const ORDER_CREATED = 'order_created';

    public function beforeExecute()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var  $session \Magento\Framework\Session\Generic */
        $session   = $objectManager->get('Magento\Framework\Session\Generic');
        $response  = $objectManager->get('Magento\Framework\App\Response\Http');

        /** @var  $messageManager \Magento\Framework\Message\ManagerInterface */
        $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');

        /** @var  $chekcoutSession \Magento\Checkout\Model\Session */
        $chekcoutSession = $objectManager->get('Magento\Checkout\Model\Session');

        if ( ( $quote = $chekcoutSession->getQuote() ) &&
            $quote->getId() > 0 &&
            $quote->hasItems() &&
            $quote->getHasError() === null &&
            ($payment = $quote->getPayment()) &&
            ($payment->getMethod() === "paypal_express") &&
            ($order = $chekcoutSession->getLastRealOrder()) &&
            $order->getStatus() === "processing") {

            if ( ( $orderId = $session->getData(self::ORDER_CREATED) ) ) {

                $session->destroy(['send_expire_cookie' => true]);
                $messageManager->addNoticeMessage(
                    __('Please check your email inbox, if your orders was not created try again.')
                );
                $response->setRedirect( "customer/account/login" )->sendResponse();
                die();

            } else {

                $session->setOrderCreated($order->getId());

            }


        }

    }

    public function afterExecute()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var  $session \Magento\Framework\Session\Generic */
        $session   = $objectManager->get('Magento\Framework\Session\Generic');
        /** @var  $chekcoutSession \Magento\Checkout\Model\Session */
        $chekcoutSession = $objectManager->get('Magento\Checkout\Model\Session');

        if ( ( $quote = $chekcoutSession->getQuote() ) &&
            $quote->getId() > 0 &&
            $quote->hasItems() &&
            $quote->getHasError() === null &&
            ($payment = $quote->getPayment()) &&
            ($payment->getMethod() === "paypal_express") &&
            ($order = $chekcoutSession->getLastRealOrder()) &&
            $order->getStatus() === "processing") {


            $session->setOrderCreated($order->getId());


        }
    }

}