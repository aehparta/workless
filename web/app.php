<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php';
$kernel = kernel::getInstance();
try {
    $controller = $kernel->load(__DIR__ . '/../config/');
    $kernel->render($controller);
    /* append to history */
    $kernel->historyAppend($_SERVER['REQUEST_URI']);
}
catch (RedirectException $e)
{
    /* do redirect */
    http_response_code($e->getCode());
    header('Location: ' . $e->getMessage());
    exit;
}
catch (Exception $e)
{
    $code = $e->getCode();
    if ($code < 100)
    {
        $code = 500;
    }
    if ($kernel->format == 'json')
    {
        json_exception($e, $code);
    }
    else
    {
        html_exception($e, $code, $code . '.html');
    }
}
function json_exception($e, $code)
{
    $kernel = kernel::getInstance();
    http_response_code($code);
    $ret = array(
        'success' => false,
        'msg'     => $e->getMessage(),
    );
    if ($kernel->config['setup']['debug'])
    {
        $ret['trace'] = array();
        foreach ($e->getTrace() as $n => $trace)
        {
            $ret['trace'][] = array(
                'file' => isset($trace['file']) ? $trace['file'] : false,
                'line' => isset($trace['line']) ? $trace['line'] : false,
            );
        }
    }
    echo json_encode($ret);
}
function html_exception($e, $code, $template)
{
    $kernel = kernel::getInstance();
    http_response_code($code);
    if ($kernel->config['setup']['debug'])
    {
        echo '<pre>';
        echo "Exception:\n";
        echo $e->getMessage() . "\n\n";
        foreach ($e->getTrace() as $n => $trace)
        {
            echo '#' . $n;
            if (isset($trace['file']) && isset($trace['file']))
            {
                echo ' ' . $trace['file'] . ':' . $trace['line'] . "\n";
            }
            else
            {
                echo "\n";
            }
        }
    }
    else
    {
        $config = array(
            ROUTE_KEY_CONTROLLER => 'Common',
            ROUTE_KEY_ACTION     => 'error' . $code,
        );
        $class      = CONTROLLER_CLASS_BASE;
        $controller = new $class('common', $config, false, false);
        $controller->display($template, array('code' => $code, 'msg' => $e->getMessage()));
    }
}
