<?php

namespace PayPlugModule\Controller\Admin;

use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\OrderStatusService;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;

class ConfigurationController extends BaseAdminController
{
    public function viewAction()
    {
        // Create default order statuses
        /** @var OrderStatusService $orderStatusesService */
        $orderStatusesService = $this->container->get('payplugmodule_order_status_service');
        $orderStatusesService->initAllStatuses();

        return $this->render(
            "PayPlugModule/configuration"
        );
    }

    public function saveAction()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('payplugmodule_configuration_form');

        try {
            $data = $this->validateForm($form)->getData();

            foreach ($data as $key => $value) {
                if (in_array($key, PayPlugConfigValue::getConfigKeys())) {
                    PayPlugModule::setConfigValue($key, $value);
                }
            }

        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    PayPlugModule::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
            return $this->viewAction();
        }

        return $this->generateSuccessRedirect($form);
    }

}