<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use app\models\db\ProductDownloaded;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class GetNotLinkMsController extends Controller
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

        $request = Yii::$app->getRequest()->bodyParams;
        if (!isset($request['mpId']) || !isset($request['linkType'])) {
            $response['success'] = false;
            $response['error'] = 3;
            return $this->asJson($response);
        }

        $mpId = (int)$request['mpId'];
        $linkTypeId = (int)$request['linkType'];

        try {
            if ($mpId === 1) {
                $productNotLink = ProductDownloaded::getProductNotLink($userId, $linkTypeId, $mpId);
            } else if ($mpId === 2) {
                $productNotLink = ProductDownloaded::getProductNotLinkWbMs($userId, $linkTypeId, $mpId);
            } else {
                $productNotLink = ProductDownloaded::getProductNotLinkYandexMs($userId, $linkTypeId, $mpId);
            }




            $response['count'] = count($productNotLink);
            $response['data'] = $productNotLink;



        } catch (Exception $e) {
            $response['success'] = false;
            $response['error'] = 4;
        }



        return $this->asJson($response);
    }
}