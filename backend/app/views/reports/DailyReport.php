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
        foreach($data['items'] as $ff):?>

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
        <tr>


        <tr>

            <td colspan="2" style="text-align:right;border-right-style:none;">
                <span style="font-weight:bold;font-size:15px;">現金總數:<br />
                月結總數:<br />
                現金退貨單:<br />
                現金補貨單:<br />
                現金換貨單:</span>
            </td>

            <td style="text-align:left;">
                <span style="font-weight:bold;font-size:15px;"><?php echo number_format($data['countcod']); ?>單 $<?php echo number_format($data['sumcod'], 2, '.', ','); ?>
                <br />
                    <?php echo number_format($data['countcredit']); ?>單 $<?php echo number_format($data['sumcredit'], 2, '.', ','); ?>
                    <br />
                    <?php echo number_format($data['countcodreturn']); ?>單
                    <br />
                    <?php echo number_format($data['countcodreplace']); ?>單
                       <br />
                    <?php echo number_format($data['countcodreplenishment']); ?>單
                </span>
            </td>
        </tr>
    </tbody>
</table>