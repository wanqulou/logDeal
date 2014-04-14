<?php
/**
 * daemon常量
 * $Id$
 */
define('_SUBDIR_WORKERS', 'workers');
define('_SUBDIR_DATA',    'data');

define('_SUBPATH_RUN', 'run');
define('_SUBPATH_TMP', 'tmp');

//process status
define('_PSTAT_STANDBY', 0);
define('_PSTAT_RUNNING', 1);
define('_PSTAT_DEAD',    2);  //死亡
define('_PSTAT_ZOMBIE',  3);  //僵尸
