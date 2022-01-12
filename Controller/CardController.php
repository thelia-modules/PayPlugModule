<?php

namespace PayPlugModule\Controller;

use PayPlugModule\Model\PayPlugCardQuery;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Tools\URL;

class CardController extends BaseFrontController
{
    public function deleteCurrentCustomerCard(Request $request)
    {
        $customerId = $request->getSession()->getCustomerUser()->getId();

        if (null !== $card = PayPlugCardQuery::create()->findOneByCustomerId($customerId)) {
            $card->delete();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/order/invoice'));
    }

}
