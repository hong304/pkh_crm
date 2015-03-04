<?php if(count($data['1F9F']>0)) foreach($data['1F9F'] as $nf):?>
<h4 class="font-green-sharp"><?php echo $nf['customerInfo']['customerName_eng']; ?> (<?php echo $nf['customerInfo']['customerId'];?>)</h4>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">
                貨品編號
            </th>
            <th width="60%">
                貨品名稱
            </th>
            <th width="20%"> 
                數量
            </th>
        </tr>
    </thead>
    <tbody>
            <?php foreach($nf['items'] as $itembu):?> 
                <?php foreach($itembu as $item):?>
                <tr>
                    <td>
                        <?php echo $item['productId'];?>
                    </td>
                    <td> 
                        <?php echo $item['name'];?>
                    </td>
                    <td>
                        <?php echo $item['counts'];?> <?php echo $item['unit_txt'];?> 
                    </td>
                </tr>  
                <?php endforeach; ?>
            <?php endforeach; ?> 
            <tr>
                <td colspan="3" style="text-align:right;">
                    <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo number_format($nf['totalAmount']); ?></span>
                </td>
            <tr>
    </tbody>
</table>
<?php endforeach; ?> 