<?php

use yii\helpers\Url;
use yii\web\View;
use yii\helpers\Html;
use kartik\select2\Select2;

$this->title = 'Generator';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Url::base().'/css/generator.css');

$url = Url::to(['generator/diff']);
$preview_url = Url::to(['generator/preview']);
$js = <<<JS
$(document).ready(function(){

        $('#tombol_preview').click(function () {
            $('#mode').val('preview');
            $(this).closest('form').submit();
        });

        $('#tombol_generate').click(function () {
            $('#mode').val('generate');
            $(this).closest('form').submit();
        });

        $('.difflink').click(function(e){
            e.preventDefault();
            
            var modaltitle = $(this).data('title');
            var table = $(this).data('table');
            var extra = $(this).data('extra');
            var type = $(this).data('type');
            $('#modal-title').text(modaltitle);
            $('#ajax-loading').show();
            $('#ajax-content').html('');
            $('#modalnya').modal();
            $.get('$url', {
                table_name : table,
                type : type,
                extra : extra,
            }, function(data){

                $('#ajax-content').html(data);
                $('#ajax-loading').hide();
            });
        });

        $('.filelink').click(function(e){
            e.preventDefault();
            var _table_name = $(this).data('table');
            var _type = $(this).data('type');
            var _extra = $(this).data('extra');
            var _location = $(this).text();
            $('#modal-title').text(_location);
            $('#ajax-loading').show();
            $('#ajax-content').html('');
            $('#modalnya').modal();
            $.get('$preview_url', {
                table_name  : _table_name,
                type        : _type,
                extra       : _extra
            }, function(data){

                $('#ajax-content').html(data);
                $('#ajax-loading').hide();
            });
        });

        $('#checkall').click(function(){
            var checkall = $(this);
            console.log('tes');
            $('#tabelnya tbody input[type="checkbox"]:enabled').each(function(){
              if (checkall.is(':checked')){
                console.log('checked');
                $(this).prop('checked', true).closest('span').addClass('checked');
              }
              else {
                console.log('not checked');
                $(this).prop('checked', false).closest('span').removeClass('checked');
              }
            });
        });

        $('#cekcrud').click(function(){
            console.log('test');
           if ($(this).is(':checked')){
                console.log('dicentang');
                $('#cekhistory').removeAttr('disabled').closest('.checker').removeClass('disabled');
           }
           else {
                console.log('tidak dicentang');
                $('#cekhistory').attr('disabled', 'disabled').closest('.checker').addClass('disabled');
           }
        });

});
JS;
$this->registerJs($js, View::POS_END);

$ds = DIRECTORY_SEPARATOR;
$folder_model = \Yii::getAlias('@app') . $ds . 'models';
$folder_view = \Yii::getAlias('@app') . $ds . 'views';
$folder_controller = \Yii::getAlias('@app') . $ds . 'controllers';

$listmodel = scandir($folder_model);

$table_dropdown = [];
foreach ($tables as $v){
    $table_dropdown[$v['table_name']] = $v['table_name'];
}

$req = \Yii::$app->request;
?>

<!--<h1><?= Html::encode($this->title) ?></h1>-->

<div class="row-fluid" style="margin-top:20px">
    <div class="col-md-12">
        <form action="" method="post">
            <input type="hidden" name="mode" id="mode" value="">
            <div class="alert alert-warning">Pastikan terdapat kolom <strong>user_update</strong> bertipe <strong>VARCHAR(65)</strong> dan <strong>last_update</strong> bertipe <strong>DATETIME</strong> pada tabel</div>

            <div class="form-group">
                <label class="control-label">Table</label>
                
                <select class="form-control" name="table">
                    <?php
                    foreach ($tables as $v):
                        ?>
                        <option value="<?= $v['table_name'] ?>" <?= (isset($table)) ? (($v['table_name'] == $table) ? 'selected' : '') : '' ?>><?= $v['table_name'] ?></option>
                        <?php
                    endforeach;
                    ?>
                </select>
                <div style="margin:10px 0">
                    <input type="checkbox" name="cekcrud" id="cekcrud" <?php if (isset($cekcrud) && $cekcrud == 1): ?> checked <?php endif; ?>> CRUD
                </div>
                <div style="margin:10px 0">
                    <input type="checkbox" name="cekhistory" id="cekhistory" disabled <?php if (isset($cekhistory) && $cekhistory == 1): ?> checked <?php endif; ?>> History
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn green" id="tombol_preview"><span class="fa fa-list-ul"></span> Preview</button>
                <?php if (isset($_POST['mode']) && $_POST['mode'] == "preview"): ?>
                <button type="button" class="btn btn-danger" id="tombol_generate"><span class="fa fa-cog"></span> Generate</button>
                <?php endif; ?>
            </div>

            <?php if ($req->isPost && isset($_POST['mode'])): ?>
                <?php
                if ($_POST['mode'] == "preview"): ?>

                <div  style="margin-top:20px">
                    <table class="table table-bordered table-responsive" id="tabelnya">
                        <colgroup>
                            <col style="width:80%">
                            <col style="width:15%">
                            <col style="width:5%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>Code File</th>
                            <th>Action</th>
                            <th><input type="checkbox" id="checkall"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 0;
                        foreach ($filelist as $v):
                        ?>
                            <?php if ($_POST['mode'] == 'preview' && $v['operation'] == 'overwrite'): ?>
                            <tr class="warning">
                            <?php elseif ($_POST['mode'] == 'generate' && isset($v['status']) && $v['status'] == 'ok'): ?>
                            <tr style="background-color:rgba(255, 208, 0, 0.39);color:#333">
                             <?php else: ?>
                            <tr>
                            <?php endif; ?>
                                <td>
                                    <a href="#" class="filelink" data-table="<?= $table ?>" data-type="<?= $v['type'] ?>" data-extra="<?= isset($v['filename']) ? $v['filename'] : '' ?>"><?= $v['location'] ?></a>
                                    <?php if ($_POST['mode'] == 'preview' && $v['operation'] == 'overwrite'): ?><a href="#" data-extra="<?= isset($v['location']) ? $v['location'] : '' ?>" data-table="<?= $table ?>" data-type="<?= $v['type'] ?>" data-title="<?= $v['location'] ?>" class="difflink label label-info">Diff</a> <?php endif; ?>

                                </td>
                                <td><?= $v['operation'] ?></td>
                                <td>
                                    <input type="hidden" name="FileList[<?= $i ?>][location]" value="<?= isset($v['filename']) ? $v['filename'] : '' ?>">
                                    <input type="hidden" name="FileList[<?= $i ?>][real_location]" value="<?= isset($v['location']) ? $v['location'] : '' ?>">
                                    <input type="hidden" name="FileList[<?= $i ?>][type]" value="<?= $v['type'] ?>">
                                    <input type="hidden" name="FileList[<?= $i ?>][operation]" value="<?= $v['operation'] ?>">
                                    <?php if ($v['type'] == 'model' || $v['type'] == 'historymodel'): ?>
                                    <input type="hidden" name="FileList[<?= $i ?>][table]" value="<?= $v['table'] ?>">
                                    <?php endif; ?>
                                    <input type="checkbox" class="cekoverwrite" name="FileList[<?= $i ?>][overwrite]" <?php if ($v['operation'] === 'skip'): ?> disabled <?php endif; ?>>
                                </td>
                            </tr>
                        <?php
                        $i++;
                        endforeach;
                        ?>
                <?php elseif ($_POST['mode'] == "generate"): ?>
                
                <div style="margin-top:20px">

                <pre style="height:200px;overflow-y:scroll"><?php 
                foreach ($filelist as $v){
                    echo trim($v['real_location']).' : '.$v['status']."\n";
                }
                ?>
                </pre>
                <a href="<?= Url::to([strtolower($modelname).'/index']) ?>" target="_blank" class="btn btn-primary"><span class="fa fa-eye"></span> Click here to view the result</a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </form>

    </div>
</div>

<div class="modal fade bs-modal-lg" id="modalnya" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title" id="modal-title"></h4>
            </div>
            <div class="modal-body">
                <div id="ajax-content"></div>
                <div class="progress" id="ajax-loading" style="display:none">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                        <span class="sr-only"></span>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><span class="fa fa-times"></span> Close</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
