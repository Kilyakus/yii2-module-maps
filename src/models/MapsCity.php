<?php
namespace bin\admin\modules\geo\models;

use Yii;

class MapsCity extends \bin\admin\components\ActiveRecord
{

    public function rules()
    {
        return [
            [['country_id',], 'required'],
            [['name_ru','name_en','latitude','longitude',], 'trim'],
            [['name_ru','name_en',], 'string', 'max' => 128],
            [['type',], 'string', 'max' => 1024],
            [['country_id','region_id','region','postal_code',], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name_ru' => Yii::t('easyii', 'City'),
            'name_en' => Yii::t('easyii', 'City (EN)'),
            'type' => Yii::t('easyii', 'Type'),
            'region' => Yii::t('easyii', 'Region'),
            'postal_code' => Yii::t('easyii', 'Postal code'),
            'latitude' => Yii::t('easyii', 'Latitude'),
            'longitude' => Yii::t('easyii', 'Longitude'),
        ];
    }

    public static function find()
    {
        return new \bin\admin\components\ActiveQuery(get_called_class());
    }
}