<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use app\models\db\MpLinkTypes;
use app\models\db\ProductDownloaded;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class GetManualBindingController extends Controller
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
            $response['data']['productLink'] = $productLink;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['error'] = 5;
            // $response['error_text'] = $e->getMessage() . $e->getTraceAsString();
        }

        return $this->asJson($response);
    }
}
