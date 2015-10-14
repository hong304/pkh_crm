<style>
.dateFormat
{
    width: 130px;
}
</style>
<?php 
//print_r($shipTableNote);
//echo "<br/>";
//print_r($shipTable);
//echo "<br/>";
//print_r($outputAad);
//echo "<br/>";
//print_r($eta);
//echo "<br/>";
//print_r($createweek);

?>
<table>  
<?php

$translate = array("last_last_week"=>"以往","last_week"=>"上星期","this_week"=>"今星期","next_week"=>"下星期");
function formatDate($date)
{
    $splitDate = explode("-",$date);
    $stringdate = $splitDate[1]."-".$splitDate[2];
    return $stringdate;
}
if (isset($createweek)) {
    echo "<tr><td></td>";
    foreach($createweek as $weekdayK=>$weekdayV)
    {
        if($weekdayK !== "last_last_week")
        {
            echo "<td class = 'dateFormat' style='text-align:center;text-decoration: underline;'>".formatDate($weekdayV[1])." 至 ".formatDate($weekdayV[0])."</td>";
        }
        else
        {
             echo "<td class = 'dateFormat' style='text-align:center;text-decoration: underline;'><".formatDate($weekdayV[0])."或之前</td>";
        }
    }
    echo "</tr>";
}
?>
</tr>
<tr>
    <?php
        echo "<td style='width: 86px;'>實際到港日期</td>";
        if(isset($outputAad))
        {
            foreach($outputAad as $key=>$value)
            {
                echo "<td style='text-align:center'>".$value,"</td>";
            }
        }else
        {
            for($i = 0;$i<4;$i++)
            {
                echo "<td style='text-align:center'>0</td>";
            }
        }
    ?>
</tr>
<tr>
    <?php
        echo "<td style='width: 86px;'>預期到港日期</td>";
        if(isset($eta))
        {
            foreach($eta as $k=>$v)
            {
                echo "<td style='text-align:center'>".$v."</td>";
            }
        }else
        {
            for($i = 0;$i<4;$i++)
            {
                echo "<td style='text-align:center'>0</td>";
            }
        }
       
    ?>
</tr>
</table>