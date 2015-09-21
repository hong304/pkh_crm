<?php //print_r($daterange)    ?>
<table border ="1">  

    <?php
    if (isset($other)) {
        echo '<tr style="position: absolute;left: 35px;top: 81px;"> <td rowspan="1" style="text-align:center;font-size:15px;width: 295px;">每天總數:</td>';
        foreach ($date as $a) {
            if (isset($other[$a])) {
                echo '<td style="text-align:center;padding:10px;">' . $other[$a]['storeAll'] . ' / ' . $other[$a]['storeAllData'] . '</td>';
            } else {
                echo '<td>&nbsp;</td>';
            }
        }
    }
    ?>
</tr>
<tr style="position: absolute;left: 35px;top: 113px;">
    <td rowspan="1" style="text-align:center;font-size:15px;width: 295px;">日期:</td>
    <?php
    $totalDate = 0;
    foreach ($date as $k) {
        if ($k == date("Y-m-d"))
            echo '<td style="padding:5px;background-color:#CCFFCC;font-size:17px;">Today</td>';
        else {
            $pos = strpos($k, '-') + 1;
            $new = substr($k, $pos);
            echo '<td style="padding:5px;font-size:20px;">' . $new . '</td>';
        }

        $totalDate++;
    }
    ?>
</tr>

<?php

function compareArray($date, $range) {

    if (isset($date) && isset($range)) {
        unset($range['actualDate']);
        for ($k = 0; $k < count($date); $k++) {
            if (in_array($date[$k], $range)) {
                return true;  //If the selected date range exists in the fsp date , then no need to do 
            }
        }
        return false;
    }
}

function checkActual($date, $actualDay) {
    if (isset($date) && isset($actualDay)) {
        return in_array($actualDay, $date);
    }
}

function formatDate($day)
{
    if(isset($day))
    {
        $pos = strpos($day, '-') + 1;
        $new = substr($day, $pos);
        return $new;
    }
}
$flag = "false";
if (isset($daterange)) {
    foreach ($daterange as $a => $b) {
        if (compareArray($date, $daterange[$a]) && !checkActual($date, $b['actualDate'])) {
            echo "<tr>";
            echo '<td style="padding:5px;width:295px;cursor:pointer;font-size:15px;word-wrap: break-word;"  onclick="clickShip(\'' . $a . '\')"> '.$b['supplier'].'<br/>船務編號:' . $a .'</td>';
            $flag = "true";
            $storeprint = 0;
            for ($h = 0; $h < count($daterange[$a]) - 2; $h++) {
                if (checkActual($date, $daterange[$a][$h])) {
                    echo "<td style='padding:5px;background-color:grey;color:white;text-align:center;'>" . formatDate($daterange[$a][count($daterange[$a])-3]) . "</td>";
                    $storeprint++;
                }
            }
            for ($d = 0; $d < count($date) - $storeprint; $d++) {
                echo "<td style='padding:5px;'></td>";
            }
        }
        echo "</tr>";
    }
}

if (isset($data)) {
    foreach ($data as $k => $v) {
        echo "<tr>";
        echo '<td style="padding:5px;width:295px;cursor:pointer;font-size:15px;word-wrap: break-word;"  onclick="clickShip(\'' . $k . '\')">' . array_values($v)[0]['supplier']  . '<br/>船務編號:' . $k .'</td>';
        $count = 0; // clear count when next record enter this loop
        $flag = true;

        // date('Y-m-d',$date1);
        while (count($date) > $count) {
            if (isset($v[$date[$count]])) {
                if ($v[$date[$count]]['mode'] == 'actual') {
                    $f = $v[$date[$count]]['receive'];
                    $addValue = explode("+", $f);
                    $totalAdd = (int)$addValue[0] + (int)$addValue[1];
                    if ($totalAdd == $v[$date[$count]]['no'])
                        echo "<td style='padding:5px;background-color:green;color:white;text-align:center;'>" . $v[$date[$count]]['receive'] . " / " . $v[$date[$count]]['no'] . "</td>";
                    else
                        echo "<td style='padding:5px;background-color:yellow;color:black;text-align:center;'>" . $v[$date[$count]]['receive'] . " / " . $v[$date[$count]]['no'] . "</td>";
                    if ($count !== 14) {
                        for ($i = 0; $i < $v[$date[$count]]['fsp']; $i++) {
                            if ($count + $i < 14) {
                                $first_day = $date[$count];
                                $last_day = date('Y-m-d', strtotime($first_day) + 24 *60 * 60* $v[$date[$count]]['fsp']);
                                echo "<td style='padding:5px;background-color:grey;color:white;text-align:center;'>" . formatDate($last_day) . "</td>";
                            }
                        }
                        $count = $count + $v[$date[$count]]['fsp'];
                    }
                } else if ($v[$date[$count]]['mode'] == 'eta') {
                    echo "<td style='padding:5px;background-color:red;color:white;text-align:center;'>0+0 / " . $v[$date[$count]]['no'] . "</td>";
                }
            } else {
                echo "<td style='padding:5px;'></td>";
            }
            $count++;
        }
        echo "</tr>";
    }
} 

if(!isset($data) && $flag == "false"){
    echo "<span style='font-size:20px;display: block;margin-top: 80px;'>" . $date[0] . " 至 " . $date[count($date) - 1] . "沒有船務紀錄</span>";
}
?>

</table>
