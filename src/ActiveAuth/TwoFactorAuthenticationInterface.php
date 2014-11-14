<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 4:41 PM
 */

namespace ActiveAuth;

/**
 * Interface TwoFactorAuthenticationInterface
 * @package ActiveAuth
 */
interface TwoFactorAuthenticationInterface
{
    /**
     * @param $username
     * @return mixed
     */
    public function signIn($username);

    /**
     * @param $response
     * @return mixed
     */
    public function verify($response);
}