
<?php require('staff.inc.php');
require_once('payment.php');
require_once INCLUDE_DIR . 'class.report.php';

$nav->setTabActive('payments');
require_once(STAFFINC_DIR.'header.inc.php');
$report = new OverviewReport($_POST['start'], $_POST['end']);


?>

<div style="margin-bottom:5px; padding-top:5px;">
    <div class="sticky placeholder"></div>
    <div class="sticky bar opaque">
        <div class="content" style="width: 938px;">
            <div class="pull-left flush-left">
                <h2>List Payment
                    <i class="help-tip icon-question-sign" href="#ticket_activity"></i>
                </h2>
            </div>
        <a class="only sticky scroll-up" href="#" data-stop="116">
            <i class="icon-chevron-up icon-large"></i>
        </a>
        <br><br>
        </div>
    </div>
</div>
<form method="get" action="">
<table>
        <tr>
            <td width="10%"><b>Date Span</b> Between</td>
            <td width="40%">
                <label>
                <input type="text" class="dp input-medium search-query"
                    name="start"
                    value="<?php
                        echo Format::htmlchars($report->getStartDate());
                    ?>" />
                </label>
                <label>
                <input type="text" class="dp input-medium search-query"
                    name="end"
                    value="<?php
                        echo Format::htmlchars($report->getStartDate());
                    ?>" />
                </label>

            </td>
            <td width="10%">Mã booking</td>
            <td width="20%">
                <input type="text" name="booking_code"  value="" id="code_booking">
            </td>
            <td width="10%">Mã Receipt</td>
            <td width="20%">
                <input type="text" name="code_receipt"  value="" id="code_receipt">
            </td>


        </tr>
        <tr>
           <td width="10%">Thu/Chi</td>
            <td width="40%">
                 <select name="type" >
                    <option value="now" selected="selected">
                        --All--
                    </option>
            </select>
            </td>
            <td width="5%"> Phương thức</td>
            <td  width="20%">
                <select name="method">
                    <option value="now" selected="selected">
                        --All--
                    </option>
                </select>
            </td>
            <td  width="5%">Số tiền</td>
            <td width="20%"><input type="text" value="" name="amount"></td>

        </tr>
        <tr>
           <td width="10%">Loại Chi</td>
            <td width="40%">
                <select name="outcome_type" style="width: 100%">
                    <option value="now" selected="selected" >
                        --All--
                    </option>
                </select>
            </td>
            <td  width="5%">Tour</td>
            <td  width="20%">
                <select name="tour" style="width:100%">
                    <option value="now" selected="selected">
                        --All--
                    </option>
            </select>
            </td>
            <td width="5%">Ghi chú</td>
            <td  width="20%"><input type="text" name="note" value="" ></td>

        </tr>
        <tr>
            <td width="10%">Loại Thu</td>
            <td width="40%">
                <select name="income_type">
                    <option value="now" selected="selected">
                        --All--
                    </option>
            </select>
            </td>
            <td  width="5%">Khu vực</td>
            <td  width="20%">
                 <select name="area">
                    <option value="now" selected="selected">
                        --All--
                    </option>
                </select>
            </td>
            <td  width="5%">Agent</td>
            <td  width="20%">
                <select name="agent">
                    <option value="now" selected="selected">
                        --All--
                    </option>
                </select>
            </td>
        </tr>
</table>
    <div class="form-inline" style="margin-top: 1%;margin-bottom:2%">
            <button type="submit" name="search" class="btn btn-primary" style="padding: 5px;border-radius: 5px;color: white;background-color: lightblue">Search</button>
            <button type="submit" name="reset" class="Reset" style="padding: 5px;border-radius: 5px;color: red;background-color: brown">Reset</button>
            <button type="submit" name="export" class="primary" style="padding: 5px;border-radius: 5px">Export</button>
    </div>
</form>


<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="10%">Ticket</th>
            <th width="5%">Booking Code</th>
            <th width="7%">Mã Receipt</th>
            <th width="10%">Thời gian</th>
            <th width="5%">Số tiền</th>
            <th width="7%">Phương thức</th>
            <th width="7%">Người thu/chi</th>
            <th width="7%">Loại</th>
            <th width="5%">Số lượng</th>
            <th width="25%">Ghi chú</th>
        </tr>
    </thead>
    <tbody>
        <?php

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "osticket";
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error>0) {
            die("Lỗi : ".$conn->connect_error);
        }
        $conn->set_charset('utf8');
        $kq = $conn->query("SELECT * FROM payment_tmp ORDER BY ticket_id DESC");
        if(!$kq) die('co loi');
        //Payment::getListPayment();
        $i = 0;
        while ($rowkq = $kq->fetch_assoc()) {
        ?>
            <tr id="<?= $rowkq['ticket_id']?>">
                <td nowrap="" align="center">
                    <?= "TK-".$rowkq['number']?>
                </td>
                <td>
                    <?= $rowkq['booking_code']?>
                </td>
                 <td>
                    <?= $rowkq['receipt_code']?>
                </td>
                <td>
                    <?= date('d/m/Y',strtotime($rowkq['time']))?>
                </td>
                <td>
                    <?= number_format($rowkq['amount'],0)?>
                </td>

                <td>
                    <?php
                    $idMethod = explode("\"",$rowkq['method'])[1];
                    $kq1 = $conn->query("SELECT value FROM ost_list_items where id = $idMethod");
                    $rowkq1 = $kq1->fetch_assoc();
                    echo $rowkq1['value'];
                    ?>
                </td>
                <td>
                    <?php
                    $idMethod = explode("\"",$rowkq['agent'])[1];
                    $kq1 = $conn->query("SELECT value FROM ost_list_items where id = $idMethod");
                    $rowkq1 = $kq1->fetch_assoc();
                    echo $rowkq1['value'];
                    ?>
                </td>
                <td>
                    <?php
                    $idMethod = explode("\"",$rowkq['income_type'])[1];
                    $kq1 = $conn->query("SELECT value FROM ost_list_items where id = $idMethod");
                    $rowkq1 = $kq1->fetch_assoc();
                    echo $rowkq1['value'];
                    ?>
                </td>
                <td>
                    <?= $rowkq['quantity']?>
                </td>
                <td>
                    <?= $rowkq['note']?>
                </td>
            </tr>

        <?php
            }
        ?>
    </tbody>

</table>

<?php
require_once(STAFFINC_DIR.'footer.inc.php');
?>