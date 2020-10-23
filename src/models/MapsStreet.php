<?php
namespace bin\admin\modules\geo\models;

use Yii;

/**
 * Base MapsCity. Shared by categories
 * @package bin\admin\components
 * @inheritdoc
 */
class MapsStreet extends \bin\admin\components\ActiveRecord
{

    public function rules()
    {
        return [
            ['city_id', 'required'],
            [['name_ru','name_en',], 'trim'],
            [['name_ru','name_en',], 'string', 'max' => 128],
            [['postal_code',], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name_ru' => Yii::t('easyii', 'Street'),
            'name_en' => Yii::t('easyii', 'Street (EN)'),
            'postal_code' => Yii::t('easyii', 'Postal Code'),
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