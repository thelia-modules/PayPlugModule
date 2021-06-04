<?php

namespace PayPlugModule\Controller\Admin;

use PayPlugModule\Form\ConfigurationForm;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\Model\PayPlugModuleDeliveryTypeQuery;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\OrderStatusService;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;

class ConfigurationController extends BaseAdminController
{
    public function viewAction(OrderStatusService $orderStatusesService)
    {
        $orderStatusesService->initAllStatuses();
        $deliveryModuleFormFields = ConfigurationForm::getDeliveryModuleFormFields();

        return $this->render(
            "PayPlugModule/configuration",
                compact('deliveryModuleFormFields')
        );
    }

    public function saveAction()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConfigurationForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            foreach ($data as $key => $value) {
                if (in_array($key, PayPlugConfigValue::getConfigKeys())) {
                    PayPlugModule::setConfigValue($key, $value);
                }

                $explodedKey = explode(':', $key);
                if ($explodedKey[0] === ConfigurationForm::DELIVERY_MODULE_TYPE_KEY_PREFIX) {
                    $moduleId = $explodedKey[1];
                    $payPlugModuleDeliveryType = PayPlugModuleDeliveryTypeQuery::create()->filterByModuleId($moduleId)->findOneOrCreate();
                    $payPlugModuleDeliveryType->setDeliveryType($value)
                        ->save();
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