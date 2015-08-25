<?php

$heart->register_page("bought_services", "PageAdminBoughtServices", "admin");

class PageAdminBoughtServices extends PageAdmin
{

	const PAGE_ID = "bought_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang->bought_services;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE, $templates;

		// Wyszukujemy dane ktore spelniaja kryteria
		if (isset($get['search']))
			searchWhere(array("t.id", "t.payment", "t.payment_id", "t.uid", "t.ip", "t.email", "t.auth_data", "CAST(t.timestamp as CHAR)"), $get['search'], $where);

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			$where .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$row['auth_data'] = htmlspecialchars($row['auth_data']);
			$row['email'] = htmlspecialchars($row['email']);
			$username = $row['uid'] ? htmlspecialchars($row['username']) . " ({$row['uid']})" : $lang->none;

			// Pobranie danych o usłudze, która została kupiona
			$service = $heart->get_service($row['service']);

			// Pobranie danych o serwerze na ktorym zostala wykupiona usługa
			$server = $heart->get_server($row['server']);

			// Przerobienie ilosci
			$amount = $row['amount'] != -1 ? "{$row['amount']} {$service['tag']}" : $lang->forever;

			// Poprawienie timestampa
			$row['timestamp'] = convertDate($row['timestamp']);

			// Rozkulbaczenie extra daty
			$row['extra_data'] = json_decode($row['extra_data'], true);
			$extra_data = array();
			foreach ($row['extra_data'] as $key => $value) {
				if (!strlen($value))
					continue;

				$value = htmlspecialchars($value);

				if ($key == "password")
					$key = $lang->password;
				else if ($key == "type") {
					$key = $lang->type;
					$value = get_type_name($value);
				}

				$extra_data[] = $key . ": " . $value;
			}
			$row['extra_data'] = implode("<br />", $extra_data);

			// Pobranie linku płatności
			$payment_link = "admin.php?pid=payment_{$row['payment']}&payid={$row['payment_id']}";

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/bought_services_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		$buttons = eval($templates->render("admin/form_search"));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/bought_services_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

}