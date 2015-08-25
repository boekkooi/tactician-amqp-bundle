<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title></title>
</head>
<body>
    <form method="post">
        <div>
            <label for="direct-name">Name</label>
            <input type="text" name="name" id="direct-name">
        </div>
        <div>
            <input type="submit" name="direct" value="Submit To direct">
            <input type="submit" name="rpc" value="Submit To rpc">
        </div>
    </form>

    <?php if ($result !== null) { ?>
    <p>
        <h1>Result</h1>
        <pre>
            <?php var_dump($result); ?>
        </pre>
    </p>
    <?php } ?>
</body>
</html>
