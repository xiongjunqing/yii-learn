<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model app\models\CountryModel */


$this->title = 'Test Yii framework';

?>

<div>
    <p>假如时光倒流我能做什么，让你没说的去想要的！</p>
</div>
<h1><?= Html::encode($this->title) ?></h1>

<p>Please fill out the following fields to login:</p>

<?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'code') ?>
    <?= $form->field($model, 'name') ?>
    <?= Html::submitButton('submit') ?>
<?php ActiveForm::end(); ?>



