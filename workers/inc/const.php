<?php
/**
 * $Id$
 */

define('_DATA_REQUEST',      0); //请求
define('_DATA_IMPRESSION',   1); //展示
define('_DATA_CLICK',       11); //点击
define('_DATA_REAL_REQ',     7); //真实请求
define('_DATA_WASH_REQ',    38); //清洗请求
define('_DATA_WASH_IMP',    39); //清洗展示
define('_DATA_WASH_CLI',    40); //清洗点击

//定义log位置map
define('_OFFSET_MATERIALID',   0); //物料ID
define('_OFFSET_ADPOSITIONID', 1); //广告位ID
define('_OFFSET_NETWORKID',    2); //广告网络ID
define('_OFFSET_LOGTYPE',      3); //日志类型
define('_OFFSET_IDENTIFY',     4); //用户标识
define('_OFFSET_USERAGENTID',  5); //UserAgentID
define('_OFFSET_GATEWAY',      6); //网关地址
define('_OFFSET_SESSIONID',    7); //SessionID
define('_OFFSET_TIMESTAMP',    8); //时间戳
define('_OFFSET_SDKVERSION',   9); //SDK版本
define('_OFFSET_CAMPTYPE',    10); //广告类型
define('_OFFSET_MATERIALURL', 11); //物料地址
define('_OFFSET_OPTIMCAMPID', 12); //Optimad广告活动ID(可为空)
define('_OFFSET_CHANNELMARK', 17); //渠道标识符(非渠道ID)


//投放形式
define('_SERVTYPE_CPC', 1); //CPC
define('_SERVTYPE_CPM', 2); //CPM

//广告类型
define('_AD_BANNERTEXT', 1); //图文广告(文字链)
define('_AD_BANNER',     2); //banner广告(图片)
define('_AD_FULLSCREEN', 3); //全屏广告(全屏)

//超级渠道付费模式
define('_CHANNEL_PAYMODE_APPLIST',    1); //APP分账
define('_CHANNEL_PAYMODE_THROUGHPUT', 2); //流量分账
