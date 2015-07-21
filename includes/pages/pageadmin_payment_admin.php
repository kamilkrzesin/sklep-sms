<?php

$heart->register_page("payment_admin", "PageAdminPaymentAdmin", "admin");

class PageAdminPaymentAdmin extends PageAdmin
{

	const PAGE_ID = "payment_admin";

	function __construct()
	{
		global $lang;
		$this->title = $lang->payments_admin;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $G_PAGE, $templates;

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			"WHERE t.payment = 'admin' " .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			// Podświetlenie konkretnej płatności
			if ($get['highlight'] && $get['payid'] == $row['payment_id'])
				$row['class'] = "highlighted";

			$adminname = $row['aid'] ? htmlspecialchars($row['adminname']) . " ({$row['aid']})" : $lang->none;
			$row['platform'] = get_platform($row['platform']);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/payment_admin_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/payment_admin_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

}