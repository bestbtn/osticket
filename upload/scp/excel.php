
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
if(isset($_POST["export"]))
{
 $sql = "SELECT * FROM  payment_tmp  ORDER BY ticket_id DESC";
        $result = $conn->query($sql) ;
        if(!$result) die('co loi');

        //Payment::getListPayment();
        $i = 0;

?>

<table class="list" border="0" cellspacing="1" cellpadding="0" width="940" border="1px">
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

        while ($rowkq = $result->fetch_array()) {
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
                <?php
                ob_start();
                ?>
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
ob_end_flush();
  header('Content-Type: application/xls');
  header('Content-Disposition: attachment; filename=download.xls');

}
?>