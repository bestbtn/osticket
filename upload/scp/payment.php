<?php
class Payment  {
    const KEY = 'DLNwROkurGhgMEqKvbjn';


    public static $payment_type_exclude_cache;

    public static function getLastIdTicket(){
        $id =db_result( db_query("SELECT ticket_id FROM ost_ticket ORDER BY ticket_id DESC LIMIT 1"));
        return $id;
    }

    public static function getListPayment(){
        $list =db_result(db_query("SELECT 'booking_code','time','amount','quantity','note' FROM payment_tmp ORDER BY ticket_id DESC"));

        return $list;
    }

    public static function get_inner_sql($type) {
        return " (SELECT DISTINCT
             vv.value
           FROM ost_ticket tt
             JOIN ost_form_entry ee ON tt.ticket_id = ee.object_id AND ee.object_type = 'T' AND ee.form_id IN (" . implode(',', unserialize(IO_FORM)) . ")
             JOIN ost_form_entry_values vv ON ee.id = vv.entry_id
             JOIN ost_form_field ff ON ff.id = vv.field_id
           WHERE 1 AND ff.`name` LIKE '{$type}' AND tt.ticket_id=t.ticket_id
          ) AS {$type} ";
    }
    public static function get_inner_sql_id($type, $as) {
        return " (SELECT DISTINCT
             vv.value_id
           FROM ost_ticket tt
             JOIN ost_form_entry ee ON tt.ticket_id = ee.object_id AND ee.object_type = 'T' AND ee.form_id IN (" . implode(',', unserialize(IO_FORM)) . ")
             JOIN ost_form_entry_values vv ON ee.id = vv.entry_id
             JOIN ost_form_field ff ON ff.id = vv.field_id
           WHERE 1 AND ff.`name` LIKE '{$type}' AND tt.ticket_id=t.ticket_id
          ) AS {$as} ";
    }

    public static function get_inner_sql_trim($type, $as) {
        return " (SELECT DISTINCT
             trim(LEADING '0' FROM trim(LEADING '".BOOKING_CODE_PREFIX."' FROM vv.value))
           FROM ost_ticket tt
             JOIN ost_form_entry ee ON tt.ticket_id = ee.object_id AND ee.object_type = 'T' AND ee.form_id IN (" . implode(',', unserialize(IO_FORM)) . ")
             JOIN ost_form_entry_values vv ON ee.id = vv.entry_id
             JOIN ost_form_field ff ON ff.id = vv.field_id
           WHERE 1 AND ff.`name` LIKE '{$type}' AND tt.ticket_id=t.ticket_id
          ) AS {$as} ";
    }

    public static function get_select_sql($ticket_id = 0) {
        define('THU_TOPIC', 16);
        define('CHI_TOPIC', 15);
        define('CHI_FORM', 9);
        define('THU_FORM', 19);
        define('IO_TOPIC', serialize(array(THU_TOPIC,CHI_TOPIC)));
        define('IO_FORM', serialize(array(CHI_FORM,THU_FORM)));
        define('THU_METHOD_LIST', 11);
        define('CHI_METHOD_LIST', 7);
        define('BOOKING_CODE_PREFIX', 'TG123-');
        define('BOOKING_CODE_PREFIX_SHORT', 'TG-');

        $ticket_id = intval($ticket_id);
        $ticket_id = db_input($ticket_id);
        $booking_code = self::get_inner_sql('booking_code');
        $booking_code_trim = self::get_inner_sql_trim('booking_code', 'booking_code_trim');
        $time = self::get_inner_sql('time');
        $receipt_code = self::get_inner_sql('receipt_code');
        $amount = self::get_inner_sql('amount');
        $quantity = self::get_inner_sql('quantity');
        $income_type = self::get_inner_sql('income_type');
        $outcome_type = self::get_inner_sql('outcome_type');
        $income_type_id = self::get_inner_sql_id('income_type', 'income_type_id');
        $outcome_type_id = self::get_inner_sql_id('outcome_type', 'outcome_type_id');
        $method = self::get_inner_sql('method');
        $agent = self::get_inner_sql('agent');
        $note = self::get_inner_sql('note');
        $form = implode(',', unserialize(IO_FORM));
        $where = $ticket_id ? "  WHERE t.ticket_id = {$ticket_id}  " : " ";
        return " SELECT DISTINCT
              t.ticket_id,
              t.number,
              t.topic_id,
              t.dept_id,
              {$booking_code},
              {$booking_code_trim},
              {$time},
              {$receipt_code},
              {$amount},
              {$quantity},
              {$income_type},
              {$income_type_id},
              {$outcome_type},
              {$outcome_type_id},
              0 as exclude,
              {$method},
              {$agent},
              {$note},
              0 as booking_ticket_id
            FROM ost_ticket t
              JOIN ost_form_entry e ON t.ticket_id = e.object_id AND e.object_type = 'T' AND e.form_id IN ({$form})
              JOIN ost_form_entry_values v ON e.id = v.entry_id
              JOIN ost_form_field f ON f.id = v.field_id
             {$where} ";
    }

    public static function get_create_sql() {
        $select = self::get_select_sql();
        $sql = "CREATE TABLE IF NOT EXISTS payment_tmp
            (UNIQUE INDEX ticket_id_ix(ticket_id), INDEX time_ix (`time`(20)),
                INDEX booking_code_ix (booking_code(20)),
                 INDEX topic_ix (topic_id),
                 INDEX income_type_id_ix (income_type_id),
                 INDEX outcome_type_id_ix (outcome_type_id),
                INDEX booking_ticket_id_ix (booking_ticket_id))
            {$select} ";
        return $sql;
    }

    public static function create_table() {
        $sql = self::get_create_sql();
        return db_query($sql);
    }

    public static function push($ticket_id = 0) {
        $select = self::get_select_sql($ticket_id);
        $sql_tmp_table = " REPLACE INTO payment_tmp {$select} LIMIT 1";
        db_query($sql_tmp_table);
        return self::get_hash($ticket_id);
    }

    public static function get_hash($ticket_id) {
        $select = self::get_select_sql($ticket_id);
        $sql = " ${select} LIMIT 1 ";
        $res = db_query($sql);
        if (!$res) return null;
        $row = db_fetch_array($res);
        if (!$row) return null;
        $hash = self::_hash($row);
        $string = trim($row['ticket_id'])
            .'<>'.(self::_get_string_to_hash($row));
        $data = strval($string);
        self::save_hash($hash, $data);
        return $hash;
    }

    private static function _hash($row) {
        return md5((self::_get_string_to_hash($row)).self::KEY);
    }

    private static function _get_string_to_hash($row) {
        return trim($row['booking_code']).trim($row['receipt_code']).trim($row['amount']);
    }

    public static function save_hash($hash, $data) {
        $sql = "INSERT INTO ost_hash_data SET
            `hash` = '{$hash}',
            `type` = 'payment',
            `data` = '{$data}'";
        try {
            return db_query($sql);
        } catch (Exception $ex) { return false; }
    }

    public static function export($params) {
        $sql = self::_get_payment_list_sql($params);
        return self::_payment_export_excel($sql);
    }

    private static function _payment_export_excel($sql) {
        require_once(INCLUDE_DIR.'../lib/PHPExcel.php');
        $dir = INCLUDE_DIR.'../public_file/';
        $file_name = 'Payment_list_' . Format::userdate("Y-m-d_H-i-s", time()).".xlsx";

        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory;
        $cacheSettings = [ 'memoryCacheSize' => '16MB' ];
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        $objPHPExcel = new PHPExcel();
        $properties = $objPHPExcel->getProperties();
        $properties->setCreator(NOTIFICATION_TITLE);
        $properties->setLastModifiedBy("System");
        $properties->setTitle(NOTIFICATION_TITLE." - Payment Daily Report");
        $properties->setSubject(NOTIFICATION_TITLE." - Payment Daily Report");
        $properties->setDescription(NOTIFICATION_TITLE." - Payment Daily Report - ".Format::userdate("Y-m-d H:i:s", time()));
        $properties->setKeywords(NOTIFICATION_TITLE." - Payment Daily Report");
        $properties->setCategory(NOTIFICATION_TITLE." - Payment Daily Report");

        $sheet = $objPHPExcel->getActiveSheet();

        $header = [
            'Ngày',
            'Booking Code',
            'Receipt Code',
            'Loại',
            'Số lượng',
            'Tổng tiền',
            'Phương thức',
            'Note/Agent',
        ];

        $excel_row = 1;
        $col = 'A';
        foreach ($header as $title) {
            $sheet->setCellValue($col++.$excel_row, $title);
        }
        // Set cell A1 with a string value
        $excel_row = 2;
        $res = db_query($sql);
        if ($res) {
            while (($row = db_fetch_array($res))) {
                $data = [
                    Format::userdate('d/m/Y', $row['time']),
                    $row['booking_code'],
                    $row['receipt_code'],
                    _String::json_decode($row['income_type'] ?: $row['outcome_type']),
                    $row['quantity'],
                    ($row['income_type'] || $row['topic_id']==THU_TOPIC) ? number_format($row['amount']) : number_format(-1 * $row['amount']),
                    _String::json_decode($row['method']),
                    $row['note'] . $row['agent'],
                ];
                $sheet->fromArray($data, NULL, 'A'.$excel_row++);
            } //end of while.
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");
        $objWriter->save($dir.$file_name);

        $objPHPExcel->disconnectWorksheets();
        unset($objWriter);
        unset($objPHPExcel);
        return $dir.$file_name;
    }

    private static function _get_payment_list_sql($params) {
        global $thisstaff;
        $type = $booking_code = $amount = $receipt_code = $method = $dept_id = null;
        if ($params['type']) {
            switch (strtolower($params['type'])) {
                case 'in':
                    $type = $params['type'];
                    break;
                case 'out':
                    $type = $params['type'];
                    break;
                default:
                    $type = null;
            }
        }

        if (isset($params['booking_code']))
            $booking_code = trim($params['booking_code']);

        if (isset($params['receipt_code']))
            $receipt_code = trim($params['receipt_code']);

        if (isset($params['amount']))
            $amount = preg_replace('/[^0-9]/', '', trim($params['amount']));

        if (isset($params['method']))
            $method = trim(json_encode(trim($params['method'])), '"');

        if (isset($params['dept_id']))
            $dept_id = trim($params['dept_id']);

        $qwhere = ' WHERE 1 ';
        //Type
        if ($type)
            $qwhere .= ' AND topic_id=' . db_input($type == 'in' ? THU_TOPIC : CHI_TOPIC);

        if ($dept_id)
            $qwhere .= sprintf(" AND (  dept_id = %s ) ", db_input("$dept_id"));

        if ($booking_code)
            $qwhere .= sprintf(" AND (  booking_code LIKE %s ) ", db_input("%$booking_code%"));

        if ($receipt_code)
            $qwhere .= sprintf(" AND (  receipt_code LIKE %s ) ", db_input("%$receipt_code%"));

        if ($method)
            $qwhere .= sprintf(" AND (  method LIKE %s ESCAPE '|'  ) ", "'%".str_replace('\\', '\\\\', $method)."%'");

        if (!empty($amount))
            $qwhere .= sprintf(" AND (  amount = %s ) ", db_input("$amount"));

        //dates
        $startTime = ($params['startDate'] && (strlen($params['startDate']) >= 8)) ? Format::userdate('Y-m-d', strtotime($params['startDate'])) : 0;
        $endTime = ($params['endDate'] && (strlen($params['endDate']) >= 8)) ? Format::userdate('Y-m-d', strtotime($params['endDate'])) : 0;
        if ($startTime > $endTime && $endTime > 0) {
            $errors['err'] = __('Entered date span is invalid. Selection ignored.');
            $startTime = $endTime = 0;
        } else {
            if ($startTime)
                $qwhere .= " AND DATE(FROM_UNIXTIME(`time`)) >= date(" . (db_input($startTime)) . ")";

            if ($endTime)
                $qwhere .= " AND DATE(FROM_UNIXTIME(`time`)) <= date(" . (db_input($endTime)) . ")";
        }

        $where_dept = ' ';
        if ($thisstaff) {
            $staff_dept = $thisstaff->getGroup()->getDepartments();
            if ($staff_dept)
                $where_dept = ' AND dept_id IN ('.implode(',', $staff_dept).') ';
            else
                $where_dept = '  AND 1 = 0 ';
        }

        $qwhere .= $where_dept;

//        self::create_table();
        $sql = " SELECT * FROM payment_tmp $qwhere ORDER BY `time`, ticket_id DESC ";
        return $sql;
    }

    public static function updateTypeId($ticket_id = 0) {
        if (!$ticket_id) return false;
        $payment = static::lookup($ticket_id);
        if (!$payment) return false;
        if (!isset($payment->topic_id) && !$payment->topic_id) return false;

        if ($payment->topic_id == THU_TOPIC)
            $column_name = 'income_type';
        elseif ($payment->topic_id == CHI_TOPIC)
            $column_name = 'outcome_type';
        else
            return false;

        $type_id = static::getTypeId($payment->$column_name);
        if (!$type_id || !is_numeric($type_id)) return false;
        $exclude = 0;
        if (isset(static::$payment_type_exclude_cache[ $column_name ][ $type_id ])) {
            if (intval(trim(static::$payment_type_exclude_cache[ $column_name ][ $type_id ]))) {
                $exclude = 1;
            }
        } else {
            $exclude = static::$payment_type_exclude_cache[ $column_name ][ $type_id ] = static::isExclude( $type_id );
        }

        $payment->setAll(
            [
                $column_name.'_id' => db_input($type_id),
                'exclude' => db_input($exclude),
            ]
        );

        $payment->save();

        return true;
    }

    public static function isExclude($payment_type_id) {
        $list_item = DynamicListItem::lookup($payment_type_id);
        $config = $list_item->getConfiguration();
        if (isset($config[ INCOME_EXCLUDE ]) && $config[ INCOME_EXCLUDE ])
            return 1;
        if (isset($config[ OUTCOME_EXCLUDE ]) && $config[ OUTCOME_EXCLUDE ])
            return 1;

        return 0;
    }

    public static function getTypeId($type_json) {
        $tmp = explode('":"', $type_json);
        if (!is_array($tmp) || !isset($tmp[0])) return null;
        $id = preg_replace('/[^0-9]/', '', $tmp[0]);
        return $id;
    }

}
//Gây lỗi vì ngoài class vẫn còn dòng này chưa được comment
    // function db_input($n){
    //     return "'$n'";
    // }


// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "osticket";

// // Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);
// // Check connection
// if ($conn->connect_error>0) {
//     die("Lỗi : ".$conn->connect_error);
// }
// $sql = Payment::get_select_sql(19);
// $conn->set_charset('utf8');
// $kq = $conn->query("REPLACE INTO payment_tmp {$sql} LIMIT 1");
// if(!$kq) die('co loi');





// $conn->close();


// $sql = Payment::get_select_sql(19);

//  echo "<pre>";
//  print_r($sql);
//  echo "<pre>";
