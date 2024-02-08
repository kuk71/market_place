<?php

namespace app\controllers\ms;

use app\controllers\Exeption;
use app\models\db\MpMs;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class ImportController extends Controller
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

        $file = __DIR__ ."/../../ms.csv";
        $f = fopen($file, 'r');

        try {
            $fieldsPosition = self::getFieldsPosition($f);

            MpMs::deleteAll(['user_id' => $userId]);

            while (($data = fgetcsv($f, NULL, ";")) !== FALSE) {
                $mpMs = new MpMs();

                $mpMs->user_id = $userId;
                foreach($fieldsPosition AS $position => $field) {
                    $mpMs->$field = $data[$position] == '' ? NULL : $data[$position];
                }

                $a = $mpMs->weight_kg;
                $a = str_replace(",", ".", $a);
                $a = floatval($a);

                $mpMs->weight_kg = is_null($mpMs->weight_kg) ? NULL : floatval(str_replace(",", ".", $mpMs->weight_kg));
                $mpMs->length_sm = is_null($mpMs->length_sm) ? NULL : floatval(str_replace(",", ".", $mpMs->length_sm));
                $mpMs->width_sm = is_null($mpMs->width_sm) ? NULL : floatval(str_replace(",", ".", $mpMs->width_sm));
                $mpMs->height_sm = is_null($mpMs->height_sm) ? NULL : floatval(str_replace(",", ".", $mpMs->height_sm));

                $a = $mpMs->save();

                $a = 1;
            }

            fclose($f);

        } catch (\Exception $e) {
            return $e->getMessage();
        }


        return "Данные успешно импортированы";
    }

    private static function getFieldsPosition(&$f)
    {
        $fieldsPosition = [];

        $fieldsNeed = [
            'UUID' => 'UUID',
            'Код' => 'code',
            'Наименование' => 'name',
            'Внешний код' => 'external_code',
            'Артикул' => 'article',
            'Штрихкод Code128' => 'barcode',
            'Вес' => 'weight_kg',
            'Доп. поле: Длина' => 'length_sm',
            'Доп. поле: Ширина' => 'width_sm',
            'Доп. поле: Высота' => 'height_sm',
        ];

        $fields = fgetcsv($f, NULL, ";");

        foreach ($fields AS $key => $field){
            if (isset($fieldsNeed[$field])) {
                $fieldsPosition[$key] = $fieldsNeed[$field];
            }
        }

        $a = array_search('UUID', $fieldsPosition);

        foreach ($fieldsNeed AS $key => $field) {
            if (array_search($field, $fieldsPosition) === false) {
                throw new \Exception("В файле нет поля: $key");
            }
        }

        return $fieldsPosition;
    }


}
