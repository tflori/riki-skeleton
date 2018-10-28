<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?= $status ?> - <?= $title ?></title>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-rc.2/css/materialize.min.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>
    <body>
        <div class="container">
            <h4><?= $title ?></h4>
            <p><?= $message ?></p>
            <?php if ($exception instanceof Exception) : ?>
                <p>
                    <?= get_class($exception); ?>:
                    <?= $exception->getMessage() ?>
                    (<?= $exception->getFile() ?>:<?= $exception->getLine() ?>)
                </p>
                <p>Please add this to an error report:</p><!-- maybe add encryption here -->
                <pre><?= chunk_split(base64_encode($exception->__toString())) ?></pre>
            <?php endif; ?>
        </div>

        <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-rc.2/js/materialize.min.js"></script>
    </body>
</html>
