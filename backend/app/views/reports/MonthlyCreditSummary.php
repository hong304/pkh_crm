<?php foreach($data as $client):?>
<h4 class="font-green-sharp"><?php echo $client['customer']['customerName']; ?> (<?php echo $client['customer']['customerId'];?>)</h4>

<table class="table table-bordered table-hover" style="font-size:15px;margin-bottom:0px">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">訂單日期</th>
            <th width="20%">訂單編號</th>
            <th width="20%">借方</th>
            <th with="20%">貸方</th>
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
                        HK$<?php echo number_format($b['invoiceAmount'],2,'.',','); ?>
                    </td>
                    <td>
                        HK$<?php echo number_format($b['paid'],2,'.',','); ?>
                    </td>

                    <td>
                        HK$<?php echo number_format($b['accumulator'],2,'.',',');?>
                    </td>
                </tr>  
            <?php endforeach; ?> 
            <tr>
                <td colspan="2" style="text-align:right;">
                </td>
                <td style="text-align:left;">
                    <span style="font-weight:bold;font-size:15px;">HK$<?php
                       $count = sizeof($client['breakdown']);
                        echo number_format($client['breakdown'][$count-1]['accInvoiceAmount'],2,'.',','); ?></span>
                </td>
                <td  style="text-align:right;"></td>

                <td style="text-align:left;">
                    <span style="font-weight:bold;font-size:15px;">HK$<?php
                        $count = sizeof($client['breakdown']);
                        echo number_format($client['breakdown'][$count-1]['accumulator'],2,'.',','); ?></span>
                </td>
            <tr>
    </tbody>
</table>


    <table class="table table-bordered table-hover" style="font-size:15px;">
        <tbody>

             <tr>
                <td colspan="5">
                    The outstanding balance is aged by invoice date as <?php echo $date?> below:

                </td>
            </tr>

             <tr>
                 <td >
                     <?=$month[0]?>
                 </td>
                 <td >
                     <?=$month[1]?>
                 </td>
                 <td >
                     <?=$month[2]?>
                 </td>
                 <td >
                     <?=$month[3]?>
                 </td>
                 <td >
                     <?=$month[4]?>
                 </td>
             </tr>

             <tr>
                 <td >
                     <?php echo '$' . number_format(isset($monthly[$month[0]][$client['customer']['customerId']]) ? end($monthly[$month[0]][$client['customer']['customerId']])['accumulator'] : 0, 2, '.', ',')?>
                 </td>
                 <td >
                     <?php echo '$' . number_format(isset($monthly[$month[1]][$client['customer']['customerId']]) ? end($monthly[$month[0]][$client['customer']['customerId']])['accumulator'] : 0, 2, '.', ',')?>
                 </td>
                 <td >
                     <?php echo '$' . number_format(isset($monthly[$month[2]][$client['customer']['customerId']]) ? end($monthly[$month[0]][$client['customer']['customerId']])['accumulator'] : 0, 2, '.', ',')?>
                 </td>
                 <td >
                     <?php echo '$' . number_format(isset($monthly[$month[3]][$client['customer']['customerId']]) ? end($monthly[$month[0]][$client['customer']['customerId']])['accumulator'] : 0, 2, '.', ',')?>
                 </td>
                 <td >
                     <?php echo '$' . number_format(isset($monthly[$month[4]][$client['customer']['customerId']]) ? end($monthly[$month[0]][$client['customer']['customerId']])['accumulator'] : 0, 2, '.', ',')?>
                 </td>
             </tr>

             <tr>
                 <td colspan="5">
                     Payment received after statement date not included
                 </td>
             </tr>

        </tbody>
    </table>

<?php endforeach; ?> 