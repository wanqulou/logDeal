<?php
/**
 * 文件相关函数
 * $Id$
 */
$GLOBALS['_sys']['mkdir'] = '/bin/mkdir';
$GLOBALS['_sys']['bzip2'] = '/usr/bin/bzip2';
$GLOBALS['_sys']['gzip'] = '/usr/bin/gzip';
$GLOBALS['_sys']['dd'] = '/bin/dd';
$GLOBALS['_sys']['mv'] = '/bin/mv';
$GLOBALS['_sys']['bzcat'] = '/usr/bin/bzcat';
$GLOBALS['_sys']['gzcat'] = '/usr/bin/gzcat';
$GLOBALS['_sys']['tar'] = '/usr/bin/tar';
$GLOBALS['_sys']['scp'] = '/usr/bin/scp';
$GLOBALS['_sys']['cp'] = '/bin/cp';
$GLOBALS['_sys']['rm'] = '/bin/rm';

/**
 * 建立文件夹
 */
function _makeDir($path, $type = 'd', $mode = '0755')
{
    $type = empty($type) ? 'd' : strtolower($type);
    $path = ($type === 'd') ? $path : dirname($path);
    if (!file_exists($path))
    {
        @exec("{$GLOBALS['_sys']['mkdir']} -p -m $mode $path");
    }
    else if (is_dir($path))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 查找文件
 * 
 */
function _findAllFiles($dir, $fileExt='tbz2', $check = true, $max = 0)
{
    $result = false;
    if ($root = scandir($dir))
    {
        foreach($root as $value)
        { 
            if ($value === '.' || $value === '..')
            {
                continue;
            }
            $file = $dir . '/' . $value;
            if (is_file($file))
            {
                if (!empty($fileExt)) //需要判断后缀名
                {
                    $info = pathinfo($value);
                    if ($info['extension'] == $fileExt)
                    {
                        $stat = 0;
                        if ($check == true)
                        {
                            $checkCmd = ($fileExt == 'tbz2') ? $GLOBALS['_sys']['bzip2'] : $GLOBALS['_sys']['gzip'];
                            @system("$checkCmd -t $file 2>> /dev/null", $stat);
                        }
                        if ($stat == 0)
                        {
                            $result[] = $file;
                        }
                    }
                }
            }
            if ($max > 0 && count($result) >= $max)
            {
                break;
            }
        }
    }
    return $result; 
}


/**
 *  _findBadFiles
 */
function _findBadFiles($dir, $fileExt = 'tbz2', $timeout = 7200)
{
    $files = array();
    $root = scandir($dir); 
    foreach($root as $value)
    { 
        if($value === '.' || $value === '..')
        {
            continue;
        } 
        $file = $dir . '/' . $value;
        if(is_file($file))
        {
            if (!empty($fileExt)) //需要判断后缀名
            {
                $info = pathinfo($value);
                if ($info['extension'] == $fileExt)
                {
                    $checkCmd = ($fileExt=='tbz2') ? $GLOBALS['_sys']['bzip2'] : $GLOBALS['_sys']['gzip'];
                    @system("$checkCmd -t $file 2>> /dev/null", $stat);
                    if ($stat != 0)
                    {
                        $fileCreateTime = @filemtime($file);
                        if ($timeout <= abs($GLOBALS['currentTime'] - $fileCreateTime)) //解压失败,并且创建时间超过2小时
                        {
                            $files[] = $file;
                        }
                    }
                }
            }
        }
    } 
    return $files; 
}

/**
 * _moveFiles
 */
function _moveFiles(array $files, $path)
{
    $result = false;
    
    if (empty($files) || !is_dir($path))
    {
        return false;
    }
    
    $moveFiles = $failFiles = array();
    foreach ($files as $file) 
    {
        if (is_file($file)) 
        {
            $moveFiles[] = $file;
        }
        else
        {
            $failFiles[] = $file;
        }
    }
    
    if (!empty($moveFiles))
    {
        @exec("{$GLOBALS['_sys']['mv']} -f " . implode(' ', $moveFiles) . " {$path}", $arrLines, $stat);
        $result = ($stat == 0);
        _debug("[success:" . implode(',', $moveFiles) . "][failed:" . implode(',', $failFiles) . "][to:{$path}]", _DLV_NOTICE);
    }
    else
    {
        _debug("[success:" . implode(',', $moveFiles) . "][failed:" . implode(',', $failFiles) . "][to:{$path}]", _DLV_WARNING);
    }

    return $result;
}

/**
 * _transferFile 
 */
function _transferFile($file, $path, $host = null, $port = null, $user = null, $bak_dir = null, $retry_dir = null)
{
    if (file_exists($file) && !empty($path))
    {
        if (!empty($host)) //host不为空,说明是ssh方式
        {
            $cmd = "{$GLOBALS['_sys']['scp']} -P {$port} -o StrictHostKeyChecking=no -o ConnectTimeout=20 {$file} {$user}@{$host}:{$path} 2>>/dev/null";
        }
        else //local
        {
            _makeDir($path);
            $cmd = "{$GLOBALS['_sys']['cp']} {$file} {$path}";
        }
        _debug("[" . __FUNCTION__ . "][command:{$cmd}]", _DLV_NOTICE);
        system($cmd, $trans_stat);
        if ($trans_stat === 0)
        {
            return true;
        }
    }
    return false;
}

/**
 * _package
 */
function _package($files, $tarball = null, $type = 'j')
{
    if (empty($files)) 
    {
        return false;
    }
    
    $files = (array)$files;
    
    if (empty($tarball)) 
    {
        if (count($files) > 1) 
        {
            return false;
        }
        else
        {
            $info = pathinfo(reset($files));
            $tarball = $info['filename'] . ($type == 'j') ? '.tbz2' : '.tgz';
        }
    }

    foreach ($files as $file)
    {
        if (!file_exists($file) || filesize($file) <= 0)
        {
            return false;
        }
    }

    system("{$GLOBALS['_sys']['tar']} c{$type}f {$tarball} " . implode(' ', $files) . " 2>>/dev/null", $tarStat);
    if ($tarStat == 0)
    {
        exec("{$GLOBALS['_sys']['rm']} -f " . implode(' ', $files));
    }

    return $tarball;
}
