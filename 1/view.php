<?php
$arrayLength = sizeof($array);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Table</title>
    <style>
        td {
            min-width: 16px;
        }
    </style>
</head>
<body>
<?php if($arrayLength === 0) : ?>
    <p>Array is empty</p>
<?php else : ?>
    <table border="1">
        <tbody>
        <?php for($cols = 7, $i = 0; $i < ceil($arrayLength / $cols); $i++) : ?>
            <tr>
                <?php for($j = 0; $j < $cols; $j++) : ?>
                    <?php // print value if it exists ?>
                    <td><?php echo isset($array[$i * $cols + $j]) ? $array[$i * $cols + $j] : '&nbsp;'; ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
