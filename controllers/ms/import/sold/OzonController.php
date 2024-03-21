<?php
// импортирует отчет комиссионера Ozon из csv файла

namespace app\controllers\ms\import\sold;

use app\models\db\MP;
use app\models\db\MpSalesReportContents;
use app\models\db\MpSalesReports;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class OzonController extends Controller
{
    private const CSV_PATH = "/../../../../sold_ozon.csv";

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

        exit;

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

        while (($data = fgetcsv($f, NULL, ";")) !== FALSE) {
            $countSold = (int)$data[7];

            if ($countSold !== 0) {
                $soldPosition = new MpSalesReportContents();

                $soldPosition->sales_report_id = $soldId;

                $soldPosition->position_in_report = (int)$data[0];


                $soldPosition->name = $data[1];
                $soldPosition->article = $data[2];

                $soldPosition->sku = $data[3] == "" ? 0 : $data[3];
                $soldPosition->mp_product_barcode = $data[4] == "" ? "no barcode" : $data[4];
                $soldPosition->count_sold = $countSold;

                // цена реализации в копейках
                $price = (float)str_replace(",", ".", $data[8]);

                $reward = round($price * $countSold * $commissionWeight * 100);

                $price = round($price * 100);

                // баллы от Озона в копейках
                $scores = (float)str_replace(",", ".", $data[6]);
                $scores = round($scores * 100);

                // $price += ($scores / $countSold);

                // вознаграждение комиссионера в копейках
//                $reward = (float)str_replace(",", ".", $data[11]);
//                $reward = round($reward * 100);

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

            $countSold = (int)$data[15];


            if ($countSold !== 0) {
                $soldPosition = new MpSalesReportContents();

                $soldPosition->sales_report_id = $soldId;

                $soldPosition->position_in_report = (int)$data[0];

                $soldPosition->name = $data[1];
                $soldPosition->article = $data[2];

                $soldPosition->sku = $data[3] == "" ? 0 : $data[3];
                $soldPosition->mp_product_barcode = $data[4] == "" ? "no barcode" : $data[4];
                $soldPosition->count_sold = -$countSold;

                $price = (float)str_replace(",", ".", $data[16]);
                $price = round($price * 100);

                $scores = (float)str_replace(",", ".", $data[14]);
                $scores = round($scores * 100);

//                $reward = (float)str_replace(",", ".", $data[17]);
//                $reward = round($reward * 100);
                $reward = 0;

                $soldPosition->price_sold_kop = $price;
                $soldPosition->reward_sold_kop = $reward;
                $soldPosition->scores_kop = $scores;

                $soldPosition->save();

                if (count($soldPosition->errors) !== 0) {
                    throw new Exception("<pre>Ошибка при добавлении позиции " . print_r($data, true) . ". " . print_r($soldPosition->errors, true));
                }
            }
        }

        // сравнение комиссии в отчете и в импортированных данных
        $deltaReward = $rewardSum - $soldParams['commissionSumKop'];

        // echo "<pre>"; print_r($rewardSum); echo "<br>"; print_r($soldParams['commissionSumKop']); exit;

        if ($deltaReward !== 0) {
            $sold = MpSalesReportContents::findOne(['sales_report_id' => $soldId, ['>', 'count_sold', 0]]);
                //->where(['sales_report_id' => $soldId])
                //->Where(['>', 'count_sold', 0]);
                //->one();

            if ($sold !== false) {
                $sold->reward_sold_kop -= $deltaReward;

                // echo "<pre>"; print_r($sold); exit;

                $sold->save();
            }
        }
    }

    private static function addSalesReport(&$f, &$soldParams): void
    {
        $sold = new MpSalesReports();

        self::getSoldParams($f, $sold);

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

}
