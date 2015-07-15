<?php foreach($data as $client):?>
<h4 class="font-green-sharp"><?php echo $client['customer']['customerName']; ?> (<?php echo $client['customer']['customerId'];?>)</h4>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">訂單日期</th>
            <th width="20%">訂單編號</th>
            <th width="20%">借方</th>
            <th with="20%">已付</th>
            <th width="20%">累計未清付金額</th>
        </tr>
    </thead>
    <tbody>
            <?php foreach($client['breakdown'] as $b):?> 
                <tr>
                    <td>
                        <?php echo date("Y-m-d", $b['invoiceDate']); ?>
                    </td>
                    <td> 
                        <?php echo $b['invoice']; ?>
                    </td>
                    <td>
                        HK$<?php echo number_format($b['invoiceAmount']); ?>
                    </td>
                    <td>
                        HK$<?php echo number_format($b['paid']); ?>
                    </td>

                    <td>
                        HK$<?php echo number_format($b['accumulator']); ?> 
                    </td>
                </tr>  
            <?php endforeach; ?> 
            <tr>
                <td colspan="5" style="text-align:right;">
                    <span style="font-weight:bold;font-size:15px;">總計: HK$<?php
                       $count = sizeof($client['breakdown']);
                        echo $client['breakdown'][$count-1]['accumulator']; ?></span>
                </td>
            <tr>
    </tbody>
</table>
<?php endforeach; ?> 