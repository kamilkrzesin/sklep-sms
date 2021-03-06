<?php
namespace App\Models;

class PaymentTransfer
{
    /** @var string */
    private $id;

    /** @var int */
    private $income;

    /** @var string */
    private $transferService;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    public function __construct($id, $income, $transferService, $ip, $platform)
    {
        $this->id = $id;
        $this->income = $income;
        $this->transferService = $transferService;
        $this->ip = $ip;
        $this->platform = $platform;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @return string
     */
    public function getTransferService()
    {
        return $this->transferService;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
