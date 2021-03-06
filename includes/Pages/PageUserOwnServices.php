<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedMust;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\System\Auth;
use App\System\Database;
use App\System\Settings;
use App\System\Template;
use Symfony\Component\HttpFoundation\Request;

class PageUserOwnServices extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'user_own_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('user_own_services');
    }

    protected function content(array $query, array $body)
    {
        $heart = $this->heart;
        $lang = $this->lang;

        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        // Ktore moduly wspieraja usługi użytkowników
        $modules = array_filter($this->heart->getServicesModules(), function ($module) {
            return in_array(IServiceUserOwnServices::class, class_implements($module["class"]));
        });

        $moduleIds = [];
        foreach ($modules as $module) {
            $moduleIds[] = $module["id"];
        }

        $usersServices = [];
        $rowsCount = 0;
        if (!empty($moduleIds)) {
            $moduleIds = implode_esc(', ', $moduleIds);

            $rowsCount = $db->getColumn(
                $db->prepare(
                    "SELECT COUNT(*) as `amount` FROM `" .
                        TABLE_PREFIX .
                        "user_service` AS us " .
                        "INNER JOIN `" .
                        TABLE_PREFIX .
                        "services` AS s ON us.service = s.id " .
                        "WHERE us.uid = '%d' AND s.module IN ({$moduleIds}) ",
                    [$user->getUid()]
                ),
                'amount'
            );

            $result = $db->query(
                $db->prepare(
                    "SELECT us.id FROM `" .
                        TABLE_PREFIX .
                        "user_service` AS us " .
                        "INNER JOIN `" .
                        TABLE_PREFIX .
                        "services` AS s ON us.service = s.id " .
                        "WHERE us.uid = '%d' AND s.module IN ({$moduleIds}) " .
                        "ORDER BY us.id DESC " .
                        "LIMIT " .
                        get_row_limit($this->currentPage->getPageNumber(), 4),
                    [$user->getUid()]
                )
            );

            $userServiceIds = [];
            while ($row = $db->fetchArrayAssoc($result)) {
                $userServiceIds[] = $row['id'];
            }

            if (!empty($userServiceIds)) {
                $usersServices = get_users_services(
                    "WHERE us.id IN (" . implode(', ', $userServiceIds) . ")",
                    false
                );
            }
        }

        $userOwnServices = '';
        foreach ($usersServices as $userService) {
            if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
                continue;
            }

            if (!($serviceModule instanceof IServiceUserOwnServices)) {
                continue;
            }

            if (
                $settings['user_edit_service'] &&
                $serviceModule instanceof IServiceUserOwnServicesEdit
            ) {
                $buttonEdit = create_dom_element("button", $lang->translate('edit'), [
                    'class' => "button is-small edit_row",
                    'type' => 'button',
                ]);
            }

            $userOwnServices .= create_brick(
                $serviceModule->userOwnServiceInfoGet(
                    $userService,
                    isset($buttonEdit) ? $buttonEdit : ''
                )
            );
        }

        // Nie znalazło żadnych usług danego użytkownika
        if (!strlen($userOwnServices)) {
            $userOwnServices = $lang->translate('no_data');
        }

        $pagination = get_pagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $query,
            4
        );
        $paginationClass = $pagination ? "" : "display_none";

        return $template->render(
            "user_own_services",
            compact('userOwnServices', 'paginationClass', 'pagination')
        );
    }
}
