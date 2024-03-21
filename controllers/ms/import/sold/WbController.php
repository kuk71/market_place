<?php
// импортирует отчет комиссионера WB из csv файла

namespace app\controllers\ms\import\sold;

use app\models\db\MP;
use app\models\db\MpSalesReportContents;
use app\models\db\MpSalesReports;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class WbController extends Controller
{
    private const CSV_PATH = "/../../../../sold_wb.csv";

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

        $file = __DIR__ . self::CSV_PATH;
        $f = fopen($file, 'r');

        try {
            // создать запись об отчете о продажах
            $soldParams = [];
            self::addSalesReport($f, $soldParams);

            self::addSalesReportContent($f, $soldParams);

            fclose($f);

        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return "Данные успешно импортированы";
    }

    private static function addSalesReportContent(&$f, array $soldParams)
    {
        $rewardSum = 0;
        $soldId = $soldParams['id'];
        $commissionWeight = $soldParams['commissionWeight'];

        $positionInReport = 0;

        while (($data = fgetcsv($f, NULL, ";")) !== FALSE) {
            $positionInReport++;
            $countSold = (int)$data[13];

            // echo $data[9]; exit;

            $operationType = mb_strtolower(trim($data[9]));

            if ($operationType === "возврат" || $operationType === "продажа") {
                if (mb_strtolower(trim($data[9])) === "возврат") {
                    $countSold = -$countSold;
                }

                $soldPosition = new MpSalesReportContents();

                $soldPosition->sales_report_id = $soldId;

                $soldPosition->position_in_report = $positionInReport;


                $soldPosition->name = $data[6];
                $soldPosition->article = $data[5];

                $soldPosition->sku = (int)$data[3];
                $soldPosition->mp_product_barcode = (string)$data[8];
                $soldPosition->count_sold = $countSold;

                // цена реализации в копейках
                $price = (float)str_replace(",", ".", $data[19]);

                $price = round($price * 100);

                // баллы в копейках
                $scores = 0;

                $reward = (float)str_replace(",", ".", $data[29]);
                $reward = round($reward * 100);

                $soldPosition->price_sold_kop = $price;
                $soldPosition->reward_sold_kop = $reward;
                $rewardSum += $reward;

                $soldPosition->scores_kop = $scores;

                $soldPosition->save();

                if (count($soldPosition->errors) !== 0) {
                    throw new Exception("<pre>Ошибка при добавлении позиции " . print_r($data, true) . ". " . print_r($soldPosition->errors, true));
                }
            }

            if ($data[15] == "") {
                continue;
            }
        }

//        // сравнение комиссии в отчете и в импортированных данных
//        $deltaReward = $rewardSum - $soldParams['commissionSumKop'];
//
//        // echo "<pre>"; print_r($rewardSum); echo "<br>"; print_r($soldParams['commissionSumKop']); exit;
//
//        if ($deltaReward !== 0) {
//            $sold = MpSalesReportContents::findOne(['sales_report_id' => $soldId, ['>', 'count_sold', 0]]);
//            //->where(['sales_report_id' => $soldId])
//            //->Where(['>', 'count_sold', 0]);
//            //->one();
//
//            if ($sold !== false) {
//                $sold->reward_sold_kop -= $deltaReward;
//
//                // echo "<pre>"; print_r($sold); exit;
//
//                $sold->save();
//            }
//        }
    }

    private static function addSalesReport(&$f, &$soldParams): void
    {
        $sold = new MpSalesReports();

        self::getSoldParams($f, $sold);

        // зачистить ранее загруженный отчёт
        // TODO удалить зачистку и добавить проверку на повторное добавление
        self::clearSold($sold);

        $sold->save();

        if (count($sold->errors) !== 0) {
            throw new Exception("Ошибка создание записи об отчете о продажах." . print_r($sold->errors));
        }

        $soldParams['id'] = $sold->id;
        $soldParams['commissionWeight'] = $sold->commissionWeight;
        $soldParams['commissionSumKop'] = $sold->commissionSumKop;
    }

    private static function getSoldParams(&$f, MpSalesReports $sold): void
    {
        $data = fgetcsv($f, NULL, ";");

        if (!(isset($data[0]) && isset($data[1]) && isset($data[2]))) {
            throw new Exception("Не задано одно из полей: Название маркетплейса, Дата начала или Дата окончания.");
        }

        $sold->mp_id = MP::getMpIdByName($data[0]);

        if ($sold->mp_id === false) {
            throw new Exception("Не верно задано имя маркет плейса.");
        }

        $sold->date_start = $data[1];
        $sold->date_end = $data[2];

        list($year, $month, $day) = explode("/", $data[1]);

        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            throw new Exception("Не верно задана дата начала.");
        }

        list($year, $month, $day) = explode("/", $data[2]);

        if (!checkdate($month, $day, $year)) {
            throw new Exception("Не верно задана дата окончания.");
        }

        $sold->date_start = $data[1];
        $sold->date_end = $data[2];
        $sold->commissionWeight = ((float)$data[4]) / ((float)$data[3]);
        $sold->commissionSumKop = (int)$data[4];
    }

    // удаляе записи об отчете комиссионера за период
    private static function clearSold($sold): void
    {
        $soldForDel = MpSalesReports::findOne(['date_start' => $sold->date_start, 'date_end' => $sold->date_end, 'mp_id' => 2]);

        if ($soldForDel === null) {
            return;
        }

        MpSalesReportContents::deleteAll(['sales_report_id' => $soldForDel->id]);

        $soldForDel->delete();
    }
}
