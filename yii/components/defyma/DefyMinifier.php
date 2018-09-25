<?php
/**
 * Defy M Aminuddin
 * defyma85@gmail.com
 * defyma.com
 */

namespace app\components\defyma;

use Yii;
use yii\base\Widget;

class DefyMinifier extends Widget
{

    public function init()
    {
        parent::init();
        ob_start();
        ob_implicit_flush(false);
    }

    public function run()
    {
        //Original YII Spaceless
        $html = trim(preg_replace('/>\s+</', '><', ob_get_clean()));

        //Minify
        require_once(Yii::getAlias('@app/components/defyma/minifier/Minifier.php'));
        $minifier      = new \Minifier;
        $html_compress = $minifier->minifyHTML($html);

        $html_compress .= "
<!-- Generate Date: ".date('Y-m-d H:i:s')." -->

";
        //Output
        echo $html_compress;
    }
}
