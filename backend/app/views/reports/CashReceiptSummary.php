<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="6">已收款項</th>
    </tr>
    </thead>

    <thead>
        <tr role="row" class="heading">
            <th width="20%">訂單</th>
            <th width="40%">客戶</th>
            <th width="5%">訂單車區</th>
            <th width="15%">已收金額</th>
            <th width="15%">累計</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($account as $row):?>
            <tr>
                <td><?php echo $row['invoiceNumber']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['zoneId']; ?></td>
                <td>HK$ <?php echo $row['amount']; ?></td>
                <td>HK$ <?php echo $row['accumulator']; ?></td>
            </tr>  
         <?php endforeach; ?>.
         <tr>
            <td colspan="6" style="text-align:right;">
                <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($account)['accumulator']; ?></span>
            </td>
         <tr>
    </tbody>
</table>



<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="6">補收及代收款項</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="20%">訂單</th>
        <th width="40%">客戶</th>
        <th width="5%">訂單車區</th>
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
            <td><?php echo $row['zoneId']; ?></td>
            <td><?php echo $row['deliveryDate']; ?></td>
            <td>HK$ <?php echo $row['amount']; ?></td>
            <td>HK$ <?php echo $row['accumulator']; ?></td>
        </tr>
    <?php endforeach; ?>.
    <tr>
        <td colspan="6" style="text-align:right;">
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
        <th width="100%" colspan="5">支出款項</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="20%">停車場</th>
        <th width="20%">隧道</th>
        <th width="20%">採購貨品</th>
        <th width="20%">雜費</th>
        <th width="20%">司機</th>
    </tr>
    </thead>
    <tbody>

        <tr>
            <td>HK$ <?php echo $expenses['cost1']; ?></td>
            <td>HK$ <?php echo $expenses['cost2']; ?></td>
            <td>HK$ <?php echo $expenses['cost3']; ?></td>
            <td>HK$ <?php echo $expenses['cost4']; ?></td>
            <td>HK$ <?php echo $expenses['cost5']; ?></td>
        </tr>

    <tr>
        <td colspan="5" style="text-align:right;">
            <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo $expenses['amount']; ?></span>
        </td>
    <tr>
    </tbody>
</table>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <tr><td>應收現金:</td><td><?php
            echo sprintf("$%s + $%s - $%s = $%s",number_format(end($account)['accumulator'],2,'.',','),number_format(end($paidInvoice)['accumulator'],2,'.',','), ($expenses['amount']<0)?"(".number_format($expenses['amount']*-1,2,'.',',').")":$expenses['amount'] , number_format(end($paidInvoice)['accumulator']+end($account)['accumulator']-$expenses['amount'],2,'.',','));
            ?></td></tr>

    <tr><td>實收現金:</td><td><?php
            echo sprintf("紙幣:$%s  硬幣:$%s  總數:$%s", number_format($cash,2,'.',','),number_format($coins,2,'.',','), number_format($coins+$cash,2,'.',','));?></td></tr>

    <tr><td><?php echo sprintf('月結單數:%s',$summary['count_credit'])?></td><td><?php echo sprintf('金額:$%s',number_format($summary['amount_credit'],2,'.',','))?></td></tr>
    <tr><td><?php echo sprintf('現金單數:%s',$summary['count_cod'])?></td><td><?php echo sprintf('金額:$%s',number_format($summary['amount_cod'],2,'.',','))?></td></tr>
</table>