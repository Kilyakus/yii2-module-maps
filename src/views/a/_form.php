<?php
use bin\admin\helpers\Image;
use bin\admin\widgets\DateTimePicker;
use bin\admin\widgets\TagsInput;
use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use bin\admin\widgets\Redactor;
use bin\admin\widgets\SeoForm;

$module = $this->context->module->id;
?>
<?php $form = ActiveForm::begin([
    'enableAjaxValidation' => true,
    'options' => ['enctype' => 'multipart/form-data', 'class' => 'model-form']
]); ?>
<?= $form->field($model, 'title') ?>
<?php if($this->context->module->settings['enableThumb']) : ?>
    <?= $form->field($model, 'image')->widget(FileInput::classname(),['name' => 'image','attribute' => 'image','options' => ['multiple' => false],
        'pluginOptions' => [
            'deleteUrl' => Url::toRoute(['/admin/'.$module.'/a/clear-image', 'id' => $model->geo_id]),
            'showPreview' => true,
            'showCaption' => true,
            'showRemove' => true,
            'showUpload' => false,
            'initialCaption' => $model->image,
            'initialPreview' => $model->image ? [Html::img(Image::thumb($model->image, 240), [])] : [],
            'overwriteInitial' => true,
        ],
    ])->fileInput(); ?>
<?php endif; ?>
<?php if($this->context->module->settings['enableShort']) : ?>
    <?= $form->field($model, 'short')->textarea() ?>
<?php endif; ?>
<?= $form->field($model, 'text')->widget(Redactor::className(),[
    'options' => [
        'minHeight' => 200,
        'maxHeight' => 400,
        'imageUpload' => Url::to(['/admin/redactor/upload', 'dir' => 'geo']),
        'fileUpload' => Url::to(['/admin/redactor/upload', 'dir' => 'geo']),
        'plugins' => ['fullscreen']
    ]
]) ?>

<?= $form->field($model, 'time')->widget(DateTimePicker::className()); ?>

<?php if($this->context->module->settings['enableTags']) : ?>
    <?= $form->field($model, 'tagNames')->widget(TagsInput::className()) ?>
<?php endif; ?>

<?php if(IS_ROOT) : ?>
    <?= $form->field($model, 'slug') ?>
    <?= SeoForm::widget(['model' => $model]) ?>
<?php endif; ?>

<?= Html::submitButton(Yii::t('easyii', 'Save'), ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end(); ?>
