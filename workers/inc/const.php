<?php
/**
 * $Id$
 */

define('_DATA_REQUEST',      0); //����
define('_DATA_IMPRESSION',   1); //չʾ
define('_DATA_CLICK',       11); //���
define('_DATA_REAL_REQ',     7); //��ʵ����
define('_DATA_WASH_REQ',    38); //��ϴ����
define('_DATA_WASH_IMP',    39); //��ϴչʾ
define('_DATA_WASH_CLI',    40); //��ϴ���

//����logλ��map
define('_OFFSET_MATERIALID',   0); //����ID
define('_OFFSET_ADPOSITIONID', 1); //���λID
define('_OFFSET_NETWORKID',    2); //�������ID
define('_OFFSET_LOGTYPE',      3); //��־����
define('_OFFSET_IDENTIFY',     4); //�û���ʶ
define('_OFFSET_USERAGENTID',  5); //UserAgentID
define('_OFFSET_GATEWAY',      6); //���ص�ַ
define('_OFFSET_SESSIONID',    7); //SessionID
define('_OFFSET_TIMESTAMP',    8); //ʱ���
define('_OFFSET_SDKVERSION',   9); //SDK�汾
define('_OFFSET_CAMPTYPE',    10); //�������
define('_OFFSET_MATERIALURL', 11); //���ϵ�ַ
define('_OFFSET_OPTIMCAMPID', 12); //Optimad���ID(��Ϊ��)
define('_OFFSET_CHANNELMARK', 17); //������ʶ��(������ID)


//Ͷ����ʽ
define('_SERVTYPE_CPC', 1); //CPC
define('_SERVTYPE_CPM', 2); //CPM

//�������
define('_AD_BANNERTEXT', 1); //ͼ�Ĺ��(������)
define('_AD_BANNER',     2); //banner���(ͼƬ)
define('_AD_FULLSCREEN', 3); //ȫ�����(ȫ��)

//������������ģʽ
define('_CHANNEL_PAYMODE_APPLIST',    1); //APP����
define('_CHANNEL_PAYMODE_THROUGHPUT', 2); //��������
