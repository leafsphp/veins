<?php

// include
require "library/Veins/autoload.php";

// namespace
use Veins\Template;
$veins = new Template;


// configure
$config = array(
    "base_url"      => null,
    "veins_dir"       => "views/",
    "cache_dir"     => "cache/",
    "debug"         => true // set to false to improve the speed
);
$veins->configure($config);

// Add PathReplace plugin
$veins->registerPlugin(new Template\Plugin\PathReplace());



// set variables
$var = array(
    "variable"	=> "Hello World!",
    "pageTitle"	=> "Leaf Templating",
    "version"	=> "3.0 Alpha",
    "menu"		=> array(
        array("name" => "Home", "link" => "index.php", "selected" => true ),
        array("name" => "FAQ", "link" => "index.php/FAQ/", "selected" => null ),
        array("name" => "Documentation", "link" => "index.php/doc/", "selected" => null )
    ),
    "week"		=> array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday" ),
    "user"		=> (object) array("name"=>"Veins", "citizen" => "Earth", "race" => "Human" ),
    "numbers"	=> array( 3, 2, 1 ),
    "bad_text"	=> 'Hey this is a malicious XSS <script>alert(1);</script>',
    "table"		=> array( array( "Apple", "1996" ), array( "PC", "1997" ) ),
    "title"		=> "Mychi Bootstrap",
    "copyright" => "Copyright 2006 - 2012 Veins TPL<br>Project By Veins Team",
);

// add a function
$veins->registerTag(	"({@.*?@})", // preg split
    "{@(.*?)@}", // preg match
    function( $params ){ // function called by the tag
        $value = $params[0];
        return "Translate: <b>$value</b>";
    }
);

// draw
$veins->assign($var);
echo $veins->draw( "bootstrap/hero" );


// end