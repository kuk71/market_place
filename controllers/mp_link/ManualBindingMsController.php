<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class ManualBindingMsController extends Controller
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

    public
    function actionIndex()
    {
        return $this->render('/mp_link/manual_binding_ms');
    }
}
