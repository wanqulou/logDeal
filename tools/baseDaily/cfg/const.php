<?php
// $Id: const.php 1445 2014-04-11 10:25:26Z sunxiang $

// debug level
define('_DEBUG_NORMAL',  0);
define('_DEBUG_ERROR',   1);
define('_DEBUG_WARNING', 2);
define('_DEBUG_NOTICE',  3);
define('_DEBUG_INFO',    4);

// dir
define('_SUB_RUN_DIR', 'run');
define('_SUB_TMP_DIR', 'tmp');

// cmd path
define('_CMD_MKDIR_PATH', '/bin/mkdir');
define('_CMD_MV_PATH',    '/bin/mv');
define('_CMD_RM_PATH',    '/bin/rm');
define('_CMD_CP_PATH',    '/bin/cp');
define('_CMD_GZCAT_PATH', '/usr/bin/gzcat');
define('_CMD_BZCAT_PATH', '/usr/bin/bzcat');
define('_CMD_BZIP2_PATH', '/usr/bin/bzip2');
define('_CMD_GZIP_PATH',  '/usr/bin/gzip');
define('_CMD_TAR_PATH',   '/usr/bin/tar');

// logs offset
define('_OFFSET_ADNETWORKID',     0);
define('_OFFSET_ADNETWORK_OWNER', 1);
define('_OFFSET_CHANNEL_USERID',  2);
define('_OFFSET_APP_OWNER',       3);
define('_OFFSET_APPLICATIONID',   4);
define('_OFFSET_CAMPTYPE',        5);
define('_OFFSET_LOGTYPE',         6);
define('_OFFSET_DATACOUNT',       7);
define('_OFFSET_BILLING_COST',    8);
define('_OFFSET_APP_AMOUNT',      9);
define('_OFFSET_CHANNEL_AMOUNT', 10);
define('_OFFSET_REPORTTS',       11);

// log type
define('_DATA_REQUEST',      0); //请求
define('_DATA_IMPRESSION',   1); //展示
define('_DATA_CLICK',       11); //点击

// sleep
define('_PROC_SLEEP', 2);

//syslog tag
define('_SYSLOG_TAG', 'BaseDaily');