<?php
/**
 * $Id$
 */
// Garbage Collection (PHP >= 5.3)
if (function_exists('gc_enable'))
{
    gc_enable();
}

// timezone
$tzStr = empty($_SERVER['TZ']) ? 'Asia/Shanghai' : $_SERVER['TZ'];
date_default_timezone_set($tzStr);

// 初始化全局变量
$GLOBALS['OPTIONS'] = array('loaded' => false);

//角色
$GLOBALS['_daemon']['role'] = 'master';   //default master

//状态
$GLOBALS['_daemon']['status'] = 'foreground'; //状态,前台或者后台,foreground/background
$GLOBALS['_daemon']['count'] = 0;

//time info
list($usec, $GLOBALS['currentTime']) = explode(" ", microtime());
$GLOBALS['firstStamp'] = (float) $usec + (float) $GLOBALS['currentTime']; //请求开始时间
$GLOBALS['lastStamp'] = $GLOBALS['firstStamp'];
$GLOBALS['requestDuration'] = 0;

/**
 * command line params
 * 注意这些参数默认是以'@'开头的,以避免跟hphp等冲突
 */
$GLOBALS['PARAMS'] = _readArgv();

//自定义的配置文档, 必须为ini格式
$GLOBALS['configFile'] = !empty($GLOBALS['PARAMS']['c']) ? $GLOBALS['PARAMS']['c'] : null;

//程序设置(综合参数以及配置文件,支持多次load,这之前的设置都是不能reload)
_loadOptions();

/**
 * workers
 */
$GLOBALS['_daemon']['_WORKERROOT_'] = _DAEMON_ROOT . _SUBDIR_WORKERS;
$GLOBALS['_daemon']['runningWorkers'] = array();

if (!empty($GLOBALS['OPTIONS']['workers']))
{
    foreach ($GLOBALS['OPTIONS']['workers'] as $workerTitle => $workerInfo)
    {
        list($scriptFile, $workerCount, $maxLoop) = array_pad(explode('*', $workerInfo), 3, 0);   //脚本名, worker数, 最大轮询数(处理几次结束)
        $workerCount = ((int)$workerCount === 0) ? 1 : (int)$workerCount;
        $maxLoop = ((int)$maxLoop === 0) ? 1 : (int)$maxLoop;
        if (empty($GLOBALS['OPTIONS']['title']) && !empty($workerTitle))   //如果没有定义daemon_title, 则用第一个
        {
            $GLOBALS['OPTIONS']['title'] = $workerTitle;
        }
        $workScript = $GLOBALS['_daemon']['_WORKERROOT_'] . '/' . $scriptFile;
        $GLOBALS['_daemon']['runningWorkers'][$workerTitle] = array(
            'script' => $workScript,
            'wcount' => $workerCount,
        );
        for ($i = 1; $i <= $workerCount; $i++)
        {
            $GLOBALS['_daemon']['runningWorkers'][$workerTitle]["#{$i}"] = array(
                'realTitle' => $workerTitle,
                'title' => "{$workerTitle}#{$i}",
                'max' => $maxLoop,
                'sn' => $i,
                'script' => $workScript,
                'pid' => 0, //spawn成功后改变
                'stat' => _PSTAT_STANDBY,
            );
        }
    }
}

// 注册debug的syslog信息,$GLOBALS['sysLog']['_debug']
_syslogRegister($GLOBALS['OPTIONS']['debug_log'], '_debug', $GLOBALS['OPTIONS']['title']);

if (empty($GLOBALS['OPTIONS']['title'])) // 必须要有
{
    _debug("Not Found Process Title", _DLV_EMERG);
    _shutdown();
}

/**
 * 预加载,加入worker定义的一些常数以及函数(php文件)
 * 这些文件需要放在worker目录下,并且以path=file形式出现,如果一个目录下有多个文件,用','分隔
 */
if (!empty($GLOBALS['OPTIONS']['preload']))
{
    foreach ($GLOBALS['OPTIONS']['preload'] as $prePath => $preBases)
    {
        $bases = explode(',', $preBases);
        foreach ($bases as $base)
        {
            $preFile = $GLOBALS['_daemon']['_WORKERROOT_'] . '/'  .$prePath . '/' . $base;
            if (@include_once($preFile))
            {
                _debug("[pre:{$preFile}][loaded]", _DLV_NOTICE);
            }
            else
            {
                _debug("[pre:{$preFile}][load_fail]", _DLV_NOTICE);
            }
        }
    }
}

//run files
$GLOBALS['_daemon']['_RUNROOT_'] = _DAEMON_ROOT . _SUBPATH_RUN;
$GLOBALS['_daemon']['pidFile'] = $GLOBALS['_daemon']['_RUNROOT_'] . '/' . $GLOBALS['OPTIONS']['title'] . '.pid';
_makeDir($GLOBALS['_daemon']['pidFile'], 'f');
$GLOBALS['_daemon']['statusFile'] = $GLOBALS['_daemon']['_RUNROOT_'] . '/' . $GLOBALS['OPTIONS']['title'] . '.status';
_makeDir($GLOBALS['_daemon']['statusFile'], 'f');

//tmp dir
$GLOBALS['_daemon']['tmpDir'] = $GLOBALS['_daemon']['_WORKERROOT_'] . '/' . _SUBPATH_TMP;
_makeDir($GLOBALS['_daemon']['tmpDir']);
