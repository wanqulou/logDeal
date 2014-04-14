<?php
/**
 * $Id: init.php 1445 2014-04-11 10:25:26Z sunxiang $
 */
error_reporting(E_ALL);

$proc_name = $proc_info['filename'];
$proc_dir = $proc_info['dirname'];

if (function_exists('pcntl_fork')) 
{
    $pid = pcntl_fork();
    if ($pid === -1)
    {
        debug('[Process could not be forked]', 'error');
    } else if ($pid)
    {
        debug("[Parent: ".getmypid()."][byebye]", 'notice');
        exit;
    }

    posix_setsid();
}
else
{
    debug('[Process could not be forked]', 'error');
    exit;
}

umask(0);

chdir($proc_dir);

$confs = parse_ini_file('cfg/conf.ini', true);

empty($confs['timezone']) && $confs['timezone'] = 'Asia/Shanghai';
date_default_timezone_set($confs['timezone']);

$run_dir = $proc_dir . '/' . _SUB_RUN_DIR;
$tmp_dir = $proc_dir . '/' . _SUB_TMP_DIR;
!is_dir($run_dir) && makeDir($run_dir);
!is_dir($tmp_dir) && makeDir($tmp_dir);

empty($confs['proc_mode']) && $confs['proc_mode'] = 'run';
empty($confs['logs_limit']) && $confs['logs_limit'] = '40';

global $facility, $priority, $debug_show, $debug_save;

!isset($confs['debug']['save']) && $confs['debug']['save'] = '0';
$confs['debug']['save'] == '1' && $debug_save = true;

empty($confs['debug']['target']) && $confs['debug']['target'] = 'local1.info';
list($facility, $priority) = explode('.', $confs['debug']['target']);
$facility = constant('LOG_' . strtoupper($facility));
$priority = constant('LOG_' . strtoupper($priority));

!isset($confs['debug']['show']) && $confs['debug']['show'] = '0';
$confs['debug']['show'] == '1' && $debug_show = true;

empty($confs['debug']['level']) && $confs['debug']['level'] = 'notice';
!in_array($confs['debug']['level'], array('error', 'warning', 'notice', 'info')) 
    && $confs['debug']['level'] = 'notice';
$debug_level = constant('_DEBUG_' . strtoupper($confs['debug']['level']));

!isset($confs['backup']['enable']) && $confs['backup']['enable'] = '0';
empty($confs['backup']['path']) && $confs['backup']['path'] = 'bak';
empty($confs['backup']['format']) && $confs['backup']['format'] = 'ymd/H';

$pidfile = "{$run_dir}/{$proc_name}.pid";
if (!isSingleProcess($proc_name, $pidfile))
{
    debug("[This script file has already been running...]", 'notice');
    exit;
}

global $db_finance;
$db_finance_host = isset($confs['db']['finance_host']) ? $confs['db']['finance_host'] : null;
$db_finance_user = isset($confs['db']['finance_user']) ? $confs['db']['finance_user'] : null;
$db_finance_pass = isset($confs['db']['finance_pass']) ? $confs['db']['finance_pass'] : null;
$db_finance_db = isset($confs['db']['finance_db']) ? $confs['db']['finance_db'] : null;
$db_finance = @new mysqli($db_finance_host, $db_finance_user, $db_finance_pass, $db_finance_db);
if ($db_finance->connect_error)
{
    debug("[Connect Error({$db_finance->connect_errno}):{$db_finance->connect_error}]", 'error');
    exit;
}

$run = false;