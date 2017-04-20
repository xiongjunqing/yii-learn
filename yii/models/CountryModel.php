<?php

namespace app\models;

class CountryModel extends BaseModel
{

    public $code;
    public $name;
    public $population;

    static $instance = null;

    static function tableName()
    {
        return 'country';
    }

    static function instance($data = [])
    {
        if(self::$instance === null){
            self::$instance = new self;
        }

        $attributes = self::$instance->attributes();

        foreach ($attributes as $key => $value) {

            if(isset($data[$value])){
                self::$instance->$value = $data[$value];
            }
        }

        return self::$instance;
        // return $attributes;
    }

    public function attributeLabels()
    {
        return [
            'code' => 'country code',
            'name' => 'country name',
            'population' => 'The popou of one Country',
        ];
    }
}
