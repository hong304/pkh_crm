<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="15%">訂單</th>
            <th width="15%">送貨日期</th>
            <th width="30%">客戶</th>
            <th width="20%">應收金額</th>
            <th width="20%">累計</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['invoiceNumber']; ?></td>
                <td><?php echo $row['deliveryDate']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td>HK$ <?php echo $row['amount']; ?></td>
                <td>HK$ <?php echo $row['accumulator']; ?></td>
            </tr>  
         <?php endforeach; ?>.
         <tr>
            <td colspan="5" style="text-align:right;">
                <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($data)['accumulator']; ?></span>
            </td>
         <tr>
    </tbody>
</table>

