<?php if(count($data['1F9F']>0)) foreach($data['1F9F'] as $nf):?>
<h4 class="font-green-sharp"><?php echo $nf['customerInfo']['customerName_eng']; ?> (<?php echo $nf['customerInfo']['customerId'];?>)</h4>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">
                貨品編號
            </th>
            <th width="40%">
                貨品名稱
            </th>
            <th width="20%"> 
                數量
            </th>
            <th width="10%">
                售價
            </th>
            <th width="10%">
                總數
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
                    <td>
                        <?php echo '$'.$item['itemPrice'];?>
                    </td>
                    <td>
                        <?php echo '$'.round($item['itemPrice']*$item['counts']*(100-$item['discount'])/100,2);?>
                    </td>
                </tr>  
                <?php endforeach; ?>
            <?php endforeach; ?>

<?php if(isset($nf['returnitems'])){?>
    <tr><td colspan="3"></td></tr>
            <?php foreach($nf['returnitems'] as $itembu){?>
                <?php foreach($itembu as $item){?>
                    <tr>
                        <td>
                            <?php echo $item['productId'];?>
                        </td>
                        <td>
                            <?php echo $item['name'];?>
                        </td>
                        <td>
                            <?php echo '-'.$item['counts'];?> <?php echo $item['unit_txt'];?>
                        </td>
                        <td>
                            <?php echo '$'.$item['itemPrice'];?>
                        </td>
                        <td>
                            <?php echo '$'.round($item['itemPrice']*$item['counts']*(100-$item['discount'])/100,2);?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
<?php }?>

            <tr>
                <td colspan="5" style="text-align:right;">
                    <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo number_format($nf['totalAmount'],2,'.',','); ?></span>
                </td>
            <tr>
    </tbody>
</table>
<?php endforeach; ?> 