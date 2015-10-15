<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="4">已收款項</th>
    </tr>
    </thead>

    <thead>
        <tr role="row" class="heading">
            <th width="20%">訂單</th>
            <th width="50%">客戶</th>
            <th width="15%">已收金額</th>
            <th width="15%">累計</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['invoiceNumber']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td>HK$ <?php echo $row['amount']; ?></td>
                <td>HK$ <?php echo $row['accumulator']; ?></td>
            </tr>  
         <?php endforeach; ?>.
         <tr>
            <td colspan="4" style="text-align:right;">
                <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($data)['accumulator']; ?></span>
            </td>
         <tr>
    </tbody>
</table>



<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="5">補收款項</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="20%">訂單</th>
        <th width="40%">客戶</th>
        <th width="10%">送貨日期</th>
        <th width="15%">收回金額</th>
        <th width="15%">累計</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($paidInvoice as $row):?>
        <tr>
            <td><?php echo $row['invoiceNumber']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['deliveryDate']; ?></td>
            <td>HK$ <?php echo $row['amount']; ?></td>
            <td>HK$ <?php echo $row['accumulator']; ?></td>
        </tr>
    <?php endforeach; ?>.
    <tr>
        <td colspan="5" style="text-align:right;">
            <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($paidInvoice)['accumulator']; ?></span>
        </td>
    <tr>
    </tbody>
</table>


<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="6">支票</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="10%">訂單</th>
        <th width="40%">客戶</th>
        <th width="10%">支票號碼</th>
        <th width="10%">送貨日期</th>
        <th width="15%">收回金額</th>
        <th width="15%">累計</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($paidInvoiceCheque as $row):?>
        <tr>
            <td><?php echo $row['invoiceNumber']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['chequeNo']; ?></td>
            <td><?php echo $row['deliveryDate']; ?></td>
            <td>HK$ <?php echo $row['amount']; ?></td>
            <td>HK$ <?php echo $row['accumulator']; ?></td>
        </tr>
    <?php endforeach; ?>.
    <tr>
        <td colspan="6" style="text-align:right;">
            <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($paidInvoiceCheque)['accumulator']; ?></span>
        </td>
    <tr>
    </tbody>
</table>


<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="4">未收款項</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="20%">訂單</th>
        <th width="50%">客戶</th>
        <th width="15%">尚欠金額</th>
        <th width="15%">累計</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($backaccount as $row):?>
        <tr>
            <td><?php echo $row['invoiceNumber']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td>HK$ <?php echo $row['amount']; ?></td>
            <td>HK$ <?php echo $row['accumulator']; ?></td>
        </tr>
    <?php endforeach; ?>.
    <tr>
        <td colspan="4" style="text-align:right;">
            <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($backaccount)['accumulator']; ?></span>
        </td>
    <tr>
    </tbody>
</table>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="4">支出款項</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="25%">停車場</th>
        <th width="25%">隧道</th>
        <th width="25%">採購貨品</th>
        <th width="25%">雜費</th>
    </tr>
    </thead>
    <tbody>

        <tr>
            <td>HK$ <?php echo $expenses['cost1']; ?></td>
            <td>HK$ <?php echo $expenses['cost2']; ?></td>
            <td>HK$ <?php echo $expenses['cost3']; ?></td>
            <td>HK$ <?php echo $expenses['cost4']; ?></td>
        </tr>

    <tr>
        <td colspan="4" style="text-align:right;">
            <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo $expenses['amount']; ?></span>
        </td>
    <tr>
    </tbody>
</table>