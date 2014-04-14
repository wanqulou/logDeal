<?php
/**
 * $Id$
 */

//当前时间
$GLOBALS['currentTime'] = time();

do
{
    _syslogRegister($GLOBALS['_import']['sysLogSkip'], '_skip', $GLOBALS['_daemon']['title']);

    //reset cache
    $GLOBALS['_preData'] = array();
    $GLOBALS['_cache']['pos'] = array();
    $GLOBALS['_cache']['adnw'] = array();
    $GLOBALS['_cache']['chl'] = array();
    $GLOBALS['_cache']['chlmark'] = array();

    //connect mysql
    if (false == ($GLOBALS['_import']['adminLink'] = connectMysql($GLOBALS['_import']['adminDbHost'], $GLOBALS['_import']['adminDbUser'], $GLOBALS['_import']['adminDbPass'], $GLOBALS['_import']['adminDbName'])))
    {
        //连接失败     
        _debug("[connect admin({$GLOBALS['_import']['adminDbHost']}) failed!]", _DLV_ERROR);
        break;
    }
    $GLOBALS['_import']['adminLink']->query("SET NAMES utf8");

    if (false == ($GLOBALS['_import']['adminWLink'] = connectMysql($GLOBALS['_import']['adminWDbHost'], $GLOBALS['_import']['adminWDbUser'], $GLOBALS['_import']['adminWDbPass'], $GLOBALS['_import']['adminWDbName'])))
    {
        //连接失败     
        _debug("[connect adminW({$GLOBALS['_import']['adminWDbHost']}) failed!]", _DLV_ERROR);
        break;
    }
    $GLOBALS['_import']['adminWLink']->query("SET NAMES utf8");

    if (false == ($GLOBALS['_import']['financeLink'] = connectMysql($GLOBALS['_import']['financeDbHost'], $GLOBALS['_import']['financeDbUser'], $GLOBALS['_import']['financeDbPass'], $GLOBALS['_import']['financeDbName'])))
    {
        //连接失败     
        _debug("[connect finance({$GLOBALS['_import']['financeDbHost']}) failed!]", _DLV_ERROR);
        break;
    }
    $GLOBALS['_import']['financeLink']->query("SET NAMES utf8");

    $tmpFile = "tmp{$GLOBALS['_daemon']['sn']}.log";
    $tarDir = $GLOBALS['_import']['logRoot'] . '/' . $GLOBALS['_daemon']['realTitle'] . $GLOBALS['_daemon']['sn'];
    if (!is_dir($tarDir))
    {
        _debug("[{$tarDir} not exists]", _DLV_ERROR);
        break;
    }
    
    _debug("[$tarDir][scan_it]");

    if (false != ($logFiles = _findAllFiles($tarDir, 'tbz2', true, $GLOBALS['_import']['maxLogs'])))
    {
        $fileCount = 0;
        $totalFile = count($logFiles);
        $importStart = _microtimeFloat();
        foreach ($logFiles as $fkey => $logFile)
        {
            $fileStart = _microtimeFloat();
            $fileCount++;
            $fileTs = array();
            $logCount = 0;
            $importCount = 0;
            $conCount = 0;
            //这些file应该已经经过验证了
            $logInfo = pathinfo($logFile);
            $logName = "{$logInfo['filename']}.log";
            $command = "{$GLOBALS['_sys']['bzcat']} {$logFile} | {$GLOBALS['_sys']['tar']} xOf - {$logName} > {$tmpFile} 2>/dev/null";
            @exec($command, $arrlines, $stat);
            if ($stat == 0 && $tmpFp = @fopen($tmpFile, "rb")) //解压成功并且读取成功
            {
                _debug("[$logFile][begin]", _DLV_NOTICE);
                while (!feof($tmpFp))
                {
                    $content = trim(fgets($tmpFp, 4096));
                    if (!empty($content))
                    {
                        $logCount++;
                        //data import
                        $logDetail = explode('|', $content);

                        if (count($logDetail) < 4) //连logtype都没有
                        {
                            _debug("[{$content}][invalid]", _DLV_NOTICE);
                            $conCount++;
                            continue;
                        }
                        if (empty($fileTs[$fkey]) || $fileTs[$fkey] <= 0)
                        {
                            $fileTs[$fkey] = $logDetail[_OFFSET_TIMESTAMP];
                        }
                        if (checkLogDetail($logDetail)) //需要过滤的日志
                        {
                            $importCount++;
                            prepareData($logDetail);
                        }
                    }
                    unset($content);
                }
                fclose($tmpFp);
            }
            else
            {
                $backupDir = $GLOBALS['_import']['backupPath'] . '/failed/' . date($GLOBALS['_import']['backupType'], $GLOBALS['currentTime']);
                _debug("[$logFile][read_log_failed][to:$backupDir]", _DLV_ERROR);
                unset($logFiles[$fkey]);
                _makeDir($backupDir);
                _moveFiles((array)$logFile, $backupDir);
            }

            $fileEnd = _microtimeFloat();
            $fileDura = round(($fileEnd - $fileStart), 3);
            $importDura = round(($fileEnd - $importStart), 3);
            _debug("[$logName][$logCount][invalid:{$conCount}][import:{$importCount}][dura:{$fileDura}][{$fileCount}/{$totalFile}][import_dura:{$importDura}]", _DLV_WARNING);
        }
        _debug("[deal_log_files:{$fileCount}]", _DLV_NOTICE);
    }
    else
    {
        _debug("[nothing_to_do]");
    }

    //deal prepare data
    if (buildImportData())
    {
        //backup files
        if (!empty($logFiles))
        {
            foreach($logFiles as $fkey => $logFile)
            {
                $backupTS = (empty($fileTs[$fkey]) || $fileTs[$fkey] <= 0) ? $GLOBALS['currentTime'] : $fileTs[$fkey]; //可以用文件时间戳代替
                $backupDir = $GLOBALS['_import']['backupPath'] . '/success/' . date($GLOBALS['_import']['backupType'], $backupTS);
                _makeDir($backupDir);
                _moveFiles((array)$logFile, $backupDir);
            }
        }
    }
    
    buildMaterialData();

    //close db
    $GLOBALS['_import']['adminLink']->close();
    $GLOBALS['_import']['adminWLink']->close();
    $GLOBALS['_import']['financeLink']->close();
}
while (false);