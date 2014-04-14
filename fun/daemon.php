<?php
/**
 * 系统常量
 * $Id$
 */
$GLOBALS['_daemon']['signalsNo'] = array(
    1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17,
    18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31
);

//以下的signal列表并不准确,基本上是linux的signal,如SIGSTOP,在linux是19,在freebsd则是17,可以尝试改一下
$GLOBALS['_daemon']['sigMapping'] = array(  //stop => chld
    17 => 20,
    19 => 17,
    23 => 18,
);

/** 系统信号
 * 'kill -l' gives you a list of signals available on your UNIX.
 * Eg. Ubuntu:
 *
 *  1) SIGHUP      2) SIGINT      3) SIGQUIT      4) SIGILL
 *  5) SIGTRAP      6) SIGABRT      7) SIGBUS      8) SIGFPE
 *  9) SIGKILL    10) SIGUSR1    11) SIGSEGV    12) SIGUSR2
 * 13) SIGPIPE    14) SIGALRM    15) SIGTERM    17) SIGCHLD
 * 18) SIGCONT    19) SIGSTOP    20) SIGTSTP    21) SIGTTIN
 * 22) SIGTTOU    23) SIGURG      24) SIGXCPU    25) SIGXFSZ
 * 26) SIGVTALRM  27) SIGPROF    28) SIGWINCH    29) SIGIO
 * 30) SIGPWR      31) SIGSYS      33) SIGRTMIN    34) SIGRTMIN+1
 * 35) SIGRTMIN+2  36) SIGRTMIN+3  37) SIGRTMIN+4  38) SIGRTMIN+5
 * 39) SIGRTMIN+6  40) SIGRTMIN+7  41) SIGRTMIN+8  42) SIGRTMIN+9
 * 43) SIGRTMIN+10 44) SIGRTMIN+11 45) SIGRTMIN+12 46) SIGRTMIN+13
 * 47) SIGRTMIN+14 48) SIGRTMIN+15 49) SIGRTMAX-15 50) SIGRTMAX-14
 * 51) SIGRTMAX-13 52) SIGRTMAX-12 53) SIGRTMAX-11 54) SIGRTMAX-10
 * 55) SIGRTMAX-9  56) SIGRTMAX-8  57) SIGRTMAX-7  58) SIGRTMAX-6
 * 59) SIGRTMAX-5  60) SIGRTMAX-4  61) SIGRTMAX-3  62) SIGRTMAX-2
 * 63) SIGRTMAX-1  64) SIGRTMAX
 *
 * SIG_IGN, SIG_DFL, SIG_ERR are no real signals
 *
 */
$GLOBALS['_daemon']['signalsName'] = array(
    SIGHUP    => 'SIGHUP',
    SIGINT    => 'SIGINT',
    SIGQUIT   => 'SIGQUIT',
    SIGILL    => 'SIGILL',
    SIGTRAP   => 'SIGTRAP',
    SIGABRT   => 'SIGABRT',
    7         => 'SIGEMT',
    SIGFPE    => 'SIGFPE',
    SIGKILL   => 'SIGKILL',
    SIGBUS    => 'SIGBUS',
    SIGSEGV   => 'SIGSEGV',
    SIGSYS    => 'SIGSYS',
    SIGPIPE   => 'SIGPIPE',
    SIGALRM   => 'SIGALRM',
    SIGTERM   => 'SIGTERM',
    SIGURG    => 'SIGURG',
    SIGSTOP   => 'SIGSTOP',
    SIGTSTP   => 'SIGTSTP',
    SIGCONT   => 'SIGCONT',
    SIGCHLD   => 'SIGCHLD',
    SIGTTIN   => 'SIGTTIN',
    SIGTTOU   => 'SIGTTOU',
    SIGIO     => 'SIGIO',
    SIGXCPU   => 'SIGXCPU',
    SIGXFSZ   => 'SIGXFSZ',
    SIGVTALRM => 'SIGVTALRM',
    SIGPROF   => 'SIGPROF',
    SIGWINCH  => 'SIGWINCH',
    28        => 'SIGINFO',
    SIGUSR1   => 'SIGUSR1',
    SIGUSR2   => 'SIGUSR2',
);



/**
 * _spawnWorker
 * Spawn new worker processes
 */
function _spawnWorker($workerDetail)
{
    $pid = pcntl_fork();
    if ($pid === -1) 
    {
        _debug('[' . __FUNCTION__ . '][Could not fork]', _DLV_EMERG);
    }
    else if ($pid == 0)
    {
        //worker,这就是一个新的进程了,跟原来的不怎么相关
        $GLOBALS['_daemon']['role'] = 'worker';
        $GLOBALS['_daemon']['workerPid'] = posix_getpid();
        $GLOBALS['_daemon']['workerRun'] = true;
        $GLOBALS['_daemon']['workerCode'] = 0;
        $GLOBALS['_daemon']['title'] = $workerDetail['title'];
        $GLOBALS['_daemon']['realTitle'] = $workerDetail['realTitle'];
        $GLOBALS['_daemon']['sn'] = $workerDetail['sn'];
        $GLOBALS['_daemon']['workerScript'] = $workerDetail['script'];

        _setProcessTitle("{$GLOBALS['OPTIONS']['title']}:{$GLOBALS['_daemon']['title']}");

        _registerSignals($GLOBALS['_daemon']['role']);

        $loop=0;
        while ($GLOBALS['_daemon']['workerRun'] === true)
        {
            pcntl_signal_dispatch();

            include($workerDetail['script']);

            $loop++;
            _iterate(0.5);

            //check max loop
            if ($GLOBALS['_daemon']['workerRun'] === null)
            {
                $GLOBALS['_daemon']['workerCode'] = 1;    //null代表不要在respawn了
            }
            else if ($loop >= $workerDetail['max'])
            {
                _debug("[loop:$loop][reach_max]", _DLV_WARNING);
                $GLOBALS['_daemon']['workerRun'] = false;

                if ($GLOBALS['OPTIONS']['mode'] === 'run') //daemon设置为run,此时不需要再重启
                {
                    $GLOBALS['_daemon']['workerCode'] = 1;
                }
            }
        }

        _shutdown($GLOBALS['_daemon']['workerCode']);
    }

    return $pid;
}

/**
 * registerSignals
 * 注册系统信号
 * @return void
 */
function _registerSignals($roleTag)
{
    foreach ($GLOBALS['_daemon']['signalsName'] as $signo => $name)
    {
        if ($name === 'SIGKILL' || $name == 'SIGSTOP') 
        {
            //SIGKILL(9), SIGSTOP(19), 是不能被caught或者ignored的
            continue;
        }

        $handlerTag = '_' . $roleTag;
        if (!pcntl_signal($signo, $handlerTag . 'SigHandler', true))
        {
            _debug("[" . __FUNCTION__ . "][Cannot assign {$name}({$signo}) signal][it_maybe_stop]", _DLV_EMERG);
            if (isset($GLOBALS['_daemon']['sigMapping'][$signo])) //把对应的chld信号注册,这样做到兼容UNIX/Linux
            {
                $chldSig = $GLOBALS['_daemon']['sigMapping'][$signo];
                if ($GLOBALS['_daemon']['signalsName'][$chldSig] != 'SIGCHLD')
                {
                    $GLOBALS['_daemon']['signalsName'][$chldSig] = 'SIGCHLD';
                    $GLOBALS['_daemon']['signalsName'][$signo] = 'SIGSTOP';
                }
                pcntl_signal($chldSig, $handlerTag . 'SigHandler', true);
            }
        }
    }
}


/**
 * _masterSigHandler
 */
function _masterSigHandler($signo)
{
    _debug("[" . __FUNCTION__ . "][Caught:{$GLOBALS['_daemon']['signalsName'][$signo]}($signo)]", _DLV_WARNING);
    
    //解决不同系统SIGCHLD不同的问题
    if ($GLOBALS['_daemon']['signalsName'][$signo] == 'SIGCHLD')
    {
        $sigCHLD = $signo;
    }
    else
    {
        $sigCHLD = SIGCHLD;
    }
    switch ($signo)
    {
        case SIGTERM:
        case SIGHUP:
            // If we are being restarted or killed, quit all workers
            // Send the same signal to the children which we recieved
            foreach ($GLOBALS['_daemon']['runningWorkers'] as $workerTitle => $workerStatus)
            {
                if ($workerStatus['wcount'] > 0)
                {
                    for ($wSN = 1; $wSN <= $workerStatus['wcount']; $wSN++)
                    {
                        if ($workerStatus["#{$wSN}"]['stat'] === _PSTAT_RUNNING)
                        {
                            //signal worker
                            $pid = $workerStatus["#{$wSN}"]['pid'];
                            $title = $workerStatus["#{$wSN}"]['title'];
                            _debug("[" . __FUNCTION__ . "][worker:{$title}][pid:{$pid}][sent:{$signo}]", _DLV_WARNING);
                            posix_kill($pid, $signo);
                        }
                    }
                }
            }
            $GLOBALS['_daemon']['masterRun'] = false; //graceful shutdown master
            _debug("[" . __FUNCTION__ . "][master({$GLOBALS['_daemon']['masterPid']}) will graceful quit]", _DLV_WARNING);
            break;
        case SIGINT:
            //这个重启旗下所有worker
            foreach($GLOBALS['_daemon']['runningWorkers'] as $workerTitle => $workerStatus)
            {
                if ($workerStatus['wcount'] > 0) {
                    for ($wSN = 1; $wSN <= $workerStatus['wcount']; $wSN++)
                    {
                        if ($workerStatus["#{$wSN}"]['stat'] !== _PSTAT_RUNNING && $workerStatus["#{$wSN}"]['max'] > 1) {   //重启loop大于1的
                            $GLOBALS['_daemon']['runningWorkers'][$workerTitle]["#{$wSN}"]['stat'] = _PSTAT_STANDBY;
                            $title = $workerStatus["#{$wSN}"]['title'];
                            _debug("[" . __FUNCTION__ . "][worker:{$title}][turn_on]", _DLV_WARNING);
                        }
                    }
                }
            }
            break;
        case $sigCHLD:
            //Handler for the SIGCHLD (child is dead) signal in master process
            _waitPid();
            break;
        case SIGUSR1:
            //reload options
            _loadOptions();
            _debug("[" . __FUNCTION__ . "]-[reloaded]", _DLV_WARNING);
            break;
        default:
            break;
    }
}

/**
 *_waitPid
 * Called when the signal SIGCHLD caught
 */
function _waitPid()
{
    $ret = false;
    while(($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0)
    {
        if (!empty($GLOBALS['_daemon']['runningWorkers']['_pids']) && isset($GLOBALS['_daemon']['runningWorkers']['_pids'][$pid]))
        {
            $workerTitle = $GLOBALS['_daemon']['runningWorkers']['_pids'][$pid];
            $rCode = pcntl_wexitstatus($status);
            list($title, $sn) = explode('#', $workerTitle);
            if ($rCode != 1) // 不为1的,都重新启动
            {
                //update status
                _debug("[" . __FUNCTION__ . "][pid:{$pid}][{$title}#{$sn}][rcode:$rCode][will_respawn]", _DLV_WARNING);
                $GLOBALS['_daemon']['runningWorkers'][$title]["#{$sn}"]['stat'] = _PSTAT_STANDBY;
            }
            else //其它返回码,不再重新启动,标记为死亡
            {
                _debug("[" . __FUNCTION__ . "][pid:{$pid}][{$title}#{$sn}][rcode:$rCode][not_need_respawn]", _DLV_WARNING);
                $GLOBALS['_daemon']['runningWorkers'][$title]["#{$sn}"]['stat'] = _PSTAT_DEAD;
            }
            //从活动进程表中去除
            unset($GLOBALS['_daemon']['runningWorkers']['_pids'][$pid]);
            $ret = true;
        }
    }

    return $ret;
}

/**
 * _waitAll
 */
function _waitAll()
{
    do
    {
        $n = 0;
        if (empty($GLOBALS['_daemon']['runningWorkers']['_pids']))
        {
            break;
        }
        foreach ($GLOBALS['_daemon']['runningWorkers']['_pids'] as $wPid => $workerTitle)
        {
            list($title, $sn) = explode('#', $workerTitle);
            //check status
            if ($GLOBALS['_daemon']['runningWorkers'][$title]["#{$sn}"]['stat'] == _PSTAT_RUNNING)
            {
                //alive, terminate it
                _debug("[$wPid][$workerTitle][status:running][terminate_it]");
                if ($GLOBALS['OPTIONS']['mode'] === 'daemon') //模式为daemon,就kill，模式为run,等!
                {
                    posix_kill($wPid, SIGTERM);
                }
                ++$n;
            }
            else
            {
                $stat = $GLOBALS['_daemon']['runningWorkers'][$title]["#{$sn}"]['stat'];
                _debug("[$wPid][$workerTitle][status:{$stat}][not_alive]");
            }
        }

        if (!_waitPid())
        {
            _sigwait(0, 20000);
        }
    }
    while ($n > 0);
}

/**
 * _workerSigHandler
 */
function _workerSigHandler($signo)
{
    _debug("[" . __FUNCTION__ . "][Caught:{$GLOBALS['_daemon']['signalsName'][$signo]}($signo)]", _DLV_WARNING);
    switch($signo)
    {
        case SIGTERM:
            $GLOBALS['_daemon']['workerRun'] = null;
            _debug("[" . __FUNCTION__ . "][I({$GLOBALS['_daemon']['workerPid']}) will graceful quit][not_respawn]", _DLV_WARNING);
            break;
        case SIGHUP:
        case SIGINT:
            $GLOBALS['_daemon']['workerRun'] = false; //graceful shutdown worker
            _debug("[" . __FUNCTION__ . "][I({$GLOBALS['_daemon']['workerPid']}) will graceful quit][and_respawn]", _DLV_WARNING);
            break;
        case SIGUSR1:
            //reload options
            _loadOptions();
            _debug("[" . __FUNCTION__ . "]-[reload]", _DLV_WARNING);
            break;
        default:
            break;
    }
}

/**
 * 给process加标题,需要pecel扩展支持
 * Sets a title of the current process
 * @param string Title
 * @return void
 */
function _setProcessTitle($title) 
{
    if (function_exists('setproctitle'))
    {
        return setproctitle($title);
    }
    return false;
}

/**
 * _iterate
 * 循环函数, 代替以前简单的sleep, 支持小数点
 */
function _iterate($sleepSeconds = 0)
{
    if ($sleepSeconds > 0)
    {
        usleep($sleepSeconds * 1000000);
    }

    clearstatcache();

    // Garbage Collection (PHP >= 5.3)
    if (function_exists('gc_collect_cycles'))
    {
        gc_collect_cycles();
    }
    return true;
}

/**
 * _onShutdown
 * 退出时判断
 */
function _onShutdown()
{
    if (($GLOBALS['_daemon']['role'] == 'master' && $GLOBALS['_daemon']['masterRun'] == false)
        || ($GLOBALS['_daemon']['role'] == 'worker' && $GLOBALS['_daemon']['workerRun'] == false))
    {
        _debug("[byebye]", _DLV_WARNING);
        return;
    }

    _debug('[Unexcepted shutdown]', _DLV_EMERG);

    _shutdown();
}

/**
 * 退出
 */
function _shutdown($code = 0)
{
    if ($GLOBALS['_daemon']['role'] == 'master')
    {
        //wait all workers, 防止僵尸
        _debug("[wait_all_child_then_quit]", _DLV_EMERG);
        _waitAll();
    }
    else
    {
        //worker
        //posix_kill(posix_getppid(), SIGCHLD); //系统会自动发
    }

    exit($code);
}

/**
 * 载入程序设置
 */
function _loadOptions()
{
    // 读配置文件
    $config = array();
    if (!empty($GLOBALS['configFile']) && file_exists($GLOBALS['configFile']))
    {
        $config = @parse_ini_file($GLOBALS['configFile'], true);
    }

    //default setting
    if ($GLOBALS['OPTIONS']['loaded'] == false) //默认设置只需要载入一次
    {
        $GLOBALS['OPTIONS'] = array(
            'loaded' => true,   //默认设置只需要载入一次
            'mode' => 'daemon',  //运行模式,默认为daemon
            'workers' => array('default' => 'worker.php'), //worker脚本
            'debug_log' => 'local6.debug',
            'show_debug' => false,
            'debug_level' => _DLV_WARNING
        );
    }
    else
    {
        _debug("[" . __FUNCTION__ . "][reload]", _DLV_WARNING);
    }

    //以下，如果参数有则覆盖
    foreach ($config as $cKey => $cValue)
    {
        $GLOBALS['OPTIONS'][$cKey] = $cValue;
    }

    if (isset($GLOBALS['PARAMS']['t']))
    {
        $GLOBALS['OPTIONS']['title'] = $GLOBALS['PARAMS']['t'];
    }
    
    if (isset($GLOBALS['PARAMS']['m']))
    {
        $GLOBALS['OPTIONS']['mode'] = $GLOBALS['PARAMS']['m'];
    }

    if (isset($GLOBALS['PARAMS']['d']))
    {
        $GLOBALS['OPTIONS']['debug_level'] = _getDebugLevel($GLOBALS['PARAMS']['d']);
    }

    return true;
}

/**
 * _sigwait
 */
function _sigwait($sec = 0, $nano = 1)
{
    $siginfo = null;
    $signo = pcntl_sigtimedwait($GLOBALS['_daemon']['signalsNo'], $siginfo, $sec, $nano);

    if (is_bool($signo))
    {
        return $signo;
    }

    if ($signo > 0)
    {
        _workerSigHandler($signo);
        return true;
    }
    return false;
}