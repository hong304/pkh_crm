<?php if(count($data>0)) foreach($data as $k=> $nf){
    foreach($nf as $z) {
    ?>
<h4 class="font-green-sharp"><?php echo $k; ?> (<?php echo isset($z['name'])?$z['name']:'';?>) [<?php echo isset($z['unit'])?$z['unit']:'';?>]</h4>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">
                客戶編號
            </th>
            <th width="30%">
                客戶名稱
            </th>
            <th width="15%">
                訂單編號
            </th>
            <th width="10%">
                送貨日期
            </th>
            <th width="10%">
                數量
            </th>
            <th width="10%">
                價格
            </th>
        </tr>
    </thead>
    <tbody>

     <?php   foreach ($z['invoice'] as $item) {
            ?>

            <tr>
                <td>
                    <?php echo $item['customerId'];?>
                </td>
                <td>
                    <?php echo $item['name'];?>
                </td>
                <td>
                    <?php echo $item['invoiceNumber'];?>
                </td>
                <td>
                    <?php echo $item['invoiceDate'];?>
                </td>
                <td>
                    <?php echo $item['qty'];?>
                </td>
                <td>
                    <?php echo $item['price'];?>
                </td>

            </tr>

        <?php
    }?>

            <tr>
                <td colspan="6" style="text-align:right;">
                    <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo number_format($z['amount'],2,'.',','); ?>, <?php echo number_format($z['amount_qty'],0,'.',','); echo $z['unit']?></span>
                </td>
            <tr>
    </tbody>
</table>
<?php }}