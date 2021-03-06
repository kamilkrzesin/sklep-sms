<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\System\Database;
use App\System\Path;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class SettingsController
{
    public function put(
        Request $request,
        Database $db,
        TranslationManager $translationManager,
        Path $path,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $smsService = $request->request->get('sms_service');
        $transferService = $request->request->get('transfer_service');
        $currency = $request->request->get('currency');
        $shopName = $request->request->get('shop_name');
        $shopUrl = $request->request->get('shop_url');
        $senderEmail = $request->request->get('sender_email');
        $senderEmailName = $request->request->get('sender_email_name');
        $signature = $request->request->get('signature');
        $vat = $request->request->get('vat');
        $contact = $request->request->get('contact');
        $rowLimit = $request->request->get('row_limit');
        $licenseToken = $request->request->get('license_token');
        $cron = $request->request->get('cron');
        $language = escape_filename($request->request->get('language'));
        $theme = escape_filename($request->request->get('theme'));
        $dateFormat = $request->request->get('date_format');
        $deleteLogs = $request->request->get('delete_logs');
        $googleAnalytics = trim($request->request->get('google_analytics'));
        $gadugadu = $request->request->get('gadugadu');
        $userEditService = $request->request->get('user_edit_service');

        $warnings = [];

        // Serwis płatności SMS
        if (strlen($smsService)) {
            $result = $db->query(
                $db->prepare(
                    "SELECT id " .
                        "FROM `" .
                        TABLE_PREFIX .
                        "transaction_services` " .
                        "WHERE `id` = '%s' AND sms = '1'",
                    [$smsService]
                )
            );
            if (!$db->numRows($result)) {
                $warnings['sms_service'][] = $lang->translate('no_sms_service');
            }
        }

        // Serwis płatności internetowej
        if (strlen($transferService)) {
            $result = $db->query(
                $db->prepare(
                    "SELECT id " .
                        "FROM `" .
                        TABLE_PREFIX .
                        "transaction_services` " .
                        "WHERE `id` = '%s' AND transfer = '1'",
                    [$transferService]
                )
            );
            if (!$db->numRows($result)) {
                $warnings['transfer_service'][] = $lang->translate('no_net_service');
            }
        }

        // Email dla automatu
        if (strlen($senderEmail) && ($warning = check_for_warnings("email", $senderEmail))) {
            $warnings['sender_email'] = array_merge((array) $warnings['sender_email'], $warning);
        }

        // VAT
        if ($warning = check_for_warnings("number", $vat)) {
            $warnings['vat'] = array_merge((array) $warnings['vat'], $warning);
        }

        // Usuwanie logów
        if ($warning = check_for_warnings("number", $deleteLogs)) {
            $warnings['delete_logs'] = array_merge((array) $warnings['delete_logs'], $warning);
        }

        // Wierszy na stronę
        if ($warning = check_for_warnings("number", $rowLimit)) {
            $warnings['row_limit'] = array_merge((array) $warnings['row_limit'], $warning);
        }

        // Cron
        if (!in_array($cron, ["1", "0"])) {
            $warnings['cron'][] = $lang->translate('only_yes_no');
        }

        // Edytowanie usługi przez użytkownika
        if (!in_array($userEditService, ["1", "0"])) {
            $warnings['user_edit_service'][] = $lang->translate('only_yes_no');
        }

        // Motyw
        if (!is_dir($path->to("themes/{$theme}")) || $theme[0] == '.') {
            $warnings['theme'][] = $lang->translate('no_theme');
        }

        // Język
        if (!is_dir($path->to("translations/{$language}")) || $language[0] == '.') {
            $warnings['language'][] = $lang->translate('no_language');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $setLicenseToken = "";
        $keyLicenseToken = "";
        if ($licenseToken) {
            $setLicenseToken = $db->prepare(
                "WHEN 'license_password' THEN '%s' WHEN 'license_login' THEN 'license' ",
                [$licenseToken]
            );
            $keyLicenseToken = ",'license_password', 'license_login'";
        }

        // Edytuj ustawienia
        $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "settings` " .
                    "SET value = CASE `key` " .
                    "WHEN 'sms_service' THEN '%s' " .
                    "WHEN 'transfer_service' THEN '%s' " .
                    "WHEN 'currency' THEN '%s' " .
                    "WHEN 'shop_name' THEN '%s' " .
                    "WHEN 'shop_url' THEN '%s' " .
                    "WHEN 'sender_email' THEN '%s' " .
                    "WHEN 'sender_email_name' THEN '%s' " .
                    "WHEN 'signature' THEN '%s' " .
                    "WHEN 'vat' THEN '%.2f' " .
                    "WHEN 'contact' THEN '%s' " .
                    "WHEN 'row_limit' THEN '%s' " .
                    "WHEN 'cron_each_visit' THEN '%d' " .
                    "WHEN 'user_edit_service' THEN '%d' " .
                    "WHEN 'theme' THEN '%s' " .
                    "WHEN 'language' THEN '%s' " .
                    "WHEN 'date_format' THEN '%s' " .
                    "WHEN 'delete_logs' THEN '%d' " .
                    "WHEN 'google_analytics' THEN '%s' " .
                    "WHEN 'gadugadu' THEN '%s' " .
                    $setLicenseToken .
                    "END " .
                    "WHERE `key` IN ( 'sms_service','transfer_service','currency','shop_name','shop_url','sender_email','sender_email_name','signature','vat'," .
                    "'contact','row_limit','cron_each_visit','user_edit_service','theme','language','date_format','delete_logs'," .
                    "'google_analytics','gadugadu'{$keyLicenseToken} )",
                [
                    $smsService,
                    $transferService,
                    $currency,
                    $shopName,
                    $shopUrl,
                    $senderEmail,
                    $senderEmailName,
                    $signature,
                    $vat,
                    $contact,
                    $rowLimit,
                    $cron,
                    $userEditService,
                    $theme,
                    $language,
                    $dateFormat,
                    $deleteLogs,
                    $googleAnalytics,
                    $gadugadu,
                ]
            )
        );

        if ($db->affectedRows()) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('settings_admin_edit'),
                    $user->getUsername(),
                    $user->getUid()
                )
            );

            return new ApiResponse('ok', $lang->translate('settings_edit'), 1);
        }

        return new ApiResponse("not_edited", $lang->translate('settings_no_edit'), 0);
    }
}
