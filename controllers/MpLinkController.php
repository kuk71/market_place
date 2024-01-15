<?php

namespace app\controllers;

use app\models\db\MpLinkCandidates;
use app\models\db\MpLinkTypes;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;
use yii\filters\VerbFilter;


class MpLinkController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('list');
    }

    public function actionGet()
    {
        $request = Yii::$app->getRequest()->bodyParams;

        $userId = 2;
        $linkNum = 0;

        $response['success'] = true;

        if (!isset($request['linkType'])) {
            $response['success'] = false;
            $response['error'] = 1;
            return $this->asJson($response);
        }

        $linkTypeId = (int)$request['linkType'];

        MpLinkCandidates::createLinkProductFirst($userId, $linkTypeId);
        $response['data'] = MpLinkCandidates::getLinkProduct($userId, $linkTypeId, $linkNum);

        return $this->asJson($response);
    }

    public function actionGetSecond()
    {
        $userId = 2;
        $linkNum = 2;

        $request = Yii::$app->getRequest()->bodyParams;

        $response['success'] = true;

        if (!isset($request['linkType'])) {
            $response['success'] = false;
            $response['error'] = 1;
            return $this->asJson($response);
        }

        $linkTypeId = (int)$request['linkType'];

        $queryPairNotLink = MpLinkCandidates::getQueryPairNotLink($userId, $linkTypeId);

        if (!$queryPairNotLink) {
            $response['success'] = false;
            $response['error'] = 2;
            return $this->asJson($response);
        }

        MpLinkCandidates::addLinkSecond($userId, $linkTypeId, $queryPairNotLink);

        $response['data'] = MpLinkCandidates::getLinkProduct($userId, $linkTypeId, $linkNum);

        return $this->asJson($response);
    }

    public function actionDelLink()
    {
        $userId = 2;

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
