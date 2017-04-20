<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Request;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\CountryModel;

class TestController extends Controller
{
    public function actionIndex()
    {
        // $model = new CountryModel();
        // return $this->render('index',[
        //     'model' => $model,
        // ]);

        $request = Yii::$app->request;
        $method  = $request->method;

        $url     = $request->url;

        $headers = $request->headers;
        var_dump($headers);
        echo $url . '<br/>';
        echo 'method is ' . $method;

        var_export($request->get());
        var_dump($request->post());
        var_dump($request->bodyParams);
        die();

        echo '@webroot';
        echo '@web';
        die();
        // if(Yii::$app->request->post()){
        //     dump($_POST);die();
        // }
    }
}
