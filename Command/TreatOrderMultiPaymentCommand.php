<?php

namespace PayPlugModule\Command;

use PayPlugModule\Event\PayPlugPaymentEvent;
use PayPlugModule\Model\OrderPayPlugMultiPayment;
use PayPlugModule\Model\OrderPayPlugMultiPaymentQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class TreatOrderMultiPaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("payplug:treat:multi_payment")
            ->setDescription("Treat multi payment order");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initRequest();
        $dispatcher = $this->getDispatcher();
        $today = (new \DateTime())->setTime(0,0,0,0);

        $todayPlannedOrderPayments = OrderPayPlugMultiPaymentQuery::create()
            ->filterByPaidAt(null, Criteria::ISNULL)
            ->filterByPlannedAt($today)
            ->find();

        /** @var OrderPayPlugMultiPayment $todayPlannedOrderPayment */
        foreach ($todayPlannedOrderPayments as $todayPlannedOrderPayment) {
           $output->writeln($todayPlannedOrderPayment->getId());

           $order = $todayPlannedOrderPayment->getOrder();
           $paymentEvent = @(new PayPlugPaymentEvent())->buildFromOrder($order)
               ->setAmount($todayPlannedOrderPayment->getAmount())
               ->setPaymentMethod($todayPlannedOrderPayment->getPaymentMethod())
               ->setInitiator("MERCHANT");

           $dispatcher->dispatch($paymentEvent, PayPlugPaymentEvent::CREATE_PAYMENT_EVENT);

           $todayPlannedOrderPayment->setPaymentId($paymentEvent->getPaymentId())
                ->save();
        }
    }
}