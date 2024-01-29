<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use yii\filters\AccessControl;
use yii\rest\Controller;


class AutoController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('/mp_link/auto');
    }
}
