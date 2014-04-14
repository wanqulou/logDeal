<?php
/**
 * $Id$
 * 垃圾处理
 */

//当前时间
$GLOBALS['currentTime']=time();

do
{
    //处理传送失败的无法解压的文件
    if (!is_dir($GLOBALS[$GLOBALS['confTag']]['logRoot']))
    {
        _debug("[logRoot invalid][{$GLOBALS[$GLOBALS['confTag']]['logRoot']}]", _DLV_ERROR);
        break;
    }
    _debug("[logRoot:{$GLOBALS[$GLOBALS['confTag']]['logRoot']}]", _DLV_NOTICE);

    if (false != ($badFiles = _findBadFiles($GLOBALS[$GLOBALS['confTag']]['logRoot'])))
    {
        $backupDir = $GLOBALS[$GLOBALS['confTag']]['backupPath'] . '/bad/' . date($GLOBALS[$GLOBALS['confTag']]['backupType'], $GLOBALS['currentTime']);
        _makeDir($backupDir);
        _moveFiles($badFiles, $backupDir);
        _debug("[bad_files:"  .implode(',', $badFiles)."][to:{$backupDir}]", _DLV_ERROR);
    }
}
while (false);
