<?php $assetBase = $assetBase ?? ''; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($assetBase); ?>img/favicon.png">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Pagina de turnos en PHP"; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetBase); ?>css/fullcalendar.min.css">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://kit.fontawesome.com/7f3ec427f5.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetBase); ?>css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetBase); ?>css/home.css">
</head>

<body>
