<?php

class TplData {
    public $str;
    public $flags;
    public $loaded = false;

    public function __construct ($str, $flags) {
        $this->str = $str;
        $this->flags = $flags;
    }
}

class Tpl {
    const LOAD_STRING = 0;
    const LOAD_FILE = 1;
    const LOAD_PHP = 3;
    const LOAD_MD = 4;
    protected $md;
    private $loadOrder = [];
    private $vars = [];
    private $eval;

    public function __construct (Parsedown $md) {
        $this->md = $md;
    }

    public function doEval () {
        extract($this->vars);
        $include = function ($file, $option = self::LOAD_FILE) {
            $this->load($file, $option);
            print $this->singlePage(array_pop($this->loadOrder));
        };
        eval("?>" . $this->eval);
    }

    public function load ($str, $option = self::LOAD_STRING) {
        if ($option & self::LOAD_FILE) {
            $str = file_get_contents($str);
        }
        if ($option & self::LOAD_MD) {
            $str = $this->md->parse($str);
        }

        $this->loadOrder[] = new TplData($str, $option);
    }

    public function singlePage ($data) {
        $data->loaded = true;
        $str = $this->parse($data->str);

        if ($data->flags & self::LOAD_PHP) {
            $this->eval = $str;
            ob_start();
            $this->doEval();
            return ob_get_clean();
        } else {
            return $str;
        }
    }

    protected function parse ($data) {
        return preg_replace_callback("#{([a-zA-Z_]+)}#", function ($m) {
            return isset($this->vars[$m[1]]) ? $this->vars[$m[1]] : $m[0];
        }, $data);
    }

    public function set ($key, $val = null) {
        if (is_array($key) && $val === null) {
            $this->vars += $key;
        } else {
            $this->vars[$key] = $val;
        }
    }

    public function page ($page = "") {
        foreach ($this->loadOrder as $data) {
            if ($data->loaded) {
                continue;
            }

            $page .= $this->singlePage($data);
        }
        return $page;
    }
}
