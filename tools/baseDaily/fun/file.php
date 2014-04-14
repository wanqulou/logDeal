<?php
// 文件相关函数
// $Id: file.php 1318 2014-03-21 10:44:41Z sunxiang $

/**
 * 查找文件
 */
function getFiles($dir, $limit = 20, $ext = '*', $check = true)
{
    if (!is_dir($dir))
    {
        return false;
    }
    $files = array();
    $result = scandir($dir);
    foreach ($result as $item)
    {
        if ($item === '.' || $item === '..')
        {
            continue;
        }
        $file = $dir . '/' . $item;
        if (is_file($file))
        {
            $stat = 0;
            $fileinfo = pathinfo($file);
            if ($ext !== '*' && $ext !== $fileinfo['extension'])
            {
                continue;
            }
            if ($check == true)
            {
                $cmd = ($ext == 'tbz2' ? _CMD_BZIP2_PATH : _CMD_GZIP_PATH) . " -t {$file} 2>> /dev/null";
                exec($cmd, $output, $stat);
            }
            if ($stat == 0)
            {
                $files[] = $file;
            }
        }
        if ($limit > 0 && count($files) >= $limit)
        {
            break;
        }
    }
    return $files;
}

/**
 * 建立文件夹
 */
function makeDir($path, $mode = '0755')
{
    if (!file_exists($path))
    {
        $cmd = _CMD_MKDIR_PATH . " -p -m {$mode} {$path}";
        exec($cmd);
    }
    if (is_dir($path))
    {
        return true;
    }
    return false;
}

/**
 * 移动文件
 */
function _moveFile($file, $path)
{
    if (!is_file($files) || !is_dir($path))
    {
        return false;
    }
    $stat = 0;
    $cmd = _CMD_MV_PATH . " -f {$file} {$path}";
    exec($cmd, $output, $stat);
    if ($stat == 0)
    {
        return true;
    }
    return false;
}