<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Js Tree Implementing</title>
        <meta name="viewport" content="width=device-width" />
        <link href="node_modules/font-awesome/css/font-awesome.min.css" media="all" rel="stylesheet">
        <link href="dist/css/styles.css" media="all" rel="stylesheet">
        <script>
            var app = {
                appUrl : "<?php echo APP_URL; ?>",
                ajaxUrl : "<?php echo AJAX_URL; ?>",
            }
        </script>
        <?php ?>
    </head>
    <body>
        <div id="container" role="main">
            <div id="tree"></div>
            <div id="data">
                <div class="content code" style="display:none;"><textarea id="code" readonly="readonly"></textarea></div>
                <div class="content folder" style="display:none;"></div>
                <div class="content image" style="display:none; position:relative;"><img src="" alt="" style="display:block; position:absolute; left:50%; top:50%; padding:0; max-height:90%; max-width:90%;" /></div>
                <div class="content default" style="text-align:center;">Select a node from the tree.</div>
            </div>
        </div>
    </body>
    <script src="node_modules/requirejs/require.js"></script>
    <script async="async" src="dist/js/init.js"></script>
</html>


<?php
