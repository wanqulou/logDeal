<?php
/**
 * $Id$
 */

$GLOBALS['confTag'] = '_import';

//get config
$_logRoot = isset($GLOBALS['OPTIONS']['setting']['log_root']) ? $GLOBALS['OPTIONS']['setting']['log_root'] : '/tmp/logs';
$GLOBALS['_import']['logRoot'] = is_dir($_logRoot) ? $_logRoot : '/tmp';
$GLOBALS['_import']['backupPath'] = isset($GLOBALS['OPTIONS']['setting']['backup_path']) ? $GLOBALS['OPTIONS']['setting']['backup_path'] : "/services/thirdLogBackup";
$GLOBALS['_import']['backupType'] = isset($GLOBALS['OPTIONS']['setting']['backup_type']) ? $GLOBALS['OPTIONS']['setting']['backup_type'] : 'ymd/H';

$GLOBALS['_import']['sysLogSkip'] = isset($GLOBALS['OPTIONS']['setting']['skip_log']) ? $GLOBALS['OPTIONS']['setting']['skip_log'] : 'local6.error';

//mysql
$GLOBALS['_import']['adminDbHost'] = empty($GLOBALS['OPTIONS']['setting']['admin_host']) ? null : $GLOBALS['OPTIONS']['setting']['admin_host'];
$GLOBALS['_import']['adminDbUser'] = empty($GLOBALS['OPTIONS']['setting']['admin_user']) ? null : $GLOBALS['OPTIONS']['setting']['admin_user'];
$GLOBALS['_import']['adminDbPass'] = empty($GLOBALS['OPTIONS']['setting']['admin_pass']) ? null : $GLOBALS['OPTIONS']['setting']['admin_pass'];
$GLOBALS['_import']['adminDbName'] = empty($GLOBALS['OPTIONS']['setting']['admin_db']) ? null : $GLOBALS['OPTIONS']['setting']['admin_db'];
//write
$GLOBALS['_import']['adminWDbHost'] = empty($GLOBALS['OPTIONS']['setting']['adminw_host']) ? null : $GLOBALS['OPTIONS']['setting']['adminw_host'];
$GLOBALS['_import']['adminWDbUser'] = empty($GLOBALS['OPTIONS']['setting']['adminw_user']) ? null : $GLOBALS['OPTIONS']['setting']['adminw_user'];
$GLOBALS['_import']['adminWDbPass'] = empty($GLOBALS['OPTIONS']['setting']['adminw_pass']) ? null : $GLOBALS['OPTIONS']['setting']['adminw_pass'];
$GLOBALS['_import']['adminWDbName'] = empty($GLOBALS['OPTIONS']['setting']['adminw_db']) ? null : $GLOBALS['OPTIONS']['setting']['adminw_db'];
//finance
$GLOBALS['_import']['financeDbHost'] = empty($GLOBALS['OPTIONS']['setting']['finance_host']) ? null : $GLOBALS['OPTIONS']['setting']['finance_host'];
$GLOBALS['_import']['financeDbUser'] = empty($GLOBALS['OPTIONS']['setting']['finance_user']) ? null : $GLOBALS['OPTIONS']['setting']['finance_user'];
$GLOBALS['_import']['financeDbPass'] = empty($GLOBALS['OPTIONS']['setting']['finance_pass']) ? null : $GLOBALS['OPTIONS']['setting']['finance_pass'];
$GLOBALS['_import']['financeDbName'] = empty($GLOBALS['OPTIONS']['setting']['finance_db']) ? null : $GLOBALS['OPTIONS']['setting']['finance_db'];

$GLOBALS['_import']['maxLogs'] = empty($GLOBALS['OPTIONS']['setting']['max_logs']) ? 5 : (int)$GLOBALS['OPTIONS']['setting']['max_logs'];

//waiting to transfer
$GLOBALS['_import']['waitingRoot'] = empty($GLOBALS['OPTIONS']['setting']['waiting_root']) ? '/services/thirdLogWaiting' : $GLOBALS['OPTIONS']['setting']['waiting_root'];
_makeDir($GLOBALS['_import']['waitingRoot']);
$GLOBALS['_import']['billingSub'] = empty($GLOBALS['OPTIONS']['setting']['billing_sub']) ? 'billing' : $GLOBALS['OPTIONS']['setting']['billing_sub'];
_makeDir($GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['billingSub']);
$GLOBALS['_import']['realreqSub'] = empty($GLOBALS['OPTIONS']['setting']['realreq_sub']) ? 'realreq' : $GLOBALS['OPTIONS']['setting']['realreq_sub'];
_makeDir($GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['realreqSub']);
$GLOBALS['_import']['washedSub'] = empty($GLOBALS['OPTIONS']['setting']['washed_sub']) ? 'washed' : $GLOBALS['OPTIONS']['setting']['washed_sub'];
_makeDir($GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['washedSub']);
$GLOBALS['_import']['materialSub'] = empty($GLOBALS['OPTIONS']['setting']['material_sub']) ? 'material' : $GLOBALS['OPTIONS']['setting']['material_sub'];
_makeDir($GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['materialSub']);


//system setting
$GLOBALS['_import']['systemRate'] = empty($GLOBALS['OPTIONS']['setting']['system_rate']) ? 0.4 : (float)$GLOBALS['OPTIONS']['setting']['system_rate'];

//transfer
$GLOBALS['_import']['transfer']['#1'] = 'billing';
$GLOBALS['_import']['transfer']['#2'] = 'realreq';
$GLOBALS['_import']['transfer']['#3'] = 'washed';
$GLOBALS['_import']['transfer']['#4'] = 'material';

$GLOBALS['_import']['transfer']['billing'] = array(
    'waiting' => $GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['billingSub'],
    'target' => empty($GLOBALS['OPTIONS']['setting']['billing_tardir']) ? '/services/TDBLOGS' : $GLOBALS['OPTIONS']['setting']['billing_tardir'],
    'host' => empty($GLOBALS['OPTIONS']['setting']['billing_host']) ? null : $GLOBALS['OPTIONS']['setting']['billing_host'],
    'port' => empty($GLOBALS['OPTIONS']['setting']['billing_port']) ? '22' : $GLOBALS['OPTIONS']['setting']['billing_port'],
    'user' => empty($GLOBALS['OPTIONS']['setting']['billing_user']) ? 'sunxiang' : $GLOBALS['OPTIONS']['setting']['billing_user'],
);
$GLOBALS['_import']['transfer']['realreq'] = array(
    'waiting' => $GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['realreqSub'],
    'target' => empty($GLOBALS['OPTIONS']['setting']['realreq_tardir']) ? '/services/TDRLOGS' : $GLOBALS['OPTIONS']['setting']['realreq_tardir'],
    'host' => empty($GLOBALS['OPTIONS']['setting']['realreq_host']) ? null : $GLOBALS['OPTIONS']['setting']['realreq_host'],
    'port' => empty($GLOBALS['OPTIONS']['setting']['realreq_port']) ? '22' : $GLOBALS['OPTIONS']['setting']['realreq_port'],
    'user' => empty($GLOBALS['OPTIONS']['setting']['realreq_user']) ? 'sunxiang' : $GLOBALS['OPTIONS']['setting']['realreq_user'],
);
$GLOBALS['_import']['transfer']['washed'] = array(
    'waiting' => $GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['washedSub'],
    'target' => empty($GLOBALS['OPTIONS']['setting']['washed_tardir']) ? '/services/TDWLOGS' : $GLOBALS['OPTIONS']['setting']['washed_tardir'],
    'host' => empty($GLOBALS['OPTIONS']['setting']['washed_host']) ? null : $GLOBALS['OPTIONS']['setting']['washed_host'],
    'port' => empty($GLOBALS['OPTIONS']['setting']['washed_port']) ? '22' : $GLOBALS['OPTIONS']['setting']['washed_port'],
    'user' => empty($GLOBALS['OPTIONS']['setting']['washed_user']) ? 'sunxiang' : $GLOBALS['OPTIONS']['setting']['washed_user'],
);
$GLOBALS['_import']['transfer']['material'] = array(
    'waiting' => $GLOBALS['_import']['waitingRoot'] . '/' . $GLOBALS['_import']['materialSub'],
    'target' => empty($GLOBALS['OPTIONS']['setting']['material_tardir']) ? '/services/TDMLOGS' : $GLOBALS['OPTIONS']['setting']['material_tardir'],
    'host' => empty($GLOBALS['OPTIONS']['setting']['material_host']) ? null : $GLOBALS['OPTIONS']['setting']['material_host'],
    'port' => empty($GLOBALS['OPTIONS']['setting']['material_port']) ? '22' : $GLOBALS['OPTIONS']['setting']['material_port'],
    'user' => empty($GLOBALS['OPTIONS']['setting']['material_user']) ? 'sunxiang' : $GLOBALS['OPTIONS']['setting']['material_user'],
);

//hostname
$GLOBALS['_import']['hostname'] = gethostname();
$GLOBALS['_import']['localtag'] = substr(md5($GLOBALS['_import']['hostname']), 0, 6);

//清洗的日志类型
$GLOBALS['_import']['washLogType'] = array(_DATA_WASH_REQ, _DATA_WASH_IMP, _DATA_WASH_CLI);
