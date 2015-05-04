<?php
$current_year = date('Y');
$last_year = date('Y')-1;
?>
<?php
if ($data[13] == null){
    echo "No Data";
    die;
}
?>
<h3 class="font-red-sunglo"><?=$data[13][$current_year]['product_name']?> (<?=$data[13][$current_year]['product_id']?>)</h3>

<table class="table table-bordered table-hover" style="text-align: right">
    <thead>
        <tr role="row" class="heading">
            <th width="8%">
                月份
            </th>

            <th width="20%">
                <?=$last_year?> 銷售
            </th>
            <th width="10%">
                <?=$last_year?> 數量
            </th>
            <th width="10%">
                <?=$last_year?> 單價
            </th>
<th width="5%"></th>
            <th width="20%">
                <?=$current_year?> 銷售
            </th>
            <th width="10%">
                <?=$current_year?> 數量
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

            <?php if( isset($ff[$last_year]) && $ff[$last_year]['qty'] > 0){?>
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
                        echo "<span style='color:red'>".number_format($ff[$last_year]['qty'])."</span>";
                    else
                        echo number_format($ff[$last_year]['qty']);?>
                </td>
                <td>     <?php
                    if($data[13][$last_year]['highest_single'] == $ff[$last_year]['amount']/$ff[$last_year]['qty'])
                        echo "<span style='color:red'>HK$ ".number_format($ff[$last_year]['amount']/$ff[$last_year]['qty'], 2, '.', ',')."</span>";
                    else
                        echo "HK$ ". number_format($ff[$last_year]['amount']/$ff[$last_year]['qty'], 2, '.', ',');?>
                </td>
            <?php }else{?>

                <td>HK$ 0</td>
                <td>0</td>
                <td>HK$ 0</td>
            <?php }?>
            <td></td>
            <?php if( isset($ff[$current_year]) && $ff[$current_year]['qty'] > 0){?>
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
                        echo "<span style='color:red'>".number_format($ff[$current_year]['qty'])."</span>";
                    else
                        echo number_format($ff[$current_year]['qty']);?>
                </td>
                <td>       <?php
                    if($data[13][$current_year]['highest_single'] == $ff[$current_year]['amount']/$ff[$current_year]['qty'] and $k !=13)
                        echo "<span style='color:red'>HK$ ".number_format($ff[$current_year]['amount']/$ff[$current_year]['qty'], 2, '.', ',')."</span>";
                    else
                        echo "HK$ ". number_format($ff[$current_year]['amount']/$ff[$current_year]['qty'], 2, '.', ',');?></td>

            <?php }else if ($k <= date('n')){?>
                <td>HK$ 0</td><td>0</td><td>HK$ 0</td>
            <?php }?>
        </tr>


    <?php }?>
    </tbody>
</table>
<br/>