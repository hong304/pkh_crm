<h3 class="font-red-sunglo">日結列表</h3>
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
        //pd($data);
        if(count($data['items']>0))
        foreach($data['items'] as $ffbu):?>
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
        <tr>


            <?php
            //pd($data);
            if(count($data['returnitems']>0))
            ?><tr><td colspan="3"></td></tr><?php
            foreach($data['returnitems'] as $ffbu):?>
            <?php foreach($ffbu as $ff): ?>
            <tr>
                <td>
                    <?php echo $ff['productId'];?>
                </td>
                <td>
                    <?php echo $ff['name'];?>
                </td>
                <td>
                    <?php echo '-'.$ff['counts'];?> <?php echo $ff['unit_txt'];?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <tr>

            <td colspan="2" style="text-align:right;border-right-style:none;">
                <span style="font-weight:bold;font-size:15px;">現金總數:<br />
                月結總數:</span>
            </td>

            <td style="text-align:left;">
                <span style="font-weight:bold;font-size:15px;"><?php echo number_format($data['countcod']); ?>單 $<?php echo number_format($data['sumcod'], 2, '.', ','); ?>
                <br />
                    <?php echo number_format($data['countcredit']); ?>單 $<?php echo number_format($data['sumcredit'], 2, '.', ','); ?>
                </span>
            </td>
        <tr>
    </tbody>
</table>