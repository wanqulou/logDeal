<?php
/**
 * $Id$
 */

/**
 * connectMysql
 */
function connectMysql($host, $user, $pass, $db)
{
    $mysql = mysqli_init();
    $mysql->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    if ($mysql->real_connect($host, $user, $pass, $db))
    {
        return $mysql;
    }
    return false;
}

/**
 * getAdposInfo
 */
function getAdposInfo($adpositionId)
{
    $token = false;
    do
    {
        if (empty($adpositionId))
        {
            break;
        }
        if (isset($GLOBALS['_cache']['pos'][$adpositionId]))
        {
            _debug("[" . __FUNCTION__ . "][pos:{$adpositionId}][cache_hit]", _DLV_INFO);
            if ($GLOBALS['_cache']['pos'][$adpositionId] != false)
            {
                $token = true;
            }
            break;
        }
        $query = 'SELECT p.`adpositionId`, p.`adpositionApplicationId` AS `applicationId`, a.`applicationOwner` AS `userId`, ud.`shareRatio`,' .
            ' u.`userChannel` AS `userChannelId`, uca.`userChannelId` AS `appChannelId` FROM `t_adposition` AS p' .
            ' LEFT JOIN `t_application` AS a ON p.`adpositionApplicationId` = a.`applicationId`' .
            ' LEFT JOIN `t_user` AS u ON u.`userId` = a.`applicationOwner`' .
            ' LEFT JOIN `t_user_detail` AS ud ON ud.`userId` = u.`userId`' .
            ' LEFT JOIN `t_userchannel_application` uca ON p.`adpositionApplicationId` = uca.`applicationId`' .
            ' WHERE p.`adpositionId` = ' . $adpositionId . ' LIMIT 1';
        _debug("[" . __FUNCTION__ . "][query:{$query}]", _DLV_INFO);
        
        $result = $GLOBALS['_import']['adminLink']->query($query);
        
        if ($row = $result->fetch_assoc())
        {
            $token = true;
        }
        else
        {
            $row = false;
        }
        
        $result->close();
        
        //add to cache
        $GLOBALS['_cache']['pos'][$adpositionId] = $row;
    }
    while (false);
    
    return $token;
}

/**
 * getChannelInfo
 */
function getChannelInfo($channelId)
{
    $token = false;
    do
    {
        if (empty($channelId))
        {
            break;
        }
        if (isset($GLOBALS['_cache']['chl'][$channelId]))
        {
            _debug("[" . __FUNCTION__ . "][chl:{$channelId}][cache_hit]", _DLV_INFO);
            if ($GLOBALS['_cache']['chl'][$channelId] != false)
            {
                $token = true;
            }
            break;
        }
        $query = 'SELECT `userId`, `payMode`, `shareRatio`, `subShareRatio`, `channelMarker`' .
            ' FROM `t_user_channel` WHERE `channelId` = ' . $channelId . ' LIMIT 1';
        _debug("[" . __FUNCTION__ . "][query:{$query}]", _DLV_INFO);
        
        $result = $GLOBALS['_import']['adminLink']->query($query);
        
        if ($row = $result->fetch_assoc())
        {
            $token = true;
        }
        else
        {
            $row = false;
        }
        
        $result->close();
        
        //add to cache
        $GLOBALS['_cache']['chl'][$channelId] = $row;
    }
    while (false);
    
    return $token;
}

/**
 * getChannelIdByMark
 */
function getChannelIdByMark($mark)
{
    if (empty($mark))
    {
        return false;
    }

    if (empty($GLOBALS['_cache']['chlmark'][$mark]))
    {
        $query = 'SELECT `channelId`, `userId`, `payMode`, `shareRatio`, `subShareRatio`, `channelMarker`' .
        ' FROM `t_user_channel` WHERE `channelMarker` = \'' . $mark . '\' LIMIT 1';
        _debug("[" . __FUNCTION__ . "][query:{$query}]", _DLV_INFO);
        
        $result = $GLOBALS['_import']['adminLink']->query($query);
        if (($row = $result->fetch_assoc()) && !empty($row['channelId']))
        {
            $GLOBALS['_cache']['chl'][$row['channelId']] = $row;
            $GLOBALS['_cache']['chlmark'][$mark] = $row['channelId'];
        }
        else
        {
            $GLOBALS['_cache']['chlmark'][$mark] = false;
        }
        $result->close();
    }
    else
    {
        _debug("[" . __FUNCTION__ . "][chlmark:{$mark}][cache_hit]", _DLV_INFO);
    }
    
    return $GLOBALS['_cache']['chlmark'][$mark];
}

/**
 * getAdnetworkInfo
 */
function getAdnetworkInfo($adnetworkId)
{
    $token = false;
    do
    {
        if (empty($adnetworkId))
        {
            break;
        }
        if (isset($GLOBALS['_cache']['adnw'][$adnetworkId]))
        {
            _debug("[" . __FUNCTION__ . "][adnetwork:{$adnetworkId}][cache_hit]", _DLV_INFO);
            if ($GLOBALS['_cache']['adnw'][$adnetworkId] != false)
            {
                $token = true;
            }
            break;
        }

        $query = 'SELECT `userId` AS `owner`, `adnetworkCode` AS `code`, `adnetworkName` AS `name` FROM `t_adnetwork`' .
            ' WHERE `adnetworkId` = ' . $adnetworkId . ' LIMIT 1';
        _debug("[" . __FUNCTION__ . "][query:{$query}]", _DLV_INFO);
        
        $result = $GLOBALS['_import']['adminLink']->query($query);
        
        if ($row = $result->fetch_assoc())
        {
            if (empty($row['owner']))
            {
                $row = false;
                _debug("[" . __FUNCTION__ . "][$adnetworkId][no_owner]", _DLV_ERROR);
            }
            else
            {
                $query = 'SELECT `data` FROM `t_third_contract`' .
                    ' WHERE networkId = ' . $adnetworkId . ' AND `status` = 1 AND `type` = 1 LIMIT 1';
                _debug("[" . __FUNCTION__ . "][query:{$query}]", _DLV_INFO);
                
                $result = $GLOBALS['_import']['adminLink']->query($query);

                if (($_row = $result->fetch_assoc()))
                {
                    $contract = json_decode($_row['data'], true);
                    if (!empty($contract['contractPrice']))
                    {
                        $discount = empty($contract['discount']) ? 1 : $contract['discount'] / 100;
                        $row['servtype'] = empty($contract['chargeType']) ? _SERVTYPE_CPC : $contract['chargeType'];
                        $row['conprice'] = $contract['contractPrice'] * $discount * 10000;
                        $token = true;
                    }
                    else
                    {
                        _debug("[" . __FUNCTION__ . "]][adnetwork:{$adnetworkId}][noprice]", _DLV_INFO);
                        $row = false;
                    }
                }
            }
        }
        else
        {
            $row = false;
        }
        
        $result->close();
        
        //add to cache
        $GLOBALS['_cache']['adnw'][$adnetworkId] = $row;
    }
    while (false);
    
    return $token;
}