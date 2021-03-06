<?php
namespace App\Pages;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\Div;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;

class PageAdminPaymentAdmin extends PageAdmin
{
    const PAGE_ID = 'payment_admin';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('payments_admin');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('admin_id')));
        $table->addHeadCell(new HeadCell($this->lang->translate('ip')));
        $table->addHeadCell(new HeadCell($this->lang->translate('platform'), "platform"));
        $table->addHeadCell(new HeadCell($this->lang->translate('date')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                "WHERE t.payment = 'admin' " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            if ($query['payid'] == $row['payment_id']) {
                $bodyRow->addClass('highlighted');
            }

            $adminname = $row['aid']
                ? "{$row['adminname']} ({$row['aid']})"
                : $this->lang->translate('none');

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($adminname));
            $bodyRow->addCell(new Cell($row['ip']));

            $cell = new Cell();
            $div = new Div(get_platform($row['platform']));
            $div->addClass('one_line');
            $cell->addContent($div);
            $bodyRow->addCell($cell);

            $bodyRow->addCell(new Cell(convertDate($row['timestamp'])));

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
