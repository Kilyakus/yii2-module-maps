<?php
namespace bin\admin\modules\geo\models;

class MapsCityAssign extends \bin\admin\components\ActiveRecord
{
    public static function tableName()
    {
        return 'maps_city_assign';
    }

    public function rules()
    {
        return [
            [['item_id','map_id'], 'required'],
            ['class', 'string'],
            [['item_id','map_id',], 'integer'],
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