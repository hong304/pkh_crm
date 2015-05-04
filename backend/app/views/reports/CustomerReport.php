<?php
$current_year = date('Y');
$last_year = date('Y')-1;

?>

<div class="row">

    <?php
    if ($data[13] == null){
        echo " <div class='col-md-12'>No Data!!</div>";
        die;
    }
    ?>

<h3 class="font-red-sunglo" style="display: inline-block;margin-left: 15px;"><?=$data[13][$current_year]['product_name']?> (<?=$data[13][$current_year]['product_id']?>)</h3> <span style="font-size: 15px"><?=$data[13][$current_year]['address']?> (<?=$data[13][$current_year]['contact']?>)</span>

    <div class="col-md-9">
            <table class="table table-bordered table-hover" style="text-align: right">
                <thead>
                <tr role="row" class="heading">
                    <th width="5%">
                        月份
                    </th>

                    <th width="12%">
                        <?=$last_year?> 銷售
                    </th>
                    <th width="10%">
                        <?=$last_year?> 發票量
                    </th>
                    <th width="10%">
                        <?=$last_year?> 單價
                    </th>
                    <th width="5%"></th>
                    <th width="12%">
                        <?=$current_year?> 銷售
                    </th>
                    <th width="10%">
                        <?=$current_year?> 發票量
                    </th>
                    <th width="10%">
                        <?=$current_year?> 單價
                    </th>

                </tr>
                </thead>
                <tbody>
                <?php
                foreach($data as $k=>$ff){?>

                    <tr <?=($k==13)?"style='font-weight:bold;'":'';?>>

                        <td>
                            <?php echo ($k != '13')?$k:'';?>
                        </td>

                        <?php if( isset($ff[$last_year])  && $ff[$last_year]['qty']  > 0){?>
                            <td>
                                <?php
                                if($data[13][$last_year]['highest_amount'] == $ff[$last_year]['amount'])
                                    echo "<span style='color:red'>HK$ ".number_format($ff[$last_year]['amount'])."</span>";
                                else
                                    echo "HK$ ".number_format($ff[$last_year]['amount']);?>
                            </td>
                            <td>
                                <?php
                                if($data[13][$last_year]['highest_qty'] == $ff[$last_year]['qty'])
                                    echo "<span style='color:red'>".$ff[$last_year]['qty']."</span>";
                                else
                                    echo number_format($ff[$last_year]['qty']);?>
                            </td>
                            <td>     <?php
                                if($data[13][$last_year]['highest_single'] == $ff[$last_year]['amount']/$ff[$last_year]['qty'])
                                    echo "<span style='color:red'>HK$ ".number_format($ff[$last_year]['amount']/$ff[$last_year]['qty'])."</span>";
                                else
                                    echo "HK$ ". number_format($ff[$last_year]['amount']/$ff[$last_year]['qty'], 2, '.', ',');?>
                            </td>
                        <?php }else{?>

                            <td>HK$ 0</td>
                            <td>0</td>
                            <td>HK$ 0</td>
                        <?php }?>
                        <td></td>
                        <?php if( isset($ff[$current_year]) && $ff[$current_year]['qty']  > 0){
                           // p($ff[$current_year]);
                            ?>
                            <td>
                                <?php
                                if($data[13][$current_year]['highest_amount'] == $ff[$current_year]['amount'] and $k !=13)
                                    echo "<span style='color:red'>HK$ ".number_format($ff[$current_year]['amount'])."</span>";
            else
                                    echo "HK$ ".number_format($ff[$current_year]['amount']);?>
                            </td>
                            <td>
                                <?php
                                if($data[13][$current_year]['highest_qty'] == $ff[$current_year]['qty'] and $k !=13)
                                    echo "<span style='color:red'>".$ff[$current_year]['qty']."</span>";
                                else
                                    echo number_format($ff[$current_year]['qty']);?>
                            </td>
                            <td>       <?php
                                if($data[13][$current_year]['highest_single'] == $ff[$current_year]['amount']/$ff[$current_year]['qty'] and $k !=13)
                                    echo "<span style='color:red'>HK$ ".number_format($ff[$current_year]['amount']/$ff[$current_year]['qty'])."</span>";
                                else
                                    echo "HK$ ". number_format($ff[$current_year]['amount']/$ff[$current_year]['qty'], 2, '.', ',');?></td>

                        <?php }else if ($k <= date('n')){?>
                            <td>HK$ 0</td><td>0</td><td>HK$ 0</td>
                        <?php }?>
                    </tr>


                <?php }?>
                </tbody>
            </table>

 </div>
    <div class="col-md-3">
        <table class="table table-bordered table-hover" style="text-align: right">
            <tr role="row" class="heading">
                <th colspan="2">
                    客戶搞要
                </th>
            </tr>
            <tr><td>
                   建立日期
                </td>
                <td><?=$data[13][$current_year]['craete_date']?></td></tr>
            <tr><td>
                    上次銷售
                </td>
                <td><?=$data[13][$current_year]['last_time']?></td></tr>
            <tr><td>
                    銷售距今
                </td>
                <td><?=$data[13][$current_year]['last_to_now']?></td></tr>
            <tr><td>
                    銷售員
                </td>
                <td><?=$data[13][$current_year]['saleman']?></td></tr>
            <tr><td>
                    區域
                </td>
                <td><?=$data[13][$current_year]['area']?></td></tr>
            <tr><td>
                    路線
                </td>
                <td><?=$data[13][$current_year]['area_id']?></td></tr>
            <tr><td>
                    付款條件
                </td>
                <td><?=$data[13][$current_year]['paymentTerm']?></td></tr>
            </table>

    </div>

</div>