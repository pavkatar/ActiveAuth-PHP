<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 4:46 PM
 */

namespace ActiveAuth;

/**
 * Interface ConfigAwareInterface
 * @package ActiveAuth
 */
interface ConfigAwareInterface
{
    /**
     * @return mixed
     */
    public function getConfig();

    /**
     * @param array $config
     * @return mixed
     */
    public function setConfig(array $config);
}