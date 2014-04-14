<?php
/**
 * $Id$
 */

/**
 * checkLogDetail
 */
function checkLogDetail($logDetail)
{
    $allowTyeps = array_merge(array(
        _DATA_IMPRESSION,
        _DATA_CLICK,
        _DATA_REAL_REQ
    ), $GLOBALS['_import']['washLogType']);
    
    if (!in_array($logDetail[_OFFSET_LOGTYPE], $allowTyeps))
    {
        _saveSysLog(implode('|', $logDetail), '_skip');
        return false;
    }

    if (empty($logDetail[_OFFSET_ADPOSITIONID]))
    {
        _saveSysLog(implode('|', $logDetail), '_skip');
        return false;
    }
    if (empty($logDetail[_OFFSET_NETWORKID]))
    {
        _saveSysLog(implode('|', $logDetail), '_skip');
        return false;
    }
    if (empty($logDetail[_OFFSET_TIMESTAMP]))
    {
        _saveSysLog(implode('|', $logDetail), '_skip');
        return false;
    }
    if (!empty($logDetail[_OFFSET_MATERIALURL]))
    {
		if(!preg_match("/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9_\-\.\:]+/i", $logDetail[_OFFSET_MATERIALURL]))
        {
			_saveSysLog(implode('|', $logDetail), '_skip');
            return false;
        }
    }
    return true;
}

/**
 * prepareData
 */
function prepareData($logDetail)
{
    $materialId = trim($logDetail[_OFFSET_MATERIALID]);
    $materialUrl = trim($logDetail[_OFFSET_MATERIALURL]);
    
    $logType = trim($logDetail[_OFFSET_LOGTYPE]);
    $logType = (empty($materialId) && $logType == _DATA_IMPRESSION) ? _DATA_REQUEST : $logType;
    $adpositionId = trim($logDetail[_OFFSET_ADPOSITIONID]);
    $campaignType = trim($logDetail[_OFFSET_CAMPTYPE]);
    if (empty($campaignType) || !is_numeric($campaignType) || strlen($campaignType) > 2)
    {
        $campaignType = _AD_BANNER;
    }
    
    if (false === getAdposInfo($adpositionId))
    {
        _debug("[" . __FUNCTION__ . "][no_adpos]", _DLV_INFO);
        _saveSysLog(implode('|', $logDetail), '_skip');
        return;
    }
    _debug("[" . __FUNCTION__ . "][adpos:{$adpositionId}]", _DLV_INFO);

    $logTS = trim($logDetail[_OFFSET_TIMESTAMP]);

    $reportTS = $logTS - $logTS % 1800;   //半小时一周期

    if (!empty($logDetail[_OFFSET_CHANNELMARK]))
    {
        $channelMark = trim($logDetail[_OFFSET_CHANNELMARK]);
    }

    !empty($channelMark) && ($GLOBALS['_cache']['pos'][$adpositionId]['channelMark'] = $channelMark); //缓存广告位对应的channelMark
    
    $adnetworkId = trim($logDetail[_OFFSET_NETWORKID]);

    if ($adnetworkId && getAdnetworkInfo($adnetworkId))
    {
        //$adnkCode = $GLOBALS['_cache']['adnw'][$adnetworkId]['code'];
        //$adnkName = $GLOBALS['_cache']['adnw'][$adnetworkId]['name'];
        $adnkOwner = $GLOBALS['_cache']['adnw'][$adnetworkId]['owner'];
        $adnkServType = $GLOBALS['_cache']['adnw'][$adnetworkId]['servtype'];
        $adnkConPrice = $GLOBALS['_cache']['adnw'][$adnetworkId]['conprice'];
        _debug("[" . __FUNCTION__ . "][{$adnetworkId}][adnetworkOwner:{$adnkOwner}][servtype:{$adnkServType}][conprice:{$adnkConPrice}]", _DLV_INFO);
        
        //prepare data
        $key = "{$adpositionId}_{$adnetworkId}_{$campaignType}_{$logType}";
        
        if (isset($GLOBALS['_preData'][$reportTS]) && isset($GLOBALS['_preData'][$reportTS][$key]))
        {
            $GLOBALS['_preData'][$reportTS][$key] += 1;
        }
        else
        {
            $GLOBALS['_preData'][$reportTS][$key] = 1;
        }
        
        //prepare meterial
        if (!empty($materialId))
        {
            $date = date('ymd', $reportTS);
            $mKey = "{$materialId}_{$adnetworkId}_{$date}";
            $GLOBALS['_preMtrlData'][$mKey] = array(
                'ts' => $logTS,
                'url' => $materialUrl
            );
        }
    }
    else
    {
        _saveSysLog(implode('|', $logDetail), '_skip');
    }
}

/**
 * buildImportData
 */
function buildImportData()
{
    if (empty($GLOBALS['_preData'])) 
    {
        return true;
    }

    $buildStart = _microtimeFloat();
    $fileTag = $GLOBALS['_daemon']['sn'] . $GLOBALS['_import']['localtag'] . date('_Y_m_d_H_i_s', $GLOBALS['currentTime']);

    //记账
    $filename = 'billing' . $fileTag;
    $GLOBALS['_billingFile']['name'] = $filename;
    $GLOBALS['_billingFile']['file'] = $filename . '.log';
    $GLOBALS['_billingFile']['tarball'] = $filename . '.tbz2';
    $GLOBALS['_billingFile']['fp'] = @fopen($GLOBALS['_billingFile']['file'], 'wb');
    $GLOBALS['_billingFile']['stat'] = false;
    
    //真实请求
    $filename = 'realreq' . $fileTag;
    $GLOBALS['_realreqFile']['name'] = $filename;
    $GLOBALS['_realreqFile']['file'] = $filename . '.log';
    $GLOBALS['_realreqFile']['tarball'] = $filename . '.tbz2';
    $GLOBALS['_realreqFile']['fp'] = @fopen($GLOBALS['_realreqFile']['file'], 'wb');
    $GLOBALS['_realreqFile']['stat'] = false;
    
    //清洗
    $filename = 'washed' . $fileTag;
    $GLOBALS['_washedFile']['name'] = $filename;
    $GLOBALS['_washedFile']['file'] = $filename . '.log';
    $GLOBALS['_washedFile']['tarball'] = $filename . '.tbz2';
    $GLOBALS['_washedFile']['fp'] = @fopen($GLOBALS['_washedFile']['file'], 'wb');
    $GLOBALS['_washedFile']['stat'] = false;

    $billingCount = 0;
    $realreqCount = 0;
    $washedCount = 0;

    foreach ($GLOBALS['_preData'] as $reportTS => $thirdData) 
    {
        $importDate = date('ymd', $reportTS);
        foreach ($thirdData as $dataKey => $dataVal) 
        {
            list($adpositionId, $adnetworkId, $campaignType, $logType) = explode('_', $dataKey);
            
            $applicationId = $GLOBALS['_cache']['pos'][$adpositionId]['applicationId'];
            
            if (in_array($logType, $GLOBALS['_import']['washLogType']))
            {
                buildWashedData($importDate, $logType, $applicationId, $adnetworkId, $campaignType, $dataVal);
                $washedCount++;
            }
            else if ($logType == _DATA_REAL_REQ)
            {
                buildRealReqData($importDate, $applicationId, $adnetworkId, $campaignType, $dataVal);
                $realreqCount++;
            }
            else
            {
                buildCountData($importDate, $logType, $adpositionId, $adnetworkId, $campaignType, $dataVal);
                $billingCount++;
            }
        }
    }

    //billing
    if (false != ($billingTarball = _package($GLOBALS['_billingFile']['file'], $GLOBALS['_billingFile']['tarball'])))
    {
        $billingWaiting = $GLOBALS['_import']['waitingRoot'] . '/'. $GLOBALS['_import']['billingSub'];
        _moveFiles((array)$billingTarball, $billingWaiting);
    }
    else
    {
        if (file_exists($GLOBALS['_billingFile']['file']) && 0 >= filesize($GLOBALS['_billingFile']['file']))
        {
            @exec("{$GLOBALS['_sys']['rm']} -f {$GLOBALS['_billingFile']['file']}");
        }
        _debug("[" . __FUNCTION__ . "][{$GLOBALS['_billingFile']['file']}][{$GLOBALS['_billingFile']['tarball']}][package_failed]", _DLV_NOTICE);
    }

    //real request
    if (false != ($realreqTarball = _package($GLOBALS['_realreqFile']['file'], $GLOBALS['_realreqFile']['tarball'])))
    {
        $realreqWaiting = $GLOBALS['_import']['waitingRoot'] .'/' . $GLOBALS['_import']['realreqSub'];
        _moveFiles((array)$realreqTarball, $realreqWaiting);
    }
    else
    {
        if (file_exists($GLOBALS['_realreqFile']['file']) && 0 >= filesize($GLOBALS['_realreqFile']['file']))
        {
            @exec("{$GLOBALS['_sys']['rm']} -f {$GLOBALS['_realreqFile']['file']}");
        }
        _debug("[" . __FUNCTION__ ."][{$GLOBALS['_realreqFile']['file']}][{$GLOBALS['_realreqFile']['tarball']}][package_failed]", _DLV_NOTICE);
    }
    
    //washed
    if (false != ($washedTarball = _package($GLOBALS['_washedFile']['file'], $GLOBALS['_washedFile']['tarball'])))
    {
        $washedWaiting = $GLOBALS['_import']['waitingRoot'] .'/' . $GLOBALS['_import']['washedSub'];
        _moveFiles((array)$washedTarball, $washedWaiting);
    }
    else
    {
        if (file_exists($GLOBALS['_washedFile']['file']) && 0 >= filesize($GLOBALS['_washedFile']['file']))
        {
            @exec("{$GLOBALS['_sys']['rm']} -f {$GLOBALS['_washedFile']['file']}");
        }
        _debug("[" . __FUNCTION__ . "][{$GLOBALS['_washedFile']['file']}][{$GLOBALS['_washedFile']['tarball']}][package_failed]", _DLV_NOTICE);
    }

    $buildEnd = _microtimeFloat();
    $buildDura = round(($buildEnd - $buildStart), 3);
    _debug("[" . __FUNCTION__ . "][{$GLOBALS['_billingFile']['file']}:{$billingCount}][{$GLOBALS['_realreqFile']['file']}:{$realreqCount}][{$GLOBALS['_washedFile']['file']}:{$washedCount}][buildDura:{$buildDura}]", _DLV_WARNING);
    unset($GLOBALS['_preData']);
    return true;
}

/**
 * buildMaterialData
 */
function buildMaterialData()
{
    if (empty($GLOBALS['_preMtrlData'])) 
    {
        return true;
    }

    $buildStart = _microtimeFloat();
    $fileTag = $GLOBALS['_daemon']['sn'] . $GLOBALS['_import']['localtag'] . date('_Y_m_d_H_i_s', $GLOBALS['currentTime']);

    //物料
    $filename = 'material' . $fileTag;
    $GLOBALS['_materialFile']['name'] = $filename;
    $GLOBALS['_materialFile']['file'] = $filename . '.log';
    $GLOBALS['_materialFile']['tarball'] = $filename . '.tbz2';
    $GLOBALS['_materialFile']['fp'] = @fopen($GLOBALS['_materialFile']['file'], 'wb');
    $GLOBALS['_materialFile']['stat'] = false;

    $mtrlCount = 0;

    foreach ($GLOBALS['_preMtrlData'] as $dataKey => $dataVal) 
    {
        list($materialId, $adnetworkId, $date) = explode('_', $dataKey);
        
        $materialContent = "{$materialId}|{$adnetworkId}|{$dataVal['ts']}|{$dataVal['url']}|";
        
        if (!$GLOBALS['_materialFile']['stat'])
        {
            $materialContent .= $GLOBALS['_materialFile']['name'];
            $GLOBALS['_materialFile']['stat'] = true;
        }
        fputs($GLOBALS['_materialFile']['fp'], $materialContent . "\n");
        $mtrlCount++;
    }

    //material
    if (false != ($materialTarball = _package($GLOBALS['_materialFile']['file'], $GLOBALS['_materialFile']['tarball'])))
    {
        $materialWaiting = $GLOBALS['_import']['waitingRoot'] . '/'. $GLOBALS['_import']['materialSub'];
        _moveFiles((array)$materialTarball, $materialWaiting);
    }
    else
    {
        if (file_exists($GLOBALS['_materialFile']['file']) && 0 >= filesize($GLOBALS['_materialFile']['file']))
        {
            @exec("{$GLOBALS['_sys']['rm']} -f {$GLOBALS['_materialFile']['file']}");
        }
        _debug("[" . __FUNCTION__ . "][{$GLOBALS['_materialFile']['file']}][{$GLOBALS['_materialFile']['tarball']}][package_failed]", _DLV_NOTICE);
    }

    $buildEnd = _microtimeFloat();
    $buildDura = round(($buildEnd - $buildStart), 3);
    _debug("[" . __FUNCTION__ . "][{$GLOBALS['_materialFile']['file']}:{$mtrlCount}][buildDura:{$buildDura}]", _DLV_WARNING);
    unset($GLOBALS['_preMtrlData']);
    return true;
}

/**
 * buildCountData
 */
function buildCountData($importDate, $logType, $adpositionId, $adnetworkId, $campaignType, $dataCount)
{
    $applicationId = $GLOBALS['_cache']['pos'][$adpositionId]['applicationId'];
    $userId = $GLOBALS['_cache']['pos'][$adpositionId]['userId'];
    $userChannelId = $GLOBALS['_cache']['pos'][$adpositionId]['userChannelId'];
    $appChannelId = $GLOBALS['_cache']['pos'][$adpositionId]['appChannelId']; //app所属渠道，通常是某个超级渠道商
    $customShareRatio = (int)$GLOBALS['_cache']['pos'][$adpositionId]['shareRatio'];
    
    $servType = $GLOBALS['_cache']['adnw'][$adnetworkId]['servtype'];
    $conPrice = $GLOBALS['_cache']['adnw'][$adnetworkId]['conprice'];

    $billingCost = 0; //费用总和
    $appAmount = 0; // 媒体收入
    $channelUserId = 0; //渠道商用户ID
    $userChannelAmount = 0; //渠道商收入

    //billing
    if (($logType == _DATA_CLICK && $servType == _SERVTYPE_CPC) || ($logType == _DATA_IMPRESSION && $servType == _SERVTYPE_CPM))
    {
        $price = ($logType == _DATA_CLICK) ? $conPrice : $conPrice / 1000;
        $billingCost = $price * $dataCount;
        _debug("[" . __FUNCTION__ . "][adnetwork:{$adnetworkId}][servtype:{$servType}][logtype:{$logType}][price:{$price}][billingCost:{$billingCost}]", _DLV_INFO);

        if ($billingCost > 0) 
        {
            if ($userChannelId > 0 && getChannelInfo($userChannelId)) //普通渠道商
            {
                //渠道用户分成
                $channelShare = $GLOBALS['_cache']['chl'][$userChannelId]['shareRatio'];
                $appAmount = $billingCost * $channelShare / 10000;
                if ($appAmount < 10)
                {
                    $appAmount = round($appAmount, 0); //四舍五入
                }
                else
                {
                    $appAmount = ceil($appAmount); //给开发者多点钱
                }

                //渠道商分成
                $channelUserId = $GLOBALS['_cache']['chl'][$userChannelId]['userId'];
                $userChannelAmount = $appAmount * (1 - $GLOBALS['_cache']['chl'][$userChannelId]['subShareRatio'] / 10000);
                if ($userChannelAmount < 10)
                {
                    $userChannelAmount = round($userChannelAmount, 0);
                }
                else
                {
                    $userChannelAmount = floor($userChannelAmount); //给渠道少点,开发者多点
                }
            } 
            else if ($customShareRatio > 0 && $customShareRatio <= 10000) //通常为知名开发者
            {
                $appAmount = $billingCost * $customShareRatio / 10000;
                if ($appAmount < 10) 
                {
                    $appAmount = round($appAmount, 0);
                }
                else
                {
                    $appAmount = ceil($appAmount);
                }
            }
            else //普通用户
            {
                $appAmount = $billingCost * (1 - $GLOBALS['_import']['systemRate']);
                if ($appAmount < 10)
                {
                    $appAmount = round($appAmount, 0);
                }
                else
                {
                    $appAmount = ceil($appAmount);
                }
            }

            // 如果用户不是渠道用户，但是应用属于某个超级渠道商，则需要给超级渠道商分成
            if (empty($userChannelId))
            {
                if (getChannelInfo($appChannelId) && ($GLOBALS['_cache']['chl'][$appChannelId]['payMode'] == _CHANNEL_PAYMODE_APPLIST)) //channel按applist分账
                {
                    $userChannelAmount = floor($billingCost * $GLOBALS['_cache']['chl'][$appChannelId]['shareRatio'] / 10000);
                    $userChannelAmount = min($userChannelAmount, floor($billingCost - $appAmount)); //保证 $userChannelAmount + $appAmount <= $billingCost
                    $appAmount += $userChannelAmount;
                    $userChannelId = $appChannelId; //后面需要记录channelId
                    $channelUserId = $GLOBALS['_cache']['chl'][$userChannelId]['userId'];
                }
                else if (!empty($GLOBALS['_cache']['pos'][$adpositionId]['channelMark'])) //APP上传channelMark,channel按流量分成
                {
                    $appChannelId = getChannelIdByMark($GLOBALS['_cache']['pos'][$adpositionId]['channelMark']);
                    if ($appChannelId != false && $GLOBALS['_cache']['chl'][$appChannelId]['payMode'] == _CHANNEL_PAYMODE_THROUGHPUT) //确认分账模式
                    {
                        $userChannelAmount = floor($billingCost * $GLOBALS['_cache']['chl'][$appChannelId]['shareRatio'] / 10000);
                        $userChannelAmount = min($userChannelAmount, floor($billingCost - $appAmount)); //保证 $userChannelAmount + $appAmount <= $billingCost
                        $appAmount += $userChannelAmount;
                        $userChannelId = $appChannelId; //后面需要记录channelId
                        $channelUserId = $GLOBALS['_cache']['chl'][$userChannelId]['userId'];
                    }
                }
            }
            $billingCost = floor($billingCost);
        }
    }

    // 不计费的情况，也需要记录超级渠道商的channelId和channelUserId
    if (empty($userChannelId))
    {
        if (getChannelInfo($appChannelId) && ($GLOBALS['_cache']['chl'][$appChannelId]['payMode'] == _CHANNEL_PAYMODE_APPLIST)) //确认分账模式是applist分成
        {
            $userChannelId = $appChannelId; //后面需要记录channelId
            $channelUserId = $GLOBALS['_cache']['chl'][$userChannelId]['userId'];
        }
        else if (!empty($GLOBALS['_cache']['pos'][$adpositionId]['channelMark'])) //APP上传的channelMark
        {
            $appChannelId = getChannelIdByMark($GLOBALS['_cache']['pos'][$adpositionId]['channelMark']);
            if ($appChannelId != false && $GLOBALS['_cache']['chl'][$appChannelId]['payMode'] == _CHANNEL_PAYMODE_THROUGHPUT) //确认分账模式确实是流量分成
            {
                $userChannelId = $appChannelId; //后面需要记录channelId
                $channelUserId = $GLOBALS['_cache']['chl'][$userChannelId]['userId'];
            }
        }
    }

    if ($dataCount > 0)
    {
        $billingContent = "{$adnetworkId}|{$channelUserId}|{$applicationId}|{$dataCount}|{$campaignType}".
            "|{$logType}|{$importDate}|{$billingCost}|{$appAmount}|{$userChannelAmount}|";
        if (!$GLOBALS['_billingFile']['stat'])
        {
            $billingContent .= $GLOBALS['_billingFile']['name'];
            $GLOBALS['_billingFile']['stat'] = true;
        }
        fputs($GLOBALS['_billingFile']['fp'], $billingContent . "\n");
    }
}

/**
 * 记录清洗数据
 */
function buildWashedData($importDate, $logType, $applicationId, $adnetworkId, $campaignType, $dataCount)
{
    $washedContent = "{$importDate}|{$adnetworkId}|{$applicationId}|{$campaignType}|{$logType}|{$dataCount}|";
    if (!$GLOBALS['_washedFile']['stat'])
    {
        $washedContent .= $GLOBALS['_washedFile']['name'];
        $GLOBALS['_washedFile']['stat'] = true;
    }
    fputs($GLOBALS['_washedFile']['fp'], $washedContent . "\n");
}

/**
 * 记录真实请求数据
 */
function buildRealReqData($importDate, $applicationId, $adnetworkId, $campaignType, $dataCount)
{
    $realreqContent = "{$adnetworkId}|{$applicationId}|{$dataCount}|{$campaignType}|{$importDate}|";
    if (!$GLOBALS['_realreqFile']['stat'])
    {
        $realreqContent .= $GLOBALS['_realreqFile']['name'];
        $GLOBALS['_realreqFile']['stat'] = true;
    }
    fputs($GLOBALS['_realreqFile']['fp'], $realreqContent . "\n");
}