<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use yii\filters\AccessControl;
use yii\rest\Controller;
use Yii;


class GetLinkMsController extends Controller
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
        $linkTypeId = 4; // ozon / Мой склад

        $request = Yii::$app->getRequest()->bodyParams;
        $userId = Yii::$app->user->id;

        $response['success'] = true;

        if (!isset($request['linkNum'])) {
            $response['success'] = false;
            $response['error'] = 1;
            return $this->asJson($response);
        }

        $linkNum = (int)$request['linkNum'];

        if (isset($request['delLink']) && $request['delLink'] === true) {
            // удалить ранее созданне соединения
            MpLinkCandidates::deleteAll(['user_id' => $userId, 'mp_link_type_id' => $linkTypeId]);
        }

        if ($linkNum === 1) {
            // первый уровень соединения
            MpLinkCandidates::createLinkProductFirstMs($userId, $linkTypeId);
        } else {
            // второй уровень соединения
            $queryPairNotLink = MpLinkCandidates::getQueryPairNotLinkMs($userId, $linkTypeId);

            // print_r($queryPairNotLink); exit;

            if (!$queryPairNotLink) {
                $response['success'] = false;
                $response['error'] = 2;
                return $this->asJson($response);
            }

            MpLinkCandidates::addLinkSecondMs($userId, $linkTypeId, $queryPairNotLink);
        }

        if ($linkNum === 1){
            $linkNum = 0;
        }

        $response['data'] = MpLinkCandidates::getLinkProductMs($userId, $linkTypeId, $linkNum);

        return $this->asJson($response);
    }
}
