<?php
namespace bin\admin\modules\geo;

class GeoModule extends \bin\admin\components\Module
{
    public $icon;
    
    public $settings = [];

    public $redirects = [];

    public static $installConfig = [
        'title' => [
            'en' => 'Maps',
            'ru' => 'Карты',
        ],
        'icon' => 'map-marker',
        'order_num' => 70,
    ];
}