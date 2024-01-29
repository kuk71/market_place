<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class DelLinkController extends Controller
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
        $userId = Yii::$app->user->id;

        $request = Yii::$app->getRequest()->bodyParams;

        $response['success'] = true;

        if (!isset($request['linkId'])) {
            $response['success'] = false;
            return $this->asJson($response);
        }

        $linkId = (int)$request['linkId'];

        try {
            $res = MpLinkCandidates::delLink($userId, $linkId);
        } catch (Exeption $e) {
            $response['success'] = false;
        }


        return $this->asJson($response);
    }
}
