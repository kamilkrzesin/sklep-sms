<?php
namespace App\Repositories;

use App\Models\User;
use App\System\Database;

class UserRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(
        $username,
        $password,
        $email,
        $forename,
        $surname,
        $steamId,
        $ip,
        $groups,
        $wallet = 0
    ) {
        $salt = get_random_string(8);
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `wallet`, `steam_id`, `regdate`) " .
                    "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%d','%s',NOW())",
                [
                    $username,
                    hash_password($password, $salt),
                    $salt,
                    $email,
                    $forename,
                    $surname,
                    $ip,
                    $groups,
                    $wallet,
                    $steamId,
                ]
            )
        );

        return $this->get($this->db->lastId());
    }

    public function update(User $user)
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `username` = '%s', `forename` = '%s', `surname` = '%s', `email` = '%s', `groups` = '%s', `wallet` = '%d', `steam_id` = '%s' " .
                    "WHERE `uid` = '%d'",
                [
                    $user->getUsername(),
                    $user->getForename(),
                    $user->getSurname(),
                    $user->getEmail(),
                    implode(";", $user->getGroups()),
                    $user->getWallet(),
                    $user->getSteamId(),
                    $user->getUid(),
                ]
            )
        );
    }

    /**
     * @return User[]
     */
    public function allWithSteamId()
    {
        $result = $this->db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "users` WHERE `steam_id` != ''"
        );

        $users = [];
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $users[] = $this->mapToModel($row);
        }

        return $users;
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare("SELECT * FROM `" . TABLE_PREFIX . "users` WHERE `uid` = '%d'", [
                    $id,
                ])
            );

            if ($data = $this->db->fetchArrayAssoc($result)) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param string $steamId
     * @return User|null
     */
    public function findBySteamId($steamId)
    {
        if (!strlen($steamId)) {
            return null;
        }

        // SID can start with STEAM_0 or STEAM_1. They are used interchangeably.
        $steamIdSuffix = preg_replace("/^STEAM_[01]/", "", $steamId);

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "users` WHERE `steam_id` IN ('STEAM_0%s', 'STEAM_1%s')",
                [$steamIdSuffix, $steamIdSuffix]
            )
        );

        if ($this->db->numRows($result)) {
            $data = $this->db->fetchArrayAssoc($result);
            return $this->mapToModel($data);
        }

        return null;
    }

    /**
     * @param string $emailOrUsername
     * @param string $password
     * @return User|null
     */
    public function findByPassword($emailOrUsername, $password)
    {
        if (!strlen($emailOrUsername) || !strlen($password)) {
            return null;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "users` " .
                    "WHERE (`username` = '%s' OR `email` = '%s') AND `password` = md5(CONCAT(md5('%s'), md5(`salt`)))",
                [$emailOrUsername, $emailOrUsername, $password]
            )
        );

        $data = $this->db->fetchArrayAssoc($result);

        return $data ? $this->mapToModel($data) : null;
    }

    private function mapToModel(array $data)
    {
        return new User(
            intval($data['uid']),
            $data['username'],
            $data['password'],
            $data['salt'],
            $data['email'],
            $data['forename'],
            $data['surname'],
            $data['steam_id'],
            explode(';', $data['groups']),
            $data['regdate'],
            $data['lastactiv'],
            intval($data['wallet']),
            $data['regip'],
            $data['lastip'],
            $data['reset_password_key']
        );
    }
}
