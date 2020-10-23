<?php
namespace bin\admin\modules\geo\api;

use Yii;
use bin\admin\components\API;
use yii\helpers\Url;

class CountryObject extends \bin\admin\components\ApiObject
{
    private $_regions;

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

    public function getCode(){
        return LIVE_EDIT ? API::liveEdit($this->model->code, $this->editLink) : $this->model->code;
    }

    public function  getEditLink(){
        return Url::to(['/admin/geo/a/edit/', 'id' => $this->id]);
    }

    public function getCities(){
        if(!$this->_regions){
            $this->_regions = [];

            foreach(Geo::regions(['where' => ['region_id' => $this->id]]) as $model){
                $this->_regions[] = new RegionObject($model->model);
            }
        }
        return $this->_regions;
    }
}