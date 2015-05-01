
<table class="table table-bordered table-hover">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">
                貨品編號
            </th>
            <th width="60%">
                貨品名稱
            </th>
            <th width="10%">
                數量
            </th>
            <th width="10%">
                總數
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
                <td>
                    <input type="text" id="<?=$ff['productId']?>"
                </td>
            </tr>  
            <?php endforeach; ?> 
        <?php endforeach; ?> 
    </tbody>
</table>


