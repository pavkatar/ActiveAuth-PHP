<?php

/*

  Copyright 2014 George Velinov <g.velinov@icn.bg>

  Distributed under the MIT License.

  See accompanying file COPYING or copy at
  http://opensource.org/licenses/MIT

*/

class ActiveAuth
{
  private function _sing($username, $integration_id, $key, $prefix, $expire_after)
  {
    $expire_time = time() + $expire_after;
    $account_string = base64_encode($username.'|'.$integration_id.'|'.$expire_time);
    $info_string = $prefix.'|'.$account_string;
    $signature = hash_hmac('sha1', $info_string, $key);

    return $info_string.'|'.$signature;
  }

  public function sing($username, $integration_id, $server_key, $application_key)
  {
    $application_signature = $this->_sing($username, $integration_id, $application_key, 'APP', 3600);
    $server_signature = $this->_sing($username, $integration_id, $server_key, 'SRV', 300);

    if (!$application_signature || !$server_signature) {
      return false;
    }

    return $application_signature.':'.$server_signature;
  }

  private function _gerUser($signature, $key)
  {
    $now = time();
    list($prefix, $account_string, $sent_signature) = explode('|', $signature);
    $verification = hash_hmac('sha1', $prefix.'|'.$account_string, $key);

    if ($sent_signature != $verification) {
      die('Problem _getUser');
    }

    list($user, $undefined, $expires) = explode('|', base64_decode($account_string));

    if (time() >= $expires) {
      die('Time expires.');
    }

    return $user;
  }

  public function verify($response, $server_key, $application_key)
  {
    list($server_response, $application_response) = explode(':', $response);
    $server_user = $this->_gerUser($server_response, $server_key);
    $application_user = $this->_gerUser($application_response, $application_key);

    if ($server_user != $application_user) {
      die('Users not equal.');
    }

    return $server_user;
  }
}
