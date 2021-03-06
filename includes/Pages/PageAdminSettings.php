<?php
namespace App\Pages;

use App\Html\Option;
use App\Html\Select;
use App\System\Path;
use App\Translation\TranslationManager;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = 'settings';
    protected $privilege = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('settings');
    }

    protected function content(array $query, array $body)
    {
        /** @var Path $path */
        $path = $this->app->make(Path::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $this->lang;
        $langShop = $translationManager->shop();

        // Pobranie listy serwisów transakcyjnych
        $result = $this->db->query(
            "SELECT id, name, sms, transfer " . "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $smsServices = $transferServices = "";
        while ($row = $this->db->fetchArrayAssoc($result)) {
            if ($row['sms']) {
                $smsServices .= create_dom_element("option", $row['name'], [
                    'value' => $row['id'],
                    'selected' => $row['id'] == $this->settings['sms_service'] ? "selected" : "",
                ]);
            }
            if ($row['transfer']) {
                $transferServices .= create_dom_element("option", $row['name'], [
                    'value' => $row['id'],
                    'selected' =>
                        $row['id'] == $this->settings['transfer_service'] ? "selected" : "",
                ]);
            }
        }

        $cronSelect = $this->createCronSelect();
        $userEditServiceSelect = $this->createUserEditServiceSelect();

        // Pobieranie listy dostępnych szablonów
        $dirlist = scandir($path->to('themes'));
        $themesList = "";
        foreach ($dirlist as $dirName) {
            if ($dirName[0] != '.' && is_dir($path->to("themes/$dirName"))) {
                $themesList .= create_dom_element("option", $dirName, [
                    'value' => $dirName,
                    'selected' => $dirName == $this->settings['theme'] ? "selected" : "",
                ]);
            }
        }

        // Pobieranie listy dostępnych języków
        $dirlist = scandir($path->to('translations'));
        $languagesList = "";
        foreach ($dirlist as $dirName) {
            if ($dirName[0] != '.' && is_dir($path->to("translations/{$dirName}"))) {
                $languagesList .= create_dom_element(
                    "option",
                    $lang->translate('language_' . $dirName),
                    [
                        'value' => $dirName,
                        'selected' => $dirName == $langShop->getCurrentLanguage() ? "selected" : "",
                    ]
                );
            }
        }

        // Pobranie wyglądu strony
        return $this->template->render(
            "admin/settings",
            compact(
                "userEditServiceSelect",
                "smsServices",
                "transferServices",
                "languagesList",
                "themesList",
                "cronSelect"
            ) + ["title" => $this->title]
        );
    }

    protected function createUserEditServiceSelect()
    {
        $yesOption = new Option($this->lang->translate("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["user_edit_service"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->translate("no"));
        $noOption->setParam("value", "0");
        if (!$this->settings["user_edit_service"]) {
            $noOption->setParam("selected", "selected");
        }

        $userEditServiceSelect = new Select();
        $userEditServiceSelect->setParam("id", "user_edit_service");
        $userEditServiceSelect->setParam("name", "user_edit_service");
        $userEditServiceSelect->addContent($yesOption);
        $userEditServiceSelect->addContent($noOption);

        return $userEditServiceSelect;
    }

    protected function createCronSelect()
    {
        $yesOption = new Option($this->lang->translate("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["cron_each_visit"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->translate("no"));
        $noOption->setParam("value", "0");
        if (!$this->settings["cron_each_visit"]) {
            $noOption->setParam("selected", "selected");
        }

        $cronSelect = new Select();
        $cronSelect->setParam("id", "cron");
        $cronSelect->setParam("name", "cron");
        $cronSelect->addContent($yesOption);
        $cronSelect->addContent($noOption);

        return $cronSelect;
    }
}
