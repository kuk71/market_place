<?php
// импортирует данные отчетов о продажах из csv файла

namespace app\controllers\ms\import;

use app\controllers\Exeption;
use app\models\db\MP;
use app\models\db\MpSalesReportContents;
use app\models\db\MpSalesReports;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\rest\Controller;


class SoldController extends Controller
{
    private const CSV_PATH = "/../../../sold.csv";

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
            $soldId = self::addSalesReport($f);

            self::addSalesReportContent($f, $soldId);

            fclose($f);

        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return "Данные успешно импортированы";
    }

    private static function addSalesReportContent(&$f, int $soldId)
    {
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


                $price = str_replace(",", ".", $data[12]);
                $price = (float)$price;

                // вознаграждение комиссионера
                $reward = str_replace(",", ".", $data[11]);
                $reward = (float)$reward;

                $price = $price + $reward;

                $reward = (int)($reward * 100);

                $price = $price * 100 / $countSold;

                $soldPosition->price_sold_kop = (int)($price);
                $soldPosition->reward_sold_kop = $reward;

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

                $price = str_replace(",", ".", $data[18]);
                $price = (float)$price;
                $price = $price * 100 / $countSold;

                $soldPosition->price_sold_kop = (int)($price);
                $soldPosition->reward_sold_kop = 0;

                $soldPosition->save();

                if (count($soldPosition->errors) !== 0) {
                    throw new Exception("<pre>Ошибка при добавлении позиции " . print_r($data, true) . ". " . print_r($soldPosition->errors, true));
                }
            }
        }
    }

    private static function addSalesReport(&$f): int
    {
        $sold = new MpSalesReports();

        self::getSoldParams($f, $sold);

        $sold->save();

        if (count($sold->errors) !== 0) {
            throw new Exception("Ошибка создание записи об отчете о продажах." . print_r($sold->errors));
        }

        return $sold->id;
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
    }

}
