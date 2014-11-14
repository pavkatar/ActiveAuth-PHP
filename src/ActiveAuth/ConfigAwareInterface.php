<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 4:46 PM
 */

namespace ActiveAuth;

interface ConfigAwareInterface
{
    public function getConfig();

    public function setConfig(array $config);
}