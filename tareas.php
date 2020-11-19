<?php header('Content-Type: text/html; charset=UTF-8');?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0" />
    <title>Aqualyt - Coordinador</title>
    <?php include 'styles.php';?>
</head>

<body class="bodyNoOverflow">
    <?php 
    include 'urlAPI.php';
    include 'checkToken.php';

    if ((!isset($_COOKIE["AquaCoordinadorToken"])) || (checkToken($urlAPI)->codigo==200)) {
        header("Refresh:0; url=index.php");

} else {?>
    <div class="navbar-fixed">
        <nav class="red darken-3" role="navigation">
            <div class="nav-wrapper">
                <a id="logo-container" href="index.php" class="brand-logo">
                    <img src="imgs/logo_negativo.png">
                </a>
                <a href="#" data-activates="mobileMenu" class="button-collapse"><i class="material-icons">menu</i></a>
                <ul class="right hide-on-med-and-down">
                    <li>
                        <a href="index.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar Equipos</a>
                    </li>
                    <li>
                        <a href="coordinadorPS.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar PS</a>
                    </li>
                    <li>
                        <a href="index.php?logout=1">
                            <i class="material-icons left">exit_to_app</i><?php echo $_COOKIE['AquaCoordinadorTokenNOMBRE']; ?></a>
                    </li>
                </ul>
                <ul class="side-nav" id="mobileMenu">
                    <li>
                        <a href="index.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar Equipos</a>
                    </li>
                    <li>
                        <a href="coordinadorPS.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar PS</a>
                    </li>
                    <li>
                        <a href="index.php?logout=1">
                            <i class="material-icons left">exit_to_app</i><?php echo $_COOKIE['AquaCoordinadorTokenNOMBRE']; ?></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <main>
        <div class="section">
            <div class="row">
                <div class="col s12" id="tareasList">
                    <div class="progress" id="loadingEquipos">
                        <div class="indeterminate"></div>
                    </div>
                    <ul class="collection z-depth-2 optiscroll columnHeight" id="tareasListCol">
                    </ul>
                </div>
            </div>
        </div>
        <br>
        <br>
    </main>
    <?php include 'scripts.php';
    } ?>
</body>

</html>