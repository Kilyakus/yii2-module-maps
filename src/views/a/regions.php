<?php
use bin\admin\modules\geo\api\Geo as ApiGeo;
use bin\admin\modules\geo\models\Geo;
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = Yii::t('easyii/geo', 'Geo');

$module = $this->context->module->id;
?>

<?= $this->render('_menu') ?>
<div class="card">
    <?php if($data->count > 0) : ?>
        <table class="table table-xs table-hover">
            <thead>
                <tr>
                    <?php if(IS_ROOT) : ?>
                        <th width="50">#</th>
                    <?php endif; ?>
                    <th><?= Yii::t('easyii', 'Title') ?></th>
                    <th width="100" class="text-center"><?= Yii::t('easyii', 'Cities') ?></th>
                    <th width="120"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data->models as $item) : ?>
                    <tr data-id="<?= $item->primaryKey ?>">
                        <?php if(IS_ROOT) : ?>
                            <td>
                                <div class="row">
                                    <div class="col-sm-4 col-xs-4 hidden-lg hidden-md"><strong>#</strong></div>
                                    <div class="col-lg-12 col-md-12 col-sm-8 col-xs-8"><?= $item->primaryKey ?></div>
                                </div>
                            </td>
                        <?php endif; ?>
                        <td>
                            <div class="row">
                                <div class="col-sm-4 col-xs-4 hidden-lg hidden-md"><strong><?= Yii::t('easyii', 'Title') ?></strong></div>
                                <div class="col-lg-12 col-md-12 col-sm-8 col-xs-8"><?= $item->name_ru ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="row">
                                <div class="col-sm-4 col-xs-4 hidden-lg hidden-md"><strong><?= Yii::t('easyii', 'Cities') ?></strong></div>
                                <div class="col-lg-12 col-md-12 col-sm-8 col-xs-8">
                                    <a href="<?= Url::toRoute(['/admin/'.$module.'/a/cities/', 'id' => $item->primaryKey]) ?>" class="btn btn-sm btn-default btn-block"><?= count($item->cities) ?></a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= Url::to(['/'.$module.'/view/', 'id' => $item->primaryKey]) ?>" target="_blank" class="btn btn-default" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= Yii::t('easyii', 'View') ?>"><span class="glyphicon glyphicon-eye-open"></span></a>
                                <a href="<?= Url::to(['/admin/'.$module.'/a/edit', 'id' => $item->primaryKey]) ?>" class="btn btn-default" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= Yii::t('easyii', 'Edit') ?>"><span class="glyphicon glyphicon-pencil"></span></a>
                                <a href="<?= Url::to(['/admin/'.$module.'/a/delete', 'id' => $item->primaryKey]) ?>" class="btn btn-default confirm-delete" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= Yii::t('easyii', 'Delete item') ?>"><span class="glyphicon glyphicon-remove"></span></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= yii\widgets\LinkPager::widget([
            'pagination' => $data->pagination
        ]) ?>
    <?php else : ?>
        <p><?= Yii::t('easyii', 'No records found') ?></p>
    <?php endif; ?>
</div>