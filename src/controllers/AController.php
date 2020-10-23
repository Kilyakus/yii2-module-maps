<?php
namespace bin\admin\modules\geo\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\widgets\ActiveForm;
use bin\admin\components\Controller;
use bin\admin\modules\geo\api\Geo;
use bin\admin\modules\geo\models\MapsCountry;
use bin\admin\modules\geo\models\MapsRegion;
use bin\admin\modules\geo\models\MapsCity;


class AController extends \bin\admin\components\Controller
{
    public function behaviors()
    {
        return [
        ];
    }

    public function actionIndex()
    {
        $data = new ActiveDataProvider([
            'query' => MapsCountry::find()->with(['regions'])->orderBy(['code' => SORT_ASC]),
            'pagination' => ['pageSize' => 30]
        ]);

        return $this->render('index', [
            'data' => $data
        ]);
    }

    public function actionCreate()
    {
        $model = new MapsCountry;
        $model->time = time();

        if ($model->load(Yii::$app->request->post())) {
            if(Yii::$app->request->isAjax){
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
            else{
                if(isset($_FILES) && $this->module->settings['enableThumb']){
                    $model->image = UploadedFile::getInstance($model, 'image');
                    if($model->image && $model->validate(['image'])){
                        $model->image = Image::upload($model->image, 'geo');
                    }
                    else{
                        $model->image = '';
                    }
                }
                if($model->save()){
                    $this->flash('success', Yii::t('easyii/geo', 'Country created'));
                    return $this->redirect(['/admin/'.$this->module->id]);
                }
                else{
                    $this->flash('error', Yii::t('easyii', 'Create error. {0}', $model->formatErrors()));
                    return $this->refresh();
                }
            }
        }
        else {
            return $this->render('create', [
                'model' => $model
            ]);
        }
    }

    public function actionEdit($id)
    {
        $model = MapsCountry::findOne($id);

        if($model === null){
            $this->flash('error', Yii::t('easyii', 'Not found'));
            return $this->redirect(['/admin/'.$this->module->id]);
        }

        if ($model->load(Yii::$app->request->post())) {
            if(Yii::$app->request->isAjax){
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
            else{
                if(isset($_FILES) && $this->module->settings['enableThumb']){
                    $model->image = UploadedFile::getInstance($model, 'image');
                    if($model->image && $model->validate(['image'])){
                        $model->image = Image::upload($model->image, 'geo');
                    }
                    else{
                        $model->image = $model->oldAttributes['image'];
                    }
                }

                if($model->save()){
                    $this->flash('success', Yii::t('easyii/geo', 'Country updated'));
                }
                else{
                    $this->flash('error', Yii::t('easyii', 'Update error. {0}', $model->formatErrors()));
                }
                return $this->refresh();
            }
        }
        else {
            return $this->render('edit', [
                'model' => $model
            ]);
        }
    }

    public function actionRegions($id)
    {
        if(!($model = MapsCountry::findOne($id))){
            return $this->redirect(['/admin/'.$this->module->id]);
        }
        $data = new ActiveDataProvider([
            'query' => MapsRegion::find()->where(['country_id' => $id])->orderBy(['id' => SORT_ASC]),
            'pagination' => ['pageSize' => 30]
        ]);

        return $this->render('regions', [
            'data' => $data
        ]);
    }

    public function actionCities($id,$region = null)
    {
        if(!($model = MapsCountry::findOne($id))){
            return $this->redirect(['/admin/'.$this->module->id]);
        }

        $query = ['country_id' => $id];

        if($region){
            $query['region_id'] = $region;
        }

        $data = new ActiveDataProvider([
            'query' => MapsCity::find()->where($query)->orderBy(['id' => SORT_ASC]),
            'pagination' => ['pageSize' => 30]
        ]);

        return $this->render('cities', [
            'data' => $data,
        ]);
    }

    public function actionCountryDelete($id)
    {
        if(($model = MapsCountry::findOne($id))){
            $model->delete();
        } else {
            $this->error = Yii::t('easyii', 'Not found');
        }
        return $this->formatResponse(Yii::t('easyii/geo', 'Country deleted'));
    }

    public function actionRegionDelete($id)
    {
        if(($model = MapsRegion::findOne($id))){
            $model->delete();
        } else {
            $this->error = Yii::t('easyii', 'Not found');
        }
        return $this->formatResponse(Yii::t('easyii/geo', 'Country deleted'));
    }

    public function actionCityDelete($id)
    {
        if(($model = MapsCity::findOne($id))){
            $model->delete();
        } else {
            $this->error = Yii::t('easyii', 'Not found');
        }
        return $this->formatResponse(Yii::t('easyii/geo', 'Country deleted'));
    }
}