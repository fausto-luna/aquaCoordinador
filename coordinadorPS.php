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
} else {
    
    function getContent($url){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "token: ".$_COOKIE['AquaCoordinadorToken']
        ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);

        return json_decode($response);
    }

    ?>
    <div class="navbar-fixed">
        <nav class="red darken-3" role="navigation">
            <div class="nav-wrapper">
                <a id="logo-container" href="index.php" class="brand-logo">
                    <img src="imgs/logo_negativo.png">
                </a>
                <a href="#" data-activates="mobileMenu" class="button-collapse"><i class="material-icons">menu</i></a>
                <ul class="right hide-on-med-and-down">
                    <li>
                        <a href="#" class="datepicker" data-value="" id="selectorFecha">
                            <i class="material-icons left">date_range</i>
                            <span id="filtroFecha"></span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="datepicker2" data-value="" id="selectorFecha2">
                            <i class="material-icons left">date_range</i>
                            <span id="filtroFecha2"></span>
                        </a>
                    </li>
                    <li>
                        <a href="tareas.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Tareas</a>
                    </li>
                    <li>
                        <a href="index.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar Equipos</a>
                    </li>
                    <li>
                        <a href="index.php?logout=1">
                            <i class="material-icons left">exit_to_app</i><?php echo $_COOKIE['AquaCoordinadorTokenNOMBRE']; ?></a>
                    </li>
                </ul>
                <ul class="side-nav" id="mobileMenu">
                    <li>
                        <a href="#" class="datepicker" data-value="" id="selectorFecha">
                            <i class="material-icons left">date_range</i>
                            <span id="filtroFechaMobile"></span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="datepicker2" data-value="" id="selectorFecha2">
                            <i class="material-icons left">date_range</i>
                            <span id="filtroFecha2Mobile"></span>
                        </a>
                    </li>
                    <li>
                        <a href="tareas.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Tareas</a>
                    </li>
                    <li>
                        <a href="index.php" class="modal-trigger">
                            <i class="material-icons left">list</i>Listar Equipos</a>
                    </li>
                    <li>
                        <a href="index.php?logout=1">
                            <i class="material-icons left">exit_to_app</i><?php echo $_COOKIE['AquaCoordinadorTokenNOMBRE']; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <main>
        <div class="section">
            <div class="row">
                <div class="col s3" id="mapaBig">
                    <div class="col s12">
                        <ul class="tabs tabs-fixed-width">
                            <li class="tab col s3"><a class="active"  href="#tabEquipos">Equipos</a></li>
                            <li class="tab col s3"><a class="active" href="#tabPS">Estados PS</a></li>
                            <li class="tab col s3"><a href="#tabPersonas">Personas</a></li>
                        </ul>
                    </div>
                    <div id="tabPS" class="col s12 optiscroll columnHeight">
                        <div class="collection">
                            <a href="#!" class="collection-item botonesFiltroPS active" id="mostrarPendientes"><span class="badge"></span>Pendientes Validar</a>
                            <a href="#!" class="collection-item botonesFiltroPS" id="mostrarValidadas"><span class="badge"></span>PS Validadas</a>
                            <a href="#!" class="collection-item botonesFiltroPS" id="mostrarAnuladas"><span class="badge"></span>PS Anuladas</a>
                            <a href="#!" class="collection-item botonesFiltroPS" id="mostrarNoRealizadas"><span class="badge"></span>PS No realizadas</a>
                            <a href="#!" class="collection-item botonesFiltroPS" id="mostrarTodas"><span class="badge"></span>Todas las PS</a>
                        </div>
                    </div>
                    <div id="tabEquipos" class="col s12 optiscroll columnHeight">
                        <div class="collection collectionEquiposMenu"></div>
                    </div>
                    <div id="tabPersonas" class="col s12 optiscroll columnHeight">
                        <div class="collection collectionPersonasMenu"></div>
                    </div>
                </div>
                    <div class="col s3" id="equiposList">
                        <div class="progress" id="loadingEquipos">
                            <div class="indeterminate"></div>
                        </div>
                        <ul class="collection z-depth-2 optiscroll columnHeight" id="equiposListCol"></ul>
                    </div>
                <div class="col s6" id="infoPS">
                    <div class="card z-depth-2">
                        <div class="card-content optiscroll columnHeight">
                           
                            <div class="row">
                            <div class="col s4">
                            <span class="card-title" id="infoIDPS"></span> <span id="infoLugar"></span>
                            </div>
                                <div class="col s8" id="revisarPSButton">
                                    <a href="#modalPS" id="revisarPS" class="waves-effect waves-indigo btn col s12 blue">Revisar PS</a>
                                </div>
                            </div>
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
                                <div class="col s6">
                                    <dl class="datosAgentePS">
                                        <dt>Dirección:</dt>
                                        <dd id="infoDireccion"></dd>

                                        <dt>Solicitante:</dt>
                                        <dd id="infoSolicitante"></dd>
                                        
                                        <dt>Cliente:</dt>
                                        <dd id="infoCliente"></dd>

                                        <dt>Agente:</dt>
                                        <dd id="infoAgente"></dd>

                                        <dt>Agente de cobro:</dt>
                                        <dd id="infoAgenteCobro"></dd>

                                        <dt>Administrador:</dt>
                                        <dd id="infoAdministrador"></dd>
                                    </dl>
                                </div>
                                <div class="col s6">
                                    <dl class="datosAgentePS">
                                        <dt class="equipoPS">Equipo:</dt>
                                        <dd class="equipoPS" id="EquipoNombre"></dd>

                                        <dt class="equipoPS">Componentes:</dt>
                                        <dd class="equipoPS">
                                            <ul id="equipoPersonas"></ul>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <div class="card">
                                        <div class="card-content">
                                        <span class="card-title">Problema</span>
                                        <p id="infoProblema"></p>
                                        </div>
                                    </div>
                                    <div class="card" id="collapsibleinfoNotas">
                                        <div class="card-content">
                                        <span class="card-title">Notas</span>
                                        <p id="infoNotas"></p>
                                        </div>
                                    </div>
                                    <div class="card" id="collapsibleinfoNotaVital">
                                        <div class="card-content">
                                        <span class="card-title">Nota vital <a href="#modalEditarNotaVital" class="modal-trigger btn-flat btn-small actionBtn"><i class="material-icons">edit</i></a></span>
                                        <p id="infoNotaVital"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <br>
    </main>
    <div id="modalEditarNotaVital" class="modal editarNotaVital">
        <div class="modal-content">
            <h4>Editar Nota Vital</h4>
            <div class="row">
                <div class="col s12">
                    <div class="row">
                        <div class="input-field col s12">
                            <textarea id="textAreaNotaVital" class="materialize-textarea"></textarea>
                            <label for="textAreaNotaVital">Nota Vital</label>
                        </div>
                        <input type="hidden" id="idPropietario" name="idPropietario">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarNotaVitalBtn" idInstalacion="">Editar</a>
        </div>
    </div>
    <?php include 'scripts.php';
    } ?>
</body>

</html>