<?php

namespace app\controllers;

use yii\web\ForbiddenHttpException;
use yii\gii\components\DiffRendererHtmlInline;
use yii\web\Controller;

class GeneratorController extends Controller
{
    public function beforeAction($action)
    {
        date_default_timezone_set('Asia/Jakarta');
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        // if (\yii::$app->user->isGuest || (!\yii::$app->user->isGuest && \yii::$app->user->identity->position !== 'Developer')) {
        //     throw new ForbiddenHttpException('You dont have permission to access this page.');
        // }

        if ($_SERVER['HTTP_HOST'] !== 'localhost'){
            return $this->render('localhost');
        }

        $req = \Yii::$app->request;
        $tables = \Yii::$app->db->createCommand("SELECT table_name FROM information_schema.tables WHERE table_schema = database() AND table_name NOT LIKE '%_history';")->queryAll();
        $cekcrud = 0;
        $cekhistory = 0;
        if ($req->isPost) {
            $mode = $req->post('mode');
            $table = $req->post('table');
            $ds = DIRECTORY_SEPARATOR;
            $model_dir = \Yii::getAlias('@app') . $ds . 'models';
            $controller_dir = \Yii::getAlias('@app') . $ds . 'controllers';
            $view_dir = \Yii::getAlias('@app') . $ds . 'views';
            $generator_dir = \Yii::getAlias('@app') . $ds . 'generator';
            $generator_template_dir = $generator_dir . $ds . 'templates';
            $history_generator_template_dir = $generator_template_dir . $ds . 'history';
            if ($mode == 'preview') {
                $filelist = [];
                /* BEGIN MODEL */
                $model_name = $this->generateModelName($table);
                $gen = new \yii\gii\generators\model\Generator();
                $gen->tableName = $table;
                $data = $gen->generate();
                $model_location = $model_dir . $ds . $model_name . '.php';
                $filelist[] = ['location' => $model_dir . $ds . $model_name . '.php', 'operation' => $this->operation($model_location, $data[0]->content), 'type' => 'model', 'table' => $table];
                /* END MODEL */
                /* BEGIN CRUD */
                if (isset($_POST['cekcrud'])) {
                    //model
                    $model_name = $this->generateModelName($table);
                    $model_location = $model_dir . $ds . $model_name . '.php';
                    $model_temp = false;
                    if (!file_exists($model_location)) {
                        $gen = new \yii\gii\generators\model\Generator();
                        $gen->tableName = $table;
                        $data = $gen->generate();
                        file_put_contents($model_location, $data[0]->content);
                        $model_temp = true;
                    }
                    $cekcrud = 1;
                    $crudgen = new \yii\gii\generators\crud\Generator();
                    $crudgen->controllerClass = 'app\\controllers\\' . $model_name . 'Controller';
                    $crudgen->modelClass = 'app\\models\\' . $model_name;
                    $crudgen->searchModelClass = 'app\\models\\' . $model_name . 'Search';
                    $crudgen->viewPath = '@app/views/' . strtolower($model_name);
                    $data = $crudgen->generate();
                    /* BEGIN SEARCH MODEL */
                    $search_model_location = $model_dir . $ds . $model_name . 'Search.php';
                    $filelist[] = ['location' => $search_model_location, 'operation' => $this->operation($search_model_location, $data[1]->content), 'type' => 'searchmodel'];
                    /* END SEARCH MODEL */
                    /* BEGIN CONTROLLER */
                    $content = $this->generateController($model_name);
                    $controller_location = $controller_dir . $ds . ucfirst(strtolower($model_name)) . 'Controller.php';
                    $filelist[] = [
                        'location' => $controller_location,
                        'operation' => $this->operation($controller_location, $content),
                        'type' => 'controller'
                    ];
                    /* END CONTROLLER */
                    /* BEGIN VIEW */
                    $templates = scandir($generator_template_dir);
                    for ($i = 0; $i < count($templates); ++$i) {
                        if ($templates[$i] == '.' || $templates[$i] == '..' || $templates[$i] == 'history') {
                            continue;
                        }
                        $template_path = $view_dir . $ds . strtolower($model_name) . $ds . $templates[$i];
                        $view_content = $this->generateView($templates[$i], $table, 'crud');
                        $filelist[] = ['location' => $template_path, 'operation' => $this->operation($template_path, $view_content), 'type' => 'view', 'filename' => $templates[$i], 'cekcrud' => $cekcrud, 'cekhistory' => $cekhistory];
                    }
                    /* END VIEW */
                    if ($model_temp) {
                        unlink($model_location);
                    }
                }
                /* END CRUD */
                /* BEGIN HISTORY */
                if (isset($_POST['cekhistory'])) {

                    //table
                    try {
                        $this->generateHistoryTable($table);
                    } catch (\Exception $e) {
                        die($e->getMessage());
                    }

                    //model
                    $model_name = $this->generateModelName($table . '_history');
                    $model_location = $model_dir . $ds . $model_name . '.php';
                    $model_operation = '';
                    $model_temp = false;
                    $gen = new \yii\gii\generators\model\Generator();
                    $gen->tableName = $table . '_history';
                    $data = $gen->generate();
                    if (!file_exists($model_location)) {
                        $model_operation = 'write';
                        file_put_contents($model_location, $data[0]->content);
                        $model_temp = true;
                    } else {
                        $model_operation = $this->operation($model_location, $data[0]->content);
                    }
                    $filelist[] = ['location' => $model_location, 'operation' => $model_operation, 'type' => 'historymodel', 'table' => $table . '_history'];
                    $cekhistory = 1;
                    $crudgen = new \yii\gii\generators\crud\Generator();
                    $crudgen->controllerClass = 'app\\controllers\\' . ucfirst(strtolower($model_name)) . 'Controller';
                    $crudgen->modelClass = 'app\\models\\' . $model_name;
                    $crudgen->searchModelClass = 'app\\models\\' . $model_name . 'Search';
                    $crudgen->viewPath = '@app/views/' . strtolower($model_name);
                    $data = $crudgen->generate();
                    /* BEGIN SEARCH MODEL */
                    $search_model_location = $model_dir . $ds . $model_name . 'Search.php';
                    $filelist[] = ['location' => $search_model_location, 'operation' => $this->operation($search_model_location, $data[1]->content), 'type' => 'historysearchmodel'];
                    /* END SEARCH MODEL */
                    /* BEGIN CONTROLLER */
                    $content = $this->generateController($model_name, TRUE);
                    $controller_location = $controller_dir . $ds . ucfirst(strtolower($model_name)) . 'Controller.php';
                    $filelist[] = [
                        'location' => $controller_location,
                        'operation' => $this->operation($controller_location, $content),
                        'type' => 'historycontroller'
                    ];
                    /* END CONTROLLER */
                    /* BEGIN VIEW */
                    $templates = scandir($history_generator_template_dir);
                    for ($i = 0; $i < count($templates); ++$i) {
                        if ($templates[$i] == '.' || $templates[$i] == '..') {
                            continue;
                        }
                        $template_path = $view_dir . $ds . strtolower($model_name) . $ds . $templates[$i];
                        $view_content = $this->generateView($templates[$i], $table . '_history', 'history');
                        $filelist[] = ['location' => $template_path, 'operation' => $this->operation($template_path, $view_content), 'type' => 'historyview', 'filename' => $templates[$i]];
                    }
                    if ($model_temp) {
                        unlink($model_location);
                    }
                    /* END HISTORY */
                }
                /* END CRUD */
                //return var_dump($filelist);
                return $this->render('index', ['tables' => $tables, 'filelist' => $filelist, 'data' => $data, 'table' => $table, 'cekcrud' => $cekcrud, 'cekhistory' => $cekhistory]);
            } elseif ($mode == 'generate') {
                $filelist = $_POST['FileList'];
                //return var_dump($filelist);
                $model_name = $this->generateModelName($table);
                $history_model_name = $this->generateModelName($table . '_history');
                if (isset($_POST['cekcrud'])) {
                    $cekcrud = 1;
                }
                if (isset($_POST['cekhistory'])) {
                    $cekhistory = 1;
                }
                for ($i = 0; $i < count($filelist); ++$i) {
                    if ($filelist[$i]['type'] === 'model') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $model_name = $this->generateModelName($filelist[$i]['table']);
                            $gen = new \yii\gii\generators\model\Generator();
                            $gen->tableName = $filelist[$i]['table'];
                            $data = $gen->generate();
                            $model_location = $model_dir . $ds . $model_name . '.php';
                            file_put_contents($model_location, $data[0]->content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    } elseif ($filelist[$i]['type'] === 'historymodel') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $model_name = $this->generateModelName($filelist[$i]['table']);
                            $gen = new \yii\gii\generators\model\Generator();
                            $gen->tableName = $filelist[$i]['table'];
                            $data = $gen->generate();
                            $model_location = $model_dir . $ds . $model_name . '.php';
                            file_put_contents($model_location, $data[0]->content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    } elseif ($filelist[$i]['type'] === 'searchmodel' || $filelist[$i]['type'] === 'historysearchmodel') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $gen = new \yii\gii\generators\crud\Generator();
                            $gen->controllerClass = 'app\\controllers\\' . $model_name . 'Controller';
                            $gen->modelClass = 'app\\models\\' . $model_name;
                            $gen->searchModelClass = 'app\\models\\' . $model_name . 'Search';
                            $gen->viewPath = '@app/views/' . strtolower($model_name);
                            $data = $gen->generate();
                            $search_model_location = $model_dir . $ds . $model_name . 'Search.php';
                            file_put_contents($search_model_location, $data[1]->content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    } elseif ($filelist[$i]['type'] === 'controller') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $content = $this->generateController($model_name);
                            $controller_location = $controller_dir . $ds . ucfirst(strtolower($model_name)) . 'Controller.php';
                            file_put_contents($controller_location, $content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    } elseif ($filelist[$i]['type'] === 'historycontroller') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $content = $this->generateController($history_model_name, TRUE);
                            $controller_location = $controller_dir . $ds . ucfirst(strtolower($history_model_name)) . 'Controller.php';
                            file_put_contents($controller_location, $content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    } elseif ($filelist[$i]['type'] === 'view') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $content = $this->generateView($filelist[$i]['location'], $table);
                            if (!file_exists($view_dir . $ds . strtolower($model_name))) {
                                mkdir($view_dir . $ds . strtolower($model_name));
                            }
                            $view_location = $view_dir . $ds . strtolower($model_name) . $ds . $filelist[$i]['location'];
                            file_put_contents($view_location, $content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    } elseif ($filelist[$i]['type'] === 'historyview') {
                        if ($filelist[$i]['operation'] == 'write' || $filelist[$i]['operation'] == 'overwrite' && isset($filelist[$i]['overwrite'])) {
                            $content = $this->generateView($filelist[$i]['location'], $table . '_history', 'history');
                            if (!file_exists($view_dir . $ds . strtolower($history_model_name))) {
                                mkdir($view_dir . $ds . strtolower($history_model_name));
                            }
                            $view_location = $view_dir . $ds . strtolower($history_model_name) . $ds . $filelist[$i]['location'];
                            file_put_contents($view_location, $content);
                            $filelist[$i]['status'] = 'OK';
                        } else {
                            $filelist[$i]['status'] = 'Skipped';
                        }
                    }
                }
                //return var_dump($filelist);
                return $this->render('index', ['tables' => $tables, 'filelist' => $filelist, 'table' => $table, 'cekcrud' => $cekcrud, 'cekhistory' => $cekhistory, 'modelname' => $this->generateModelName($table)]);
            }
        }
        return $this->render('index', ['tables' => $tables]);
    }

    private function generateModelName($table_name)
    {
        $x = explode('_', $table_name);
        if (count($x) > 0) {
            for ($i = 0; $i < count($x); ++$i) {
                $x[$i] = ucfirst($x[$i]);
            }
            return implode('', $x);
        }
        return strtolower($table_name);
    }

    public function actionPreview($table_name, $type, $extra = null)
    {
        // if (\yii::$app->user->isGuest || (!\yii::$app->user->isGuest && \yii::$app->user->identity->position !== 'Developer')) {
        //     throw new ForbiddenHttpException('You dont have permission to access this page.');
        // }

        $output = '';
        $ds = DIRECTORY_SEPARATOR;
        $model_dir = \Yii::getAlias('@app') . $ds . 'models';
        $model_name = $this->generateModelName($table_name);
        $history_model_name = $this->generateModelName($table_name . "_history");
        $model_location = $model_dir . $ds . $model_name . '.php';
        $history_model_location = $model_dir . $ds . $history_model_name . '.php';
        $model_temp = false;
        $history_model_temp = false;
        if ($type == 'model') {
            //model
            $gen = new \yii\gii\generators\model\Generator();
            $gen->tableName = $table_name;
            $data = $gen->generate();
            $output = $data[0]->content;
        } elseif ($type == 'historymodel') {
            //history model
            $gen = new \yii\gii\generators\model\Generator();
            $gen->tableName = $table_name . "_history";
            $data = $gen->generate();
            $output = $data[0]->content;
        } elseif ($type == 'searchmodel') {
            //search model
            if (!file_exists($model_location)) {
                $gen = new \yii\gii\generators\model\Generator();
                $gen->tableName = $table_name;
                $data = $gen->generate();
                file_put_contents($model_location, $data[0]->content);
                $model_temp = true;
            }
            $gen = new \yii\gii\generators\crud\Generator();
            $gen->controllerClass = 'app\\controllers\\' . $model_name . 'Controller';
            $gen->modelClass = 'app\\models\\' . $model_name;
            $gen->searchModelClass = 'app\\models\\' . $model_name . 'Search';
            $gen->viewPath = '@app/views/' . strtolower($model_name);
            $data = $gen->generate();
            $output = $data[1]->content;
        } elseif ($type == 'historysearchmodel') {
            //history search model
            if (!file_exists($history_model_location)) {
                $gen = new \yii\gii\generators\model\Generator();
                $gen->tableName = $table_name . "_history";
                $data = $gen->generate();
                file_put_contents($history_model_location, $data[0]->content);
                $history_model_temp = true;
            }
            $gen = new \yii\gii\generators\crud\Generator();
            $gen->controllerClass = 'app\\controllers\\' . $history_model_name . 'Controller';
            $gen->modelClass = 'app\\models\\' . $history_model_name;
            $gen->searchModelClass = 'app\\models\\' . $history_model_name . 'Search';
            $gen->viewPath = '@app/views/' . strtolower($history_model_name);
            $data = $gen->generate();
            $output = $data[1]->content;
        } elseif ($type == 'controller') {
            //controller
            if (!file_exists($model_location)) {
                $gen = new \yii\gii\generators\model\Generator();
                $gen->tableName = $table_name;
                $data = $gen->generate();
                file_put_contents($model_location, $data[0]->content);
                $model_temp = true;
            }

            $output = $this->generateController($model_name);
        } elseif ($type == 'historycontroller') {
            //history controller
            if (!file_exists($history_model_location)) {
                $gen = new \yii\gii\generators\model\Generator();
                $gen->tableName = $table_name . "_history";
                $data = $gen->generate();
                file_put_contents($history_model_location, $data[0]->content);
                $history_model_temp = true;
            }

            $output = $this->generateController($history_model_name, TRUE);

        } elseif ($type == 'view') {
            //view
            $output = $this->generateView($extra, $table_name);
        } elseif ($type == 'historyview') {
            //view
            $output = $this->generateView($extra, $table_name, 'history');
        }
        if ($model_temp) {
            unlink($model_location);
        }
        if ($history_model_temp) {
            unlink($history_model_location);
        }
        return highlight_string($output, TRUE);
    }

    public function actionDiff($table_name, $type, $extra)
    {
        $lines1 = '';
        $lines2 = '';

        $ds = DIRECTORY_SEPARATOR;

        if ($type == 'model') {
            $gen = new \yii\gii\generators\model\Generator();
            $gen->tableName = $table_name;
            $data = $gen->generate();
            $lines1 = file($data[0]->path);
            $lines2 = $data[0]->content;
        } else if ($type == 'historymodel') {
            $gen = new \yii\gii\generators\model\Generator();
            $gen->tableName = $table_name . "_history";
            $data = $gen->generate();
            $lines1 = file($data[0]->path);
            $lines2 = $data[0]->content;
        } else if ($type == 'searchmodel') {
            $model_name = $this->generateModelName($table_name);
            $gen = new \yii\gii\generators\crud\Generator();
            $gen->controllerClass = 'app\\controllers\\' . $model_name . 'Controller';
            $gen->modelClass = 'app\\models\\' . $model_name;
            $gen->searchModelClass = 'app\\models\\' . $model_name . 'Search';
            $gen->viewPath = '@app/views/' . strtolower($model_name);
            $data = $gen->generate();
            $lines1 = file($extra);
            $lines2 = $data[1]->content;
        } else if ($type == 'historysearchmodel') {
            $model_name = $this->generateModelName($table_name . '_history');
            $gen = new \yii\gii\generators\crud\Generator();
            $gen->controllerClass = 'app\\controllers\\' . $model_name . 'Controller';
            $gen->modelClass = 'app\\models\\' . $model_name;
            $gen->searchModelClass = 'app\\models\\' . $model_name . 'Search';
            $gen->viewPath = '@app/views/' . strtolower($model_name);
            $data = $gen->generate();
            $lines1 = file($extra);
            $lines2 = $data[1]->content;
        } else if ($type == 'controller') {
            $model_name = $this->generateModelName($table_name);
            $controller_file = $model_name . 'Controller.php';
            $lines1 = file($extra);
            //var_dump($extra); exit;
            $lines2 = $this->generateController($model_name);
        } else if ($type == 'historycontroller') {
            $model_name = $this->generateModelName($table_name . "_history");
            $controller_file = $model_name . 'Controller.php';
            $lines1 = file($extra);
            //var_dump($extra); exit;
            $lines2 = $this->generateController($model_name, TRUE);
        } else if ($type == 'view') {
            $model_name = $this->generateModelName($table_name);
            $tmp = explode($ds, $extra);
            $filename = $tmp[count($tmp) - 1];
            $lines1 = file($extra);
            $lines2 = $this->generateView($filename, $table_name);
        } else if ($type == 'historyview') {
            $model_name = $this->generateModelName($table_name . '_history');
            $tmp = explode($ds, $extra);
            $filename = $tmp[count($tmp) - 1];
            $lines1 = file($extra);
            $lines2 = $this->generateView($filename, $table_name, 'history');
        }

        if (!is_array($lines1)) {
            $lines1 = explode("\n", $lines1);
        }
        if (!is_array($lines2)) {
            $lines2 = explode("\n", $lines2);
        }
        foreach ($lines1 as $i => $line) {
            $lines1[$i] = rtrim($line, "\r\n");
        }
        foreach ($lines2 as $i => $line) {
            $lines2[$i] = rtrim($line, "\r\n");
        }
        $renderer = new \yii\gii\components\DiffRendererHtmlInline();
        $diff = new \Diff($lines1, $lines2);
        return $diff->render($renderer);
    }

    public function operation($file1, $file2)
    {
        if (!file_exists($file1)) {
            return 'write';
        }
        $lines1 = trim(file_get_contents($file1));
        $lines2 = trim($file2);
        return $lines1 === $lines2 ? 'skip' : 'overwrite';
    }

    public function actionTes()
    {
        if (\yii::$app->user->isGuest || (!\yii::$app->user->isGuest && \yii::$app->user->identity->position !== 'Developer')) {
            throw new ForbiddenHttpException('You dont have permission to access this page.');
        }

        header('Content-type:text/plaiin');
        $gen = new \yii\gii\generators\controller\Generator();
        $gen->controllerClass = 'app\\controllers\\VideoController';
        return var_dump($gen->generate());
    }

    public function actionTesview()
    {
        if (\yii::$app->user->isGuest || (!\yii::$app->user->isGuest && \yii::$app->user->identity->position !== 'Developer')) {
            throw new ForbiddenHttpException('You dont have permission to access this page.');
        }

        header('Content-type:text/plain');
        $gen = new \yii\gii\generators\crud\Generator();
        $gen->controllerClass = 'app\\controllers\\VideoController';
        $gen->modelClass = 'app\\models\\Video';
        $gen->searchModelClass = 'app\\models\\VideoSearch';
        $gen->viewPath = '@app/views/video';
        return var_dump($gen->generate());
    }

    private function cleanController($content)
    {
        $beforeaction = <<<'BA'
public function beforeAction($action) {
        date_default_timezone_set('Asia/Jakarta');
        return parent::beforeAction($action);
    }

    public function behaviors()
BA;
        //$content = preg_replace('/^\/(.*)@inheritdoc(.*)\/$/', $beforeaction, $content);
        $content = str_replace('public function behaviors()', $beforeaction, $content);
        $content = str_replace('extends Controller', 'extends Base2Controller', $content);
        $content = str_replace("'delete' => ['POST'],", "//'delete' => ['POST'],", $content);
        return $content;
    }

    public static function quote($str)
    {
        $quotes = ['"', "'", '`'];
        if (!isset($str)) {
            return '';
        }
        $str = trim($str);
        if (is_numeric($str)) {
            return $str;
        }
        $first = substr($str, 0, 1);
        $last = substr($str, strlen($str) - 1);
        if (in_array($first, $quotes) || in_array($last, $quotes)) {
            if ($first !== $last) {
                return "'" . addslashes($str) . "'";
            }
            return $str;
        } else {
            return "'" . addslashes($str) . "'";
        }
    }

    private function generateView($filename, $tablename, $type = 'crud')
    {
        $ds = DIRECTORY_SEPARATOR;
        $generator_dir = \Yii::getAlias('@app') . $ds . 'generator';
        $generator_template_dir = $generator_dir . $ds . 'templates' . $ds;
        $ignore = ['status_update', 'user_update', 'last_update'];
        if ($type == 'history') {
            $generator_template_dir = $generator_dir . $ds . 'templates' . $ds . 'history' . $ds;
        }
        $modelname = $this->generateModelName($tablename);
        $controllername = strtolower($modelname);
        $fields = \Yii::$app->db->createCommand('DESC ' . $tablename)->queryAll();
        $columns = '';
        $primarykey = '';
        $html_input = "<?= \$form->field(\$model, '%COLUMN_NAME%')->textInput(['maxlength' => true]) ?>";
        $field_input = '';
        foreach ($fields as $v) {
            if ($type == 'history' && (in_array($v['Field'], $ignore) || strpos($v['Field'], '_history') !== false)) {
                continue;
            } else {
                $columns .= self::quote($v['Field']) . ',';
                if (!isset($v['Extra']) || $v['Extra'] === '') {
                    $field_input .= str_replace('%COLUMN_NAME%', $v['Field'], $html_input);
                }
            }
            if (isset($v['Extra']) && $v['Extra'] === 'PRI') {
                $primarykey = $v['Extra'];
            }
        }
        if ($primarykey === '') {
            $primarykey = $fields[0]['Field'];
        }
        //die($generator_template_dir.$filename);
        //file_get_contents($generator_template_dir.$filename) or die($generator_template_dir.$filename);
        $content = file_get_contents($generator_template_dir . $filename);
        $content = str_replace('%MODEL_NAME%', $modelname, $content);
        $content = str_replace('%CONTROLLER_NAME%', $controllername, $content);
        $content = str_replace('%COLUMNS%', $columns, $content);
        $content = str_replace('%PLURAL_MODEL_NAME%', $this->pluralize($modelname), $content);
        $content = str_replace('%PRIMARY_KEY%', $primarykey, $content);
        $content = str_replace('%FIELD_INPUT%', $field_input, $content);
        return $content;
    }

    private function generateController($modelname, $history = FALSE)
    {
        $ds = DIRECTORY_SEPARATOR;
        $generator_dir = \Yii::getAlias('@app') . $ds . 'generator';
        $controllername = ucfirst(strtolower($modelname));

        $tmp = preg_split('/(?=[A-Z])/', $modelname, -1, PREG_SPLIT_NO_EMPTY);
        if (!isset($tmp) || $tmp === NULL) {
            $tmp = [];
        }

        $table_name = (count($tmp) > 1) ? strtolower(implode("_", preg_split('/(?=[A-Z])/', $modelname, -1, PREG_SPLIT_NO_EMPTY))) : strtolower($modelname);
        $fields = \Yii::$app->db->createCommand('DESC ' . $table_name)->queryAll();
        $primarykey = '';
        foreach ($fields as $v) {
            if (isset($v['Extra']) && $v['Extra'] === 'PRI') {
                $primarykey = $v['Extra'];
            }
        }
        if ($primarykey === '') {
            $primarykey = $fields[0]['Field'];
        }

        $content = ($history) ? file_get_contents($generator_dir . $ds . "historycontroller.php") : file_get_contents($generator_dir . $ds . "controller.php");
        $content = str_replace('%MODEL_NAME%', $modelname, $content);
        $content = str_replace('%CONTROLLER_NAME%', $controllername, $content);
        $content = str_replace('%PRIMARY_KEY%', $primarykey, $content);
        return $content;
    }

    private function pluralize($word = '')
    {
        $last = substr($word, -1);
        $plural = '';
        if ($last == 'y') {
            $plural = substr($word, 0, strlen($word) - 1) . 'ies';
        } elseif (in_array($last, ['s', 'x', 'h'])) {
            $plural = $word . 'es';
        } else {
            $plural = $word . 's';
        }
        $tmp = preg_split('/(?=[A-Z])/', $plural, -1, PREG_SPLIT_NO_EMPTY);
        if (count($tmp) > 0) {
            $plural = implode(' ', $tmp);
        }
        return $plural;
    }

    public function generateHistoryTable($db_table)
    {
        $db_user = \yii::$app->db->username;
        $db_pass = \yii::$app->db->password;
        $db_param = '';
        //$db_table = 'absen_jam';
        $db_name = explode('dbname=', \yii::$app->db->dsn)[1];
        $ds = DIRECTORY_SEPARATOR;
        $gen_dir = \yii::getAlias('@app') . $ds . 'generator' . $ds;
        $tab = "\t";
        $template = <<<TEMPLATE
{$tab}`%NAMA_PK%` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
TEMPLATE;
        $desc = \yii::$app->db->createCommand("DESC {$db_table}")->queryAll();
        $sql = "DROP TABLE IF EXISTS `" . $db_table . "_history`;\n\n";
        $sql .= 'CREATE TABLE `' . $db_table . "_history` (\n";
        $pri = "id_" . $db_table . "_history";
        for ($i = 0; $i < count($desc); $i++) {
            $sql .= "\t`" . $desc[$i]['Field'] . '` ' . $desc[$i]['Type'];
            $sql .= ",\n";
        }
        $template = str_replace("%NAMA_PK%", $pri, $template);
        $sql .= $template;
        $sql .= "\n);";
        return \yii::$app->db->createCommand($sql)->execute();
    }
}