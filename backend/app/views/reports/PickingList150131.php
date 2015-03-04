<h3 class="font-red-sunglo">一樓備貨單</h3>
<table class="table table-bordered table-hover">
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
        <?php
        if(count($data['1F']>0)) 
        foreach($data['1F'] as $ffbu):?>
            <?php foreach($ffbu as $ff): ?>
            <tr>
                <td>
                    <?php echo $ff['productId'];?>
                </td>
                <td> 
                    <?php echo $ff['name'];?>
                </td> 
                <td>
                    <?php echo $ff['counts'];?> <?php echo $ff['unit_txt'];?> 
                </td> 
            </tr>  
            <?php endforeach; ?> 
        <?php endforeach; ?> 
    </tbody>
</table>
<br/>

<h3 class="font-red-sunglo">九樓備貨單</h3>
<?php if(count($data['9F']>0)) foreach($data['9F'] as $nf):?>
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
        
    </tbody>
</table>
<?php endforeach; ?> 