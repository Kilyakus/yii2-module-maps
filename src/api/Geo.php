<?php
namespace bin\admin\modules\geo\api;

use Yii;
use yii\data\ActiveDataProvider;
use yii\widgets\LinkPager;

use bin\admin\modules\geo\models\MapsCountry as Country;
use bin\admin\modules\geo\models\MapsRegion as Region;
use bin\admin\modules\geo\models\MapsCity as City;
use bin\admin\modules\geo\models\MapsStreet as Street;
use bin\admin\modules\geo\models\MapsStreetNumber as StreetNumber;

class Geo extends \bin\admin\components\API
{
    private $_adp_countries;
    private $_adp_regions;
    private $_adp_cities;
    private $_adp_streets;
    private $_adp_numbers;
    private $_countries;
    private $_regions;
    private $_cities;
    private $_streets;
    private $_numbers;
    private $_country = [];
    private $_region = [];
    private $_city = [];
    private $_street = [];
    private $_number = [];

    public function api_countries($options = [])
    {
        if(!$this->_countries){
            $this->_countries = [];

            $with = [];

            $query = Country::find()->with($with);

            if(!empty($options['where'])){
                $query->andFilterWhere($options['where']);
            }

            if(!empty($options['orderBy'])){
                $query->orderBy($options['orderBy']);
            } else {
                $query->orderBy(['id' => SORT_ASC]);
            }

            $this->_adp_countries = new ActiveDataProvider([
                'query' => $query,
                'pagination' => !empty($options['pagination']) ? $options['pagination'] : []
            ]);

            foreach($this->_adp_countries->models as $model){
                $this->_countries[] = new CountryObject($model);
            }
        }
        return $this->_countries;
    }

    public function api_country($id_name)
    {
        if(!isset($this->_country[$id_name])) {
            $this->_country[$id_name] = $this->findCountry($id_name);
        }
        return $this->_country[$id_name];
    }

    private function findCountry($id_name)
    {
    	if($id_name){
	        $geo = Country::find()->where(['or', ['id' => $id_name], ['or',['like','name_ru',$id_name],['like','name_en',$id_name]]])->one();

	        if($geo){
	            return new CountryObject($geo);
	        }
	    }
    }

    public function api_regions($options = [])
    {
        if(!$this->_regions){
            $this->_regions = [];

            $with = [];

            $query = Region::find()->with($with);

            if(!empty($options['where'])){
                $query->andFilterWhere($options['where']);
            }

            if(!empty($options['orderBy'])){
                $query->orderBy($options['orderBy']);
            } else {
                $query->orderBy(['id' => SORT_ASC]);
            }

            $this->_adp_regions = new ActiveDataProvider([
                'query' => $query,
                'pagination' => !empty($options['pagination']) ? $options['pagination'] : []
            ]);

            foreach($this->_adp_regions->models as $model){
                $this->_regions[] = new RegionObject($model);
            }
        }
        return $this->_regions;
    }

    public function api_region($id_name)
    {
        if(!isset($this->_region[$id_name])) {
            $this->_region[$id_name] = $this->findRegion($id_name);
        }
        return $this->_region[$id_name];
    }

    private function findRegion($id_name)
    {
    	if($id_name){
	        $geo = Region::find()->where(['or', ['id' => $id_name], ['or',['like','name_ru',$id_name],['like','name_en',$id_name]]])->one();

	        if($geo){
	            return new RegionObject($geo);
	        }
	    }
    }

    public function api_cities($options = [])
    {
        if(!$this->_cities){
            $this->_cities = [];

            $with = [];

            $query = City::find()->with($with);

            if(!empty($options['where'])){
                $query->andFilterWhere($options['where']);
            }

            if(!empty($options['orderBy'])){
                $query->orderBy($options['orderBy']);
            } else {
                $query->orderBy(['id' => SORT_ASC]);
            }

            $this->_adp_cities = new ActiveDataProvider([
                'query' => $query,
                'pagination' => !empty($options['pagination']) ? $options['pagination'] : []
            ]);

            foreach($this->_adp_cities->models as $model){
                $this->_cities[] = new CityObject($model);
            }
        }
        return $this->_cities;
    }

    public function api_city($id_name)
    {
        if(!isset($this->_city[$id_name])) {
            $this->_city[$id_name] = $this->findCity($id_name);
        }
        return $this->_city[$id_name];
    }

    private function findCity($id_name)
    {
    	if($id_name){
	        $geo = City::find()->where(['or', ['id' => $id_name], ['or',['like','name_ru',$id_name],['like','name_en',$id_name]]])->one();

	        if($geo){
	            return new CityObject($geo);
	        }
	    }
    }

    public function api_cityPagination()
    {
        return $this->_adp_cities ? $this->_adp_cities->pagination : null;
    }

    public function api_streets($options = [])
    {
        if(!$this->_streets){
            $this->_streets = [];

            $with = [];

            $query = Street::find()->with($with);

            if(!empty($options['where'])){
                $query->andFilterWhere($options['where']);
            }

            if(!empty($options['orderBy'])){
                $query->orderBy($options['orderBy']);
            } else {
                $query->orderBy(['id' => SORT_ASC]);
            }

            $this->_adp_streets = new ActiveDataProvider([
                'query' => $query,
                'pagination' => !empty($options['pagination']) ? $options['pagination'] : []
            ]);

            foreach($this->_adp_streets->models as $model){
                $this->_streets[] = new StreetObject($model);
            }
        }
        return $this->_streets;
    }

    public function api_street($id_name)
    {
        if(!isset($this->_street[$id_name])) {
            $this->_street[$id_name] = $this->findStreet($id_name);
        }
        return $this->_street[$id_name];
    }

    private function findStreet($id_name)
    {
    	if($id_name){
	        $geo = Street::find()->where(['id' => $id_name])->one();

	        if($geo){
	            return new StreetObject($geo);
	        }
	    }
    }

    public function api_numbers($options = [])
    {
        if(!$this->_numbers){
            $this->_numbers = [];

            $with = [];

            $query = StreetNumber::find()->with($with);

            if(!empty($options['where'])){
                $query->andFilterWhere($options['where']);
            }

            if(!empty($options['orderBy'])){
                $query->orderBy($options['orderBy']);
            } else {
                $query->orderBy(['id' => SORT_ASC]);
            }

            $this->_adp_numbers = new ActiveDataProvider([
                'query' => $query,
                'pagination' => !empty($options['pagination']) ? $options['pagination'] : []
            ]);

            foreach($this->_adp_numbers->models as $model){
                $this->_numbers[] = new StreetNumberObject($model);
            }
        }
        return $this->_numbers;
    }

    public function api_number($id_name)
    {
        if(!isset($this->_number[$id_name])) {
            $this->_number[$id_name] = $this->findNumber($id_name);
        }
        return $this->_number[$id_name];
    }

    private function findNumber($id_name)
    {
    	if($id_name){
	        $geo = Street::find()->where(['or', ['id' => $id_name], ['or',['like','name_ru',$id_name],['like','name_en',$id_name]]])->one();

	        if($geo){
	            return new StreetNumberObject($geo);
	        }
	    }
    }

    public function api_address($data)
    {
        $country = $this->findCountry($data->country_id)->name;
        $region = $this->findRegion($data->region_id)->name;
        $city = $this->findCity($data->city_id)->name;
        $street = $this->findStreet($data->street_id)->name;
        $number = $this->findNumber($data->street_number_id)->name;
        $address = '';
        if($country){
            $address .= $country;
        }
        if($region && $country){
            $address .= ', '.$region;
        }
        if($city && $country){
            $address .= ', '.$city;
        }
        if($street && $city){
            $address .= ', '.$street;
        }
        if($street_number && $street){
            $address .= ', '.$street_number;
        }
        
        return $address;
    }

    public function api_pagination()
    {
        return $this->_adp ? $this->_adp->pagination : null;
    }

    public function api_pages()
    {
        return $this->_adp ? LinkPager::widget(['pagination' => $this->_adp->pagination]) : '';
    }
}