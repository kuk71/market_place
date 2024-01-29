<?php

namespace app\controllers\mp_link;

use app\controllers\Exeption;
use app\models\db\MpLinkCandidates;
use yii\filters\AccessControl;
use yii\rest\Controller;
use Yii;


class GetLinkController extends Controller
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
        $request = Yii::$app->getRequest()->bodyParams;
        $userId = Yii::$app->user->id;

        $response['success'] = true;

        if (!(isset($request['linkType']) && isset($request['linkNum']))) {
            $response['success'] = false;
            $response['error'] = 1;
            return $this->asJson($response);
        }

        $linkTypeId = (int)$request['linkType'];
        $linkNum = (int)$request['linkNum'];

        if (isset($request['delLink']) && $request['delLink'] === true) {
            // удалить ранее созданне соединения
            MpLinkCandidates::deleteAll(['user_id' => $userId, 'mp_link_type_id' => $linkTypeId]);
        }

        if ($linkNum === 1) {
            // первый уровень соединения
            MpLinkCandidates::createLinkProductFirst($userId, $linkTypeId);
        } else {
            // второй уровень соединения
            $queryPairNotLink = MpLinkCandidates::getQueryPairNotLink($userId, $linkTypeId);

            if (!$queryPairNotLink) {
                $response['success'] = false;
                $response['error'] = 2;
                return $this->asJson($response);
            }

            MpLinkCandidates::addLinkSecond($userId, $linkTypeId, $queryPairNotLink);
        }



        $response['data'] = MpLinkCandidates::getLinkProduct($userId, $linkTypeId, $linkNum);

        return $this->asJson($response);
    }
}
