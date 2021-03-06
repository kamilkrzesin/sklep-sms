<?php
namespace App\Services\MybbExtraGroups;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceCreate;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Service;
use App\System\CurrentPage;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

abstract class ServiceMybbExtraGroupsSimple extends Service implements
    IServiceAdminManage,
    IServiceCreate,
    IServiceUserServiceAdminDisplay
{
    const MODULE_ID = "mybb_extra_groups";
    const USER_SERVICE_TABLE = "user_service_mybb_extra_groups";

    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    public function __construct(\App\Models\Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
    }

    /**
     * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
     * Powinna zwracać dodatkowe pola do uzupełnienia
     *
     * @return string
     */
    public function serviceAdminExtraFieldsGet()
    {
        // WEB
        if ($this->showOnWeb()) {
            $webSelYes = "selected";
        } else {
            $webSelNo = "selected";
        }

        // Jeżeli edytujemy
        if ($this->service !== null) {
            // DB
            $dbPassword = strlen($this->service->getData()['db_password']) ? "********" : "";
            $dbHost = $this->service->getData()['db_host'];
            $dbUser = $this->service->getData()['db_user'];
            $dbName = $this->service->getData()['db_name'];

            // MyBB groups
            $mybbGroups = $this->service->getData()['mybb_groups'];
        }

        return $this->template->render(
            "services/mybb_extra_groups/extra_fields",
            compact(
                'webSelNo',
                'webSelYes',
                'mybbGroups',
                'dbHost',
                'dbUser',
                'dbPassword',
                'dbName'
            ) + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    /**
     * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
     * jak coś się jej nie spodoba to zwraca o tym info w tablicy
     *
     * @param array $data
     *
     * @return array        'key' => DOM Element name
     *                      'value' => Array of error messages
     */
    public function serviceAdminManagePre($data)
    {
        $warnings = [];

        // Web
        if (!in_array($data['web'], ["1", "0"])) {
            $warnings['web'][] = $this->lang->translate('only_yes_no');
        }

        // MyBB groups
        if (!strlen($data['mybb_groups'])) {
            $warnings['mybb_groups'][] = $this->lang->translate('field_no_empty');
        } else {
            $groups = explode(",", $data['mybb_groups']);
            foreach ($groups as $group) {
                if (!my_is_integer($group)) {
                    $warnings['mybb_groups'][] = $this->lang->translate('group_not_integer');
                    break;
                }
            }
        }

        // Db host
        if (!strlen($data['db_host'])) {
            $warnings['db_host'][] = $this->lang->translate('field_no_empty');
        }

        // Db user
        if (!strlen($data['db_user'])) {
            $warnings['db_user'][] = $this->lang->translate('field_no_empty');
        }

        // Db password
        if ($this->service === null && !strlen($data['db_password'])) {
            $warnings['db_password'][] = $this->lang->translate('field_no_empty');
        }

        // Db name
        if (!strlen($data['db_name'])) {
            $warnings['db_name'][] = $this->lang->translate('field_no_empty');
        }

        return $warnings;
    }

    /**
     * Metoda zostaje wywołana po tym, jak  weryfikacja danych
     * przesłanych w formularzu dodania nowej usługi w PA przebiegła bezproblemowo
     *
     * @param array $data
     *
     * @return array (
     *    'query_set' - array of query SET elements:
     *        array(
     *            'type'    => '%s'|'%d'|'%f'|'%c'|etc.
     *            'column'    => kolumna
     *            'value'    => wartość kolumny
     *        )
     */
    public function serviceAdminManagePost($data)
    {
        $mybbGroups = explode(",", $data['mybb_groups']);
        foreach ($mybbGroups as $key => $group) {
            $mybbGroups[$key] = trim($group);
            if (!strlen($mybbGroups[$key])) {
                unset($mybbGroups[$key]);
            }
        }

        $extraData = [
            'mybb_groups' => implode(",", $mybbGroups),
            'web' => $data['web'],
            'db_host' => $data['db_host'],
            'db_user' => $data['db_user'],
            'db_password' => if_strlen(
                $data['db_password'],
                $this->service->getData()['db_password']
            ),
            'db_name' => $data['db_name'],
        ];

        return [
            'query_set' => [
                [
                    'type' => '%s',
                    'column' => 'data',
                    'value' => json_encode($extraData),
                ],
            ],
        ];
    }

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->translate('mybb_groups');
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        $pageNumber = $currentPage->getPageNumber();

        $wrapper = new Wrapper();
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('user')));
        $table->addHeadCell(new HeadCell($this->lang->translate('service')));
        $table->addHeadCell(new HeadCell($this->lang->translate('mybb_user')));
        $table->addHeadCell(new HeadCell($this->lang->translate('expires')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($query['search'])) {
            searchWhere(
                ["us.id", "us.uid", "u.username", "s.name", "usmeg.mybb_uid"],
                $query['search'],
                $where
            );
        }
        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . ' ';
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS us.id, us.uid, u.username, " .
                "s.id AS `service_id`, s.name AS `service`, us.expire, usmeg.mybb_uid " .
                "FROM `" .
                TABLE_PREFIX .
                "user_service` AS us " .
                "INNER JOIN `" .
                TABLE_PREFIX .
                $this::USER_SERVICE_TABLE .
                "` AS usmeg ON usmeg.us_id = us.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "services` AS s ON s.id = usmeg.service " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "users` AS u ON u.uid = us.uid " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT " .
                get_row_limit($pageNumber)
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Cell(
                    $row['uid']
                        ? $row['username'] . " ({$row['uid']})"
                        : $this->lang->translate('none')
                )
            );
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['mybb_uid']));
            $bodyRow->addCell(
                new Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->translate('never')
                        : date($this->settings['date_format'], $row['expire'])
                )
            );
            if (get_privileges("manage_user_services")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(false);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }
}
