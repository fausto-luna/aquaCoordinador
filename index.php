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

    ini_set("allow_url_fopen", 1);

const TOKEN = "AquaCoordinadorToken";
const TOKENNOMBRE = "AquaCoordinadorTokenNOMBRE";
const TOKENID = "AquaCoordinadorTokenID";

function get_content($URL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$errorAcceso = false;

if (isset($_POST['formSent'])) {
    $formSent = $_POST['formSent'];
} else {
    $formSent = false;
}

if((isset($_GET['logout'])) && ($_GET['logout'])){
    setcookie(TOKEN, "0", time() - 1);
    setcookie(TOKENNOMBRE, "0", time() - 1);
    setcookie(TOKENID, "0", time() - 1);
    header("Refresh:0; url=index.php");
}

if ($formSent) {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $url = $urlAPI . '/oficina/auth?acc=' . $usuario . '&pw=' . $password;

    $json = file_get_contents($url);
    $obj = json_decode($json);

    if ($obj->codigo == 100) {
        // la duración de las cookies es de 12 horas (43200 segundos)
        setcookie(TOKEN, $obj->contenido->TOKEN, time() + 43200);
        setcookie(TOKENNOMBRE, $obj->contenido->EMPLEADO_NOMBRE, time() + 43200);
        setcookie(TOKENID, $obj->contenido->EMPLEADO_ID, time() + 43200);
        header("Refresh:0");
    } else {
        $errorAcceso = true;
    }
}

if ((!isset($_COOKIE[TOKEN])) || (checkToken($urlAPI)->codigo==200)) { ?>
    <div class="navbar-fixed">
        <nav class="red darken-3" role="navigation">
            <div class="nav-wrapper">
                <a id="logo-container" href="index.php" class="brand-logo">
                    <img src="imgs/logo_negativo.png">
                </a>
            </div>
        </nav>
    </div>
    <main>
        <div class="section">
            <div class="row">
                <form action="index.php" class="col offset-s3 s6" method="post">
                    <div class="row center-align">
                        <img src="imgs/logo.jpg" alt="">
                    </div>
                    <?php if ($errorAcceso) {?>
                    <div class="row">
                        <div class="col s12">
                            <div class="card-panel red lighten-4">
                                <span class="red-text darken-4">Error de acceso, comprueba el nombre de usuario y contraseña.</span>
                            </div>
                        </div>
                    </div>
                    <?php }?>
                    <div class="row">
                        <div class="input-field col s12">
                        <input id="usuario" name="usuario" type="text" class="validate">
                        <label for="usuario">Usuario</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                        <input id="password" name="password" type="password" class="validate">
                        <label for="password">Password</label>
                        </div>
                    </div>
                    <div class="row">
                    <input type="hidden" name="formSent" value="true">
                    <button class="btn waves-effect waves-light" type="submit" name="action">Entrar
                        <i class="material-icons right">send</i>
                    </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
    <script src="js/materialize.min.js"></script>
<?php } else {  ?>
    <div class="navbar-fixed">
        <nav class="red darken-3" role="navigation">
            <div class="nav-wrapper">
                <a id="logo-container" href="index.php" class="brand-logo">
                    <img src="imgs/logo_negativo.png">
                </a>
                <a href="#" data-activates="mobileMenu" class="button-collapse"><i class="material-icons">menu</i></a>
                <ul class="right hide-on-med-and-down">
                    <li>
                        <a href="tareas.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Tareas</a>
                    </li>
                    <li>
                        <a href="coordinadorPS.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar PS</a>
                    </li>
                    <li>
                        <a href="index.php?logout=1">
                            <i class="material-icons left">exit_to_app</i><?php echo $_COOKIE[TOKENNOMBRE]; ?></a>
                    </li>
                </ul>
                <ul class="side-nav" id="mobileMenu">
                <li>
                        <a href="tareas.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Tareas</a>
                    </li>
                    <li>
                        <a href="coordinadorPS.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar PS</a>
                    </li>
                    <li>
                        <a href="index.php?logout=1">
                            <i class="material-icons left">exit_to_app</i><?php echo $_COOKIE[TOKENNOMBRE]; ?></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <main>
        <div class="section">
            <div class="row">
                <div class="col s5" id="mapaBig">
                    <div id="map" class="mapa z-depth-2"></div>
                </div>
                <div class="col s3" id="equiposList">
                    <div class="progress" id="loadingEquipos">
                        <div class="indeterminate"></div>
                    </div>
                    <ul class="collection z-depth-2 optiscroll columnHeight" id="equiposListCol">
                    </ul>
                </div>
                <div class="col s4" id="infoPS">
                    <div class="card z-depth-2">
                        <div class="card-content">
                            <span class="card-title" id="infoIDPS"></span> <span id="infoLugar"></span>
                            <h5>Información de PS</h5>
                            <div class="row">
                                <div class="col s3">
                                    <strong>Estado:</strong>
                                </div>
                                <div class="col s3">
                                    <span id="infoEstado"></span>
                                </div>
                                <div class="col s3">
                                    <strong>Naturaleza:</strong>
                                </div>
                                <div class="col s3">
                                    <span id="infoNaturaleza"></span>
                                </div>
                                <div class="col s3">
                                    <strong>Tipo:</strong>
                                </div>
                                <div class="col s3">
                                    <span id="infoTipo"></span>
                                </div>
                                <div class="col s3">
                                    <strong>Urgencia:</strong>
                                </div>
                                <div class="col s3">
                                    <span id="infoUrgencia"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s3">
                                    <strong>Dirección:</strong>
                                </div>
                                <div class="col s9">
                                    <span id="infoDireccion"></span>
                                </div>
                                <div class="col s3">
                                    <strong>Solicitante:</strong>
                                </div>
                                <div class="col s9">
                                    <span id="infoSolicitante"></span>
                                </div>
                                <div class="col s3">
                                    <strong>Agente:</strong>
                                </div>
                                <div class="col s9">
                                    <span id="infoAgente"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <ul class="collapsible collapsibleinfo" data-collapsible="accordion">
                                        <li>
                                            <div class="collapsible-header">Problema</div>
                                            <div class="collapsible-body">
                                                <span id="infoProblema"></span>
                                            </div>
                                        </li>
                                        <li id="collapsibleinfoNotas">
                                            <div class="collapsible-header">Notas</div>
                                            <div class="collapsible-body">
                                                <span id="infoNotas"></span>
                                            </div>
                                        </li>
                                        <li id="collapsibleinfoNotaVital">
                                            <div class="collapsible-header">Nota vital</div>
                                            <div class="collapsible-body">
                                                <span id="infoNotaVital"></span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- <div class="row">
                                <div class="col s6">
                                    <a href="#modalOfertarMantenimiento" class="modal-trigger waves-effect waves-green btn col s12">Mantenimiento</a>
                                </div>
                                <div class="col s6">
                                    <a href="#modalOfertarServicio" class="modal-trigger waves-effect waves-green btn col s12">Oferta Servicio</a>
                                </div>
                                
                            </div>
                            <div class="row">
                                <div class="col s6">
                                    <a href="#modalInformeTV" class="modal-trigger waves-effect waves-green btn col s12 orange">Informe técnico</a>
                                </div>
                                <div class="col s6" id="informePS">
                                    
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <br>
    </main>
    <?php include 'scripts.php'; 
    }?>
</body>

</html>