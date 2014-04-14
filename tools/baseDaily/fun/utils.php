<?php
// 通用函数
// $Id: utils.php 1445 2014-04-11 10:25:26Z sunxiang $

/**
 * 日志入库
 */
function logsToTable($baseDailyData)
{
    global $db_finance;
    
    $dCount = count($baseDailyData);
    $dIndex = 0;
    foreach ($baseDailyData as $key => $item)
    {
        list($adnwId, $appId, $chlUserId, $campType, $reportDate) = explode('_', $key);
        $reqs = $item['reqs'];
        $imps = $item['imps'];
        $clis = $item['clis'];
        $adAmount = $item['adcost'];
        $userIncome = $item['uincome'];
        $chlIncome = $item['cincome'];
        $adnwOwner = $item['adnwOwner'];
        $appOwner = $item['appOwner'];
        
        $db_finance->query("set @result = NULL");
        $sql = "CALL selfservice_third_billing({$reportDate}, {$adnwId}, {$campType}, {$appId}, {$adnwOwner}, {$adAmount}, {$appOwner}, {$userIncome}, {$chlUserId}, {$chlIncome}, {$reqs}, {$imps}, {$clis}, @result)";

        $db_finance->query($sql);
        if ($result = $db_finance->query('SELECT @result AS result'))
        {
            $row = $result->fetch_assoc();
            $result = $row['result'] == '1' ? 'yes' : 'no';
        }

        $dIndex++;
        debug("[SQL:{$sql}][billingResult:{$result}][{$dIndex}/{$dCount}]", 'notice');
    }
}

