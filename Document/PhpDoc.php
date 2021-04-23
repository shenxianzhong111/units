<?php

namespace utils\Document;

class PhpDoc extends DocumentParser {

    /**
     * @var mixed
     */
    private $_register = array();

    /**
     * @title current class
     * 用于出错时找到所属的class
     */
    private $_current_class = '';

    /**
     * Store data into register
     * @param string $name
     * @param string $key
     * @param mixed $val
     */
    protected function _setRegister($name, $key, $val = null) {
        if ($val) {
            $vName = isset($val[0]) ? trim($val[0]) : '';
            $vDval = isset($val[1]) ? trim($val[1]) : '';
            $vDesc = isset($val[2]) ? trim($val[2]) : '';
            switch ($key) {
                case 'params' :
                    $this->_register[$name][$key][$vName] = array(
                        'dval' => $vDval,
                        'desc' => $vDesc
                    );
                    break;
                default :
                    $this->_register[$name][$key] = $vName;
                    break;
            }
        } else {
            $this->_register[$name] = $key;
        }
    }

    /**
     * Get register by name
     * @param string $name
     */
    protected function _getRegister($name) {

        if (isset($this->_register[$name])) {
            return $this->_register[$name];
        } else {
            $short = preg_replace('/.*\//', '', $this->_current_class);
            exit("Please Check " . $short);
        }
    }

    /**
     * Clean register by name
     * @param string $name
     */
    protected function _delRegister($name) {
        $this->_register[$name] = array();
    }

    /**
     * Parse annotations for a class file
     * @param $classFile
     * @return void
     */
    public function parseCode($classFile) {
        $this->_current_class = $classFile;

        $fp = fopen($classFile, 'r');
        while (!feof($fp)) {
            $codeLine = fgets($fp);
            $this->_parseLine($codeLine);
        }
        fclose($fp);
    }

    /**
     * Parse each line annotation
     * @param string $codeLine
     * @return void
     */
    protected function _parseLine($codeLine) {
        // easy to parse
        $codeLine .= '  ';
        // set annotations
        if (preg_match('/@(\w+)\s+(.*?)\s+(.*?)\s+(.*)/i', $codeLine, $annotationRes)) {
            $annotationName = isset($annotationRes[1]) ? trim($annotationRes[1]) : '';
            if ($annotationName) {
                array_shift($annotationRes);
                array_shift($annotationRes);
                $this->_setRegister('annotation', $annotationName, $annotationRes);
            }
        }

        // get class annotation
        if (preg_match('/class\s+(\w+)\s?/i', $codeLine, $classRes)) {
            $className = isset($classRes[1]) ? trim($classRes[1]) : '';
            if ($className) {
                $this->_setRegister('class', $className);
                $classAnnotation = $this->_getRegister('annotation');
                if ($classAnnotation) {
                    $this->_annotation[$className]['_annotation'] = $classAnnotation;
                    $this->_delRegister('annotation');
                }
            }
        }

        // get function annotation
        if (preg_match('/function\s+(\w+)\s?/i', $codeLine, $functionRes)) {
            $functionName = isset($functionRes[1]) ? trim($functionRes[1]) : '';
            if ($functionName) {
                $className = $this->_getRegister('class');
                $functionAnnotation = $this->_getRegister('annotation');
                if ($functionAnnotation) {
                    $this->_annotation[$className]['_methodlist'][$functionName]['_annotation'] = $functionAnnotation;
                    $this->_delRegister('annotation');
                }
            }
        }
    }

}
