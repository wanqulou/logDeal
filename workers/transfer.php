<?php
/**
 * $Id$
 * 日志传送
 */

//当前时间
$GLOBALS['currentTime'] = time();

do
{
    $configTag = $GLOBALS['_import']['transfer']["#{$GLOBALS['_daemon']['sn']}"];
    $waitingDir = $GLOBALS['_import']['transfer'][$configTag]['waiting'];
    if (!is_dir($waitingDir))
    {
        _debug("[dir invalid][{$waitingDir}]", _DLV_ERROR);
        break;
    }

    _debug("[waitingDir:{$waitingDir}]", _DLV_NOTICE);

    if (false != ($waitingFiles = _findAllFiles($waitingDir, 'tbz2', true, 10)))
    {
        $backupDir = $waitingDir . '/transfered/' . date($GLOBALS['_import']['backupType'], $GLOBALS['currentTime']);
        _makeDir($backupDir);
        foreach ($waitingFiles as $waitingFile)
        {
            $path = $GLOBALS['_import']['transfer'][$configTag]['target'];
            $host = $GLOBALS['_import']['transfer'][$configTag]['host'];
            $port = $GLOBALS['_import']['transfer'][$configTag]['port'];
            $user = $GLOBALS['_import']['transfer'][$configTag]['user'];
            if (_transferFile($waitingFile, $path, $host, $port, $user))
            {
                _moveFiles((array)$waitingFile, $backupDir);
                _debug("[waitingFile:{$waitingFile}][to:$backupDir]", _DLV_NOTICE);
            }
            else
            {
                _debug("[waitingFile:{$waitingFile}][transfer_failed]", _DLV_WARNING);
            }
        }
    }
}
while (false);
