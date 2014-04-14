<?php
/**
 * 日志分配
 * $Id$
 */
do
{
    if (!is_dir($GLOBALS[$GLOBALS['confTag']]['logRoot']))
    {
        _debug("[logRoot invalid][{$GLOBALS['confTag']}][{$GLOBALS[$GLOBALS['confTag']]['logRoot']}]", _DLV_ERROR);
        break;
    }
    else
    {
        _debug("[logRoot:{$GLOBALS[$GLOBALS['confTag']]['logRoot']}]");
    }

    if ($logFiles = _findAllFiles($GLOBALS[$GLOBALS['confTag']]['logRoot']))
    {
        //分配文件
        $importTitle = empty($GLOBALS['dispatchTitle']) ? 'import' : $GLOBALS['dispatchTitle'];
        if (empty($GLOBALS['_daemon']['runningWorkers'][$importTitle]))
        {
            break;
        }
        $wCount = $GLOBALS['_daemon']['runningWorkers'][$importTitle]['wcount'];

        foreach ($logFiles as $key => $file)
        {
            $sn = $key % $wCount + 1;
            $tarDir = $GLOBALS[$GLOBALS['confTag']]['logRoot'] . '/' . $importTitle . $sn;
            _makeDir($tarDir, 'd');
            @exec("{$GLOBALS['_sys']['mv']} $file $tarDir 2>> /dev/null");
            _debug("[{$file}][move_to:{$tarDir}]", _DLV_NOTICE);
        }
    }
}
while (false);