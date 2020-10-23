<?php
namespace bin\admin\modules\geo\models;

class MapsStreetAssign extends \bin\admin\components\ActiveRecord
{
    public static function tableName()
    {
        return 'maps_street_assign';
    }

    public function rules()
    {
        return [
            [['item_id','category_id','latitude','longitude'], 'required'],
            ['class', 'string'],
            [['latitude','longitude',], 'string', 'max' => 100],
            [['category_id','item_id','parent_id','parent_item','country_id','region_id','city_id','street_id','street_number_id','status',], 'integer'],
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