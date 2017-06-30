<?php
$arrayLength = sizeof($array);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Table</title>
</head>
<body>
<table border="1">
    <tbody>
    <?php for($cols = 7, $i = 0; $i < ceil($arrayLength / $cols); $i++) : ?>
        <tr>
            <?php for($j = 0; $j < $cols; $j++) : ?>
                <td><?php echo $array[$i * $cols + $j] ?></td>
            <?php endfor; ?>
        </tr>
    <?php endfor; ?>
    </tbody>
</table>
</body>
</html>
