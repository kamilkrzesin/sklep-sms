<?php
namespace App\Repositories;

use App\Models\Server;
use App\System\Database;

class ServerRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return Server[]
     */
    public function all()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "servers`");

        $servers = [];
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $servers[] = $this->mapToModel($row);
        }

        return $servers;
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "servers` WHERE `id` = '%d'",
                    [$id]
                )
            );

            if ($data = $this->db->fetchArrayAssoc($result)) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($name, $ip, $port, $smsService = '')
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "servers` " .
                    "SET `name`='%s', `ip`='%s', `port`='%s', `sms_service`='%s'",
                [$name, $ip, $port, $smsService]
            )
        );

        return $this->get($this->db->lastId());
    }

    private function mapToModel(array $data)
    {
        return new Server(
            intval($data['id']),
            $data['name'],
            $data['ip'],
            $data['port'],
            $data['sms_service'],
            $data['type'],
            $data['version']
        );
    }
}
