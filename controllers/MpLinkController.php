<?php

namespace app\controllers;

use app\models\db\MpLinkCandidates;
use app\models\db\MpLinkTypes;
use app\models\db\ProductDownloaded;
use Exception;
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

    public function actionNotLink()
    {
        return $this->render('listNotLink');
    }

    public function actionManualBinding()
    {
        return $this->render('manualBinding');
    }

    public function actionLinkProducts()
    {
        $userId = 2;
        $response['success'] = true;

        $request = Yii::$app->getRequest()->bodyParams;

        if (!isset($request['firstProductId']) || !isset($request['secondProductId'])) {
            $response['success'] = false;
            $response['error'] = 6;
            return $this->asJson($response);
        }

        $firstProductId = (int)$request['firstProductId'];
        $secondProductId = (int)$request['secondProductId'];

        try {
            // получить маркет плейсы из которых присланы товары для связывания
            $firstMpId = ProductDownloaded::getMpIdByProductId($userId, $firstProductId);
            $secondMpId = ProductDownloaded::getMpIdByProductId($userId, $secondProductId);

            if (count($firstMpId) === 0 || count($secondMpId) === 0) {
                throw new Exception();
            }

            $firstMpId = (int) $firstMpId['mp_id'];
            $secondMpId = (int) $secondMpId['mp_id'];

            // получить информацию о типе связи между маркет плейсами
            $typeLink = MpLinkTypes::getTypeLinkIdByMpId($userId, $firstMpId, $secondMpId);

            if (count($typeLink) === 0) {
                throw new Exception();
            }

            $typeLinkId = (int) $typeLink[0]['id'];

            // привести значение переменных продуктов и маркет плейсов
            // в соответствие с типом связи в котором они участвуют
            if ($typeLink[0]['mp_first_id'] == $secondMpId) {
                $tmp = $firstProductId;
                $firstProductId = $secondProductId;
                $secondProductId = $tmp;
            }

            MpLinkCandidates::addLink($userId, $typeLinkId, $firstProductId, $secondProductId);

        } catch (Exception $e) {
            $response['success'] = false;
            $response['error'] = 7;
        }

        return $this->asJson($response);
    }

    public function actionGetManualBinding()
    {
        $userId = 2;

        $request = Yii::$app->getRequest()->bodyParams;

        if (!isset($request['productId']) || !isset($request['linkType'])) {
            $response['success'] = false;
            $response['error'] = 3;
            return $this->asJson($response);
        }

        $productId = (int)$request['productId'];
        $linkTypeId = (int)$request['linkType'];

        $response['success'] = true;

        try {
            // товар для которого будет искаться пара
            $productLink = ProductDownloaded::getProductById($userId, $productId);
            if (count($productLink) === 0) {
                throw new Exception('product not found');
            }
            $productLink = $productLink[0];

            $productLinkMpId = (int)$productLink['mp_id'];

            $mpByLinkId = MpLinkTypes::getMpIdByLink($linkTypeId);
            if (!$mpByLinkId) {
                throw new Exception('list product not found');
            }

            // получить id маркет плейса с товарами которого будет связывание
            $forLinkMpId = $mpByLinkId['mpFirstId'];
            $numLink = 1; // номер магазина в связи таблица mp_link_types

            if ($mpByLinkId['mpFirstId'] === $productLinkMpId) {
                $forLinkMpId = $mpByLinkId['mpSecondId'];
                $numLink = 2;
            }

            // список товаров из которых будет выбираться пара
            $productsForLink['data'] = ProductDownloaded::getProductForLink($userId, $linkTypeId, $forLinkMpId, $numLink);
            $productsForLink['count'] = count($productsForLink['data']);

            $response['data']['productsForLink'] = $productsForLink;
            $response['data']['productLink'][0] = $productLink;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error'] = 5;
            // $response['error_text'] = $e->getMessage() . $e->getTraceAsString();
        }

        return $this->asJson($response);
    }


    public function actionGetNotLink()
    {
        $userId = 2;

        $request = Yii::$app->getRequest()->bodyParams;
        if (!isset($request['mpId']) || !isset($request['linkType'])) {
            $response['success'] = false;
            $response['error'] = 3;
            return $this->asJson($response);
        }

        $mpId = $linkTypeId = (int)$request['mpId'];
        $linkTypeId = (int)$request['linkType'];

        try {
            $productNotLink = ProductDownloaded::getProductNotLink($userId, $linkTypeId, $mpId);
            $response['count'] = count($productNotLink);
            $response['data'] = $productNotLink;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error'] = 4;
        }

        // print_r(); exit;

        return $this->asJson($response);
    }


    public function actionGet()
    {
        $request = Yii::$app->getRequest()->bodyParams;

        $userId = 2;
        $linkNum = 0;

        $request['linkType'] = 1;

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
