<?php
namespace App\Services\Other;

use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseOutside;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceOther extends ServiceOtherSimple implements IServicePurchase, IServicePurchaseOutside
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->heart = $this->app->make(Heart::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    /**
     * @param Purchase $purchaseData
     * @return array
     */
    public function purchaseDataValidate(Purchase $purchaseData)
    {
        $warnings = [];

        // Serwer
        $server = null;
        if (!strlen($purchaseData->getOrder('server'))) {
            $warnings['server'][] = $this->lang->translate('must_choose_server');
        } else {
            // Sprawdzanie czy serwer o danym id istnieje w bazie
            $server = $this->heart->getServer($purchaseData->getOrder('server'));
            if (!$this->heart->serverServiceLinked($server->getId(), $this->service->getId())) {
                $warnings['server'][] = $this->lang->translate('chosen_incorrect_server');
            }
        }

        // Wartość usługi
        $price = [];
        if (!strlen($purchaseData->getTariff())) {
            $warnings['value'][] = $this->lang->translate('must_choose_amount');
        } else {
            // Wyszukiwanie usługi o konkretnej cenie
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" .
                        TABLE_PREFIX .
                        "pricelist` " .
                        "WHERE `service` = '%s' AND `tariff` = '%d' AND ( `server` = '%d' OR `server` = '-1' )",
                    [$this->service->getId(), $purchaseData->getTariff(), $server->getId()]
                )
            );

            if (!$this->db->numRows($result)) {
                // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
                return [
                    'status' => "no_option",
                    'text' => $this->lang->translate('service_not_affordable'),
                    'positive' => false,
                ];
            }

            $price = $this->db->fetchArrayAssoc($result);
        }

        // E-mail
        if (
            strlen($purchaseData->getEmail()) &&
            ($warning = check_for_warnings("email", $purchaseData->getEmail()))
        ) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchaseData->setOrder([
            'amount' => $price['amount'],
            'forever' => $price['amount'] == -1 ? true : false,
        ]);

        $purchaseData->setPayment([
            'cost' => $purchaseData->getTariff()->getProvision(),
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    public function purchase(Purchase $purchaseData)
    {
        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service->getId(),
            $purchaseData->getOrder('server'),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('auth_data'),
            $purchaseData->getEmail()
        );
    }
}
