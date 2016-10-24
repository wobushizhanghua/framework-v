<?php
require_once(dirname(__FILE__) . "/debug.php");

class Component
{
    public $visiable_condition = "true";
    public $visibility = true;
    protected $use_cache = false;
    protected $cache_arguments;
    public $use_ajax = array();

    function __construct()
    {
        if ($this->use_cache) {
            //      var_dump($this);die();
            //        Using("external_classes.memcache");
        }
    }

    public function SetVar($name, $value)
    {
        $this->innerVarArray["{" . $name . "}"] = "" . $value;
        return;
        //$this->innerHTML = str_replace('{'.$name.'}' ,$value ,$this->innerHTML);
    }

    public function EchoContent($param = null)
    {
        foreach ($this->innerHTMLArray as $arr) {
            if (is_array($arr)) {
                foreach ($arr as $a) {
                    echo $a['str'];
                    if ($this->innerVarArray[$a['next_var']] !== null) {
                        echo $this->innerVarArray[$a['next_var']];
                    } else {
                        echo $a['next_var'];
                    }
                }
            } else {
                $arr->OutPutContent($param);
            }
        }
        echo $this->innerHTMLArray[$i];
    }

    function KeepState($name, $value)
    {
        $classname = get_class();
        session_start();
        $_SESSION[$classname . $name] = $value;
    }

    function GetKeepedState($name)
    {
        $classname = get_class();
        session_start();
        return $_SESSION[$classname . $name];
    }

    public function BuildContent()
    {
        $c = '';
        foreach ($this->innerHTMLArray as $a) {
            $c .= $a['str'];
            $c .= $this->innerVarArray[$a['next_var']];
        }
        $c .= $this->innerHTMLArray[$i];
        return $c;
    }

    public function SetArray($array)
    {
        if ($array && count($array) > 0) {
            foreach ($array as $row) {
                foreach ($row as $key => $value) {
                    $this->innerHTML = preg_replace('/\{d\[' . $key . ']\}/', $value, $this->innerHTML);
                }
            }
        } else {
            $this->innerHTML = preg_replace('/\{d\[.*\]\}/', '', $this->innerHTML);
        }
    }

    protected function Action($function)
    {
        $argv = func_get_args();
        $arguments_str = '';
        for ($i = 1; $i < count($argv); $i++) {
            $arguments_str .= "&argv[$i]={$argv[$i]}";
        }
        return "/main.php?action={$this->file_name}&component={$this->class_name}&function=$function" . $arguments_str . "&t=" . time();
    }

    protected function On($function)
    {
        static $function_array = array();
        $function_array[$function] = count($function_array);

        $argv = func_get_args();
        $arguments = array_slice($argv, 1);
        global $url;
        $url->SetValue($_SERVER['REQUEST_URI']);
        $s = _build_framework_args_($this->file_name, $this->class_name, $function, $arguments);
        $url->SetParam('fwargs' . "[" . $function . "]", $s);

        return $url->value;
    }

    public function OnEvent($function, $argv)
    {
        if (method_exists($this, $function)) {
            call_user_func_array(array($this, $function), $argv);
        }
//		global $url;
//		$url->SetBack();
//		$url->Go();
    }

    protected function RequireDepend()
    {
        if (is_array($this->depend)) {
            require_once(VIEW_ROOT . "/workspace/" . $this->depend['filename'] . ".php");
            global $components;
            if ($components[$this->depend['component_name']]) {
                return;
            }
            $components[$this->depend['component_name']] = new $this->depend['component_name'];
        }

    }

    protected function InitComp($file, $comp)
    {
        $file = str_replace(array('/', "\\"), array('', ''), $file);
        require_once(VIEW_ROOT . "/workspace/" . $file . ".php");
        global $components;
        $components[$comp] = new $comp;
    }

    protected function GetComp($name)
    {
        global $components;
        return $components[$name];
    }

    function CachePrepare()
    {
        return true;
    }

    function GetOutPutContent($arg = null)
    {
        ob_start();
        $this->OutPutContent($arg);
        $str = ob_get_contents();
        ob_end_clean();
        return $str;
    }

    function OutPutContent($arg = null)
    {
        $this->RequireDepend();
        //		echo microtime();
        CiDebug::TimeStart();
        if (!$this->visibility) {
            return;
        }
        if ($this->use_ajax['flag'] && $this->use_ajax['is_single_component'] != true) {
            echo "<span id='ajax_$this->name'></span>";
            echo "<script>ajax_ex('ajax_$this->name','{$this->use_ajax['url']}');</script>";
            return;
        }
        if ($this->use_cache) {
            $this->CachePrepare();
            $content = $this->CacheGet();
            if ($content) {
                echo $content;
//				echo microtime();
                CiDebug::InfoAdd($this->name, CiDebug::TimeUsed());
                return;
            }

            ob_start();
        }

        $this->OutPut($arg);

        if ($this->use_cache) {
            $this->CacheSave(ob_get_contents());
            ob_end_flush();
        }
//		echo microtime();

        CiDebug::InfoAdd($this->name, CiDebug::TimeUsed());
        return;
    }

    private function CacheSave($content)
    {
        if ($this->cache_time) {
            $cache_time = $this->cache_time;
        } else {
            $cache_time = 300;
        }
        return M::Set($this->CacheBuildKey(), $content, $cache_time);
    }

    private function CacheBuildKey()
    {
        $tmp = '';
        if (is_array($this->cache_arguments)) {
            foreach ($this->cache_arguments as $arg) {
                $tmp = $tmp . $arg . "_";
            }
        }
        return "content_" . $_SERVER['HTTP_HOST'] . "_cache_{$this->name}_{$tmp}";
    }

    private function CacheGet()
    {
        return M::Get($this->CacheBuildKey());
    }

    protected function CacheClear()
    {
        return M::Clear($this->CacheBuildKey());
    }
}

class ComponentVar extends Component
{
    public $visibility = 1;
    public $value;
    public $innerHTML;
    protected $js_var = array();
    function OutPut()
    {
        if ($this->visibility != 1) {
            return;
        }
        //	if($this->value)
        echo $this->innerHTML;
        //	else if($this->innerHTML)
        //		echo $this->innerHTML;

    }

    function SetJsVar($name, $val) {
        $this->js_var[$name] = $val;
    }

    function OutPutCiphpJs() {
        $prefix = dirname($_SERVER['REQUEST_URI']);
        $vars = "";
        foreach ($this->js_var as $name=>$val) {
            $val = addslashes($val);
            $vars .= "\"{$name}\" : \"{$val}\",";
        }
        echo " 
    <script>var ciphp_vars = {
        {$vars}
        'uri_root' : '{$prefix}'
    }; </script>
    <script src='{$prefix}/static/js/ciphp.js'></script>\n";
    }
}

class ComponentForm extends Component
{
    public $method = "post";

    function OutPut()
    {
        if (isset($this->class)) {
            $class_str = "class='$this->class'";
        }
        if (isset($this->onsubmit)) {
            $class_str .= " onsubmit='{$this->onsubmit}'";
        }
        if (isset($this->enctype)) {
            $en_str = "enctype='multipart/form-data'";
        }
        if (isset($this->target)) {
            $target_str = "target='{$this->target}'";
        }

        echo "<form $en_str name='{$this->name}' method='{$this->method}' action='{$this->action}' id='{$this->id}' $class_str $target_str>";
        $this->EchoContent();
        echo $this->input_str;
        echo "</form>";

    }

    function AddInput($name, $value)
    {
        $this->input_str .= "<input type='hidden' name='{$name}' value='{$value}'/>\n";
    }
}

class ComponentList extends Component
{
    protected $use_cache;
    protected $cache_arguments;

    function StoreInnerHTML()
    {
        $this->innerHTMLBak = $this->innerHTML;
    }

    function ResetInnerHTML()
    {
        $this->innerHTML = $this->innerHTMLBak;
    }

    function OutPut()
    {
        echo $this->innerHTML;
    }
}

require_once(dirname(__FILE__) . "/pager.php");

class ComponentElement extends Component
{
    public $visiable_condition = "true";
    public $visibility = true;
    public $use_cache = false;
    public $use_ajax = array();

    function __construct()
    {
        if ($this->use_cache) {
            //	var_dump($this);die();
            if (!class_exists("M")) {
                Using("external_classes.memcache");
            } else {
                if (!class_exists("M")) {
                    Using("classes.memcache");
                } else {
                    if (!class_exists("M")) {
                        $this->use_cache = false;
                        //发送一个通知给我
                    }
                }
            }
        }
    }

    function OutPut_main()
    {
        if ($this->use_ajax['flag'] && $this->use_ajax['is_single_component'] != true) {
            echo "<span id='ajax_$this->name'></span>";
            echo "<script>ajax_ex('ajax_$this->name','{$this->use_ajax['url']}');</script>";
            return;
        }
        if ($this->visibility == false) {
            return;
        }
        if (isset($this->visiable_condition)) {
            $flag = false;
            eval("if(!({$this->visiable_condition}))
					\$flag = true;");
            if ($flag) {
                return;
            }
        }
        if (isset($this->src) && $this->visiable_condition) {
            global $components, $url;
            $this->src = str_replace(
                array('/', '\\'),
                array('', ''),
                $this->src);
            if ($_GET['debug'] == 1) {
                echo $this->src;
            }

            $this->LoadPHP();
            return;
        }

    }

    function LoadPHP()
    {
        include_once(VIEW_ROOT . "/v/template_c/php/{$this->src}.php");
    }

    function OutPutContent($arg = null)
    {
        CiDebug::TimeStart();
        $this->OutPut();
        $t = CiDebug::TimeUsed();
        CiDebug::InfoAdd($this->src, $t);
    }

    function OutPut()
    {
//		echo microtime();
        if ($this->use_cache) {

            $content = $this->CacheGet();
            if ($content) {
                echo $content;
                //	echo microtime();
                return;
            }

            ob_start();
        }

        $this->OutPut_main();

        if ($this->use_cache) {
            $this->CacheSave(ob_get_contents());
            ob_end_flush();
        }
//		echo microtime();

        return;

    }

    private function CacheSave($content)
    {
        return M::Set("content_" . $_SERVER['HTTP_HOST'] . "_cache_$this->src", $content, 300);
    }

    private function CacheGet()
    {
        if ($this->src) {
            return M::Get("content_" . $_SERVER['HTTP_HOST'] . "_cache_$this->src");
        }
    }
}

class URL
{
    public $value;
    protected $params = array();
    protected $flag;

    function __construct()
    {
        $this->value = $_SERVER['REQUEST_URI'];
    }

    function SetValue($v)
    {
        $this->value = $v;
		return $this;
    }

    function __get($name)
    {
        return str_replace("'", '', $_GET[$name]);
    }

    function Clear()
    {
        $this->value = "http://" . $_SERVER['HTTP_HOST'] . "/main.php";
    }

    function BuildSingleParams()
    {
        static $arg_names = array(
            'part',
            'space_id',
            'action',
            'singlediary',
            'target_type',
            'post_id',
            'album_id',
            'photo_id',
            'by',
            'tagname',
            'tab',
            'city',
            'year',
            'month',
            'time',
            'city',
            'province',
            'sex'
        );
        static $arg_names_ch = array("tagname", "city", "province");


    }

    function CheckRefer($val)
    {
        preg_match("/^(http:\/\/)?([^\/]+)/i",
            $_SERVER['HTTP_REFERER'], $matches);
        $host = $matches[2];
        return ($val == $host);
    }

    function ForceChange($flag = false)
    {
        $this->flag = $flag;
    }

    function SetParam($name, $value, $flag = false)
    {
        /*		Using("external_classes.memcache");
                $args = M::Get("baobao_args");
                if($name == 'action' || $name = 'part')
                {

                }

                if(!is_array($args))
                    M::Set("baobao_args",array(),300);
                else if(!in_array($name,$args))
                {
                    $args[] = $name;
                    M::Set("baobao_args",$args,300);
                }

        */
        if ($flag || $this->flag) {
            $this->{$name} = $value;
        }
        $name_reg = str_replace(array('[', ']'), array('\[', '\]'), $name);
        if (preg_match('/[\&\?]' . $name_reg . '/', $this->value)) {
            $this->value = preg_replace('/(\?|\&)' . $name_reg . '=[^&]*/', "\$1$name=$value", $this->value);
        } elseif (!strpos($this->value, '?')) {
            $this->value = $this->value . "?$name=$value";
        } else {
            $this->value = $this->value . "&$name=$value";
        }

        return $this->value;
    }

    function GetParam($name)
    {
        $name_reg = str_replace(array('[', ']'), array('\[', '\]'), $name);
        if (preg_match('/[\&\?]' . $name_reg . '=([^&]*)/', $this->value, $match)) {
            return $match[1];
        }

        return null;
    }

    function SetBack()
    {
        $this->value = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        return $this;
        //$this->SetParam('time',time());
    }

    function Go()
    {
        header("location:{$this->value}");
        die();
    }

    function ParentGo()
    {
        echo "<head>
		<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>
		   <script>
			top.location.href='$this->value';
			</script></head>";
        die();
    }

    function AlertGo($mes)
    {
        //header("Content-Type:text/html;charset=utf-8");
        echo "<head>
		<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>
		   <script>
			alert('$mes');
			location.href='$this->value';
			</script></head>";
        die();
    }

    function ParentAlertGo($mes)
    {
        echo "<head>
		<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>
		<script>
			alert('$mes');
			parent.location.href='$this->value';
			</script></head>";
        die();
    }

    function Alert($mes)
    {
        echo "
		<head>
		<meta http-equiv='Content-Type' content='text/html;charset=utf-8'/>
		<script>
			alert('$mes');
			</script>
		</head>";
        die();
    }

    function GetPage($name)
    {
        $this->value = "/?action=$name";
        return $this->value;
    }

    function GetCurrent()
    {
        return "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}
