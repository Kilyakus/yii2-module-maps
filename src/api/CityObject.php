<?php
namespace bin\admin\modules\geo\api;

use Yii;
use bin\admin\components\API;
use yii\helpers\Url;

class CityObject extends \bin\admin\components\ApiObject
{
	private $_streets;

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

    public function getCountry(){
        return Geo::country($this->model->country_id);
    }

    public function getRegion(){
        return Geo::region($this->model->region_id);
    }

    public function getStreets(){
        if(!$this->_streets){
            $this->_streets = [];

            foreach(Geo::streets(['where' => ['city_id' => $this->id]]) as $model){
                $this->_streets[] = new StreetObject($model->model);
            }
        }
        return $this->_streets;
    }
}