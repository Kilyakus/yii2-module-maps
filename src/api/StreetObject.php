<?php
namespace bin\admin\modules\geo\api;

use Yii;
use bin\admin\components\API;
use yii\helpers\Url;

class StreetObject extends \bin\admin\components\ApiObject
{
    public function getName(){
        $attribute = 'name_' . Yii::$app->language;
        return LIVE_EDIT ? API::liveEdit($this->model->{$attribute}, $this->editLink) : $this->model->{$attribute};
    }

    public function getName_ru(){
        return $this->getName();
    }

    public function getName_en(){
        return $this->getName();
    }
    
    public function  getEditLink(){
        return Url::to(['/admin/geo/a/edit/', 'id' => $this->id]);
    }
}