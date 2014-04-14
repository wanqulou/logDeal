<?php
// 基础函数
// $Id: base.php 1318 2014-03-21 10:44:41Z sunxiang $

/**
 * 获取时间
 */
function microtimeFloat()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 保证单进程
 */
function isSingleProcess($processName, $pidFile)
{
    if (file_exists($pidFile) && $fp = @fopen($pidFile, 'rb'))
    {
        flock($fp, LOCK_SH);
        if (filesize($pidFile) > 0)
        {
            $last_pid = trim(fread($fp, filesize($pidFile)));
            fclose($fp);

            if (!empty($last_pid))
            {
                $command = exec("/bin/ps ww -p {$last_pid} -o command=");

                if (strpos($command, $processName) !== false)
                {
                    return false;
                }
            }
        }
    }

    $cur_pid = posix_getpid();

    if ($fp = @fopen($pidFile, 'wb'))
    {
        fputs($fp, $cur_pid);
        ftruncate($fp, strlen($cur_pid));
        fclose($fp);
        return true;
    }
    return false;
}


/**
 * 调试跟踪
 */
function debug($msg, $level = 'normal')
{
    global $facility;
    global $priority;
    global $debug_show;
    global $debug_level;
    global $debug_save;
    
    $level = strtoupper($level);
    $level_cst = @constant('_DEBUG_' . $level);
    if ($level_cst == null)
    {
        $level_cst = _DEBUG_NORMAL;
    }
    if ($level_cst <= $debug_level && !empty($msg))
    {
        $msg = "[{$level}] {$msg}";
        
        if ($debug_show)
        {
            $colors = array(
                _DEBUG_NORMAL  => "\033[0m",
                _DEBUG_ERROR   => "\033[31m",
                _DEBUG_WARNING => "\033[35m",
                _DEBUG_NOTICE  => "\033[33m",
                _DEBUG_INFO    => "\033[32m"
            );
            
            echo $colors[$level_cst] . date('[Y-m-d H:i:s]') . $msg . "\n";
        }
        
        if ($debug_save)
        {
            saveSyslog($msg, $facility, $priority);
        }
    }
}

function saveSyslog($msg, $facility = LOG_LOCAL1, $priority = LOG_INFO, $syslog_tag = _SYSLOG_TAG)
{
    openlog($syslog_tag, LOG_PID, $facility);
    syslog($priority, $msg);
    closelog();
}

