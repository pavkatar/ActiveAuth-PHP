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

/**
 * Class TwoFactorAuthentication
 * @package ActiveAuth
 */
class TwoFactorAuthentication implements TwoFactorAuthenticationInterface, ConfigAwareInterface
{
    /**
     * @var config - stores the main config with keys and etc to the server
     */
    private $config;
    /**
     * @var client - Stores the guzzle object to the activeauth server
     */
    private $client;

    /**
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (count($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * @param $username for the activeauth.me control panel
     * @return string
     */
    public function signIn($username)
    {
        $applicationSignature = $this->generateSignature($username, 'APP', 3600);
        $serverSignature = $this->generateSignature($username, 'SRV', 300);

        return $applicationSignature . ':' . $serverSignature;
    }

    /**
     * @param $response
     * @return mixed
     * @throws UnauthorizedException
     */
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

    /**
     * @param $username
     */
    public function getDevices($username)
    {
        //Todo: Implement this method in the server

    }

    /**
     * @param $username for the activeauth.me control panel
     * @param $deviceId the device on witch we will send the requests
     * @return array
     * @throws MissingConfigException
     * @throws RequestFaildException
     */
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

    /**
     * @param $username for the activeauth.me control panel
     * @param $deviceId the device on witch we will send the requests
     * @param $type
     * @return mixed
     */
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

    /**
     * @param $username for the activeauth.me control panel
     * @param $deviceId the device on witch we will send the requests
     * @param $type
     * @return mixed
     * @throws MissingConfigException
     */
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

    /**
     * @param $username for the activeauth.me control panel
     * @param $deviceId the device on witch we will send the requests
     * @param $code
     * @return mixed
     * @throws MissingConfigException
     */
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

    /**
     * @param $username for the activeauth.me control panel
     * @param bool $iframe
     * @return \GuzzleHttp\Stream\StreamInterface|mixed|null|string
     * @throws MissingConfigException
     */
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


    /**
     * @return Client
     */
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

    /**
     * @param BeforeEvent $event
     * @param $name
     */
    public function prepareClientRequest(BeforeEvent $event, $name)
    {
        $event->getRequest()->setHeader('Accept', 'application/hal+json;');
        $event->getRequest()->setHeader('User-Agent', 'ApiHawkClient');
    }

    /**
     * @param $username
     * @param $prefix
     * @param int $expire
     * @return string
     * @throws MissingConfigException
     * @throws \Exception
     */
    private function generateSignature($username, $prefix, $expire = 3600)
    {
        $expireTime = time() + $expire;
        $accountString = base64_encode($username . '|' . $this->getConfig()['client_id'] . '|' . $expireTime);
        $infoString = $prefix . '|' . $accountString;
        $signature = hash_hmac('sha1', $infoString, $this->getKey($prefix));
        return $infoString . '|' . $signature;
    }

    /**
     * @param $signature
     * @param $prefix
     * @return mixed
     * @throws UnauthorizedException
     * @throws \Exception
     */
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

    /**
     * @param $prefix
     * @return mixed
     * @throws MissingConfigException
     * @throws \Exception
     */
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

    /**
     * @return mixed
     * @throws MissingConfigException
     */
    public function getConfig()
    {
        if (!count($this->config)) {
            throw new MissingConfigException('Need configuration data to use ActiveAuth 2FA');
        }
        return $this->config;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }
}