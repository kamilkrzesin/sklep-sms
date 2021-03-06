<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedCannot;

class PageResetPassword extends Page implements IBeLoggedCannot
{
    const PAGE_ID = 'reset_password';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('reset_password');
    }

    protected function content(array $query, array $body)
    {
        // Brak podanego kodu
        if (!strlen($query['code'])) {
            return $this->lang->translate('no_reset_key');
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `uid` FROM `" .
                    TABLE_PREFIX .
                    "users` " .
                    "WHERE `reset_password_key` = '%s'",
                [$query['code']]
            )
        );

        if (!$this->db->numRows($result)) {
            // Nie znalazło użytkownika z takim kodem
            return $this->lang->translate('wrong_reset_key');
        }

        $row = $this->db->fetchArrayAssoc($result);
        $sign = md5($row['uid'] . $this->settings['random_key']);

        return $this->template->render("reset_password", compact('row', 'sign'));
    }
}
