<?php

class ViewController
{
    private $htmlVars;

    public function __construct()
    {
        $this->htmlVars = new stdClass();
    }


    public function minifyCss($folder = null){
        $cssFolder = "view/css/";
        $cssFiles = array_diff(scandir($cssFolder), array('..', '.'));
        /**
         * Ideally, you wouldn't need to change any code beyond this point.
         */
        $buffer = "";
        foreach ($cssFiles as $cssFile) {
            $buffer .= file_get_contents($cssFolder . $cssFile);
        }
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(': ', ':', $buffer);
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        echo($buffer);
    }

    /**
     * Assign variable to template
     * @param $var
     * @param mixed $value
     */
    public function assign($var, $value = ''){
        $this->htmlVars->$var = $value;
    }

    /**
     * Render whole prepared page @see prepare
     */
    public function renderOne($viewName,$dir = "front"){
        if ($this->htmlVars) {
            foreach ($this->htmlVars as $nmstr => $value) {
                $$nmstr = $value;
            }
        }
        include_once "view/$dir".DS."$viewName.phtml";
    }

    /**
     * Render view with along with header and footer, if ts not found - render 404
     * @param $viewName
     */
    public function renderAll($viewName,$viewDir = "front"){
        if ($this->htmlVars) {
            foreach ($this->htmlVars as $nmstr => $value) {
                $$nmstr = $value;
            }
        }
        include_once "view/$viewDir/header.phtml";
        if((@include_once "view/$viewDir/$viewName.phtml") === false)
        {
            //throw exception?
            $error = "View : '" . "view/$viewDir/$viewName.phtml" . "' not found !";
            include_once "view/$viewDir/404.phtml";
        }
        include_once "view/$viewDir/footer.phtml";
    }

}