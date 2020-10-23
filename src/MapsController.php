<?php
namespace app\controllers;

use Yii;
use yii\db\Query;
use yii\helpers\Url;
use yii\helpers\Html;
use bin\admin\helpers\Image;
use bin\admin\helpers\Request;
use bin\admin\helpers\IpHelper;
use bin\admin\components\API;
use bin\admin\components\AppController as Controller;
use bin\admin\modules\geo\api\Geo;
use bin\admin\models\MapsCountry;
use bin\admin\models\MapsRegion;
use bin\admin\models\MapsCity;
use bin\admin\models\MapsCityAssign;
use bin\admin\models\MapsStreet;
use bin\admin\models\MapsStreetNumber;
use bin\admin\models\MapsStreetAssign;
use bin\admin\widgets\Maps;
use bin\admin\models\Setting;
use bin\admin\modules\catalog\models\Item;

class MapsController extends Controller
{
    public $rootActions = 'all';

    public function getLocate($latitude = null, $longitude = null)
    {
        if(!$latitude && !$longitude){
            $latitude = IpHelper::getClient("geoplugin_latitude");
            $longitude = IpHelper::getClient("geoplugin_longitude");
        }
        $latitude = substr($latitude, 0, 4);
        $longitude = substr($longitude, 0, 4);

        $position = Geo::cities(['where' => ['or',
            ['and',
                ['like','latitude', $latitude],
                ['like','longitude', $longitude]
            ],
            ['and',
                ['<=','latitude', ($latitude+0.02)],
                ['>=','latitude', ($latitude-0.02)],
                ['<=','longitude', ($longitude+0.02)],
                ['>=','longitude', ($longitude-0.02)],
            ]
        ]]);

        if(!$position){
            $data = $latitude.','.$longitude;

            $position = self::createMaps($data,null,null,true);
            return self::getLocate($position[0]->latitude,$position[0]->longitude);
        }

        return $position;
    }

    public function genAddress($data){

        return Geo::address($data);
    }

    public function actionGetCountry($id = null, $latitude = null, $longitude = null)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if($latitude != null && $longitude != null){

            return Geo::countries(['where' => ['or',
                ['and',
                    ['country_id' => $id],
                    ['not like','latitude', $latitude],
                    ['not like','longitude', $longitude]
                ],
                ['and',
                    ['country_id' => $id],
                    ['<=','latitude', ($latitude+1)],
                    ['>=','latitude', ($latitude-1)],
                    ['<=','longitude', ($longitude+1)],
                    ['>=','longitude', ($longitude-1)],
                ]
            ]])[0]->model->country_id;
        }else{
            return $this->getCountryList($id);
        }

    }

    public function query($item){
        return ['or',['like', 'name_ru', $item],['like','name_en', $item],['name_ru' => $item],['name_en' => $item]];
    }

    public function getUrl($latitude = null, $longitude = null, $name = null){
        if($latitude != null && $longitude != null){
            $getUrl = $latitude . ',' . $longitude;
        }else{
            $getUrl = $name;
        }
        return $getUrl;
    }

    public function actionCreateMaps($data = null, $latitude = null, $longitude = null, $isAjax = false)
    {
        if($isAjax == true){

            $response = self::createMaps($data);

            return $response;

        }

        return self::createMaps($data, $latitude, $longitude, false);

    }

    public function createMaps($data = null, $latitude = null, $longitude = null, $stopResponse = false, $load = null)
    {
        $response = [];
        if(!$data && $latitude && $longitude){
            $data = $latitude . ',' . $longitude;
        }

        $data = self::getGeocode($data,'ru')['address_components'];

        $country;
        $region;
        $city;
        $street;
        $location;

        if($data){

            $country = self::genCountry($data,$latitude,$longitude);

            if($country['location']){
                $location = $country['location'];
            }

            if($country['id']){

                $region = self::genRegion($data,$latitude,$longitude,$country);

                if($region['location']){
                    $location = $region['location'];
                }

                $city = self::genCity($data,$latitude,$longitude,$country,$region);

                if($city['location']){
                    $location = $city['location'];
                }

                if($city['id']){

                    $street = self::genRoute($data,$latitude,$longitude,$country,$region,$city);

                    if($street['location']){
                        $location = $street['location'];
                    }

                    if($street['id']){
                        foreach ($data as $item) {

                            if(in_array('street_number',$item['types']) && $country['name'] != null && $city['name'] != null && $street['id'] != null){

                                if($region['name']){
                                    $region_name = $region['name'] . ', ';
                                    $q = ['region_id' => $region['id']];
                                }else{
                                    $region_name = '';
                                    $q = ['region_id' => null];
                                }
                                $name = $country['name'] . ', ' . $region_name . $city['name'] . ', ' . $street['name'] . ', ' . $item['long_name'];

                                $getUrl = self::getUrl($latitude, $longitude, $name);

                                $location = self::getGeocode($name,'ru')['geometry']['location'];

                                $mc = MapsStreetNumber::find()->where(['and',self::query($item['long_name']),['street_id' => $street['id']]]);

                                if(count($mc->all()) == 0){

                                    $ru = self::getGeocode($getUrl,'ru')['address_components'];
                                    $en = self::getGeocode($getUrl,'en')['address_components'];

                                    foreach ($ru as $component) {

                                        if(in_array('street_number',$component['types'])){
                                            $name_ru = $component['long_name'];
                                        }elseif(in_array('route',$component['types'])){

                                            $number_id = MapsStreet::find()->where(self::query($component['short_name']))->one()->id;

                                        }

                                    }

                                    foreach ($en as $component) {

                                        if(in_array('street_number',$component['types'])){
                                            $name_en = $component['long_name'];
                                        }

                                    }

                                    if($name_ru != null && $name_en != null){

                                        if(!MapsStreetNumber::find()->where(['or',['name_ru' => $name_ru],['name_en' => $name_en]])->all()){

                                            $model = new MapsStreetNumber;
                                            $model->street_id = $number_id;
                                            $model->name_ru = $name_ru;
                                            $model->name_en = $name_en;
                                            $model->latitude = self::getGeometry($getUrl)['lat'];
                                            $model->longitude = self::getGeometry($getUrl)['lng'];
                                            $model->save();
                                        }

                                        $number_id = $model->primaryKey;
                                    }

                                }else{
                                    $number_id = $mc->one()->id;
                                }
                            }
                        }
                    }
                }
            }
            if($stopResponse == false){
                
                $response = array(
                    'country' => MapsCountry::findOne($country['id']),
                    'region' => MapsRegion::findOne($region['id']),
                    'locality' => MapsCity::findOne($city['id']), 
                    'route' => MapsStreet::findOne($street['id']), 
                    'street_number' => MapsStreetNumber::findOne($number_id),
                    'geometry' => $location
                );

                \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

                return array_merge($response);

            }else{

                return MapsCity::find()->where(['id' => $city['id']])->all();

            }

        }

    }


    public function genCountry($data,$latitude,$longitude)
    {
        $response = [];

        foreach ($data as $item) {

            if(in_array('country',$item['types'])){

                $name = $item['long_name'];

                $getUrl = self::getUrl($latitude, $longitude, $name);

                $location = self::getGeocode($name,'ru')['geometry']['location'];
                
                $mc = Geo::countries(['where' => ['code' => $item['short_name']]]);

                if(count($mc) == 0){

                    $ru = self::getGeocode($getUrl,'ru')['address_components'];
                    $en = self::getGeocode($getUrl,'en')['address_components'];

                    if($ru && $en){
                        foreach ($ru as $component) {

                            if(in_array('country',$component['types'])){
                                $name_ru = $component['long_name'];
                            }

                        }

                        foreach ($en as $component) {

                            if(in_array('country',$component['types'])){
                                $code = $component['short_name'];
                                $name_en = $component['long_name'];
                            }

                        }
                    }

                    if($name_ru != null || $name_en != null){

                        $model = new MapsCountry;
                        $model->code = $code;
                        $model->name_ru = $name_ru;
                        $model->name_en = $name_en;
                        $model->save();

                        $country_id = $model->primaryKey;
                        $country_name = $name_ru;
                    }                        
                }else{
                    $country_id = $mc[0]->id;
                    $country_name = $mc[0]->name;
                }

                $response['id'] = $country_id;
                $response['name'] = $country_name;
                $response['location'] = $location;

                return $response;
            }
        }
    }

    public function genRegion($data,$latitude,$longitude,$country){

        $response = [];

        $type = 'administrative_area_level_1';

        // $stop = false;

        // foreach ($data as $level) {
        //     if(in_array($type,$level['types']) && $country['id'] != null){
        //         $stop = true;
        //     }else{
        //         if($stop == true){
        //             $stop = true;
        //         }
        //     }
        // }

        // if($stop != true){
        //     $type = 'administrative_area_level_2';
        // }

        foreach ($data as $item) {

            if(in_array($type,$item['types']) && $country['id'] != null){

                $name = $data['country']['long_name'] . ', ' . $item['long_name'];

                $getUrl = self::getUrl($latitude, $longitude, $name);

                $location = self::getGeocode($name,'ru')['geometry']['location'];

                $mc = Geo::regions(['where' => ['and',self::query($item['long_name']),['country_id' => $country['id']]]]);

                if(count($mc) == 0){

                    $ru = self::getGeocode($getUrl,'ru')['address_components'];
                    $en = self::getGeocode($getUrl,'en')['address_components'];

                    if($ru && $en){
                        foreach ($ru as $component) {

                            if(in_array($type,$component['types'])){
                                $name_ru = $component['long_name'];
                            }

                        }

                        foreach ($en as $component) {

                            if(in_array($type,$component['types'])){
                                $name_en = $component['long_name'];
                            }

                        }
                    }

                    $ru = Geo::regions(['where' => ['and',self::query($name_ru),['country_id' => $country['id']]]]);
                    $en = Geo::regions(['where' => ['and',self::query($name_en),['country_id' => $country['id']]]]);
                    
                    if(count($en) && !count($ru)){
                        // $model = MapsRegion::findOne($en[0]->id);
                        $model = $en[0]->model;
                        $model->name_ru = $name_ru;
                        $model->save();

                        $response['id'] = $model->primaryKey;
                        $response['name'] = $name_ru;
                        $response['location'] = $location;

                        return $response;
                    }else{

                        if($name_ru != null || $name_en != null){

                            $model = new MapsRegion;
                            $model->country_id = $country['id'];
                            $model->name_ru = $name_ru;
                            $model->name_en = $name_en;
                            $model->type = $item['types'][0];
                            $model->save();

                            $region_id = $model->primaryKey;
                            $region_name = $name_ru;
                        }  
                    }                    
                }else{
                    $region_id = $mc[0]->id;
                    $region_name = $mc[0]->name;
                }

                $response['id'] = $region_id;
                $response['name'] = $region_name;
                $response['location'] = $location;

                return $response;
            }
        }
    }

    public function genCity($data,$latitude,$longitude,$country,$region = null){

        $response = [];

        $type = 'locality';

        // $stop = false;

        // foreach ($data as $level) {
        //     if(in_array($type,$level['types']) && $country['id'] != null){
        //         $stop = true;
        //     }else{
        //         if($stop == true){
        //             $stop = true;
        //         }
        //     }
        // }

        // if($stop != true){
        //     $type = 'establishment';
        // }

        foreach ($data as $item) {

            if(in_array($type,$item['types']) && $country['id'] != null){

                if($data['region']['long_name']){
                    $region_name = $data['region']['long_name'] . ', ';
                    $q = ['region_id' => $region['id']];
                }else{
                    $region_name = '';
                    $q = ['region_id' => null];
                }

                $name = $data['country']['long_name'] . ', ' . $region_name . $item['long_name'];

                $getUrl = self::getUrl($latitude, $longitude, $name);

                $location = self::getGeocode($getUrl,'ru')['geometry']['location'];

                if(!$location['address_components']){

                    $name = $region_name . $item['long_name'];

                    $getUrl = self::getUrl($latitude, $longitude, $name);

                    $location = self::getGeocode($getUrl,'ru')['geometry']['location'];

                    if(!$location['address_components']){

                        $name = $data['country']['long_name'] . ', ' . $item['long_name'];

                        $getUrl = self::getUrl($latitude, $longitude, $name);

                        $location = self::getGeocode($getUrl,'ru')['geometry']['location'];

                        if(!$location['address_components']){

                            $name = $item['long_name'];

                            $getUrl = self::getUrl($latitude, $longitude, $name);

                            $location = self::getGeocode($getUrl,'ru')['geometry']['location'];

                        }

                    }

                }

                $mc = Geo::cities(['where' => [
                    'and',
                        self::query($item['long_name']),
                        ['country_id' => $country['id']],
                        ['like','latitude',substr($latitude, 0, 5)],
                        ['like','longitude',substr($longitude, 0, 5)],
                    ]
                ]);

                if(count($mc) == 0){

                    if(substr($latitude, 0, 1) != '-'){
                        $latPlus = ($latitude+0.9);
                    }else{
                        $latPlus = ($latitude-0.9);
                    }

                    if(substr($latitude, 0, 1) != '-'){
                        $latMinus = ($latitude-0.9);
                    }else{
                        $latMinus = ($latitude+0.9);
                    }

                    if(substr($longitude, 0, 1) != '-'){
                        $lngPlus = ($longitude+0.9);
                    }else{
                        $lngPlus = ($longitude-0.9);
                    }

                    if(substr($longitude, 0, 1) != '-'){
                        $lngMinus = ($longitude-0.9);
                    }else{
                        $lngMinus = ($longitude+0.9);
                    }

                    // $mc = MapsCity::find()->where(
                    //     ['and',
                    //         self::query($item['long_name']),
                    //         ['country_id' => $country['id']],
                    //         $q,
                    //         ['and',
                    //             ['<=','latitude', $latPlus],
                    //             ['>=','latitude', $latMinus],
                    //             ['<=','longitude', $lngPlus],
                    //             ['>=','longitude', $lngMinus],
                    //         ]
                    //     ]
                    // );

                    $mc = Geo::cities(['where' => [
                        'and',
                            self::query($item['long_name']),
                            ['country_id' => $country['id']],
                            $q,
                            ['and',
                                ['<=','latitude', $latPlus],
                                ['>=','latitude', $latMinus],
                                ['<=','longitude', $lngPlus],
                                ['>=','longitude', $lngMinus],
                            ]
                        ]
                    ]);
                }


                if(count($mc) == 0){

                    $ru = self::getGeocode($getUrl,'ru')['address_components'];
                    $en = self::getGeocode($getUrl,'en')['address_components'];

                    if($ru && $en){
                        foreach ($ru as $component) {

                            if(in_array($type,$component['types'])){
                                $name_ru = $component['long_name'];
                            }

                        }

                        foreach ($en as $component) {

                            if(in_array($type,$component['types'])){ //establishment
                                $name_en = $component['long_name'];
                            }

                        }
                    }

                    if($name_ru != null && $name_en != null){

                        $ru = Geo::cities(['where' => ['and',self::query($name_ru),['country_id' => $country['id']],$q]]);
                        $en = Geo::cities(['where' => ['and',self::query($name_en),['country_id' => $country['id']],$q]]);

                        if(count($en) && !count($ru)){
                            $model = MapsCity::findOne($en[0]->id);
                            $model->name_ru = $name_ru;
                            $model->save();

                            $response['id'] = $model->primaryKey;
                            $response['name'] = $name_ru;
                            $response['location'] = $location;

                            return $response;

                        }else{

                            if($name_ru != null && $name_en != null){

                                if(!Geo::cities(['where' => ['and',['country_id' => $country['id']],['or',['name_ru' => $name_ru],['name_en' => $name_en]],$q]])){

                                    $geometry = self::getGeometry($getUrl);

                                    $model = new MapsCity;
                                    $model->country_id = $country['id'];
                                    $model->region_id = $region['id'];
                                    $model->postal_code = $postal_code;
                                    $model->name_ru = $name_ru;
                                    $model->name_en = $name_en;
                                    $model->type = $item['types'][0];
                                    $model->latitude = $geometry['lat'];
                                    $model->longitude = $geometry['lng'];
                                    $model->save();
                                    
                                }
                                $city_id = $model->primaryKey;
                                $city_name = $name_ru;
                            }
                        }
                    }

                }else{
                    if(!$mc[0]->model->type){
                        $model = $mc[0]->model;
                        $model->type = $item['types'][0];
                        $model->save();
                    }
                    if(!$mc[0]->region->model->id){
                        $model = $mc[0]->model;
                        $model->region_id = $region['id'];
                        $model->save();
                    }
                    $city_id = $mc[0]->id;
                    $city_name = $mc[0]->name_ru;
                }

                $response['id'] = $city_id;
                $response['name'] = $city_name;
                $response['location'] = $location;

                return $response;

            }
        }
    }

    public function genRoute($data,$latitude,$longitude,$country,$region = null,$city){

        $response = [];

        foreach ($data as $item) {

            if(in_array('route',$item['types']) && $country['id'] != null && $city['id'] != null){

                if($region['name']){
                    $region_name = $region['name'] . ', ';
                    $q = ['region_id' => $region['id']];
                }else{
                    $region_name = '';
                    $q = ['region_id' => null];
                }
                $name = $country['name'] . ', ' . $region_name . $city['name'] . ', ' . $item['long_name'];

                $getUrl = self::getUrl($latitude, $longitude, $name);

                $location = self::getGeocode($name,'ru')['geometry']['location'];

                if($item['short_name'] != null){
                    $name = $item['short_name'];
                }else{
                    $name = $item['long_name'];
                }

                $mc = MapsStreet::find()->where(['and',self::query($name),['city_id' => $city['id']]]);

                if(count($mc->all()) == 0){

                    $ru = self::getGeocode($getUrl,'ru')['address_components'];
                    $en = self::getGeocode($getUrl,'en')['address_components'];

                    if($ru && $en){
                        foreach ($ru as $component) {

                            if(in_array('route',$component['types'])){
                                $name_ru = $component['short_name'];
                            }

                        }

                        foreach ($en as $component) {

                            if(in_array('route',$component['types'])){
                                $name_en = $component['short_name'];
                            }

                        }
                    }

                    if($name_ru != null && $name_en != null){
                        $ru = MapsStreet::find()->where(['and',self::query($name_ru),['city_id' => $city['id']]])->all();
                        $en = MapsStreet::find()->where(['and',self::query($name_en),['city_id' => $city['id']]])->all();

                        if(count($en) && !count($ru)){
                            $model = MapsStreet::findOne($en[0]->id);
                            $model->name_ru = $name_ru;
                            $model->save();

                            $response['id'] = $model->primaryKey;
                            $response['name'] = $name_ru;
                            $response['location'] = $location;

                            return $response;

                        }else{

                            if($name_ru != null && $name_en != null){

                                if(!MapsStreet::find()->where(['or',['name_ru' => $name_ru],['name_en' => $name_en]])->all()){

                                    $model = new MapsStreet;
                                    $model->city_id = $city['id'];
                                    $model->name_ru = $name_ru;
                                    $model->name_en = $name_en;
                                    $model->save();
                                }

                                $street_id = $model->primaryKey;
                                $street_name = $name_ru;
                            }
                        }
                    }

                }else{

                    $street_id = $mc->one()->id;
                    $street_name = $mc->one()->name_ru;

                }

                $response['id'] = $street_id;
                $response['name'] = $street_name;
                $response['location'] = $location;

                return $response;
            }
        }
    }

    public function getGeocode($data, $lang = null, $test = false)
    {
        // $url = 'https://maps.google.com/maps/api/geocode/json?key=' . Setting::get('maps_api_key') . '&address=' . $data . '&language=';
        // var_dump(Request::json($url . 'ru'));die;

        $data = urldecode($data);

        if($test == true){
            echo "<h3>getGeocode: data</h3>";
            var_dump($data);
            echo "<hr>";
        }

        $osm = self::osmAddress(str_replace('&latlng=', '', $data), $lang, $test);

        $relation = self::osmRelation($osm, $lang);

        $query = implode(' ',[
            $relation['country']['long_name'],
            $relation['region']['long_name'],
            $relation['locality']['long_name'],
        ]);

        $osm = self::osmAddress($query, $lang);

        if($test == true){
            echo "<h3>getGeocode: osm</h3>";
            var_dump($osm);
            echo "<hr>";
        }

        $data = [];

        $data['results'][0] = ['address_components' => $relation];

        $data['results'][0]['geometry']['location'] = [
            'lat' => $osm->lat,
            'lng' => $osm->lon,
        ];

        return $data['results'][0];
    }

    public function osmAddress($data,$lang = 'ru', $test = false)
    {
        $opts = ['http' => ['header'=>"User-Agent: *"]];

        $url = self::buildUrl('https://nominatim.openstreetmap.org/search/' . str_replace(' ', '%20', $data), ['format' => 'json', 'addressdetails' => 1, 'limit' => 1, 'accept-language' => $lang]);

        if($test == true){
            echo "<h3>osmAddress: url</h3>";
            var_dump($url);
            echo "<hr>";
        }

        $context = stream_context_create($opts);

        $_url = @file_get_contents($url, false, $context);
        $_url = (trim($_url)!='') ? $_url : null;

        if(!$_url){
            $url = self::buildUrl('https://nominatim.openstreetmap.org/search/' . translit($data), ['format' => 'json', 'addressdetails' => 1, 'limit' => 1, 'accept-language' => $lang]);
            $_url = @file_get_contents($url, false, $context);
        }

        $json = json_decode($_url)[0];

        if($test == true){
            echo "<h3>osmAddress: json</h3>";
            var_dump($json);
            echo "<hr>";
        }

        return $json;
    }

    function translit($s) {
        $s = (string) $s; // преобразуем в строковое значение
        $s = strip_tags($s); // убираем HTML-теги
        $s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
        $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
        $s = trim($s); // убираем пробелы в начале и конце строки
        $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
        $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
        $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
        $s = str_replace(" ", "%20", $s); // заменяем пробелы
        return $s; // возвращаем результат
    }

    public function buildUrl($url, $params = array())
    {
        return $url.'?'.http_build_query($params);
    }


    public function osmRelation($osm,$lang = 'ru')
    {
        $object = [];
        if($osm->address){

            foreach ($osm->address as $type => $address) {
                
                if($type == 'city'){
                    $osm = self::osmAddress(str_replace(' ', '%20', $address),$lang);
                    $object['locality']['types'] = ['locality'];
                    $object['locality']['long_name'] = $osm->address->city ? $osm->address->city : $osm->address->county;
                }

                if($type == 'state'){
                    $osm = self::osmAddress(str_replace(' ', '%20', $address),$lang);
                    $object['region']['types'] = ['administrative_area_level_1'];
                    // $object['administrative_area_level_1']['long_name'] = self::osmGenName($osm,$lang);
                    $object['region']['long_name'] = $osm->address->state;
                }

                if($type == 'country'){
                    $osm = self::osmAddress(str_replace(' ', '%20', $address),$lang);
                    $object['country']['types'] = ['country'];
                    $object['country']['long_name'] = $osm->address->country;
                }

                if($type == 'country_code'){
                    $object['country']['short_name'] = strtoupper($address);
                }

            }

        }

        return $object;
    }

    public function osmGenName($osm, $lang = 'ru')
    {
        $relation = 'https://www.openstreetmap.org/api/0.6/relation/' . $osm->osm_id;

        $xml = self::xml2array($relation);

        $items = $xml ? $xml['osm']['relation']['tag'] : [];

        if(!$xml){

            $relation = 'https://www.openstreetmap.org/api/0.6/node/' . $osm->osm_id;

            $xml = self::xml2array($relation);

            $items = $xml ? $xml['osm']['node']['tag'] : [];

        }

        foreach ($items as $attributes) {

                    if($lang == 'en'){
                        var_dump($items);die;
                    }
            $k = $attributes['k'];
            $v = $attributes['v'];

            if($k == 'name:'.$lang){

                return $v;

            }

        }

        foreach ($items as $attributes) {

            $k = $attributes['k'];
            $v = $attributes['v'];

            if($k == 'name'){

                return $v;

            }

        }
    }


    public function getGeometry($data){
        return self::getGeocode($data)['geometry']['location'];
    }


    public function getCountryList($id = null)
    {
        if($id){

            return MapsCountry::find()->where(['id' => $id])->one();

        }else{

            $countries = $key = $val = array();

            $country = Geo::countries();

            foreach($country as $each) {
                array_push($key, $each->id);
                array_push($val, $each->name);
            }

            for ($i = 0; $i<count($key); $i++) {
                $countries[$key[$i]] = $val[$i];
            }

            return $countries;
        }        
    }

    public function actionCountryList($q = null, $id = null) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new Query;
            $query->select('id, name_en, name_ru AS text')
                ->from('maps_country')
                ->where(['or',['like', 'name_ru', $q],['like', 'name_en', $q]])
                ->limit(20);
            $command = $query->createCommand();
            $data = $command->queryAll();
            if(!count($data)){
                $response = self::actionCreateMaps($q);
                return $this->actionCountryList($response['country']->name_ru,null);
            }
            $out['results'] = array_values($data);
        }
        elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' => MapsCountry::find($id)->name];
        }
        return $out;
    }

    public function getRegionList($id = null)
    {
        if($id){

            return MapsRegion::find()->where(['id' => $id])->one();

        }else{

            $regions = $key = $val = array();

            $region = Geo::regions();

            foreach($region as $each) {
                array_push($key, $each->id);
                array_push($val, $each->name);
            }

            for ($i = 0; $i<count($key); $i++) {
                $regions[$key[$i]] = $val[$i];
            }

            return $regions;
        }        
    }

    // public function actionRegionList($q = null, $id = null) {
    //     \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    //     $out = ['results' => ['id' => '', 'text' => '']];
    //     if (!is_null($q)) {
    //         $query = new Query;
    //         $query->select('id, name_en, name_ru AS text')
    //             ->from('maps_region')
    //             ->where(['or',['like', 'name_ru', $q],['like', 'name_en', $q]])
    //             ->limit(20);
    //         $command = $query->createCommand();
    //         $data = $command->queryAll();
    //         $out['results'] = array_values($data);
    //     }
    //     elseif ($id > 0) {
    //         $out['results'] = ['id' => $id, 'text' => MapsRegion::find($id)->name];
    //     }
    //     return $out;
    // }

    public function actionRegionList($q = null, $id = null) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => ''],'parents' => []];
        if (!is_null($q)) {
            $query = new Query;
            $query = $query->select('id, country_id, name_' .Yii::$app->language. ' AS text')->from('maps_region');
            if($id != null){
                $query->where(['and',['or',['like', 'name_ru', $q], ['like', 'name_en', $q]],['country_id' => $id]]);
            }else{
                $query->where(['or',['like', 'name_ru', $q], ['like', 'name_en', $q]]);
            }
            $query = $query->createCommand()->queryAll();
            if(!count($query)){
                $attribute = 'name_'.Yii::$app->language;
                $response = self::actionCreateMaps($q);
                return $this->actionRegionList($response['region']->{$attribute},null);
            }
            $out['results'] = array_values($query);
            foreach ($query as $key => $result) {
                $data = (object)[];
                $data->country_id = $result['country_id'];
                $data->region_id = $result['id'];

                $out['results'][$key]['text'] = Geo::address($data);
            }
        }
        return $out;
    }

    public function getCityList($id = null)
    {
        $cities = $key = $val = array();

        $city = Geo::cities();

        if($id){
            $mapIds = [];
            $assign = MapsCityAssign::find()->where(['item_id' => $id])->all();
            foreach ($assign as $map) {
                $mapIds[] = $map->map_id;
            }
            $assign = Geo::cities(['where' => ['id' => array_values($mapIds)]]);
            if(count($assign)){
                $city = $assign;
            }else{
                $city = self::getLocate();
            }
        }else{
            $city = self::getLocate();
        }

        foreach($city as $each) {
            array_push($key, $each->id);
            array_push($val, $each->name);
        }

        for ($i = 0; $i<count($key); $i++) {
            $cities[$key[$i]] = $val[$i];
        }

        return $cities;
    }

    public function actionCityList($q = null, $id = null) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => ''],'parents' => []];
        if (!is_null($q)) {
            $query = new Query;
            $query = $query->select('id, country_id, region_id, name_' .Yii::$app->language. ' AS text')->from('maps_city');
            if($id != null){
                $query->where(['and',['or',['like', 'name_ru', $q], ['like', 'name_en', $q]],['country_id' => $id]]);
            }else{
                $query->where(['or',['like', 'name_ru', $q], ['like', 'name_en', $q]]);
            }
            $query = $query->createCommand()->queryAll();
            if(!count($query)){
                $attribute = 'name_'.Yii::$app->language;
                $response = self::actionCreateMaps($q);
                return $this->actionCityList($response['locality']->{$attribute},null);
            }
            $out['results'] = array_values($query);
            foreach ($query as $key => $result) {
                $data = (object)[];
                $data->country_id = $result['country_id'];
                $data->region_id = $result['region_id'];
                $data->city_id = $result['id'];

                $out['results'][$key]['text'] = Geo::address($data);
            }
        }
        return $out;
    }

    public function actionStreetList($q = null, $id = null) {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new Query;
            $query->select('id, name_en, name_ru AS text')->from('maps_street')->where(['or',['like', 'name_ru', $q], ['like', 'name_en', $q]]);
            $query = $query->createCommand()->queryAll();
            $out['results'] = array_values($query);
        }
        return $out;
    }

    public function actionGetNearby($latitude, $longitude)
    {
        $latitude = (float)substr($latitude, 0, 5);
        $longitude = (float)substr($longitude, 0, 5);

        if(substr($latitude, 0, 1) != '-'){
            $latPlus = ($latitude+0.5);
        }else{
            $latPlus = ($latitude-0.5);
        }

        if(substr($latitude, 0, 1) != '-'){
            $latMinus = ($latitude-0.5);
        }else{
            $latMinus = ($latitude+0.5);
        }

        if(substr($longitude, 0, 1) != '-'){
            $lngPlus = ($longitude+0.5);
        }else{
            $lngPlus = ($longitude-0.5);
        }

        if(substr($longitude, 0, 1) != '-'){
            $lngMinus = ($longitude-0.5);
        }else{
            $lngMinus = ($longitude+0.5);
        }

        $nearby = MapsCity::find()->where(['and',
            ['!=','latitude', $latitude],
            ['!=','longitude', $longitude],
            ['<=','latitude', $latPlus],
            ['>=','latitude', $latMinus],
            ['<=','longitude', $lngPlus],
            ['>=','longitude', $lngMinus],

        ])->all();

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return array_merge($nearby);
    }

    public function actionGetPlaces($latitude = null, $longitude = null, $id, $assign = null, $action = null)
    {
        $action = explode('/', $action);
        foreach (Yii::$app->getModule('admin')->activeModules as $key => $activeModule) {
            if(in_array($key, $action)){
                $action = $key;
                return Maps::actionGetPlaces($latitude, $longitude, $id, $assign, $action);
                break;
            }
        }
    }

    public function actionGetCity($latitude = null, $longitude = null)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return array_merge($this->getLocate($latitude,$longitude));

    }

    public function actionDelCity($latitude = null, $longitude = null)
    {

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return array_merge($this->delCity($latitude = substr($latitude, 0, 4),$longitude = substr($longitude, 0, 4)));
    }

    public function delCity($latitude = null, $longitude = null)
    {
        $city = MapsCity::find()->where(['and',['like','latitude', $latitude],['like','longitude', $longitude]])->one();

        return [$city];
    }

    public function actionSelectCity($val = null,$id = null,$item)
    {
        return $this->getSelectCity($val,$id,$item);
    }

    public function getSelectCity($val = null,$id = null,$item){

        if($val != null){
            MapsCityAssign::deleteAll(['item_id' => $item]);

            $val = explode(',', preg_replace('/[^0-9,]/', '', $val));

            foreach ($val as $id) {
                if(!MapsCityAssign::find()->where(['and',['item_id' => $item],['like','map_id', $id]])->one()){
                    if($item != 0){
                        $assign = new MapsCityAssign;
                        $assign->class = Item::className();
                        $assign->item_id = $item;
                        $assign->map_id = $id;
                        $assign->save();
                    }
                }
            }
        }

        $items = $val ? $val : $id;
        
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return MapsCity::find()->where(['id' => $items])->all(); // ['country_id' => 20],
        
    }

    public function actionCreateStreet($id,$class)
    {
        if(!($model = $class::findOne($id))){
            return $this->back();
        }

        $post = Yii::$app->request->post()['MapsStreet'];
        $assign = new MapsStreetAssign;
        $assign->class              = $class;
        $assign->category_id        = $class::findOne($id)->category_id;
        $assign->item_id            = $id;
        $assign->country_id         = $post['country_id'];
        $assign->region_id          = $post['region_id'];
        $assign->city_id            = $post['city_id'];
        $assign->street_id          = $post['street_id'];
        $assign->street_number_id   = $post['street_number_id'];
        $assign->latitude           = $post['latitude'];
        $assign->longitude          = $post['longitude'];
        $assign->status             = 1;
        $assign->save();

        // $city = MapsCity::find()->where(['and',['like','id', $post['city_id']]])->one();

        // $country = MapsCountry::find()->where(['and',['like','id', $city->country_id]])->one();

        return $this->formatResponse(Yii::t('easyii', 'Marker created'));

        return $this->render('@app/widgets/views/maps', [
            'model' => $model,
            'item_id' => $id,
        ]);
    }

    public function actionDeleteStreet($id)
    {
        MapsStreetAssign::deleteAll(['id' => $id]);
        return $this->formatResponse(Yii::t('easyii', 'Marker deleted'));
    }


    function xml2array($url, $get_attributes = 1, $priority = 'tag')
    {
        $contents = "";
        if (!function_exists('xml_parser_create'))
        {
            return array ();
        }
        $parser = xml_parser_create('');
        if (!($fp = @ fopen($url, 'rb')))
        {
            return array ();
        }
        while (!feof($fp))
        {
            $contents .= fread($fp, 8192);
        }
        fclose($fp);
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values)
            return; //Hmm...
        $xml_array = array ();
        $parents = array ();
        $opened_tags = array ();
        $arr = array ();
        $current = & $xml_array;
        $repeated_tag_index = array ();
        foreach ($xml_values as $data)
        {
            unset ($attributes, $value);
            extract($data);
            $result = array ();
            $attributes_data = array ();
            if (isset ($value))
            {
                if ($priority == 'tag')
                    $result = $value;
                else
                    $result['value'] = $value;
            }
            if (isset ($attributes) and $get_attributes)
            {
                foreach ($attributes as $attr => $val)
                {
                    if ($priority == 'tag')
                        $attributes_data[$attr] = $val;
                    else
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
            if ($type == "open")
            {
                $parent[$level -1] = & $current;
                if (!is_array($current) or (!in_array($tag, array_keys($current))))
                {
                    $current[$tag] = $result;
                    if ($attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                }
                else
                {
                    if (isset ($current[$tag][0]))
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else
                    {
                        $current[$tag] = array (
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        if (isset ($current[$tag . '_attr']))
                        {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            }
            elseif ($type == "complete")
            {
                if (!isset ($current[$tag]))
                {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                }
                else
                {
                    if (isset ($current[$tag][0]) and is_array($current[$tag]))
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else
                    {
                        $current[$tag] = array (
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes)
                        {
                            if (isset ($current[$tag . '_attr']))
                            {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset ($current[$tag . '_attr']);
                            }
                            if ($attributes_data)
                            {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            }
            elseif ($type == 'close')
            {
                $current = & $parent[$level -1];
            }
        }
        return ($xml_array);
    }
}