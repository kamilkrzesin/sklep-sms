<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\HtmlResponse;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\System\Template;
use App\Translation\TranslationManager;

class UserServiceEditFormController
{
    public function get(
        $userServiceId,
        TranslationManager $translationManager,
        Settings $settings,
        Auth $auth,
        Heart $heart,
        Template $template
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        // Użytkownik nie może edytować usługi
        if (!$settings['user_edit_service']) {
            return new HtmlResponse($lang->translate('not_logged'));
        }

        $userService = get_users_services($userServiceId);

        if (empty($userService)) {
            return new HtmlResponse($lang->translate('dont_play_games'));
        }

        // Dany użytkownik nie jest właścicielem usługi o danym id
        if ($userService['uid'] != $user->getUid()) {
            return new HtmlResponse($lang->translate('dont_play_games'));
        }

        if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
            return new HtmlResponse($lang->translate('service_cant_be_modified'));
        }

        if (
            !$settings['user_edit_service'] ||
            !($serviceModule instanceof IServiceUserOwnServicesEdit)
        ) {
            return new HtmlResponse($lang->translate('service_cant_be_modified'));
        }

        $buttons = $template->render("services/my_services_savencancel");

        return new HtmlResponse($buttons . $serviceModule->userOwnServiceEditFormGet($userService));
    }
}
