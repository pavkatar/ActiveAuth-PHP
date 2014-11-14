<?php
/**
 * Created by PhpStorm.
 * User: pavkatar
 * Date: 11/14/14
 * Time: 4:38 PM
 */

namespace ActiveAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Event\BeforeEvent;
use ActiveAuth\Exception\MissingConfigException;
use ActiveAuth\Exception\UnauthorizedException;
use ActiveAuth\Exception\RequestFaildException;

class TwoFactorAuthentication implements TwoFactorAuthenticationInterface, ConfigAwareInterface
{
    private $config;
    private $client;

    public function __construct(array $config = array())
    {
        if (count($config)) {
            $this->setConfig($config);
        }
    }

    public function signIn($username)
    {
        $applicationSignature = $this->generateSignature($username, 'APP', 3600);
        $serverSignature = $this->generateSignature($username, 'SRV', 300);

        return $applicationSignature . ':' . $serverSignature;
    }

    public function verify($response)
    {
        list($serverResponse, $applicationResponse) = explode(':', $response);
        $serverUser = $this->validateSignatureData($serverResponse, 'SRV');
        $applicationUser = $this->validateSignatureData($applicationResponse, 'APP');

        if ($serverUser != $applicationUser) {
            throw new UnauthorizedException('Server user and application user are not equal!');
        }

        return $serverUser;
    }

    public function getDevices($username)
    {
        //Todo: Implement this method in the server

    }

    public function checkDevice($username, $deviceId)
    {
        $options = array(
            'body' => array(
                'srv' => $this->generateSignature($username, 'SRV'),
                'acaaccount' => $username,
                'device' => $deviceId
            )
        );
        $url = $this->getConfig()['activeauth_server'] . '/sys/checkDevice.wcgp?skin=ACA';

        $response = $this->getClient()->post($url, $options);
        $result = $response->json();
        if ($result['status'] == 'OK') {
            return array(
                'device_id' => $deviceId,
                'status' => true,
                'methods' => array(
                    'sms' => isset($result['smsable']) ? true : false,
                    'phone' => isset($result['callable']) ? true : false,
                    'push' => isset($result['pushable']) ? true : false
                )
            );
        }

        throw new RequestFaildException('The status of the device: ' . $deviceId . " is not ok!");
    }

    public function sendCode($username, $deviceId, $type)
    {
        switch ($type) {
            case 'phone':
                return $this->sendCall($username, $deviceId, 'phone');
            case 'sms':
                return $this->sendCall($username, $deviceId, 'sms');
            case 'push':
                break;
        }
    }

    public function sendCall($username, $deviceId, $type)
    {
        $options = array(
            'body' => array(
                'srv' => $this->generateSignature($username, 'SRV'),
                'acaaccount' => $username,
                'device' => $deviceId,
                'type' => $type == 'phone' ? 'call' : 'text',
                'force' => 1
            )
        );
        $url = $this->getConfig()['activeauth_server'] . '/sys/sendCall.wcgp';

        $response = $this->getClient()->post($url, $options);
        $result = $response->json();

        return $result;
    }

    public function verifyCode($username, $deviceId, $code)
    {
        //Todo: implement remember me
        $options = array(
            'body' => array(
                'srv' => $this->generateSignature($username, 'SRV'),
                'acaaccount' => $username,
                'device' => $deviceId,
                'code' => $code,
                'remember' => 0
            )
        );
        $url = $this->getConfig()['activeauth_server'] . '/sys/verifyCode.wcgp';

        $response = $this->getClient()->post($url, $options);
        $result = $response->json();

        return $result;
    }

    public function get2FABox($username, $iframe = true)
    {
        $params = http_build_query(array(
            'skin' => 'ACA',
            'srv' => $this->generateSignature($username, 'SRV'),
            'acaaccount' => $username,
        ));
        $url = $this->getConfig()['activeauth_server'] . '/sys/index.wcgp?' . $params;
        var_dump($url);

        if ($iframe) {
            $html = '<iframe width="1000" height="600" src="' . $url . '"> </iframe>';
        } else {

            $html = $this->getClient()->get($url)->getBody();
            $html = preg_replace('/\/SkinFiles/', $this->getConfig()['activeauth_server'] . '/SkinFiles', $html);
        }
        return $html;
    }


    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();

            //  $this->client->setDefaultOption('exceptions', false);
            // Add an event to set the Authorization param before sending any request
            $dispatcher = $this->client->getEmitter();
            $dispatcher->on('before', function (BeforeEvent $event, $name) {
                return $this->prepareClientRequest($event, $name);
            });
        }
        return $this->client;
    }

    public function prepareClientRequest(BeforeEvent $event, $name)
    {
        $event->getRequest()->setHeader('Accept', 'application/hal+json;');
        $event->getRequest()->setHeader('User-Agent', 'ApiHawkClient');
    }

    private function generateSignature($username, $prefix, $expire = 3600)
    {
        $expireTime = time() + $expire;
        $accountString = base64_encode($username . '|' . $this->getConfig()['client_id'] . '|' . $expireTime);
        $infoString = $prefix . '|' . $accountString;
        $signature = hash_hmac('sha1', $infoString, $this->getKey($prefix));
        return $infoString . '|' . $signature;
    }

    private function validateSignatureData($signature, $prefix)
    {
        $key = $this->getKey($prefix);
        list($prefix, $accountString, $receivedSignature) = explode('|', $signature);
        $verification = hash_hmac('sha1', $prefix . '|' . $accountString, $key);

        if ($receivedSignature != $verification) {
            throw new UnauthorizedException('Signatures are not equal!');
        }

        list($user, $undefined, $expires) = explode('|', base64_decode($accountString));
        unset($undefined);
        if (time() >= $expires) {
            throw new UnauthorizedException('Signatures is expired!');
        }

        return $user;
    }

    private function getKey($prefix)
    {
        switch ($prefix) {
            case 'SRV':
                return $this->getConfig()['client_secret'];
            case 'APP':
                return $this->getConfig()['application_secret'];
            default:
                throw new \Exception('Unsupported prefix type!');
        }
    }

    public function getConfig()
    {
        if (!count($this->config)) {
            throw new MissingConfigException('Need configuration data to use ActiveAuth 2FA');
        }
        return $this->config;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }
}