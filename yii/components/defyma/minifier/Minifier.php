<?php

/**
 * Defy M Aminuddin
 * defyma85@gmail.com
 * defyma.com
 */

class Minifier {

    private $minDir;

    function __construct()
    {
        $this->minDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'minify-3.0.3'.DIRECTORY_SEPARATOR;

        require $this->minDir.'config.php';

        ob_start();
    }

    function minifyHTML($htmlView)
    {
        //Minify HTML
        require_once $this->minDir.'lib/Minify/HTML.php';

        //Minify CSS
        require_once $this->minDir.'lib/Minify/CSS/Compressor.php';
        require_once $this->minDir.'lib/Minify/CommentPreserver.php';
        require_once $this->minDir.'lib/Minify/CSS.php';

        //Minify JS
        require_once $this->minDir.'lib/Minify/JS/JShrink_minifier.php';
        require_once $this->minDir.'lib/Minify/JS/JShrink.php';

        $minOutput = Minify_HTML::minify($htmlView, array(
            'cssMinifier' => array('Minify_CSS', 'minify'),
            'jsMinifier' => array('Minify\JS\JShrink', 'minify')
        ));
        
        return $minOutput;
    }

}
