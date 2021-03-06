<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Input;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminGroups extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'groups';
    protected $privilege = 'view_groups';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('groups');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('name')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" .
                TABLE_PREFIX .
                "groups` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['name']));

            if (get_privileges('manage_groups')) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_groups')) {
            $button = new Input();
            $button->setParam('id', 'group_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->translate('add_group'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_groups")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "group_edit") {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "groups` " . "WHERE `id` = '%d'",
                    [$query['id']]
                )
            );

            if (!$this->db->numRows($result)) {
                $query['template'] = create_dom_element(
                    "form",
                    $this->lang->translate('no_such_group'),
                    [
                        'class' => 'action_box',
                        'style' => [
                            'padding' => "20px",
                            'color' => "white",
                        ],
                    ]
                );
            } else {
                $group = $this->db->fetchArrayAssoc($result);
            }
        }

        $privileges = "";
        $result = $this->db->query("DESCRIBE " . TABLE_PREFIX . "groups");
        while ($row = $this->db->fetchArrayAssoc($result)) {
            if (in_array($row['Field'], ["id", "name"])) {
                continue;
            }

            $values = create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('no')),
                [
                    'value' => 0,
                    'selected' => isset($group) && $group[$row['Field']] ? "" : "selected",
                ]
            );

            $values .= create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('yes')),
                [
                    'value' => 1,
                    'selected' => isset($group) && $group[$row['Field']] ? "selected" : "",
                ]
            );

            $name = $row['Field'];
            $text = $this->lang->translate('privilege_' . $row['Field']);

            $privileges .= $this->template->render(
                "tr_text_select",
                compact('name', 'text', 'values')
            );
        }

        switch ($boxId) {
            case "group_add":
                $output = $this->template->render(
                    "admin/action_boxes/group_add",
                    compact('privileges')
                );
                break;

            case "group_edit":
                $output = $this->template->render(
                    "admin/action_boxes/group_edit",
                    compact('privileges', 'group')
                );
                break;

            default:
                $output = '';
        }

        return [
            'status' => 'ok',
            'template' => $output,
        ];
    }
}
