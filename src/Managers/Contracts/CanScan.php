<?php
/**
 * Created by PhpStorm.
 * User: maximkerstens
 * Date: 16/11/2016
 * Time: 07:14
 */

namespace DragonFly\TranslationManager\Managers\Contracts;


interface CanScan
{
    /**
     * Scans whatever the manager provides acccess to and adds missing translations.
     *
     * @return int number of keys found
     */
    public function scan();
}