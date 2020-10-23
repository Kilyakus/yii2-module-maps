<?php
$this->title = $model->title;
?>
<?= $this->render('_menu') ?>

<div class="card">
	<?php if($this->context->module->settings['enablePhotos']) echo $this->render('_submenu', ['model' => $model]) ?>
	<?= $this->render('_form', ['model' => $model]) ?>
</div>