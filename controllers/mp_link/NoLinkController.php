<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use app\models\db\MpLinkNo;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class NoLinkController extends Controller
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
        $userId = Yii::$app->user->id;
        $response['success'] = true;

        $request = Yii::$app->getRequest()->bodyParams;

        try {
            if (!(isset($request['linkType']) && isset($request['productId']) && isset($request['noLink']))) {
                throw new Exception();
            }

            $linkTypeId = (int)$request['linkType'];
            $productId = (int)$request['productId'];
            $noLink = (bool)$request['noLink'];

            MpLinkNo::setNoLink($userId, $linkTypeId, $productId, $noLink);

        } catch (Exception $e) {
            $response['success'] = false;
        }

        return $this->asJson($response);
    }
}
