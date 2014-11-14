<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 4:41 PM
 */

namespace ActiveAuth;


interface TwoFactorAuthenticationInterface
{
    public function signIn($username);

    public function verify($response);
}