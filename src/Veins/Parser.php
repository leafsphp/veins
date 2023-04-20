<?php

namespace Leaf\Veins;

/**
 * Veins Parser
 * ---
 * This class is responsible for parsing Leaf Veins templates.
 */
class Parser
{
    /**
     * Leaf Veins config
     */
    protected $config = [];

    /**
     * Built in tags
     */
    protected $tags = [
        'loop' => [
            '({loop.*?})',
            '/{loop="(?<variable>\${0,1}[^"]*)"(?: as (?<key>\$.*?)(?: => (?<value>\$.*?)){0,1}){0,1}}/',
        ],
        'loop_close' => ['({\/loop})', '/{\/loop}/'],
        'loop_break' => ['({break})', '/{break}/'],
        'loop_continue' => ['({continue})', '/{continue}/'],
        'foreach' => [
            '({foreach.*?})',
            '/{foreach="(?<variable>\${0,1}[^"]*)"(?: as (?<key>\$.*?)(?: => (?<value>\$.*?)){0,1}){0,1}}/',
        ],
        'foreach_close' => ['({\/foreach})', '/{\/foreach}/'],
        'foreach_break' => ['({break})', '/{break}/'],
        'foreach_continue' => ['({continue})', '/{continue}/'],
        'if' => ['({if.*?})', '/{if="([^"]*)"}/'],
        'elseif' => ['({elseif.*?})', '/{elseif="([^"]*)"}/'],
        'else' => ['({else})', '/{else}/'],
        'if_close' => ['({\/if})', '/{\/if}/'],
        'autoescape' => ['({autoescape.*?})', '/{autoescape="([^"]*)"}/'],
        'autoescape_close' => ['({\/autoescape})', '/{\/autoescape}/'],
        'noparse' => ['({noparse})', '/{noparse}/'],
        'noparse_close' => ['({\/noparse})', '/{\/noparse}/'],
        'ignore' => ['({ignore}|{\*)', '/{ignore}|{\*/'],
        'ignore_close' => ['({\/ignore}|\*})', '/{\/ignore}|\*}/'],
        'include' => ['({include.*?})', '/{include="([^"]*)"}/'],
        'function' => [
            '({function.*?})',
            '/{function="([a-zA-Z_][a-zA-Z_0-9\:]*)(\(.*\)){0,1}"}/',
        ],
        'ternary' => ['({.[^{?}]*?\?.*?\:.*?})', '/{(.[^{?}]*?)\?(.*?)\:(.*?)}/'],
        'variable' => ['({\$.*?})', '/{(\$.*?)}/'],
        'constant' => ['({#.*?})', '/{#(.*?)#{0,1}}/'],
    ];

    /**
     * black list of functions and variables
     */
    protected $blackList = [
        'exec', 'shell_exec', 'pcntl_exec', 'passthru', 'proc_open', 'system',
        'posix_kill', 'posix_setsid', 'pcntl_fork', 'posix_uname', 'php_uname',
        'phpinfo', 'popen', 'file_get_contents', 'file_put_contents', 'rmdir',
        'mkdir', 'unlink', 'highlight_contents', 'symlink',
        'apache_child_terminate', 'apache_setenv', 'define_syslog_variables',
        'escapeshellarg', 'escapeshellcmd', 'eval', 'fp', 'fput',
        'ftp_connect', 'ftp_exec', 'ftp_get', 'ftp_login', 'ftp_nb_fput',
        'ftp_put', 'ftp_raw', 'ftp_rawlist', 'highlight_file', 'ini_alter',
        'ini_get_all', 'ini_restore', 'inject_code', 'mysql_pconnect',
        'openlog', 'passthru', 'php_uname', 'phpAds_remoteInfo',
        'phpAds_XmlRpc', 'phpAds_xmlrpcDecode', 'phpAds_xmlrpcEncode',
        'posix_getpwuid', 'posix_kill', 'posix_mkfifo', 'posix_setpgid',
        'posix_setsid', 'posix_setuid', 'posix_uname', 'proc_close',
        'proc_get_status', 'proc_nice', 'proc_open', 'proc_terminate',
        'syslog', 'xmlrpc_entity_decode',
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function checkTemplate(array $config, string $template)
    {
        if (strpos($template, '.vein.html') === false) {
            $template .= '.vein.html';
        }

        $template = $config['templateDir'] . $template;

        if (!file_exists($template)) {
            throw new \Exception("Template file not found: {$template}");
        }

        $parsedTemplate = $config['cacheDir'] . md5($template . serialize($config['checksum'])) . '.vein.html';

        if (
            $config['debug'] ||
            !file_exists($parsedTemplate) ||
            filemtime($template) > filemtime($parsedTemplate)
        ) {
            $parser = new self($config);

            return $parser->parse($template, $parsedTemplate);
        }

        return $parsedTemplate;
    }

    public function parse(string $template, string $parsedTemplate): string
    {
        $template = file_get_contents($template);
        $templateDir = dirname($template);

        foreach ($this->tags as $tag => $tagArray) {
            list($split, $match) = $tagArray;
            $tagSplit[$tag] = $split;
            $tagMatch[$tag] = $match;
        }

        $keys = array_keys($this->config['customTags']);
        $tagSplit += array_merge($tagSplit, $keys);

        if ($this->config['removeComments']) {
            $template = preg_replace('/<!--(.*)-->/Uis', '', $template);
        }

        //split the code with the tags regexp
        $codeSplit = preg_split("/" . implode("|", $tagSplit) . "/", $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        //variables initialization
        $parsedCode = $commentIsOpen = $ignoreIsOpen = null;
        $openIf = $loopLevel = 0;

        // if the template is not empty
        if ($codeSplit) {

            //read all parsed code
            foreach ($codeSplit as $html) {

                //close ignore tag
                if (!$commentIsOpen && preg_match($tagMatch['ignore_close'], $html)) {
                    $ignoreIsOpen = false;
                }

                //code between tag ignore id deleted
                elseif ($ignoreIsOpen) {
                    //ignore the code
                }

                //close no parse tag
                elseif (preg_match($tagMatch['noparse_close'], $html)) {
                    $commentIsOpen = false;
                }

                //code between tag noparse is not compiled
                elseif ($commentIsOpen) {
                    $parsedCode .= $html;
                }

                //ignore
                elseif (preg_match($tagMatch['ignore'], $html)) {
                    $ignoreIsOpen = true;
                }

                //noparse
                elseif (preg_match($tagMatch['noparse'], $html)) {
                    $commentIsOpen = true;
                }

                //include tag
                elseif (preg_match($tagMatch['include'], $html, $matches)) {

                    //get the folder of the actual template
                    if (substr($templateDir, 0, strlen($this->config['veins_dir'])) == $this->config['veins_dir']) {
                        $templateDir = substr($templateDir, strlen($this->config['veins_dir']));
                    }

                    //get the included template
                    if (strpos($matches[1], '$') !== false) {
                        $includeTemplate = "'$templateDir'." . $this->varReplace($matches[1], $loopLevel);
                    } else {
                        $includeTemplate = $templateDir . $this->varReplace($matches[1], $loopLevel);
                    }

                    // reduce the path
                    $includeTemplate = Parser::reducePath($includeTemplate);

                    if (strpos($matches[1], '$') !== false) {
                        //dynamic include
                        $parsedCode .= '<?php require $this->checkTemplate(' . $includeTemplate . ');?>';
                    } else {
                        //dynamic include
                        $parsedCode .= '<?php require $this->checkTemplate("' . $includeTemplate . '");?>';
                    }
                }

                //loop
                elseif (preg_match($tagMatch['loop'], $html, $matches)) {

                    // increase the loop counter
                    $loopLevel++;

                    //replace the variable in the loop
                    $var = $this->varReplace($matches['variable'], $loopLevel - 1, $escape = false);
                    if (preg_match('#\(#', $var)) {
                        $newvar = "\$newvar{$loopLevel}";
                        $assignNewVar = "$newvar=$var;";
                    } else {
                        $newvar = $var;
                        $assignNewVar = null;
                    }

                    // check black list
                    $this->blackList($var);

                    //loop variables
                    $counter = "\$counter$loopLevel";       // count iteration

                    if (isset($matches['key']) && isset($matches['value'])) {
                        $key = $matches['key'];
                        $value = $matches['value'];
                    } elseif (isset($matches['key'])) {
                        $key = "\$key$loopLevel";               // key
                        $value = $matches['key'];
                    } else {
                        $key = "\$key$loopLevel";               // key
                        $value = "\$value$loopLevel";           // value
                    }



                    //loop code
                    $parsedCode .= "<?php $counter=-1; $assignNewVar if( isset($newvar) && ( is_array($newvar) || $newvar instanceof Traversable ) && sizeof($newvar) ) foreach( $newvar as $key => $value ){ $counter++; ?>";
                }



                //close loop tag
                elseif (preg_match($tagMatch['loop_close'], $html)) {

                    //iterator
                    $counter = "\$counter$loopLevel";

                    //decrease the loop counter
                    $loopLevel--;

                    //close loop code
                    $parsedCode .= "<?php } ?>";
                }

                //break loop tag
                elseif (preg_match($tagMatch['loop_break'], $html)) {
                    //close loop code
                    $parsedCode .= "<?php break; ?>";
                }

                //continue loop tag
                elseif (preg_match($tagMatch['loop_continue'], $html)) {
                    //close loop code
                    $parsedCode .= "<?php continue; ?>";
                }

                //loop
                elseif (preg_match($tagMatch['foreach'], $html, $matches)) {

                    // increase the foreach counter
                    $loopLevel++;

                    //replace the variable in the foreach
                    $var = $this->varReplace($matches['variable'], $loopLevel - 1, $escape = false);
                    if (preg_match('#\(#', $var)) {
                        $newvar = "\$newvar{$loopLevel}";
                        $assignNewVar = "$newvar=$var;";
                    } else {
                        $newvar = $var;
                        $assignNewVar = null;
                    }

                    // check black list
                    $this->blackList($var);

                    //foreach variables
                    $counter = "\$counter$loopLevel";       // count iteration

                    if (isset($matches['key']) && isset($matches['value'])) {
                        $key = $matches['key'];
                        $value = $matches['value'];
                    } elseif (isset($matches['key'])) {
                        $key = "\$key$loopLevel";               // key
                        $value = $matches['key'];
                    } else {
                        $key = "\$key$loopLevel";               // key
                        $value = "\$value$loopLevel";           // value
                    }



                    //foreach code
                    $parsedCode .= "<?php $counter=-1; $assignNewVar if( isset($newvar) && ( is_array($newvar) || $newvar instanceof Traversable ) && sizeof($newvar) ) foreach( $newvar as $key => $value ){ $counter++; ?>";
                }



                //close foreach tag
                elseif (preg_match($tagMatch['foreach_close'], $html)) {

                    //iterator
                    $counter = "\$counter$loopLevel";

                    //decrease the foreach counter
                    $loopLevel--;

                    //close foreach code
                    $parsedCode .= "<?php } ?>";
                }

                //break foreach tag
                elseif (preg_match($tagMatch['foreach_break'], $html)) {
                    //close foreach code
                    $parsedCode .= "<?php break; ?>";
                }

                //continue foreach tag
                elseif (preg_match($tagMatch['foreach_continue'], $html)) {
                    //close foreach code
                    $parsedCode .= "<?php continue; ?>";
                }

                //if
                elseif (preg_match($tagMatch['if'], $html, $matches)) {

                    //increase open if counter (for intendation)
                    $openIf++;

                    //tag
                    $tag = $matches[0];

                    //condition attribute
                    $condition = $matches[1];

                    // check black list
                    $this->blackList($condition);

                    //variable substitution into condition (no delimiter into the condition)
                    $parsedCondition = $this->varReplace($condition, $loopLevel, $escape = false);

                    //if code
                    $parsedCode .= "<?php if( $parsedCondition ){ ?>";
                }

                //elseif
                elseif (preg_match($tagMatch['elseif'], $html, $matches)) {

                    //tag
                    $tag = $matches[0];

                    //condition attribute
                    $condition = $matches[1];

                    // check black list
                    $this->blackList($condition);

                    //variable substitution into condition (no delimiter into the condition)
                    $parsedCondition = $this->varReplace($condition, $loopLevel, $escape = false);

                    //elseif code
                    $parsedCode .= "<?php }elseif( $parsedCondition ){ ?>";
                }

                //else
                elseif (preg_match($tagMatch['else'], $html)) {

                    //else code
                    $parsedCode .= '<?php }else{ ?>';
                }

                //close if tag
                elseif (preg_match($tagMatch['if_close'], $html)) {

                    //decrease if counter
                    $openIf--;

                    // close if code
                    $parsedCode .= '<?php } ?>';
                }

                // autoescape off
                elseif (preg_match($tagMatch['autoescape'], $html, $matches)) {

                    // get function
                    $mode = $matches[1];
                    $this->config['autoEscape_old'] = $this->config['autoEscape'];

                    if ($mode == 'off' or $mode == 'false' or $mode == '0' or $mode == null) {
                        $this->config['autoEscape'] = false;
                    } else {
                        $this->config['autoEscape'] = true;
                    }
                }

                // autoescape on
                elseif (preg_match($tagMatch['autoescape_close'], $html, $matches)) {
                    $this->config['autoEscape'] = $this->config['autoEscape_old'];
                    unset($this->config['autoEscape_old']);
                }

                // function
                elseif (preg_match($tagMatch['function'], $html, $matches)) {

                    // get function
                    $function = $matches[1];

                    // var replace
                    if (isset($matches[2])) {
                        $parsedFunction = $function . $this->varReplace($matches[2], $loopLevel, $escape = false, $echo = false);
                    } else {
                        $parsedFunction = $function . "()";
                    }

                    // check black list
                    $this->blackList($parsedFunction);

                    // function
                    $parsedCode .= "<?php echo $parsedFunction; ?>";
                }

                //ternary
                elseif (preg_match($tagMatch['ternary'], $html, $matches)) {
                    $parsedCode .= "<?php echo " . '(' . $this->varReplace($matches[1], $loopLevel, $escape = true, $echo = false) . '?' . $this->varReplace($matches[2], $loopLevel, $escape = true, $echo = false) . ':' . $this->varReplace($matches[3], $loopLevel, $escape = true, $echo = false) . ')' . "; ?>";
                }

                //variables
                elseif (preg_match($tagMatch['variable'], $html, $matches)) {
                    //variables substitution (es. {$title})
                    $parsedCode .= "<?php " . $this->varReplace($matches[1], $loopLevel, $escape = true, $echo = true) . "; ?>";
                }


                //constants
                elseif (preg_match($tagMatch['constant'], $html, $matches)) {
                    $parsedCode .= "<?php echo " . $this->conReplace($matches[1]) . "; ?>";
                }

                // registered tags
                else {

                    $found = false;
                    foreach ($this->config['customTags'] as $tags => $array) {
                        if (preg_match_all('/' . $array['parse'] . '/', $html, $matches)) {
                            $found = true;
                            $parsedCode .= "<?php echo call_user_func( static::\$registered_tags['$tags']['function'], " . var_export($matches, 1) . " ); ?>";
                        }
                    }

                    if (!$found) {
                        $parsedCode .= $html;
                    }
                }
            }
        }


        if ($openIf > 0) {
            $trace = debug_backtrace();
            $caller = array_shift($trace);

            throw new \Exception("Error! You need to close an {if} tag in the string, loaded by {$caller['file']} at line {$caller['line']}");
        }

        if ($loopLevel > 0) {
            $trace = debug_backtrace();
            $caller = array_shift($trace);

            throw new \Exception("Error! You need to close the {loop} tag in the string, loaded by {$caller['file']} at line {$caller['line']}");
        }

        $html = str_replace('?><?php', ' ', $parsedCode);

        // Execute plugins, after_parse
        $template = $parsedCode;

        file_put_contents($parsedTemplate, $template);

        return $parsedTemplate;
    }

    protected function varReplace($html, $loopLevel = null, $escape = true, $echo = false)
    {

        // change variable name if loop level
        if (!empty($loopLevel)) {
            $html = preg_replace(['/(\$key)\b/', '/(\$value)\b/', '/(\$counter)\b/'], ['${1}' . $loopLevel, '${1}' . $loopLevel, '${1}' . $loopLevel], $html);
        }

        // if it is a variable
        if (preg_match_all('/(\$[a-z_A-Z][^\s]*)/', $html, $matches)) {
            // substitute . and [] with [" "]
            for ($i = 0; $i < count($matches[1]); $i++) {

                $rep = preg_replace('/\[(\${0,1}[a-zA-Z_0-9]*)\]/', '["$1"]', $matches[1][$i]);
                //$rep = preg_replace('/\.(\${0,1}[a-zA-Z_0-9]*)/', '["$1"]', $rep);
                $rep = preg_replace('/\.(\${0,1}[a-zA-Z_0-9]*(?![a-zA-Z_0-9]*(\'|\")))/', '["$1"]', $rep);
                $html = str_replace($matches[0][$i], $rep, $html);
            }

            // update modifier
            $html = $this->modifierReplace($html);

            // if does not initialize a value, e.g. {$a = 1}
            if (!preg_match('/\$.*=.*/', $html)) {

                // escape character
                if ($this->config['autoEscape'] && $escape) {
                    //$html = "htmlspecialchars( $html )";
                    $html = "htmlspecialchars( $html, ENT_COMPAT, '" . $this->config['charset'] . "', FALSE )";
                }

                // if is an assignment it doesn't add echo
                if ($echo) {
                    $html = "echo " . $html;
                }
            }
        }

        return $html;
    }

    protected function modifierReplace($html)
    {
        $this->blackList($html);

        if (strpos($html, '|') !== false && substr($html, strpos($html, '|') + 1, 1) != "|") {
            preg_match('/([\$a-z_A-Z0-9\(\),\[\]"->]+)\|([\$a-z_A-Z0-9\(\):,\[\]"->\s]+)/i', $html, $result);

            $function_params = $result[1];
            $result[2] = str_replace("::", "@double_dot@", $result[2]);
            $explode = explode(":", $result[2]);
            $function = str_replace('@double_dot@', '::', $explode[0]);
            $params = isset($explode[1]) ? "," . $explode[1] : null;

            $html = str_replace($result[0], $function . "(" . $function_params . "$params)", $html);

            if (strpos($html, '|') !== false && substr($html, strpos($html, '|') + 1, 1) != "|") {
                $html = $this->modifierReplace($html);
            }
        }

        return $html;
    }

    protected function blackList($html)
    {
        if (!$this->config['sandbox'] || !$this->blackList) {
            return true;
        }

        if (empty($this->config['blackListPreg'])) {
            $this->config['blackListPreg'] = '#[\W\s]*' . implode('[\W\s]*|[\W\s]*', $this->blackList) . '[\W\s]*#';
        }

        // check if the function is in the black list (or not in white list)
        if (preg_match($this->config['blackListPreg'], $html, $match)) {
            // find the line of the error
            $line = 0;
            $rows = explode("\n", 'code should be here');
            while (!strpos($rows[$line], $html) && $line + 1 < count($rows)) {
                $line++;
            }

            throw new \Exception('Syntax ' . $match[0] . ' not allowed in template: ' . '$templatePath' . ' at line ' . $line);
        }
    }

    protected function conReplace($html)
    {
        $html = $this->modifierReplace($html);

        return $html;
    }

    public static function reducePath($path)
    {
        // reduce the path
        $path = str_replace("://", "@not_replace@", $path);
        $path = preg_replace("#(/+)#", "/", $path);
        $path = preg_replace("#(/\./+)#", "/", $path);
        $path = str_replace("@not_replace@", "://", $path);
        while (preg_match('#\w+\.\./#', $path)) {
            $path = preg_replace('#\w+/\.\./#', '', $path);
        }

        return $path;
    }
}
