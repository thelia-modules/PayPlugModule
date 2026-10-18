<?php

namespace PayPlugModule\Controller;

use PayPlugModule\Model\PayPlugCardQuery;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;

class CardController extends BaseFrontController
{
    public const DELETE_TOKEN_ID = 'payplug_card_delete';

    public function deleteCurrentCustomerCard(Request $request, CsrfTokenManagerInterface $csrfTokenManager)
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::DELETE_TOKEN_ID, (string) $request->request->get('_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        if (!$request->hasSession()) {
            return $this->generateRedirect('/');
        }

        $session = $request->getSession();
        $user = $session->getCustomerUser();

        if (null === $user) {
            return $this->generateRedirect('/');
        }

        $customerId = $user->getId();

        if (null !== $card = PayPlugCardQuery::create()->findOneByCustomerId($customerId)) {
            $card->delete();
        }

        return $this->generateRedirect($session->getReturnToUrl());
    }

}