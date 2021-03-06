<?php
namespace App\Pages;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = 'players_flags';
    protected $privilege = 'view_player_flags';

    protected $flags = 'abcdefghijklmnopqrstuyvwxz';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('players_flags');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('server')));
        $table->addHeadCell(
            new Cell(
                "{$this->lang->translate('nick')}/{$this->lang->translate(
                    'ip'
                )}/{$this->lang->translate('sid')}"
            )
        );

        foreach (str_split($this->flags) as $flag) {
            $table->addHeadCell(new HeadCell($flag));
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" .
                TABLE_PREFIX .
                "players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            // Pozyskanie danych serwera
            $tempServer = $this->heart->getServer($row['server']);
            $serverName = $tempServer->getName();
            unset($tempServer);

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($serverName));
            $bodyRow->addCell(new Cell($row['auth_data']));

            foreach (str_split($this->flags) as $flag) {
                if (!$row[$flag]) {
                    $bodyRow->addCell(new Cell(' '));
                } else {
                    if ($row[$flag] == -1) {
                        $bodyRow->addCell(new Cell($this->lang->translate('never')));
                    } else {
                        $bodyRow->addCell(
                            new Cell(date($this->settings['date_format'], $row[$flag]))
                        );
                    }
                }
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
