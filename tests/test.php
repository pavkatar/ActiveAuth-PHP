<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 5:22 PM
 */

include '../vendor/autoload.php';

use ActiveAuth\TwoFactorAuthentication;

$activeAuth = new TwoFactorAuthentication(include '../config/module.config.php');
$result = $activeAuth->checkDevice('pavkatar@gmail.com', '24cc6b99');
var_dump($result);


$result = $activeAuth->sendCode('pavkatar@gmail.com', '24cc6b99', 'sms');
var_dump($result);

$result = $activeAuth->verifyCode('pavkatar@gmail.com', '24cc6b99', '0481205');
var_dump($result);