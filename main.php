<?php
/**
 * 主文件
 * $Id$
 */
error_reporting(E_ALL);

define('_DAEMON_ROOT', dirname(__FILE__) . '/');

//const
include_once(_DAEMON_ROOT . 'inc/const.php');

//functions
include_once(_DAEMON_ROOT . 'fun/base.php');
include_once(_DAEMON_ROOT . 'fun/daemon.php');
include_once(_DAEMON_ROOT . 'fun/file.php');


//初始化(读取配置项等)
include_once(_DAEMON_ROOT . 'modules/initDaemon.php');

// Daemonize
include_once(_DAEMON_ROOT . 'modules/daemonize.php');

$loop = 0;
while ($GLOBALS['_daemon']['masterRun'] === true)
{
    $loop++;
    // In a real world scenario we would do some sort of conditional launch.
    // Maybe a condition in a DB is met, or whatever, here we're going to
    // cap the number of concurrent grandchildren
    $workerRun = false;
    foreach ($GLOBALS['_daemon']['runningWorkers'] as $workerTitle => $workerStatus)
    {
        if ($workerTitle == '_pids')
        {
            continue;
        }
        if ($workerStatus['wcount'] > 0)
        {
            //可能一个脚本需要fork多个worker
            for ($wSN=1; $wSN<=$workerStatus['wcount']; $wSN++) 
            {
                if ($workerStatus["#{$wSN}"]['stat'] === _PSTAT_STANDBY) //说明这个worker等着被启动...
                {
                    $workerRun = true; //只要有一个worker还在跑,就标记workerRun为true
                    //spawn worker
                    $title = $workerStatus["#{$wSN}"]['title'];
                    _debug("[{$title}][spawn_it]", _DLV_NOTICE);
                    $wPid = _spawnWorker($workerStatus["#{$wSN}"]);;
                    if (-1 === $wPid)
                    {
                        _debug("[{$workerTitle}][#{$wSN}][spawn_failed]", _DLV_EMERG);
                    }
                    else
                    {
                        _debug("[{$workerTitle}][#{$wSN}][pid:$wPid][spawn_successful]", _DLV_WARNING);
                        $GLOBALS['_daemon']['runningWorkers'][$workerTitle]["#{$wSN}"]['stat'] = _PSTAT_RUNNING;
                        $GLOBALS['_daemon']['runningWorkers'][$workerTitle]["#{$wSN}"]['pid'] = $wPid;
                        $GLOBALS['_daemon']['runningWorkers']['_pids'][$wPid] = "{$workerTitle}#{$wSN}";
                    }
                }
                else if ($workerStatus["#{$wSN}"]['stat'] === _PSTAT_RUNNING)
                {
                    $workerRun = true;
                }
            }
        }
    }

    _iterate(0.5);

    pcntl_signal_dispatch();

    if ($workerRun===false) {   //没有worker在运行了,完成历史使命,退出
        _debug("[no_worker_running]",_DLV_CRIT);
        $GLOBALS['_daemon']['masterRun']=false;
    }
    // show status, once per 500
    if ($loop%500==0) {
        $pidArr=array();
        $titleArr=array();
        foreach ($GLOBALS['_daemon']['runningWorkers'] as $key=>$value) {
            if ($key=='_pids') {
                foreach ($value as $workerPid=>$workerInfo) {
                    $pidArr[]="{$workerPid}:{$workerInfo}";
                }
                $pidStr=implode(',',$pidArr);
            } else {
                $wTitle=$key;
                foreach ($value as $snStr=>$snDetail) {
                    $titleArr[]="{$wTitle}:{$snStr}:{$snDetail['pid']}:{$snDetail['stat']}";
                }
            }
        }
        $titleStr=implode(',',$titleArr);
        _debug("[{$pidStr}][{$titleStr}]",_DLV_CRIT);
    }
}

_shutdown(0);
