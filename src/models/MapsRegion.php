<?php
namespace bin\admin\modules\geo\models;

use Yii;

class MapsRegion extends \bin\admin\components\ActiveRecord
{
    public static function tableName()
    {
        return 'maps_region';
    }

    public function rules()
    {
        return [
            [['name_ru','name_en',], 'trim'],
            [['name_ru','name_en','type',], 'string', 'max' => 128],
            [['code',], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name_ru' => Yii::t('easyii', 'Country'),
            'name_en' => Yii::t('easyii', 'Country (EN)'),
            'type' => Yii::t('easyii', 'Type'),
            'code' => Yii::t('easyii', 'Code'),
        ];
    }

    public static function find()
    {
        return new \bin\admin\components\ActiveQuery(get_called_class());
    }

    public function getCities()
    {
        return $this->hasMany(MapsCity::className(), ['country_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }
}