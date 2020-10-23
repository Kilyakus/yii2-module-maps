<?php
namespace bin\admin\modules\geo\models;

use Yii;

class MapsCountry extends \bin\admin\components\ActiveRecord
{
    public static function tableName()
    {
        return 'maps_country';
    }

    public function rules()
    {
        return [
            [['name_ru','name_en',], 'trim'],
            [['name_ru','name_en',], 'string', 'max' => 128],
            [['code',], 'string', 'max' => 2],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name_ru' => Yii::t('easyii', 'Country'),
            'name_en' => Yii::t('easyii', 'Country (EN)'),
            'code' => Yii::t('easyii', 'Code'),
        ];
    }

    public static function find()
    {
        return new \bin\admin\components\ActiveQuery(get_called_class());
    }

    public function getRegions()
    {
        return $this->hasMany(MapsRegion::className(), ['country_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function getCities()
    {
        return $this->hasMany(MapsCity::className(), ['country_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function getStreets()
    {
        return $this->hasMany(MapsStreet::className(), ['country_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }
}