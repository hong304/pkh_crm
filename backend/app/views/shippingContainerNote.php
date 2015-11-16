<style>
    td
    {
        border-style: groove;
    }
    
    #shipmentSch
    {
        overflow-y:auto;
    }
    
    .adjustWidth
    {
        width:60px;
    }
</style>
<table>
<tr>
    <td></td><td colspan="29"><?php echo $startToEnd[0]. "     To     ". $startToEnd[1]; ?></td>
</tr>
<tr>
    <td>
        日期:
    </td>
<?php

    foreach($daterange as $k=>$v)
    {
        $now = date("Y-m-d");
        $date = date("m-d",strtotime($v));
        
        if($now == $v)
        {
            echo "<td class = 'adjustWidth' style='background-color:rgb(204, 255, 204);'>Today</td>";
        }else
        {
            echo "<td class = 'adjustWidth'>" . $date . "</td>" ;
        } 
    }
    
?>
</tr>
</table>