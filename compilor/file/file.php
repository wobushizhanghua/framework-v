<?php

class ciFile
{

    function GetContent($file)
    {
        if (is_file($file)) {
            return file_get_contents($file);
        }
    }

    function GetFolderFiles($folder)
    {
        $files = array();
        $dh = opendir($folder);
        if (!$dh) {
            die("Error: VIEW_ROOT 或者目录配置有误，无法编译");
        }
        while (($file = readdir($dh)) !== false) {
            if (is_file($folder . '/' . $file)) {
                $files[] = array(
                    "name" => $file,
                    "src" => $folder . '/' . $file
                );
            }
        }
        return $files;
    }

    function CreateFolder($folder)
    {
        if (!file_exists($folder)) {
            mkdir($folder);
        }
    }

    function Write($file, $c)
    {
        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return file_put_contents($file, $c);
    }
}
