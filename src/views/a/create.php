<?php
$this->title = Yii::t('easyii/geo', 'Create geo');
?>
<?= $this->render('_menu') ?>
<div class="card">
	<?= $this->render('_form', ['model' => $model]) ?>
</div>