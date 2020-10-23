<?php
namespace bin\admin\modules\geo\models;

use Yii;

/**
 * Base MapsCity. Shared by categories
 * @package bin\admin\components
 * @inheritdoc
 */
class MapsStreetNumber extends \bin\admin\components\ActiveRecord
{

    public function rules()
    {
        return [
            ['street_id', 'required'],
            [['name_ru','name_en','latitude','longitude',], 'trim'],
            [['name_ru','name_en',], 'string', 'max' => 128],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name_ru' => Yii::t('easyii', 'Number House'),
            'name_en' => Yii::t('easyii', 'Number House (EN)'),
            'latitude' => Yii::t('easyii', 'Latitude'),
            'longitude' => Yii::t('easyii', 'Longitude'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return new \bin\admin\components\ActiveQuery(get_called_class());
    }
}