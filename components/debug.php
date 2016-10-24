<?php

class CiDebug
{
    static private $info = array();
    static private $start_i = 0;
    static private $start_time = array();
    static public $being_debug = true;

    public function MicrotimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function TimeStart()
    {
        self::$start_time[self::$start_i] = self::MicrotimeFloat();
        self::$start_i++;
    }

    public function TimeUsed()
    {
        self::$start_i--;
        return self::MicrotimeFloat() - self::$start_time[self::$start_i];
    }

    public function InfoAdd($key, $value)
    {
        self::$info[$key] = $value;
    }

    public function InfoGet()
    {
        return self::$info;
    }

    public function BeingDebug()
    {
        return self::$being_debug;
    }

    public function Dump2File($var)
    {
        file_put_contents('/tmp/v_dump', print_r($var, true), FILE_APPEND);
    }
}
