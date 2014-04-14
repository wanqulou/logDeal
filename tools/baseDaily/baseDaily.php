<?php
/**
 * $Id: baseDaily.php 1318 2014-03-21 10:44:41Z sunxiang $
 * 第三方每日基础数据
 */

$proc_info = pathinfo(__FILE__);
include_once('cfg/const.php');
include_once('fun/base.php');
include_once('fun/file.php');
include_once('fun/utils.php');
include_once('mod/init.php');

$timeStart = microtimeFloat();
do
{
    $logFiles = getFiles($confs['logs_root'], $confs['logs_limit'], 'tbz2');

    if (count($logFiles) > 0)
    {
        $baseDailyData = array();
        foreach ($logFiles as $file)
        {
            list($fileName, $fileExt) = explode('.', basename($file));
            $logFile = $fileName . '.log';
            $tmpFile = _SUB_TMP_DIR . '/tmp.log';
            debug("[deal_file_begin:{$file}]", 'notice');
            
            $cmd = _CMD_GZCAT_PATH . " {$file} | " . _CMD_TAR_PATH . " xOf - {$logFile} > {$tmpFile}";
            debug("[CMD:{$cmd}]", 'info');

            @exec($cmd, $output, $stat);

            $dealRecord = 0;
            $fileTs = '';
            if ($stat == 0 && file_exists($tmpFile))
            {
                $fp = @fopen($tmpFile, 'rb');
                while (!feof($fp))
                {
                    $content = trim(fgets($fp, 1024));
                    if (!empty($content))
                    {
                        $logDetail = explode('|', $content);
                        if (count($logDetail) < 12)
                        {
                            debug("[invalid_log][${content}]", 'warning');
                            continue;
                        }
                        $dealRecord++;
                        $adnwId = $logDetail[_OFFSET_ADNETWORKID];
                        $adnwOwner = $logDetail[_OFFSET_ADNETWORK_OWNER];
                        $chlUserId = $logDetail[_OFFSET_CHANNEL_USERID];
                        $appId = $logDetail[_OFFSET_APPLICATIONID];
                        $appOnwer = $logDetail[_OFFSET_APP_OWNER];
                        $logType = $logDetail[_OFFSET_LOGTYPE];
                        $campType = $logDetail[_OFFSET_CAMPTYPE];
                        $dataCount = $logDetail[_OFFSET_DATACOUNT];
                        $billCost = $logDetail[_OFFSET_BILLING_COST];
                        $appAmount = $logDetail[_OFFSET_APP_AMOUNT];
                        $chlAmount = $logDetail[_OFFSET_CHANNEL_AMOUNT];
                        $reportTS = $logDetail[_OFFSET_REPORTTS];
                        $reportDate = date('ymd', $reportTS);
                        if (empty($fileTs))
                        {
                            $fileTs = $reportTS;
                        }
                        $key = "{$adnwId}_{$appId}_{$chlUserId}_{$campType}_{$reportDate}";
                        if (!isset($baseDailyData[$key]))
                        {
                            $baseDailyData[$key] = array(
                                'reqs' => 0,
                                'imps' => 0,
                                'clis' => 0,
                                'adcost' => 0,
                                'uincome' => 0,
                                'cincome' => 0,
                                'adnwOwner' => $adnwOwner,
                                'appOwner' => $appOnwer,
                            );
                        }
                        switch ($logType)
                        {
                            case _DATA_REQUEST:
                                $baseDailyData[$key]['reqs'] += $dataCount;
                                break;
                            case _DATA_IMPRESSION:
                                $baseDailyData[$key]['reqs'] += $dataCount;
                                $baseDailyData[$key]['imps'] += $dataCount;
                                break;
                            case _DATA_CLICK:
                                $baseDailyData[$key]['clis'] += $dataCount;
                                break;
                        }
                        $baseDailyData[$key]['adcost'] += $billCost;
                        $baseDailyData[$key]['uincome'] += $appAmount - $chlAmount;
                        $baseDailyData[$key]['cincome'] += $chlAmount;
                    }
                    unset($content);
                    unset($logDetail);
                }
                fclose($fp);
            }
            
            debug("[deal_file_end:{$file}][deal_record:{$dealRecord}]", 'notice');

            if ($confs['backup']['enable'] == '1')
            {
                $fileTs = !empty($fileTs) ? $fileTs : time();
                $bakpath = $confs['backup']['path'] . '/' . date($confs['backup']['format'], $fileTs);
                !is_dir($bakpath) && makeDir($bakpath);
                $cmd = _CMD_MV_PATH . " {$file} {$bakpath}";
                exec($cmd);
                debug("[CMD:[{$cmd}]", 'info');
                debug("[deal_file_move:{$file}][path:{$bakpath}]", 'notice');
            }
            else
            {
                $cmd = _CMD_RM_PATH . " -f {$file}";
                exec($cmd);
                debug("[CMD:[{$cmd}]", 'info');
                debug("[deal_file_delete:{$file}]", 'notice');
            }
        }
        //插入数据表中
        logsToTable($baseDailyData);
    }
    else
    {
        debug('[nothing_to_do]', 'notice');
    }

    if ($confs['proc_mode'] == 'daemon')
    {
        $run = true;
    }
    else if ($confs['proc_mode'] == 'run')
    {
        $run = count($logFiles) > 0;
    }
    
    if ($run)
    {
        sleep(_PROC_SLEEP);
    }
}
while ($run);

$timeDura = microtimeFloat() - $timeStart;
debug("[this_time_deal_finished][duration:{$timeDura}]", 'notice');
$db_finance->close();