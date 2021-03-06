<?php
namespace App\Html;

use App\System\CurrentPage;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class Structure extends DOMElement
{
    protected $name = 'table';

    protected $params = [
        "class" => "table is-fullwidth is-hoverable",
    ];

    /** @var DOMElement[] */
    private $headCells = [];

    /** @var BodyRow[] */
    private $bodyRows = [];

    /**
     * Ilość elementów w bazie danych
     * potrzebne do stworzenia paginacji
     *
     * @var int
     */
    private $dbRowsAmount;

    /** @var DOMElement */
    public $foot = null;

    public function toHtml()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        // Tworzymy thead
        $head = new DOMElement();
        $head->setName('thead');

        $headRow = new Row();
        foreach ($this->headCells as $cell) {
            $headRow->addContent($cell);
        }
        $actions = new HeadCell($lang->translate('actions'));
        $actions->setStyle('width', '4%');
        $headRow->addContent($actions);

        $head->addContent($headRow);

        // Tworzymy tbody
        $body = new DOMElement();
        $body->setName('tbody');
        foreach ($this->bodyRows as $row) {
            $body->addContent($row);
        }

        if ($body->isEmpty()) {
            $row = new Row();
            $cell = new Cell($lang->translate('no_data'));
            $cell->setParam('colspan', '30');
            $cell->addClass("has-text-centered");
            $cell->setStyle('padding', '40px');
            $row->addContent($cell);
            $body->addContent($row);
        }

        $this->contents = [];
        $this->addContent($head);
        $this->addContent($body);
        if ($this->foot !== null) {
            $this->addContent($this->foot);
        }

        return parent::toHtml();
    }

    /**
     * @param DOMElement $headCell
     */
    public function addHeadCell($headCell)
    {
        $this->headCells[] = $headCell;
    }

    /**
     * @param string     $key
     * @param DOMElement $headCell
     */
    public function setHeadCell($key, $headCell)
    {
        $this->headCells[$key] = $headCell;
    }

    /**
     * @param BodyRow $bodyRow
     */
    public function addBodyRow($bodyRow)
    {
        $this->bodyRows[] = $bodyRow;
    }

    /**
     * @return int
     */
    public function getDbRowsAmount()
    {
        return $this->dbRowsAmount;
    }

    /**
     * @param int $amount
     */
    public function setDbRowsAmount($amount)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = app()->make(CurrentPage::class);
        /** @var Request $request */
        $request = app()->make(Request::class);

        $pageNumber = $currentPage->getPageNumber();
        $this->dbRowsAmount = intval($amount);

        $pagination = get_pagination(
            $this->dbRowsAmount,
            $pageNumber,
            $request->getPathInfo(),
            $request->query->all()
        );

        if ($pagination) {
            $this->foot = new DOMElement();
            $this->foot->setName('tfoot');
            $this->foot->addClass('display_tfoot');

            $row = new Row();

            $cell = new Cell($pagination);
            $cell->setParam('colspan', '31');

            $row->addContent($cell);
            $this->foot->addContent($row);
        }
    }
}
