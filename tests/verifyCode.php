<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 10:08 PM
 */

include '../vendor/autoload.php';

use ActiveAuth\TwoFactorAuthentication;

$activeAuth = new TwoFactorAuthentication(include '../config/module.config.php');
$result = $activeAuth->verifyCode('pavkatar@gmail.com', '24cc6b99', '0481205');
var_dump($result);