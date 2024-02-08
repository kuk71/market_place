<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use app\models\db\MpLinkNo;
use app\models\db\MpLinkTypes;
use app\models\db\ProductDownloaded;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class LinkProductsMsController extends Controller
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
        $msId = 4; // id Мой склад
        $userId = Yii::$app->user->id;
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

            if (count($firstMpId) === 0) {
                throw new Exception();
            }

            $firstMpId = (int)$firstMpId['mp_id'];
            $secondMpId = $msId;

            // получить информацию о типе связи между маркет плейсами
            $typeLink = MpLinkTypes::getTypeLinkIdByMpId($userId, $firstMpId, $secondMpId);

            if (count($typeLink) === 0) {
                throw new Exception();
            }

            $typeLinkId = (int)$typeLink[0]['id'];

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
}
