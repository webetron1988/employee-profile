<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\Encryptor;

class Services extends BaseService
{
    /**
     * Return the Encryptor service instance
     *
     * @return Encryptor
     */
    public static function encryptor($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('encryptor');
        }

        return new Encryptor();
    }
}
