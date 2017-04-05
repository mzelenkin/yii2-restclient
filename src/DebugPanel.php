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

use yii\debug\Panel;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\log\Logger;
use yii\web\View;
use yii\base\InvalidConfigException;

/**
 * Class DebugPanel Debugger panel that collects and displays Rest Client queries performed.
 * @package apexwire\restclient
 */
class DebugPanel extends Panel
{
    /** @type */
    private $_timings;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->actions['rest-query'] = [
            'class' => 'apexwire\\restclient\\DebugAction',
            'panel' => $this,
            'db' => Connection::getDriverName(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Rest Client';
    }

    /**
     * {@inheritdoc}
     */
    public function getSummary()
    {
        $timings = $this->calculateTimings();
        $queryCount = count($timings);
        $queryTime = 0;
        foreach ($timings as $timing) {
            $queryTime += $timing[3];
        }
        $queryTime = number_format($queryTime * 1000) . ' ms';
        $url = $this->getUrl();
        $output = <<<HTML
<div class="yii-debug-toolbar__block">
    <a href="$url" title="Executed $queryCount queries which took $queryTime.">Rest Client
        <span class="yii-debug-toolbar__label">$queryCount</span>
        <span class="yii-debug-toolbar__label yii-debug-toolbar__label_info">$queryTime</span>
    </a>
</div>
HTML;

        return $queryCount > 0 ? $output : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDetail()
    {
        $apiUrl = null;
        $timings = $this->calculateTimings();
        ArrayHelper::multisort($timings, 3, SORT_DESC);
        $rows = [];
        $i = 0;
        // Try to get API URL
        try {
            $restClient = \Yii::$app->get('restclient');
            $apiUrl = (StringHelper::endsWith($restClient->config['base_uri'], '/'))
                ? $restClient->config['base_uri']
                : $restClient->config['base_uri'] . '/';
        } catch (InvalidConfigException $e) {
            // Pass
        }

        foreach ($timings as $logId => $timing) {
            $time = $duration = '-';
            if (is_double($timing[2])) {
                $time = date('H:i:s.', $timing[2]) . sprintf('%03d', (int)(($timing[2] - (int)$timing[2]) * 1000));
                $duration = sprintf('%.1f ms', $timing[3] * 1000);
            }
            $message = $timing[1];
            $traces = $timing[4];

            if (($pos = mb_strpos($message, '#')) !== false) {
                $url = mb_substr($message, 0, $pos);
                $body = mb_substr($message, $pos + 1);
            } else {
                $url = $message;
                $body = null;
            }

            if (($pos = mb_strpos($message, ' ')) !== false) {
                $method = mb_substr($message, 0, $pos);
            } else {
                $method = null;
            }

            $traceString = '';
            if (!empty($traces)) {
                $traceString .= Html::ul($traces, [
                    'class' => 'trace',
                    'item' => function ($trace) {
                        return "<li>{$trace['class']}{$trace['type']}{$trace['function']}({$trace['line']})</li>";
                    },
                ]);
            }

            $runLink = $newTabLink = '';
            if ($method == 'GET') {
                $runLink = Html::a('run query',
                    Url::to(['rest-query', 'logId' => $logId, 'tag' => $this->tag]),
                    ['class' => 'restclient-link', 'data' => ['id' => $i]]
                );
                $newTabLink = Html::a('to new tab',
                    $apiUrl . preg_replace('/^[A-Z]+\s+/', '', $url) . $body,
                    ['target' => '_blank']
                );
            }

            $url_encoded = Html::encode((isset($apiUrl)) ? str_replace(' ', ' ' . $apiUrl, $url) : $url);
            $body_encoded = Html::encode($body);
            $rows[] = <<<HTML
<tr>
    <td style="width: 10%;">$time</td>
    <td style="width: 10%;">$duration</td>
    <td style="width: 75%;"><div><b>$url_encoded</b><br/><p>$body_encoded</p>$traceString</div></td>
    <td style="width: 15%;">$runLink<br/>$newTabLink</td>
</tr>
<tr style="display: none;" class="restclient-wrapper" data-id="$i">
    <td class="time"></td>
    <td class="duration"></td>
    <td colspan="2" class="result"></td>
</tr>
HTML;
            ++$i;
        }
        $rows = implode("\n", $rows);

        \Yii::$app->view->registerCss(<<<CSS
.string { color: green; }
.number { color: darkorange; }
.boolean { color: blue; }
.null { color: magenta; }
.key { color: red; }
CSS
        );

        \Yii::$app->view->registerJs(<<<JS
function syntaxHighlight(json) {
    json = json.replace(/&/g, '&').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

$('.restclient-link').on('click', function (event) {
    event.preventDefault();

    var id = $(this).data('id');
    var result = $('.restclient-wrapper[data-id=' + id +']');
    result.find('.result').html('Sending request...');
    result.show();
    $.ajax({
        type: 'POST',
        url: $(this).attr('href'),
        success: function (data) {
            var is_json = true;
            try {
               var json = JSON.parse(data.result);
            } catch(e) {
               is_json = false;
            }
            result.find('.time').html(data.time);
            result.find('.duration').html(data.duration);
            if (is_json) {
                result.find('.result').html( syntaxHighlight( JSON.stringify( JSON.parse(data.result), undefined, 10) ) );
            } else if (data.result instanceof Object) {
                console.log(typeof(data.result));
                var html = '';
                for (var key in data.result) { html += key+':'+data.result[key]+'<br/>'; }
                result.find('.result').html( html );
            } else {
                result.find('.result').html( data.result );
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            result.find('.time').html('');
            result.find('.result').html('<span style="color: #c00;">Error: ' + errorThrown + ' - ' + textStatus + '</span><br />' + jqXHR.responseText);
        },
        dataType: 'json'
    });
    return false;
});
JS
            , View::POS_READY);

        return <<<HTML
<h1>Rest Client Queries</h1>

<table class="table table-condensed table-bordered table-striped table-hover" style="table-layout: fixed;">
<thead>
<tr>
    <th style="width: 10%;">Time</th>
    <th style="width: 10%;">Duration</th>
    <th style="width: 75%;">Url / Query</th>
    <th style="width: 15%;">Run Query on node</th>
</tr>
</thead>
<tbody>
$rows
</tbody>
</table>
HTML;
    }

    /**
     * Расчет времени
     *
     * @return array
     */
    public function calculateTimings()
    {
        if ($this->_timings !== null) {
            return $this->_timings;
        }

        $messages = is_array($this->data['messages']) ? $this->data['messages'] : [];
        $timings = [];
        $stack = [];
        foreach ($messages as $i => $log) {
            list($token, $level, $category, $timestamp) = $log;
            $log[5] = $i;
            if ($level === Logger::LEVEL_PROFILE_BEGIN) {
                $stack[] = $log;
            } elseif ($level === Logger::LEVEL_PROFILE_END) {
                if (($last = array_pop($stack)) !== null && $last[0] === $token) {
                    $timings[$last[5]] = [count($stack), $token, $last[3], $timestamp - $last[3], $last[4]];
                }
            }
        }

        $now = microtime(true);
        while (($last = array_pop($stack)) !== null) {
            $delta = $now - $last[3];
            $timings[$last[5]] = [count($stack), $last[0], $last[2], $delta, $last[4]];
        }
        ksort($timings);

        return $this->_timings = $timings;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $target = $this->module->logTarget;
        $messages = $target->filterMessages($target->messages, Logger::LEVEL_PROFILE,
            ['apexwire\restclient\Connection::handleRequest']);

        return ['messages' => $messages];
    }
}
