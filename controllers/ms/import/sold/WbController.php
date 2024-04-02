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

            // заполняет отчет содержимым
            self::addSalesReportContent($f, $soldParams);


            fclose($f);

        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return "Данные успешно импортированы";
    }

    // возвращает true если запись нужно импортрировать
    private static function isImport(
        &$data,
        $soldParams,
        &$isSold,
        &$isReturned,
        &$isDefective,
        &$isSoldPartialCompensation,
        &$isReturnedPartialCompensation
    ): bool
    {
        if (self::isSold($data, $soldParams)) {
            // запись относится к продаже
            $isSold = true;
            return true;
        }

        if (self::isReturned($data, $soldParams)) {
            // запись относится в возврату
            $isReturned = true;
            return true;
        }

        if (self::isDefective($data, $soldParams)) {
            // запись относится в возврату
            $isDefective = true;
            return true;
        }

        if (self::isSoldPartialCompensation($data, $soldParams)) {
            // запись относится в возврату
            $isSoldPartialCompensation = true;
            return true;
        }

        if (self::isReturnedPartialCompensation($data, $soldParams)) {
            // запись относится в возврату
            $isReturnedPartialCompensation = true;
            return true;
        }

        return false;
    }

    private static function isSoldPartialCompensation(&$data, $soldParams): bool
    {
        if ($data[9] !== 'продажа') {
            return false;
        }

        if (
            $data[10] !== 'частичная компенсация брака'
        ) {
            return false;
        }

        if (!self::isDateValid($data, $soldParams)) {
            return false;
        }

        return true;
    }

    private static function isReturnedPartialCompensation(&$data, $soldParams): bool
    {
        if ($data[9] !== 'возврат') {
            return false;
        }

        if (
            $data[10] !== 'частичная компенсация брака'
        ) {
            return false;
        }

        if (!self::isDateValid($data, $soldParams)) {
            return false;
        }

        return true;
    }

    private static function isDefective(&$data, $soldParams): bool
    {

        if ($data[10] !== 'логистика') {
            return false;
        }

        if ($data[37] !== 'возврат брака (к продавцу)') {
            return false;
        }

        if (!self::isDateValid($data, $soldParams)) {
            return false;
        }

        return true;
    }

    private static function isReturned(&$data, $soldParams): bool
    {
        if ($data[9] !== 'возврат') {
            return false;
        }

        if (
            $data[10] !== 'возврат' &&
            $data[10] !== 'авансовая оплата за товар без движения'
        ) {
            return false;
        }

        if (!self::isDateValid($data, $soldParams)) {
            return false;
        }

        return true;
    }

    private static function isSold(&$data, $soldParams): bool
    {
        if ($data[9] !== 'продажа') {
            return false;
        }

        if (
            $data[10] !== 'продажа'
            && $data[10] !== 'корректная продажа'
            && $data[10] !== 'авансовая оплата за товар без движения'
            && $data[10] !== 'компенсация подмененного товара'
        ) {
            return false;
        }

        if (!self::isDateValid($data, $soldParams)) {
            return false;
        }

        return true;
    }

    private static function isDateValid(&$data, $soldParams): bool
    {
        $dateCheck = strtotime($data[12]);

        if ($dateCheck === false) {
            return false;
        }

        $dateStart = strtotime($soldParams['dateStart']);
        $dateEnd = strtotime($soldParams['dateEnd']);

        if ($dateCheck >= $dateStart && $dateCheck <= $dateEnd) {
            return true;
        }

        return false;
    }


    private static function addSalesReportContent(&$f, array $soldParams)
    {
        $rewardSum = 0;
        $soldId = $soldParams['id'];
        $commissionWeight = $soldParams['commissionWeight'];

        $positionInReport = 0;

        $soldPartialCompensation = []; // массив с частичной оплатой продажи
        $returnedPartialCompensation = []; // массив с частичной оплатой возврата

        while (($data = fgetcsv($f, NULL, ";")) !== FALSE) {
            $positionInReport++;

            $data[9] = mb_strtolower(trim($data[9]));
            $data[10] = mb_strtolower(trim($data[10]));
            $data[37] = mb_strtolower(trim($data[37]));

            $isSold = false; // запись относится к продаже
            $isReturned = false; // запись относится к возврату
            $isDefective = false; // запись относится к браку
            $isSoldPartialCompensation = false; // частичная компенсация продажи
            $isReturnedPartialCompensation = false; // частичная компенсация возвраты


            if (!self::isImport(
                $data,
                $soldParams,
                $isSold,
                $isReturned,
                $isDefective,
                $isSoldPartialCompensation,
                $isReturnedPartialCompensation
            )) {
                continue;
            }


            $countSold = (int)$data[13];

            if ($isReturned) {
                $countSold = -$countSold;
            }

            $soldPosition = new MpSalesReportContents();

            $soldPosition->sales_report_id = $soldId;
            $soldPosition->position_in_report = $positionInReport;
            $soldPosition->name = $data[6];
            $soldPosition->article = $data[5];
            $soldPosition->sku = (int)$data[3];
            $soldPosition->mp_product_barcode = (string)$data[8];


            // цена реализации в копейках
            $price = (float)str_replace(",", ".", $data[15]);

            $price = round($price * 100);

            // баллы в копейках
            $scores = 0;
            $reward = 0;

//            $reward = (float)str_replace(",", ".", $data[29]);
//            $reward = round($reward * 100);

            if ($isDefective) {
                // брак
                $countSold = -51;
                $price = 0;
                $reward = 0;
                $scores = 0;
            }

            $soldPosition->count_sold = $countSold;
            $soldPosition->price_sold_kop = $price;
            $soldPosition->reward_sold_kop = $reward;
            $rewardSum += $reward;
            $soldPosition->scores_kop = $scores;

            if ($isSoldPartialCompensation) {
                // частичная компенсация по товару Продажа
                if (!isset($soldPartialCompensation[$data[12]][$soldPosition->sku]['price'])) {
                    $soldPartialCompensation[$data[12]][$soldPosition->sku]['price'] = 0;
                }

                $soldPartialCompensation[$data[12]][$soldPosition->sku]['price'] += $soldPosition->price_sold_kop;
                $soldPartialCompensation[$data[12]][$soldPosition->sku]['name'] = $soldPosition->name;
                $soldPartialCompensation[$data[12]][$soldPosition->sku]['article'] = $soldPosition->article;
                $soldPartialCompensation[$data[12]][$soldPosition->sku]['mp_product_barcode'] = $soldPosition->mp_product_barcode;
            }

            if ($isReturnedPartialCompensation) {
                // частичная компенсация по товару Возврат
                if (!isset($returnedPartialCompensation[$data[12]][$soldPosition->sku]['price'])) {
                    $returnedPartialCompensation[$data[12]][$soldPosition->sku]['price'] = 0;
                }

                $returnedPartialCompensation[$data[12]][$soldPosition->sku]['price'] += $soldPosition->price_sold_kop;
                $returnedPartialCompensation[$data[12]][$soldPosition->sku]['name'] = $soldPosition->name;
                $returnedPartialCompensation[$data[12]][$soldPosition->sku]['article'] = $soldPosition->article;
                $returnedPartialCompensation[$data[12]][$soldPosition->sku]['mp_product_barcode'] = $soldPosition->mp_product_barcode;
            }

            if ($isSoldPartialCompensation || $isReturnedPartialCompensation) {
                continue;
            }

            $soldPosition->save();

            if (count($soldPosition->errors) !== 0) {
                throw new Exception("<pre>Ошибка при добавлении позиции " . print_r($data, true) . ". " . print_r($soldPosition->errors, true));
            }

            if ($data[15] == "") {
                continue;
            }
        }

        // учет частичных продаж
        foreach ($soldPartialCompensation AS $soldPartialCompensationDate) {
            foreach ($soldPartialCompensationDate AS $sku => $features) {
                $soldPosition = new MpSalesReportContents();

                $soldPosition->sales_report_id = $soldId;
                $soldPosition->position_in_report = 0;
                $soldPosition->name = $features['name'];
                $soldPosition->article = $features['article'];
                $soldPosition->sku = $sku;
                $soldPosition->mp_product_barcode = $features['mp_product_barcode'];

                $soldPosition->count_sold = 1;
                $soldPosition->price_sold_kop = $features['price'];
                $soldPosition->reward_sold_kop = 0;
                $soldPosition->scores_kop = 0;

                $soldPosition->save();
            }
        }

        // учет частичных возвратов
        foreach ($returnedPartialCompensation AS $returnedPartialCompensationDate) {
            foreach ($returnedPartialCompensationDate AS $sku => $features) {
                $soldPosition = new MpSalesReportContents();

                $soldPosition->sales_report_id = $soldId;
                $soldPosition->position_in_report = 0;
                $soldPosition->name = $features['name'];
                $soldPosition->article = $features['article'];
                $soldPosition->sku = $sku;
                $soldPosition->mp_product_barcode = $features['mp_product_barcode'];

                $soldPosition->count_sold = -1;
                $soldPosition->price_sold_kop = $features['price'];
                $soldPosition->reward_sold_kop = 0;
                $soldPosition->scores_kop = 0;

                $soldPosition->save();
            }
        }

        // echo "<pre>"; print_r($soldPartialCompensation);


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
        $soldParams['dateStart'] = $sold->date_start;
        $soldParams['dateEnd'] = $sold->date_end;
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
