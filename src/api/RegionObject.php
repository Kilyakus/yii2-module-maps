<?php
namespace bin\admin\modules\geo\api;

use Yii;
use bin\admin\components\API;
use yii\helpers\Url;

class RegionObject extends \bin\admin\components\ApiObject
{
    private $_cities;

    public function getName(){
    	$attribute = 'name_' . Yii::$app->language;
        return LIVE_EDIT ? API::liveEdit($this->model->{$attribute}, $this->editLink) : $this->model->{$attribute};
    }

    public function getName_ru(){
        $attribute = 'name_ru';
        return LIVE_EDIT ? API::liveEdit($this->model->{$attribute}, $this->editLink) : $this->model->{$attribute};
    }

    public function getName_en(){
        $attribute = 'name_en';
        return LIVE_EDIT ? API::liveEdit($this->model->{$attribute}, $this->editLink) : $this->model->{$attribute};
    }
    
    public function  getEditLink(){
        return Url::to(['/admin/geo/a/edit/', 'id' => $this->id]);
    }

    public function getCountry(){
        return Geo::country($this->model->country_id);
    }

    public function getCities(){
        if(!$this->_cities){
            $this->_cities = [];

            foreach(Geo::sities(['where' => ['region_id' => $this->id]]) as $model){
                $this->_cities[] = new CityObject($model->model);
            }
        }
        return $this->_cities;
    }
}