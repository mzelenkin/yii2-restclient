<?php

/*
 * Tools to use API as ActiveRecord for Yii2
 *
 * @link      https://github.com/apexwire/yii2-restclient
 * @package   yii2-restclient
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2016, ApexWire
 */

namespace apexwire\restclient;

use Closure;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\httpclient\Exception;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use Yii;

/**
 * Class Connection
 * @package apexwire\restclient
 *
 * Example configuration:
 * ```php
 * 'components' => [
 *     'restclient' => [
 *         'class' => 'apexwire\restclient\Connection',
 *         'config' => [
 *             'baseUrl' => 'https://api.site.com',
 *         ],
 *     ],
 * ],
 * ```
 */
class Connection extends Component
{
    /**  */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @var array Config
     */
    public $config = [];

    /** @var Client */
    protected $_client = null;

    /** @var array authorization config */
    protected $_auth = [];

    /**
     * @var Closure Callback to test if API response has error
     * The function signature: `function ($response)`
     * Must return `null`, if the response does not contain an error.
     */
    protected $_errorChecker;

    /** @type Response */
    protected $_response;

    /**
     * @param $auth
     */
    public function setAuth($auth)
    {
        $this->_auth = $auth;
    }

    /**
     * @return array|mixed
     */
    public function getAuth()
    {
        if ($this->_auth instanceof Closure) {
            $this->_auth = call_user_func($this->_auth, $this);
        }

        return $this->_auth;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->config['baseUrl']) {
            throw new InvalidConfigException('The `baseUrl` config option must be set');
        }
        $this->_client = new Client($this->config);
    }

    /**
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * Returns the name of the DB driver for the current [[dsn]].
     *
     * @return string name of the DB driver
     */
    public static function getDriverName()
    {
        return 'restclient';
    }

    /**
     * Creates a command for execution.
     *
     * @param array $config the configuration for the Command class
     *
     * @return Command the DB command
     */
    public function createCommand($config = [])
    {
        $config['db'] = $this;
        $command = new Command($config);

        return $command;
    }

    /**
     * Creates new query builder instance.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Performs GET HTTP request.
     * @param string $url URL
     * @param array $data query data
     * @param array $options request options
     * @throws \yii\base\InvalidConfigException
     * @return mixed response
     */
    public function get($url, $data = [], $options = [])
    {
        $response = $this->makeRequest('GET', $url, $data, $options);
        if ($response->isOk) {
            return $this->getResponseJsonContent();
        }

        return false;
    }

    /**
     * Performs HEAD HTTP request.
     * @param string $url URL
     * @param array $data query data
     * @param array $options request options
     * @throws \yii\base\InvalidConfigException
     * @return mixed response
     */
    public function head($url, $data = [], $options = [])
    {
        $response = $this->makeRequest('HEAD', $url, $data, $options);
        if ($response->isOk) {
            return $response->getHeaders()->toArray();
        }

        return [];
    }

    /**
     * Performs POST HTTP request.
     * @param string $url URL
     * @param array $data query data
     * @param array $options request options
     * @throws \yii\base\InvalidConfigException
     * @return mixed response
     */
    public function post($url, $data = [], $options = [])
    {
        $response = $this->makeRequest('POST', $url, $data, $options);
        if ($response->isOk) {
            return $this->getResponseJsonContent();
        }

        return false;
    }

    /**
     * Performs PUT HTTP request.
     * @param string $url URL
     * @param array $data query data
     * @param array $options request options
     * @throws \yii\base\InvalidConfigException
     * @return mixed response
     */
    public function put($url, $data = [], $options = [])
    {
        $response = $this->makeRequest('PUT', $url, $data, $options);
        if ($response->isOk) {
            return $this->getResponseJsonContent();
        }

        return false;
    }

    /**
     * Performs DELETE HTTP request.
     * @param string $url URL
     * @param array $data query data
     * @param array $options request options
     * @throws \yii\base\InvalidConfigException
     * @return mixed response
     */
    public function delete($url, $data = [], $options = [])
    {
        $response = $this->makeRequest('DELETE', $url, $data, $options);
        if ($response->isOk) {
            return $this->getResponseJsonContent();
        }

        return false;
    }

    /**
     * Make request and check for error.
     * @param string $method
     * @param string $url URL
     * @param array $data query data, (GET parameters)
     * @param array $options request options, (POST parameters)
     * @throws \yii\base\InvalidConfigException
     * @return mixed response
     */
    public function makeRequest($method, $url, $data = [], $options = [])
    {
        return $this->handleRequest($method, $url, $data, $options);
    }

    /**
     * Handles the request with handler.
     * Returns array or raw response content, if $raw is true.
     *
     * @param string $method POST, GET, etc
     * @param string $url the URL for request, not including proto and site
     * @param array $data the URL for request, not including proto and site
     * @param array $options
     * @return Response
     */
    protected function handleRequest($method, $url, $data = [], $options = [])
    {
        $method = strtoupper($method);
        $profile = $method . ' ' . $url . '#' . serialize(['data' => $data, 'options' => $options]);
        Yii::beginProfile($profile, __METHOD__);
        $this->_response = $this->_client
            ->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setData($data)
            ->setOptions($options)
            ->send();
        Yii::endProfile($profile, __METHOD__);

        return $this->_response;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getResponseJsonContent()
    {
        if (preg_match('|application/json|i', $this->_response->getHeaders()->get('content-type'))) {
            return Json::decode($this->_response->getContent());
        }

        throw new \Exception('Данные не в json формета');
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->_response;
    }
}
