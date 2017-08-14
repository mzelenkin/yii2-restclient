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

use Yii;
use yii\base\Action;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Class DebugAction is used by [[DebugPanel]] to perform Rest Client queries using ajax.
 * @package apexwire\restclient
 */
class DebugAction extends Action
{
    /**
     * @var string the connection id to use
     */
    public $db;
    /**
     * @var DebugPanel
     */
    public $panel;
    /**
     * @var \yii\debug\controllers\DefaultController
     */
    public $controller;

    /**
     * @param $logId
     * @param $tag
     * @return array
     * @throws HttpException
     * @throws NotSupportedException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($logId, $tag)
    {
        $this->controller->loadData($tag);

        $timings = $this->panel->calculateTimings();
        ArrayHelper::multisort($timings, 3, SORT_DESC);
        if (!isset($timings[$logId])) {
            throw new HttpException(404, 'Log message not found.');
        }
        $message = $timings[$logId][1];
        $data = [];
        $options = [];
        if (($pos = mb_strpos($message, '#')) !== false) {
            $url = mb_substr($message, 0, $pos);
            $params = unserialize(mb_substr($message, $pos + 1));
            if (isset($params['data']) && !empty($params['data'])) {
                $data = $params['data'];
            }
            if (isset($params['options']) && !empty($params['options'])) {
                $options = $params['options'];
            }
        } else {
            $url = $message;
            $body = null;
        }
        $method = mb_substr($url, 0, $pos = mb_strpos($url, ' '));
        $url = mb_substr($url, $pos + 1);

        /* @var $db Connection */
        $db = \Yii::$app->get($this->db);
        $time = microtime(true);
        switch ($method) {
            case 'GET':
                $result = $db->get($url, $data, $options);
                break;
            case 'POST':
                $result = $db->post($url, $data, $options);
                break;
            case 'PUT':
                $result = $db->put($url, $data, $options);
                break;
            case 'DELETE':
                $result = $db->delete($url, $data, $options);
                break;
            case 'HEAD':
                $result = $db->head($url, $data, $options);
                break;
            default:
                throw new NotSupportedException("Request method '$method' is not supported by HiArt.");
        }
        $time = microtime(true) - $time;
        $now = microtime(true);
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'time' => date('H:i:s.', $now) . sprintf('%03d', (int)(($now - (int)$now) * 1000)),
            'duration' => sprintf('%.1f ms', $time * 1000),
            'result' => $result,
        ];
    }
}
