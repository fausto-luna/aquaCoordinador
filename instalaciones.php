<?php 
    header('Content-Type: text/html; charset=UTF-8');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0" />
    <title>Aqualyt - Coordinador</title>
    <?php include 'styles.php';?>
    <style>
    .infoPS{
        margin-top: 5px;
       /* background-color: blue; */
    }
    #editarOperarioBtn_{
        margin-top: 0;
        margin-bottom: 36px;
    }
    .modal{
         width: 50%;
    }
    .modal-footer{
        display: flex;
        justify-content: center;
    }
    .centered{
        display:flex;
        justify-content:center;
    }
 
    </style>
</head>

<body class="bodyOverflow">
    <?php 
    include 'urlAPI.php';
    include 'checkToken.php';

    if ((!isset($_COOKIE["AquaCoordinadorToken"])) || (checkToken($urlAPI)->codigo==200)) {
        header("Refresh:0; url=index.php");
    } else {

    $idPS = $_GET['idPS'];
    $idLloc = $_GET['idLloc'];
    $litrosAgua = $_GET['litrosAgua'];

    define("PREIMAGE", "data:image/png;base64,");
    define("ESTILOSMODIFICACION", "yellow lighten-4");
    define("AQUAACTION", "http://app.aqualyt.net/sf/action.php"); // PRO
    //define("AQUAACTION", "http://aquaplus.shadowsland.com/sf/action.php"); // DEV

    // para debug:
    function codeOut($object){
        echo '<pre>';
        print_r($object);
        echo '</pre>';
    }
    // !para debug

    function cmp($a, $b)
    {
        return strcmp($a["value"], $b["value"]);
    }

    function iconMantenimiento($mantenimiento, $idInstalacion = 0){
        if ($mantenimiento == 0) {
            $iconMantenimiento = '<i class="material-icons">check_box_outline_blank</i>';
        } else {
            $iconMantenimiento = '<i class="material-icons">check_box</i>';
        }

        if($idInstalacion!==0){
            $iconMantenimiento = '<a href="#mantenimientoInstalacion'.$idInstalacion.'" class="modal-trigger btn-flat actionBtn mantenimientoInstalacion">'.$iconMantenimiento.'</a>';
        }
        return $iconMantenimiento;
    }

    function mountSelect($tipo, $valor, $label, $listOptions, $idInstalacion){
        $select = '<div class="input-field col s12">
                        <select id="'.$tipo.$idInstalacion.'" name="'.$tipo.$idInstalacion.'">';
        if ($valor === 0) {
            $select .= '<option value="0" selected></option>';
            foreach ($listOptions[$tipo] as $value) {
                $select .= '<option value="'.$value['id'].'">'.$value['value'].'</option>';
            }
        } else {
            $select .= '<option value="0"></option>';
            foreach ($listOptions[$tipo] as $value) {
                $select .= '<option value="'.$value['id'].'"';
                if ($value['id'] == $valor) {
                    $select .= ' selected';
                }
                $select .='>'.$value['value'].'</option>';
            }
        }

        return $select.'</select><label>'.$label.'</label></div>';
    }

    function intervenidoCheck($idInstalacion, $intervenido){
        $checkbox = '<input type="checkbox" id="intervenido'.$idInstalacion.'"';
        if ($intervenido === '1') {
            $checkbox .= ' checked="checked"';
        }

        return $checkbox.' /><label for="intervenido'.$idInstalacion.'">Intervenido</label>';
    }

    function mantenimientoCheck($idInstalacion, $intervenido){
        $checkbox = '<input type="checkbox" id="mantenimiento'.$idInstalacion.'"';
        if ($intervenido === '1') {
            $checkbox .= ' checked="checked"';
        }

        return $checkbox.' /><label for="mantenimiento'.$idInstalacion.'">Mantenimiento</label>';
    }

    function compareRevision($old, $new){
        $old = str_replace('None', null, $old);
        $new = str_replace('None', null, $new);

        if(((empty($old)) && (empty($new))) || ($old == $new)){
            return '';
        } else {
            return ESTILOSMODIFICACION;
        }    
    }
    
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
        $err = curl_error($curl);
        
        curl_close($curl);

        if ($err==null) {
            return json_decode($response);
        } else {
            return json_decode($err);
        }
    }

    function generateModalsRechazarNuevasInstalaciones($idInstalacion, $tipo) {
        return '<div id="modalRechazarInstalacion'.$idInstalacion.'" class="modal modalInstalaciones">
                <div class="modal-content">
                    <h4>Rechazar '.ucfirst($tipo).'</h4>
                    <div class="row">
                        <div class="col s12">
                            <h5>
                                ¿Seguro que quieres rechazar esta '. strtolower($tipo).'?
                            </h5>
                            <p>
                                Esta acción no se puede deshacer
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                    <a href="#!" class="modal-action modal-close waves-effect waves-red red btn rechazarInstalacionBtn" id="rechazarInstalacionBtn'.$idInstalacion.'" idInstalacion="'.$idInstalacion.'">Rechazar</a>
                </div>
            </div>';
    }

    $nuevasInstalaciones = '';
    $revisionesInstalaciones = '';
    $modalsValidarNuevasInstalaciones = '';
    $modalsRechazarNuevasInstalaciones = '';
    $modalsEditarInstalaciones = '';
    $modalsCrearRevisiones = '';
    $modalsEliminarInstalaciones = '';
    $modalsIntervenirInstalaciones = '';
    $modalsMantenimientoInstalaciones = '';
    $todasInstalaciones = '';
    $arrayInstalacionesLloc = array();
    $arrayImagesPS = array();
    $arrayImagesPortada = array();
    $arrayImagesInstalacion = array();
    $arrayIdInstalacionesRevision = array();
    $arrayServiciosLugar = array();
    $mostrarOfertas = false;
    $portadasValidadas = true;
    $mostrarAtributos = false;
    $observaciones = null;
    $ofertas = null;
    $hasImages = array();
    $validable = array();
    $countInstalaciones = 0;
    $countInstalacionesNuevas = 0;
    $countInstalacionesRevision = 0;
    $validarInstalaciones = false;

    $GETinfoValidarInstalaciones = getContent($urlAPI."/equipos/ps/validacion/validar_todo/aspecto_tecnico/permiso/user/".$_COOKIE["AquaCoordinadorTokenID"]."/ps/".$idPS);
    if ((isset($GETinfoValidarInstalaciones->codigo)) && ($GETinfoValidarInstalaciones->codigo == 100)) {
        if ($GETinfoValidarInstalaciones->contenido->permiso === 'true'){
            $validarInstalaciones = true;
        }
    }

    $GETinfoPS = getContent($urlAPI."/equipos/pss/filtrar?id=".$idPS);

    if ((isset($GETinfoPS->codigo)) && ($GETinfoPS->codigo == 100)) {
        $infoPS = $GETinfoPS->contenido->{'1'};
    }

    $listImages = getContent($urlAPI."/equipos/instalacion/coordinador/ps/".$idPS."/lugar/".$idLloc."/test");
    
    if ((isset($listImages->codigo)) && ($listImages->codigo == 100)) {
        foreach ($listImages->contenido as $key => $instalaciones) {
            foreach ($instalaciones as $key2 => $instalacionTemporal) {
                if ($key==='temporales') {
                    // Instalación nueva
                    if (count((array)$instalacionTemporal->galeria)>0) {
                        $hasImages[$key2] = true;
                        $validable[$key2] = true;
                        
                        foreach ($instalacionTemporal->galeria as $imagenTemporal) {
                            $arrayImagesPS[$key2][$imagenTemporal->estadoIntervencion] = new \stdClass();
                            $arrayImagesPS[$key2][$imagenTemporal->estadoIntervencion]->new = PREIMAGE.$imagenTemporal->base64;
                            $arrayImagesPS[$key2][$imagenTemporal->estadoIntervencion]->id = $imagenTemporal->id;
                            $arrayImagesPS[$key2][$imagenTemporal->estadoIntervencion]->idEstadoValidacion = $imagenTemporal->idEstadoValidacion;
                            $arrayImagesPS[$key2][$imagenTemporal->estadoIntervencion]->descripcion = $imagenTemporal->descripcion;

                            if (($imagenTemporal->idEstadoValidacion!='2') && ($imagenTemporal->idEstadoValidacion!='3')) {
                                $validable[$key2] = false;
                            }
                        }
                    }
                } else {
                    if (isset($instalacionTemporal->temporal->id)) {
                        $idTemporal = $instalacionTemporal->temporal->id;
                    }
                    foreach ($instalacionTemporal as $key3 => $instalacionRevision) {
                        if ($key3 === 'temporal') {
                            # Revisión
                            if ((isset($instalacionRevision->galeria))&&(count((array)$instalacionRevision->galeria)>0)) {
                                $arrayIdInstalacionesRevision[$instalacionRevision->from_instalacion] = $idTemporal;
                                $validable[$key2] = true;
                                
                                foreach ($instalacionRevision->galeria as $imagenRevision) {
                                    $hasImages[$idTemporal] = true;
                                    $validable[$idTemporal] = true;

                                    $arrayImagesPS[$idTemporal][$imagenRevision->estadoIntervencion] = new \stdClass();
                                    $arrayImagesPS[$idTemporal][$imagenRevision->estadoIntervencion]->new = PREIMAGE.$imagenRevision->base64;
                                    $arrayImagesPS[$idTemporal][$imagenRevision->estadoIntervencion]->id = $imagenRevision->id;
                                    $arrayImagesPS[$idTemporal][$imagenRevision->estadoIntervencion]->idEstadoValidacion = $imagenRevision->idEstadoValidacion;
                                    $arrayImagesPS[$idTemporal][$imagenRevision->estadoIntervencion]->descripcion = $imagenRevision->descripcion;
                                    if (($imagenRevision->idEstadoValidacion!='2') && ($imagenRevision->idEstadoValidacion!='3')) {
                                        $validable[$idTemporal] = false;
                                    }
                                }
                            }
                        } else {
                            # Existente
                            if ((isset($instalacionRevision->portada->base64))&&($instalacionRevision->portada->base64!='')) {
                                $hasImages[$instalacionRevision->from_instalacion] = true;
                                $validable[$instalacionRevision->from_instalacion] = true;
                                @$arrayImagesPortada[$instalacionRevision->from_instalacion][1]->old = PREIMAGE.$instalacionRevision->portada->base64;
                                @$arrayImagesPortada[$instalacionRevision->from_instalacion][1]->id = $instalacionRevision->portada->id;
                                @$arrayImagesPortada[$instalacionRevision->from_instalacion][1]->descripcion = $instalacionRevision->portada->descripcion;
                            }
                        }
                    }
                }
            }
        }
    }

    // Obtenemos valores de desplegables para edición
    $desplegables = getContent($urlAPI."/equipos/keyvalue/multi/instalacion");
    if ($desplegables->codigo == 100) {
        foreach ($desplegables->contenido as $tipoContenido => $listContenido) {
            $listDesplegables[$tipoContenido] = array();
            foreach ($listContenido as $key => $contenido) {
                array_push($listDesplegables[$tipoContenido], array('id'=>$contenido->id, 'value'=>$contenido->value));
            }
            usort($listDesplegables[$tipoContenido], 'cmp');
        }
    }

    $desplegableUrgencias = getContent($urlAPI."/equipos/keyvalue/urgencia");
    if ($desplegableUrgencias->codigo == 100) {
        $listDesplegables['URGENCIA'] = array();
        foreach ($desplegableUrgencias->contenido as $urgencia) {
            array_push($listDesplegables['URGENCIA'], array('id'=>$urgencia->id, 'value'=>$urgencia->urgencia));
        }
        usort($listDesplegables['URGENCIA'], 'cmp');
    }

    $desplegableNaturalezas = getContent($urlAPI."/equipos/keyvalue/naturaleza");
    if ($desplegableNaturalezas->codigo == 100) {
        $listDesplegables['NATURALEZA'] = array();
        foreach ($desplegableNaturalezas->contenido as $naturaleza) {
            array_push($listDesplegables['NATURALEZA'], array('id'=>$naturaleza->id, 'value'=>$naturaleza->naturaleza));
        }
        usort($listDesplegables['NATURALEZA'], 'cmp');
    }

    $desplegableEstados = getContent($urlAPI."/equipos/keyvalue/estado");
    if ($desplegableEstados->codigo == 100) {
        $listDesplegables['ESTADO'] = array();
        foreach ($desplegableEstados->contenido as $estado) {
            array_push($listDesplegables['ESTADO'], array('id'=>$estado->id, 'value'=>$estado->ESTADO));
        }
        usort($listDesplegables['ESTADO'], 'cmp');
    }

    //
    $desplegableEquipos = getContent($urlAPI."/equipos/equipo/mecanico/listado");
    if ($desplegableEquipos->codigo == 100) {
        $listDesplegables['EQUIPO'] = array();
        foreach ($desplegableEquipos->contenido as $equipo) {
            array_push($listDesplegables['EQUIPO'], array('id'=>$equipo->id, 'value'=>$equipo->equipo));
        }
        usort($listDesplegables['EQUIPO'], 'cmp');
    }
    //
    $desplegableOperarios = getContent($urlAPI."/equipos/equipo/listado");
    if ($desplegableOperarios->codigo == 100) {
        $listDesplegables['OPERARIOS'] = array();
        foreach ($desplegableOperarios->contenido as $operario) {
            array_push($listDesplegables['OPERARIOS'], array('id'=>$operario->id, 'value'=>$operario->nombre." ".$operario->apellido1));
        }
        usort($listDesplegables['OPERARIOS'], 'cmp');
    }

    $ofertasLugarGet = getContent($urlAPI."/equipos/ofertas_contratos/lugar/".$idLloc);
    if (isset($ofertasLugarGet->codigo) && ($ofertasLugarGet->codigo == 100)) {
        $arrayServiciosLugar = array();
        foreach ($ofertasLugarGet->contenido as $key => $ofertasLugarArray) {
            $arrayServiciosLugar[$key] = array();

            $listDesplegables[$key] = array();
            
            foreach ($ofertasLugarArray as $ofertaLugarObj) {
                array_push($arrayServiciosLugar[$key], $ofertaLugarObj);
                array_push($listDesplegables[$key], array('id'=>$ofertaLugarObj->id, 'value'=>$ofertaLugarObj->id));
            }

            usort($listDesplegables[$key], 'cmp');
        }
    }
    
    $listInstalacionesLloc = getContent($urlAPI."/equipos/instalacion/ps/".$idPS);
    if ($listInstalacionesLloc->codigo == 100) {
        foreach ($listInstalacionesLloc->contenido as $instalacion) {
            $countInstalaciones++;
            $arrayInstalacionesLloc[$instalacion->INSTALACION_LUGAR_ACTUACION->id] = $instalacion->INSTALACION_LUGAR_ACTUACION;
            $todasInstalaciones .= '<tr>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_rango->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_instalacion->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->nombre_instalacion->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->material->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_residuo->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_ubicacion->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->numero.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_puerta->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_piso->valor.'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_escalera->valor.'</td>
                                    <td>'.iconMantenimiento($instalacion->INSTALACION_LUGAR_ACTUACION->mantenimiento, $instalacion->INSTALACION_LUGAR_ACTUACION->id).'</td>
                                    <td>'.$instalacion->INSTALACION_LUGAR_ACTUACION->observaciones.'</td>
                                    <td class="accionesBtns">';
                                    if (($instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->NUEVA->base64) && (!isset($arrayIdInstalacionesRevision[$instalacion->INSTALACION_LUGAR_ACTUACION->id]))) {
                                        $todasInstalaciones .= '<a href="#modalInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small imageBtn"><i class="material-icons">image</i></a>';
                                    } elseif($instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->ACTUAL->base64) {
                                        $todasInstalaciones .= '<a href="#modalInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small black-text"><i class="material-icons">image</i></a>';
                                    } else {
                                        $todasInstalaciones .= '<a href="#" class="waves-effect waves-teal btn-flat btn-small grey-text disabled"><i class="material-icons">image</i></a>';
                                    }
                                    // Crear revisión
                                    $todasInstalaciones .= '<a href="#revisarInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">report_problem</i></a>';
                                    // Eliminar instalación
                                    $todasInstalaciones .= '<a href="#eliminarInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">delete</i></a>';
                                    $todasInstalaciones .= '</td>
                                    <td>';
                                    if ($instalacion->INSTALACION_LUGAR_ACTUACION->intervenido) {
                                        $todasInstalaciones .= '<i class="material-icons green-text">done</i>';
                                    } else {
                                        $todasInstalaciones .= '<a href="#intervenirInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons grey-text">done_outline</i></a>';
                                    }
                                    $todasInstalaciones .= '</td><td></td>
                                                    <th>
                                    </tr>';
            $modalsCrearRevisiones .= '<div id="revisarInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal revisarInstalacion">
                                        <div class="modal-content">
                                            <h4>¿Crear revisión de la instalación?</h4>
                                            <div class="row">
                                                <div class="col s12">
                                                    <h5>
                                                        ¿Seguro que quieres crear una revisión de la instalación?
                                                    </h5>
                                                    <form id="formInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'">
                                                        <input type="hidden" name="rango" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_rango->id.'">
                                                        <input type="hidden" name="tipo_instalacion" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_instalacion->id.'">
                                                        <input type="hidden" name="nombre" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->nombre_instalacion->id.'">
                                                        <input type="hidden" name="material" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->material->id.'">
                                                        <input type="hidden" name="tipo_residuo" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_residuo->id.'">
                                                        <input type="hidden" name="tipo_ubicacion" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_ubicacion->id.'">
                                                        <input type="hidden" name="numero" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->numero.'">
                                                        <input type="hidden" name="puerta" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_puerta->id.'">
                                                        <input type="hidden" name="piso" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_piso->id.'">
                                                        <input type="hidden" name="escalera" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->tipo_escalera->id.'">
                                                        <input type="hidden" name="observaciones" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->observaciones.'">
                                                        <input type="hidden" name="mantenimiento" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->mantenimiento.'">
                                                        <input type="hidden" name="from_instalacion" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'">
                                                        <input type="hidden" name="intervenido" value="'.$instalacion->INSTALACION_LUGAR_ACTUACION->intervenido.'">
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="crearRevisionBtn" idPS="'.$idPS.'" idInstalacion="'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" idLugar="'.$instalacion->INSTALACION_LUGAR_ACTUACION->IDLugar.'">Crear</a>
                                        </div>
                                    </div>';
            $modalsEliminarInstalaciones .= '<div id="eliminarInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal eliminarInstalacion">
                                        <div class="modal-content">
                                            <h4>Eliminar instalación</h4>
                                            <div class="row">
                                                <div class="col s12">
                                                    <h5>
                                                        ¿Seguro que quieres eliminar la instalación?
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="eliminarInstalacionBtn" idInstalacion="'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'">Eliminar</a>
                                        </div>
                                    </div>';
            $modalsIntervenirInstalaciones .= '<div id="intervenirInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal intervenirInstalacion">
                                        <div class="modal-content">
                                            <h4>Intervenir instalación</h4>
                                            <div class="row">
                                                <div class="col s12">
                                                    <h5>
                                                        ¿Seguro que quieres intervenir la instalación?
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="intervenirInstalacionBtn" idInstalacion="'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" idPS="'.$idPS.'" idContrato="'.$instalacion->INSTALACION_LUGAR_ACTUACION->contrato.'">Intervenir</a>
                                        </div>
                                    </div>';
            if(isset($listDesplegables['CONTRATOS_MANTENIMIENTO'])){
                $modalsMantenimientoInstalaciones .= '<div id="mantenimientoInstalacion'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" class="modal mantenimientoInstalacion">
                                        <div class="modal-content">
                                            <h4>Mantenimiento de la instalación</h4>
                                                <div class="row">
                                                    <div class="col s6">'.mountSelect('CONTRATOS_MANTENIMIENTO', $instalacion->INSTALACION_LUGAR_ACTUACION->contrato, 'Contrato', $listDesplegables, $instalacion->INSTALACION_LUGAR_ACTUACION->id).'</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                                <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="mantenimientoInstalacionBtn" idInstalacion="'.$instalacion->INSTALACION_LUGAR_ACTUACION->id.'" idPS="'.$idPS.'">Asignar contrato</a>
                                            </div>
                                        </div>';
            }
            if ($instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->NUEVA->base64) {
                @$arrayImagesPortada[$instalacion->INSTALACION_LUGAR_ACTUACION->id][0]->new = PREIMAGE.$instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->NUEVA->base64;
                @$arrayImagesPortada[$instalacion->INSTALACION_LUGAR_ACTUACION->id][0]->id = $instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->NUEVA->idDoc;
                $portadasValidadas = false;
            }
            if ($instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->ACTUAL->base64) {
                @$arrayImagesPortada[$instalacion->INSTALACION_LUGAR_ACTUACION->id][1]->old = PREIMAGE.$instalacion->INSTALACION_LUGAR_ACTUACION->PORTADAS->ACTUAL->base64;
            }
        }
    }

    $observacionesPSGet = getContent($urlAPI."/equipos/resolucion_servicio?ps=".$idPS);
    if ($observacionesPSGet->codigo == 100) {
        $observaciones = $observacionesPSGet->contenido->{1}->observaciones;
        $modalEditarObservaciones = '<div id="modalEditarObservaciones" class="modal editarObservaciones">
                                        <div class="modal-content">
                                            <h4 class="centered">Editar Observaciones</h4>
                                            <div class="row">
                                                <form class="col s12" id="formEditObservaciones">
                                                    <div class="row">
                                                        <div class="input-field col s12">
                                                        <textarea id="textAreaObservaciones" name="textAreaObservaciones" class="materialize-textarea">'.$observaciones.'</textarea>
                                                        <label for="textAreaObservaciones">Observaciones</label>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarObservacionBtn" idPS="'.$idPS.'">Editar</a>
                                        <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                        </div>
                                    </div>';
    }

    $ofertasPSGet = getContent($urlAPI."/equipos/servicio_ofertar?ps=".$idPS);
    if ($ofertasPSGet->codigo == 100) {
        foreach ($ofertasPSGet->contenido->servicios as $ofertasObj) {
            if(!isset($servicios)){
                $servicios = array();
            }
            array_push($servicios, $ofertasObj);
        }
        foreach ($ofertasPSGet->contenido->mantenimientos as $ofertasObj) {
            if(!isset($mantenimientos)){
                $mantenimientos = array();
            }
            array_push($mantenimientos, $ofertasObj);
        }
    }

    
    $atributosTempGet = getContent($urlAPI."/equipos/lugar_actuacion/".$idLloc."/atributos/tmp?idPS=".$idPS);

    if ($atributosTempGet->codigo == 100) {
        // Hay atributos nuevos
        $mostrarAtributos = true;
        $atributosTemp = $atributosTempGet->contenido->{'1'};

        $atributosTabla = '<tr>
                            <th>Nuevos</th>
                            <td>'.(($atributosTemp->tipo_lugar==1)?"Único":"Mancomunado").'</td>
                            <td>'.(($atributosTemp->furgoneta==1)?"Si":"No").'</td>
                            <td>'.(($atributosTemp->aqualyto==1)?"Si":"No").'</td>
                            <td>'.(($atributosTemp->precisa_corte_trafico==1)?"Si":"No").'</td>
                            <td>'.(($atributosTemp->permiso_tapa_abierta==1)?"Si":"No").'</td>
                            <td>'.(($atributosTemp->tiene_boca_agua==1)?"Si":"No").'</td>
                            <td>'.(($atributosTemp->boca_agua!='')?$atributosTemp->boca_agua:"").'</td>
                            <td>'.(($atributosTemp->tiene_hidrante==1)?"Si":"No").'</td>
                            <td>'.(($atributosTemp->observaciones_hidrante!='')?$atributosTemp->observaciones_hidrante:"").'</td>
                            <td class="accionesBtns">
                                <a href="#modalEditarAtributos" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">edit</i></a>
                                <a href="#modalValidarAtributos" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">thumb_up</i></a>
                                <a href="#modalRechazarAtributos" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">thumb_down</i></a>
                            </td>
                        </tr>';

        // Descargar atributos existentes
        $atributosExistentesGet = getContent($urlAPI."/equipos/lugar_actuacion/".$idLloc."/atributos");
        if ($atributosExistentesGet->codigo == 100) {
            $atributosExistentes = $atributosExistentesGet->contenido->{'1'};

            $atributosTabla .= '<tr>
                            <th>Existentes</th>
                            <td>'.(($atributosExistentes->tipo_lugar_id==1)?"Único":"Mancomunado").'</td>
                            <td>'.(($atributosExistentes->furgoneta==1)?"Si":"No").'</td>
                            <td>'.(($atributosExistentes->aqualyto==1)?"Si":"No").'</td>
                            <td>'.(($atributosExistentes->precisa_corte_trafico==1)?"Si":"No").'</td>
                            <td>'.(($atributosExistentes->permiso_tapa_abierta==1)?"Si":"No").'</td>
                            <td>'.(($atributosExistentes->tiene_boca_agua==1)?"Si":"No").'</td>
                            <td>'.(($atributosExistentes->boca_agua!='')?$atributosExistentes->boca_agua:"").'</td>
                            <td>'.(($atributosExistentes->tiene_hidrante==1)?"Si":"No").'</td>
                            <td>'.(($atributosExistentes->observaciones_hidrante!='')?$atributosExistentes->observaciones_hidrante:"").'</td>
                            <td></td>
                        </tr>';
        }

        $modalValidarAtributos = '<div id="modalValidarAtributos" class="modal">
                                    <div class="modal-content">
                                        <h4>Validar atributos</h4>
                                        <div class="row">
                                            <div class="col s12">
                                                <h5>
                                                    ¿Seguro que quieres validar los nuevos atributos?
                                                </h5>
                                                <p>
                                                    Esta acción no se puede deshacer
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="validarAtributosBtn" idLloc="'.$idLloc.'" idAtributoLugar="'.$atributosTemp->id.'">Validar</a>
                                    </div>
                                </div>';
        $modalRechazarAtributos = '<div id="modalRechazarAtributos" class="modal">
                                    <div class="modal-content">
                                        <h4>Rechazar atributos</h4>
                                        <div class="row">
                                            <div class="col s12">
                                                <h5>
                                                    ¿Seguro que quieres rechazar los nuevos atributos?
                                                </h5>
                                                <p>
                                                    Esta acción no se puede deshacer
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                        <a href="#!" class="modal-action modal-close waves-effect red waves-green btn" id="rechazarAtributosBtn" idLloc="'.$idLloc.'" idAtributoLugar="'.$atributosTemp->id.'">Rechazar</a>
                                    </div>
                                </div>';
        $modalEditarAtributos = '<div id="modalEditarAtributos" class="modal">
                                    <div class="modal-content">
                                        <h4>Editar los atributos</h4>
                                        <div class="row">
                                            <form id="edicionAtributos">
                                                <div class="col s2">
                                                    <p>
                                                        Tipo lugar
                                                    </p>
                                                    <p>
                                                        <input name="tipo_lugar" type="radio" id="tipo_lugar1" '.(($atributosTemp->tipo_lugar==1)?"checked":"").' value="1" />
                                                        <label for="tipo_lugar1">Único</label>
                                                    </p>
                                                    <p>
                                                        <input name="tipo_lugar" type="radio" id="tipo_lugar2" '.(($atributosTemp->tipo_lugar!=1)?"checked":"").' value="2" />
                                                        <label for="tipo_lugar2">Mancomunado</label>
                                                    </p>
                                                </div>
                                                <div class="col s2">
                                                    <p>
                                                        Furgoneta
                                                    </p>
                                                    <p>
                                                        <input name="furgoneta" type="radio" id="furgoneta1" '.(($atributosTemp->furgoneta==1)?"checked":"").' value="1" />
                                                        <label for="furgoneta1">Si</label>
                                                    </p>
                                                    <p>
                                                        <input name="furgoneta" type="radio" id="furgoneta2" '.(($atributosTemp->furgoneta!=1)?"checked":"").' value="0" />
                                                        <label for="furgoneta2">No</label>
                                                    </p>
                                                </div>
                                                <div class="col s2">
                                                    <p>
                                                        Aqualyto
                                                    </p>
                                                    <p>
                                                        <input name="aqualyto" type="radio" id="aqualyto1" '.(($atributosTemp->aqualyto==1)?"checked":"").' value="1" />
                                                        <label for="aqualyto1">Si</label>
                                                    </p>
                                                    <p>
                                                        <input name="aqualyto" type="radio" id="aqualyto2" '.(($atributosTemp->aqualyto!=1)?"checked":"").' value="0" />
                                                        <label for="aqualyto2">No</label>
                                                    </p>
                                                </div>
                                                <div class="col s2">
                                                    <p>
                                                        Permiso corte
                                                    </p>
                                                    <p>
                                                        <input name="precisa_corte_trafico" type="radio" id="precisa_corte_trafico1" '.(($atributosTemp->precisa_corte_trafico==1)?"checked":"").' value="1" />
                                                        <label for="precisa_corte_trafico1">Si</label>
                                                    </p>
                                                    <p>
                                                        <input name="precisa_corte_trafico" type="radio" id="precisa_corte_trafico2" '.(($atributosTemp->precisa_corte_trafico!=1)?"checked":"").' value="0" />
                                                        <label for="precisa_corte_trafico2">No</label>
                                                    </p>
                                                </div>
                                                <div class="col s2">
                                                    <p>
                                                        Permiso tapa abierta
                                                    </p>
                                                    <p>
                                                        <input name="permiso_tapa_abierta" type="radio" id="permiso_tapa_abierta1" '.(($atributosTemp->permiso_tapa_abierta==1)?"checked":"").' value="1" />
                                                        <label for="permiso_tapa_abierta1">Si</label>
                                                    </p>
                                                    <p>
                                                        <input name="permiso_tapa_abierta" type="radio" id="permiso_tapa_abierta2" '.(($atributosTemp->permiso_tapa_abierta!=1)?"checked":"").' value="0" />
                                                        <label for="permiso_tapa_abierta2">No</label>
                                                    </p>
                                                </div>
                                                <div class="col s2">
                                                    <p>
                                                        Boca carga
                                                    </p>
                                                    <p>
                                                        <input name="tiene_boca_agua" type="radio" id="tiene_boca_agua1" '.(($atributosTemp->tiene_boca_agua==1)?"checked":"").' value="1" />
                                                        <label for="tiene_boca_agua1">Si</label>
                                                    </p>
                                                    <p>
                                                        <input name="tiene_boca_agua" type="radio" id="tiene_boca_agua2" '.(($atributosTemp->tiene_boca_agua!=1)?"checked":"").' value="0" />
                                                        <label for="tiene_boca_agua2">No</label>
                                                    </p>
                                                </div>
                                                <div class="col s5">
                                                    <div class="input-field col s12">
                                                        <input placeholder="Observaciones boca agua" id="boca_agua" name="boca_agua" type="text" value="'.$atributosTemp->boca_agua.'">
                                                        <label for="boca_agua">Observaciones boca agua</label>
                                                    </div>
                                                </div>
                                                <div class="col s2">
                                                    <p>
                                                        Hidrante
                                                    </p>
                                                    <p>
                                                        <input name="tiene_hidrante" type="radio" id="tiene_hidrante1" '.(($atributosTemp->tiene_hidrante==1)?"checked":"").' value="1" />
                                                        <label for="tiene_hidrante1">Si</label>
                                                    </p>
                                                    <p>
                                                        <input name="tiene_hidrante" type="radio" id="tiene_hidrante2" '.(($atributosTemp->tiene_hidrante!=1)?"checked":"").' value="0" />
                                                        <label for="tiene_hidrante2">No</label>
                                                    </p>
                                                </div>
                                                <div class="col s5">
                                                    <div class="input-field col s12">
                                                        <input placeholder="Observaciones Hidrante" id="observaciones_hidrante" name="observaciones_hidrante" type="text" value="'.$atributosTemp->observaciones_hidrante.'">
                                                        <label for="observaciones_hidrante">Observaciones Hidrante</label>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarAtributosBtn" idLloc="'.$idLloc.'" idAtributoLugar="'.$atributosTemp->id.'">Editar</a>
                                    </div>
                                </div>';
    } else {
        $mostrarAtributos = false;
    }

    $listInstalaciones = getContent($urlAPI."/equipos/instalaciones/tmp/lugar/".$idLloc."/ps/".$idPS);

    if ($listInstalaciones->codigo == 100) {
        foreach ($listInstalaciones->contenido as $instalacion) {
            if ($instalacion->from_instalacion == 0) {
                $countInstalacionesNuevas++;
                # instalación nueva
                $nuevasInstalaciones .= '<tr>
                                <td>'.$instalacion->tipo_rango_value.'</td>
                                <td>'.$instalacion->tipo_instalacion_value.'</td>
                                <td>'.$instalacion->nombre_instalacion_value.'</td>
                                <td>'.$instalacion->material_value.'</td>
                                <td>'.$instalacion->tipo_residuo_value.'</td>
                                <td>'.$instalacion->tipo_ubicacion_value.'</td>
                                <td>'.$instalacion->numero.'</td>
                                <td>'.$instalacion->tipo_puerta_value.'</td>
                                <td>'.$instalacion->tipo_piso_value.'</td>
                                <td>'.$instalacion->tipo_escalera_value.'</td>
                                <td>'.iconMantenimiento($instalacion->mantenimiento).'</td>
                                <td>'.$instalacion->observaciones.'</td>
                                <td class="accionesBtns">';
                if (isset($hasImages[$instalacion->id])) {
                    $nuevasInstalaciones .= '<a href="#modalInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small imageBtn"><i class="material-icons">image</i></a>';
                } else {
                    $nuevasInstalaciones .= '<a href="#" class="waves-effect waves-teal btn-flat btn-small grey-text disabled"><i class="material-icons">image</i></a>';
                }

                $nuevasInstalaciones .= '<a href="#modalEditarInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">edit</i></a></td><td>';
                if ($instalacion->intervenido) {
                    $nuevasInstalaciones .= '<i class="material-icons green-text">done</i>';
                }
                $nuevasInstalaciones .= '</td>
                                <td class="accionesBtns">
                                    <div class="accionesBtns'.$instalacion->id.' '.(((isset($validable[$instalacion->id])) && (!$validable[$instalacion->id]))?"hide":"").'">
                                        <a href="#modalValidarInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn actionBtn'.$instalacion->id.'"><i class="material-icons">thumb_up</i></a>
                                        <a href="#modalRechazarInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn actionBtn'.$instalacion->id.'"><i class="material-icons">thumb_down</i></a>
                                    </div>
                                </td>
                            </tr>';
                $modalsValidarNuevasInstalaciones .= '<div id="modalValidarInstalacion'.$instalacion->id.'" class="modal modalInstalaciones">
                            <div class="modal-content">
                                <h4>Validar Nueva Instalación</h4>
                                <div class="row">
                                    <div class="col s12">
                                        <h5>
                                            ¿Seguro que quieres validar esta instalación?
                                        </h5>
                                        <p>
                                            Esta acción no se puede deshacer
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col s12">
                                    <table>
                                    <thead>
                                    <tr>
                                        <th></th>
                                        <th>Nueva Instalación</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <tr>
                                        <td>RANGO</td>
                                        <td>'.$instalacion->tipo_rango_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>TIPO INSTALACIÓN</td>
                                        <td>'.$instalacion->tipo_instalacion_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>NOMBRE</td>
                                        <td>'.$instalacion->nombre_instalacion_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>MATERIAL</td>
                                        <td>'.$instalacion->material_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>TIPO RESIDUO</td>
                                        <td>'.$instalacion->tipo_residuo_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>TIPO UBICACIÓN</td>
                                        <td>'.$instalacion->tipo_ubicacion_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>NÚMERO</td>
                                        <td>'.$instalacion->numero.'</td>
                                    </tr>
                                    <tr>
                                        <td>PUERTA</td>
                                        <td>'.$instalacion->tipo_puerta_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>PISO</td>
                                        <td>'.$instalacion->tipo_piso_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>ESCALERA</td>
                                        <td>'.$instalacion->tipo_escalera_value.'</td>
                                    </tr>
                                    <tr>
                                        <td>MANTENIMIENTO</td>
                                        <td>'.iconMantenimiento($instalacion->mantenimiento).'</td>
                                    </tr>
                                    <tr>
                                        <td>OBSERVACIONES</td>
                                        <td>'.$instalacion->observaciones.'</td>
                                    </tr>
                                    </tbody>
                                </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                <a href="#!" class="modal-action modal-close waves-effect waves-green btn validarInstalacionBtn" id="validarInstalacionBtn'.$instalacion->id.'" idInstalacion="'.$instalacion->id.'" idFrom="0">Validar</a>
                            </div>
                        </div>';
                $modalsRechazarNuevasInstalaciones .= generateModalsRechazarNuevasInstalaciones($instalacion->id, 'Nueva Instalación');
                $modalsEditarInstalaciones .= '<div id="modalEditarInstalacion'.$instalacion->id.'" class="modal">
                            <div class="modal-content">
                                <h4>Editar Instalación</h4>
                                <div class="row">
                                    <form id="edicion'.$instalacion->id.'">
                                        <div class="col s2">
                                            '.mountSelect('RANGO', $instalacion->tipo_rango, 'Rango', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            '.mountSelect('TIPO_INSTALACION', $instalacion->tipo_instalacion, 'Tipo instalación', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            '.mountSelect('NOMBRE_INSTALACION', $instalacion->nombre_instalacion, 'Nombre instalación', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s2">
                                            '.mountSelect('TIPO_MATERIAL', $instalacion->material, 'Material', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            '.mountSelect('TIPO_RESIDUO', $instalacion->tipo_residuo, 'Tipo residuo', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            <div class="input-field col s12">
                                                <input placeholder="Cantidad" id="cantidad'.$instalacion->id.'" type="number" value="'.$instalacion->numero.'">
                                                <label for="cantidad">Cantidad</label>
                                            </div>
                                        </div>
                                        <div class="col s7">
                                            '.mountSelect('TIPO_UBICACION', $instalacion->tipo_ubicacion, 'Tipo ubicación', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s1">
                                            '.mountSelect('TIPO_PISO', $instalacion->tipo_piso, 'Piso', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s2">
                                            '.mountSelect('TIPO_PUERTA', $instalacion->tipo_puerta, 'Puerta', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s2">
                                            '.mountSelect('TIPO_ESCALERA', $instalacion->tipo_escalera, 'Escalera', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s12">
                                            <div class="input-field col s12">
                                                <textarea id="observaciones'.$instalacion->id.'" class="materialize-textarea">'.$instalacion->observaciones.'</textarea>
                                                <label for="observaciones'.$instalacion->id.'">Observaciones</label>
                                            </div>
                                        </div>
                                        <div class="col s4">
                                            '.intervenidoCheck($instalacion->id, $instalacion->intervenido).'
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                <a href="#!" class="modal-action modal-close waves-effect waves-green btn validarEdicionBtn" id="validarEdicionBtn'.$instalacion->id.'" idInstalacion="'.$instalacion->id.'">Confirmar edición</a>
                            </div>
                        </div>';
            } else {
                # revisión
                $countInstalacionesRevision++;

                $revisionesInstalaciones .= '<tr>
                                <td>'.$instalacion->tipo_rango_value.'</td>
                                <td>'.$instalacion->tipo_instalacion_value.'</td>
                                <td>'.$instalacion->nombre_instalacion_value.'</td>
                                <td>'.$instalacion->material_value.'</td>
                                <td>'.$instalacion->tipo_residuo_value.'</td>
                                <td>'.$instalacion->tipo_ubicacion_value.'</td>
                                <td>'.$instalacion->numero.'</td>
                                <td>'.$instalacion->tipo_puerta_value.'</td>
                                <td>'.$instalacion->tipo_piso_value.'</td>
                                <td>'.$instalacion->tipo_escalera_value.'</td>
                                <td>'.iconMantenimiento($instalacion->mantenimiento).'</td>
                                <td>'.$instalacion->observaciones.'</td>
                                <td class="accionesBtns">';
                if (isset($hasImages[$instalacion->id])) {
                    $revisionesInstalaciones .= '<a href="#modalInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small imageBtn"><i class="material-icons">image</i></a>';
                } else {
                    $revisionesInstalaciones .= '<a href="#" class="waves-effect waves-teal btn-flat btn-small grey-text disabled"><i class="material-icons">image</i></a>';
                }

                $revisionesInstalaciones .= '<a href="#modalEditarInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">edit</i></a></td><td>';
                if ($instalacion->intervenido) {
                    $revisionesInstalaciones .= '<i class="material-icons green-text">done</i>';
                }

                $revisionesInstalaciones .= '</td>
                                <td class="accionesBtns">
                                    <div class="accionesBtns'.$instalacion->id.' '.(((isset($validable[$instalacion->id])) && (!$validable[$instalacion->id]))?"hide":"").'">
                                        <a href="#modalValidarInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn actionBtn'.$instalacion->id.'"><i class="material-icons">thumb_up</i></a>
                                        <a href="#modalRechazarInstalacion'.$instalacion->id.'" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn actionBtn'.$instalacion->id.'"><i class="material-icons">thumb_down</i></a>
                                    </div>
                                </td>
                            </tr>';
                $modalsValidarNuevasInstalaciones .= '<div id="modalValidarInstalacion'.$instalacion->id.'" class="modal modalInstalaciones">
                            <div class="modal-content">
                                <h4>Validar Revisión</h4>
                                <div class="row">
                                    <div class="col s12">
                                        <h5>
                                            ¿Seguro que quieres validar esta instalación?
                                        </h5>
                                        <p>
                                            Esta acción no se puede deshacer
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col s12">
                                        <table>
                                            <thead>
                                            <tr>
                                                <th></th>
                                                <th>Instalación original</th>
                                                <th>Revisión</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_rango->valor, $instalacion->tipo_rango_value).'">
                                                <td>RANGO</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_rango->valor.'</td>
                                                <td>'.$instalacion->tipo_rango_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_instalacion->valor, $instalacion->tipo_instalacion_value).'">
                                                <td>TIPO INSTALACIÓN</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_instalacion->valor.'</td>
                                                <td>'.$instalacion->tipo_instalacion_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->nombre_instalacion->valor, $instalacion->nombre_instalacion_value).'">
                                                <td>NOMBRE</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->nombre_instalacion->valor.'</td>
                                                <td>'.$instalacion->nombre_instalacion_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->material->valor, $instalacion->material_value).'">
                                                <td>MATERIAL</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->material->valor.'</td>
                                                <td>'.$instalacion->material_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_residuo->valor, $instalacion->tipo_residuo_value).'">
                                                <td>TIPO RESIDUO</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_residuo->valor.'</td>
                                                <td>'.$instalacion->tipo_residuo_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_ubicacion->valor, $instalacion->tipo_ubicacion_value).'">
                                                <td>TIPO UBICACIÓN</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_ubicacion->valor.'</td>
                                                <td>'.$instalacion->tipo_ubicacion_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->numero, $instalacion->numero).'">
                                                <td>NÚMERO</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->numero.'</td>
                                                <td>'.$instalacion->numero.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_puerta->valor, $instalacion->tipo_puerta_value).'">
                                                <td>PUERTA</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_puerta->valor.'</td>
                                                <td>'.$instalacion->tipo_puerta_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_piso->valor, $instalacion->tipo_piso_value).'">
                                                <td>PISO</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_piso->valor.'</td>
                                                <td>'.$instalacion->tipo_piso_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_escalera->valor, $instalacion->tipo_escalera_value).'">
                                                <td>ESCALERA</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->tipo_escalera->valor.'</td>
                                                <td>'.$instalacion->tipo_escalera_value.'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->mantenimiento, $instalacion->mantenimiento).'">
                                                <td>MANTENIMIENTO</td>
                                                <td>'.iconMantenimiento($arrayInstalacionesLloc[$instalacion->from_instalacion]->mantenimiento).'</td>
                                                <td>'.iconMantenimiento($instalacion->mantenimiento).'</td>
                                            </tr>
                                            <tr class="'.compareRevision($arrayInstalacionesLloc[$instalacion->from_instalacion]->observaciones, $instalacion->observaciones).'">
                                                <td>OBSERVACIONES</td>
                                                <td>'.$arrayInstalacionesLloc[$instalacion->from_instalacion]->observaciones.'</td>
                                                <td>'.$instalacion->observaciones.'</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                <a href="#!" class="modal-action modal-close waves-effect waves-green btn validarInstalacionBtn" id="validarInstalacionBtn'.$instalacion->id.'" idInstalacion="'.$instalacion->id.'" idFrom="'.$instalacion->from_instalacion.'">Validar</a>
                            </div>
                        </div>';
                $modalsRechazarNuevasInstalaciones .= generateModalsRechazarNuevasInstalaciones($instalacion->id, 'Revisión');
                $modalsEditarInstalaciones .= '<div id="modalEditarInstalacion'.$instalacion->id.'" class="modal">
                            <div class="modal-content">
                                <h4>Editar Revisión</h4>
                                <div class="row">
                                    <form id="edicion'.$instalacion->id.'">
                                        <div class="col s2">
                                            '.mountSelect('RANGO', $instalacion->tipo_rango, 'Rango', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            '.mountSelect('TIPO_INSTALACION', $instalacion->tipo_instalacion, 'Tipo instalación', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            '.mountSelect('NOMBRE_INSTALACION', $instalacion->nombre_instalacion, 'Nombre instalación', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s2">
                                            '.mountSelect('TIPO_MATERIAL', $instalacion->material, 'Material', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            '.mountSelect('TIPO_RESIDUO', $instalacion->tipo_residuo, 'Tipo residuo', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s5">
                                            <div class="input-field col s12">
                                                <input placeholder="Cantidad" id="cantidad'.$instalacion->id.'" type="number" value="'.$instalacion->numero.'">
                                                <label for="cantidad">Cantidad</label>
                                            </div>
                                        </div>
                                        <div class="col s7">
                                            '.mountSelect('TIPO_UBICACION', $instalacion->tipo_ubicacion, 'Tipo ubicación', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s1">
                                            '.mountSelect('TIPO_PISO', $instalacion->tipo_piso, 'Piso', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s2">
                                            '.mountSelect('TIPO_PUERTA', $instalacion->tipo_puerta, 'Puerta', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s2">
                                            '.mountSelect('TIPO_ESCALERA', $instalacion->tipo_escalera, 'Escalera', $listDesplegables, $instalacion->id).'
                                        </div>
                                        <div class="col s12">
                                            <div class="input-field col s12">
                                                <textarea id="observaciones'.$instalacion->id.'" class="materialize-textarea">'.$instalacion->observaciones.'</textarea>
                                                <label for="observaciones'.$instalacion->id.'">Observaciones</label>
                                            </div>
                                        </div>
                                        <div class="col s4">
                                            '.intervenidoCheck($instalacion->id, $instalacion->intervenido).'
                                        </div>
                                        <div class="col s4">
                                            '.mantenimientoCheck($instalacion->id, $instalacion->mantenimiento).'
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
                                <a href="#!" class="modal-action modal-close waves-effect waves-green btn validarEdicionBtn" id="validarEdicionBtn'.$instalacion->id.'" idInstalacion="'.$instalacion->id.'">Confirmar edición</a>
                            </div>
                        </div>';
            }
        }
    } elseif ($listInstalaciones->codigo == 200) {
        $mostrarOfertas = true;
    }
    
    ?>
    <div class="navbar-fixed">
        <nav class="red darken-3" role="navigation">
            <div class="nav-wrapper">
                <a id="logo-container" href="index.php" class="brand-logo">
                    <img src="imgs/logo_negativo.png" alt="Aqualyt">
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
            <div class="col s10">
                <h4 class="titleInstalacion"><a href="<?php echo AQUAACTION; ?>?SOURCE=PS2&amp;ACTION=SHOWINITIAL&amp;LOAD_PS=<?php echo $idPS; ?>" target="_blank">PS: <?php echo $idPS; ?></a><br />
                <?php 
                    echo '<small><a href="#" class="irLugar" idLugar="'.$idLloc.'">Lugar: '.$idLloc.'</a>'.(($infoPS->MANTENIMIENTO)?' (MANTENIMIENTO <a href="#" class="irMantenimiento" idMantenimiento="'.$infoPS->MANTENIMIENTO.'">'.$infoPS->MANTENIMIENTO.'</a>)':'').'<form id="LLOCEDIT'.$idLloc.'" action="'.AQUAACTION.'" method="POST" style="display:none;" target="_blank"><input id="nav_llocedit" name="nav_lloc_edit" type="submit" value="'.$idLloc.'" title="ir al Lloc"><input type="hidden" id="ACTIONLLOCEDIT" name="ACTION" value="EDITAR_EXTERNO"><input type="hidden" id="SOURCELLOC" name="SOURCE" value="LUGAR_ACTUACION"><input type="hidden" id="IDLLOCEDIT" name="ID" value="'.$idLloc.'"></form><form id="EDITCONTRATO_MNT" action="'.AQUAACTION.'" method="POST" target="_blank" style="display:none;><input id="nav_contrato_mnt" name="nav_contrato_mnt" type="submit" value="'.$infoPS->MANTENIMIENTO.'" title="ir al Contrato"><input type="hidden" id="ACTION2" name="ACTION" value="EDIT_EXTERNAL"><input type="hidden" id="SOURCECONTRATO2" name="SOURCE" value="CONTRATO_MANTENIMIENTO"><input type="hidden" id="CONTRATO_MNT_IDS2" name="CONTRATO_MNT_IDS" value="'.$infoPS->MANTENIMIENTO.'"></form></small>'; 
                ?>
                </h4>
                <?php 
                    if ($validarInstalaciones) {
                        echo '<a class="waves-effect waves-light btn modal-trigger orange" href="#modalValidarInstalaciones">Validar Todas las instalaciones</a> ';
                    }
                    if (($mostrarOfertas) && ($portadasValidadas) && (!$mostrarAtributos)) {
                        echo '<a class="waves-effect waves-light btn modal-trigger" href="#modalValidarPS">Validar PS</a> ';
                    }
                ?>
            </div>
            <div class="col s2">
                <h5><small>Litros de agua: <?php echo $litrosAgua; ?></small></h5>
            </div>
        </div>
        <div class="row">
            <div class="col m6">
                <h5>Información de la PS</h5>
                <div class="card z-depth-2">
                    <div class="card-content">
                        <div class="row">
                            <div class="col">
                                <strong>Dirección:</strong>
                            </div>
                            <div class="col">
                                <?php echo $infoPS->direccion; ?>, <?php echo $infoPS->poblacion; ?>, <?php echo $infoPS->provincia; ?>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col infoPS">
                                <strong>Estado:</strong>
                            </div>
                            <div class="col">
                                <?php echo $infoPS->estado_string.' <a href="#editarEstado" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">edit</i>editar</a>'; ?>
                            </div>
                            <div class="col infoPS">
                                <strong>Naturaleza:</strong>
                            </div>
                            <div class="col editarBtn">
                                <?php echo $infoPS->naturaleza_string.' <a href="#editarNaturaleza" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn"><i class="material-icons">edit</i>editar</a>'; ?>
                            </div>
                            
                        </div>

                        <div class="row">
                            <div class="col infoPS">
                                <strong>Tipo:</strong>
                            </div>
                            <div class="col infoPS">
                                <?php echo $infoPS->tipo_string; ?>
                            </div>
                           
                            <div class="col infoPS">
                                <strong>Urgencia:</strong>
                            </div>
                            
                            <div class="col infoPS">
                                <span>
                                    <?php echo $infoPS->urgencia_string; ?>
                                </span>
                            </div>
                            <div class="col accionesBtns"> 
                                <a  href="#editarUrgencia" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn">
                                    <i class="material-icons">edit</i>
                                    editar
                                </a>
                            </div>
                        </div>

                        <div class="row">
                        
                            <div class="col">
                                <strong>Cliente:</strong>
                            </div>
                            <div class="col">
                                <?php echo $infoPS->cliente_nombre; ?>
                            </div>
                            <div class="col">
                                <strong>Agente:</strong>
                            </div>
                            <div class="col">
                                <?php echo $infoPS->agente_nombre; ?>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col">
                                <strong>Solicitante:</strong>
                            </div>
                            <div class="col">
                                <?php echo $infoPS->solicitante_nombre; ?>
                            </div>

                        </div>
                        
                    </div>
                        
                </div>
            </div>
            <div class="col m6">
                <h5>Equipo</h5>
                <div class="card z-depth-2">
                    <div class="card-content">


                        <div class="row">

                            <div class="col s2 infoPS">
                                <strong>Equipo:</strong>
                            </div>

                            <div class="col infoPS"><?php echo $infoPS->equipo_asignado; ?></div>

                            <div class="col accionesBtns">
                            
                            <a href="#editarEquipo" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn">
                            <i class="material-icons">edit</i>editar</a>
                            
                            </div>

                        </div>
                        <div class="row">
                            <div class="col s2 infoPS">
                                <strong>Operarios:</strong>
                            </div>
                            <div class="col infoPS">
                                <?php 
                                $arrayComponentes = array();
                                foreach ($infoPS->componentes as $componente) {
                                    array_push($arrayComponentes, $componente->NOMBRE);
                                }
                                echo join(', ', $arrayComponentes); 
                                ?>
                            </div>
                                
                            <div class="col accionesBtns">
                                <a href="#editarOperario" class="modal-trigger waves-effect waves-teal btn-flat btn-small actionBtn">
                                <i class="material-icons">edit</i>editar</a> 
                            
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
            if (isset($observaciones)) {
        ?>
            <div class="row">
                <div class="col s12">
                    <h5>Observaciones <a href="#modalEditarObservaciones" class="modal-trigger btn-flat btn-small actionBtn"><i class="material-icons">edit</i>editar</a></h5>
                    <div class="card z-depth-2">
                        <div class="card-content">
                            <p><?php echo $observaciones; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            }
        ?>
            <div class="row">
                <div class="col s6">
                    <h5>Ofertas recibidas</h5>
                    <?php 
                        if(isset($servicios)){
                    ?>
                    <div class="card z-depth-2">
                        <div class="card-content">
                            <div class="card-title">
                                Ofertas de servicio
                            </div>
                            <?php 
                                echo '<ul class="collection ofertas">';
                                foreach ($servicios as $servicio) {
                                    echo '<li class="collection-item">';
                                    echo ($servicio->observaciones!='')?'<span class="title">'.$servicio->observaciones.'</span>':'';
                                    echo ($servicio->equipos_capacitados!='')?'<p><strong>Equipos capacitados:</strong> '.$servicio->equipos_capacitados.'</p>':'';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            ?>
                        </div>
                    </div>
                    <?php
                    }
                    if(isset($mantenimientos)){
                    ?>
                    <div class="card z-depth-2">
                        <div class="card-content">
                            <div class="card-title">
                                Ofertas de mantenimiento
                            </div>
                            <?php 
                                echo '<ul class="collection ofertas">';
                                foreach ($mantenimientos as $mantenimiento) {
                                    echo '<li class="collection-item">';
                                    echo ($mantenimiento->periodicidad!=0)?'<strong>Periodicidad:</strong> '.$mantenimiento->periodicidad.'<br />':'';
                                    echo ($mantenimiento->tiempo_estimado!='')?'<strong>Tiempo estimado:</strong> '.$mantenimiento->tiempo_estimado.'<br />':'';
                                    echo (($mantenimiento->observaciones!='')&&($mantenimiento->observaciones!='None'))?'<strong>Observaciones:</strong> '.$mantenimiento->observaciones.'<br />':'';

                                    echo '<table class="centered highlight">
                                    <thead>
                                      <tr>
                                          <th>Instalaciones</th>
                                          <th>Periodicidad</th>
                                          <th>Tiempo estimado</th>
                                          <th>Observaciones</th>
                                      </tr>
                                    </thead>
                                    <tbody>';
                                    foreach ($mantenimiento->INSTALACIONES as $instalacionOferta) {
                                        if ($instalacionOferta->nombreInstalacion) {
                                            echo '<tr>
                                                    <td>'.$instalacionOferta->nombreInstalacion.'</td>
                                                    <td>'.$instalacionOferta->periodicidad.'</td>
                                                    <td>'.$instalacionOferta->tiempo_estimado.'</td>
                                                    <td>'.$instalacionOferta->observaciones.'</td>
                                                </tr>';
                                        } else {
                                            echo '<tr>
                                                    <td>'.$instalacionOferta->nombreTemporal.'</td>
                                                    <td>'.$instalacionOferta->periodicidad.'</td>
                                                    <td>'.$instalacionOferta->tiempo_estimado.'</td>
                                                    <td>'.$instalacionOferta->observaciones.'</td>
                                                </tr>';
                                        }
                                    }
                                    echo '</tbody>
                                        </table>';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            ?>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                    <div class="card z-depth-2">
                        <div class="card-content">
                            <div class="row">
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
                                    <a href="<?php echo AQUAACTION; ?>?SOURCE=PS2&ACTION=SHOWINITIAL&LOAD_PS=<?php echo $idPS; ?>&TAB=Docu.%20PS|docu_ps#" target="_blank" class="waves-effect waves-teal btn col s12 orange">Doc. PS</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col s6">
                    <h5>Ofertas de servicio existentes</h5>
                    <?php 
                        if (count($arrayServiciosLugar)>0) {
                            echo '<ul class="collection with-header z-depth-2">';
                            foreach ($arrayServiciosLugar as $key => $servicioObj) {
                                switch ($key) {
                                    case 'OFERTAS_MANTENIMIENTO':
                                        $tituloServicio = 'Ofertas mantenimiento';
                                        $tipoOferta = 1;
                                        break;
                                    case 'OFERTAS_SERVICIO':
                                        $tituloServicio = 'Ofertas servicio';
                                        $tipoOferta = 2;
                                        break;
                                    case 'CONTRATOS_MANTENIMIENTO':
                                    default:
                                        $tituloServicio = 'Contratos mantenimiento';
                                        $tipoOferta = 3;
                                        break;
                                }
                                echo '<li class="collection-header"><b>'.$tituloServicio.'</b></li>';
                                foreach ($servicioObj as $value) {
                                    echo '<li class="collection-item">';
                                                switch ($tipoOferta) {
                                                    case 2:
                                                        echo "<form action='".AQUAACTION."' method='POST' target='_blank'>
                                                                <button class='btn waves-effect waves-light' type='submit' name='action'>IR a la Oferta $value->id
                                                                    <i class='material-icons right'>send</i>
                                                                </button>
                                                                <input type='hidden' id='ACTION' name='ACTION' value='EDIT_EXTERNAL'>
                                                                <input type='hidden' id='SOURCEOFERTA' name='SOURCE' value='OFERTA_SERVICIO'>
                                                                <input type='hidden' id='OFERTA_SRV_IDS' name='ID' value='$value->id'>
                                                                </form>";
                                                        break;
                                                    case 3:
                                                        echo "<form action='".AQUAACTION."' method='POST' target='_blank'>
                                                                <button class='btn waves-effect waves-light' type='submit' name='action'>IR al Contrato $value->id
                                                                    <i class='material-icons right'>send</i>
                                                                </button>
                                                                <input type='hidden' id='ACTION2' name='ACTION' value='EDIT_EXTERNAL'>
                                                                <input type='hidden' id='SOURCECONTRATO2' name='SOURCE' value='CONTRATO_MANTENIMIENTO'>
                                                                <input type='hidden' id='CONTRATO_MNT_IDS2' name='CONTRATO_MNT_IDS' value='$value->id'>
                                                                </form>";
                                                        break;
                                                    case 1:
                                                    default:
                                                        echo "<form action='".AQUAACTION."' method='POST' target='_blank'>
                                                                <button class='btn waves-effect waves-light' type='submit' name='action'>IR a la Oferta $value->id
                                                                    <i class='material-icons right'>send</i>
                                                                </button>
                                                                <input type='hidden' id='ACTION' name='ACTION' value='EDIT_EXTERNAL'>
                                                                <input type='hidden' id='SOURCEOFERTA_MNT' name='SOURCE' value='OFERTA_MANTENIMIENTO'>
                                                                <input type='hidden' id='OFERTA_MNT_IDS' name='OFERTA_MNT_IDS' value='$value->id'>
                                                                </form>";
                                                        break;
                                                }
                                    echo '</li>';
                                }
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<div class="card z-depth-2">
                                    <div class="card-content">
                                        No hay ofertas asociadas al lugar.
                                    </div>
                                </div>';
                        }
                        
                    ?>
                </div>
            </div>
            <?php
                if($mostrarAtributos){ ?>
            <div class="row">
                <div class="col s12">
                    <h5>Atributos del lugar de actuación</h5>
                    <div class="card z-depth-2">
                        <table class="highlight centered">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Tipo lugar</th>
                                    <th>Furgo</th>
                                    <th>Aqualyto</th>
                                    <th>Permiso corte</th>
                                    <th>Permiso tapa abierta</th>
                                    <th>Boca carga</th>
                                    <th>Observaciones boca carga</th>
                                    <th>Hidrante</th>
                                    <th>Observaciones hidrante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php echo $atributosTabla; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php 
                }
                if ($revisionesInstalaciones != '') {
            ?>
            <div class="row">
                <div class="col s12">
                    <h5>MODIFICACIÓN INSTALACIONES EXISTENTES (<?php echo "$countInstalacionesRevision/$countInstalaciones"; ?>)</h5>
                    <div class="card z-depth-2">
                        <table class="highlight centered">
                            <thead>
                            <tr>
                                <th>RANGO</th>
                                <th>TIPO INSTALACIÓN</th>
                                <th>NOMBRE</th>
                                <th>MATERIAL</th>
                                <th>TIPO RESIDUO</th>
                                <th>TIPO UBICACIÓN</th>
                                <th>NÚMERO</th>
                                <th>PUERTA</th>
                                <th>PISO</th>
                                <th>ESCALERA</th>
                                <th>MANTENIMIENTO</th>
                                <th>OBSERVACIONES</th>
                                <th>ACCIONES</th>
                                <th>INT</th>
                                <th>VALIDAR</th>
                            </tr>
                            </thead>

                            <tbody>

                        <?php
                        echo str_replace("None","",$revisionesInstalaciones);
                        ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
                }
                if ($nuevasInstalaciones != '') {
            ?>
            <div class="row">
                <div class="col s12">
                    <h5>INSTALACIONES NUEVAS(<?php echo "$countInstalacionesNuevas"; ?>)</h5>
                    <div class="card z-depth-2">
                        <table class="highlight centered">
                            <thead>
                            <tr>
                                <th>RANGO</th>
                                <th>TIPO INSTALACIÓN</th>
                                <th>NOMBRE</th>
                                <th>MATERIAL</th>
                                <th>TIPO RESIDUO</th>
                                <th>TIPO UBICACIÓN</th>
                                <th>NÚMERO</th>
                                <th>PUERTA</th>
                                <th>PISO</th>
                                <th>ESCALERA</th>
                                <th>MANTENIMIENTO</th>
                                <th>OBSERVACIONES</th>
                                <th>ACCIONES</th>
                                <th>INT</th>
                                <th>VALIDAR</th>
                            </tr>
                            </thead>

                            <tbody>

                        <?php
                        echo str_replace("None","",$nuevasInstalaciones);
                        ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
                }
                if ($todasInstalaciones != '') {
            ?>
            <div class="row">
                <div class="col s12">
                    <h5>INSTALACIONES EXISTENTES EN EL LLOC</h5>
                    <div class="card z-depth-2">
                        <table class="highlight">
                            <thead>
                            <tr>
                                <th>RANGO</th>
                                <th>TIPO INSTALACIÓN</th>
                                <th>NOMBRE</th>
                                <th>MATERIAL</th>
                                <th>TIPO RESIDUO</th>
                                <th>TIPO UBICACIÓN</th>
                                <th>NÚMERO</th>
                                <th>PUERTA</th>
                                <th>PISO</th>
                                <th>ESCALERA</th>
                                <th>MANTENIMIENTO</th>
                                <th>OBSERVACIONES</th>
                                <th>ACCIONES</th>
                                <th>INT</th>
                                <th></th>
                                <th></th>
                            </tr>
                            </thead>

                            <tbody>

                        <?php
                            echo str_replace("None","",$todasInstalaciones);
                        ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php 
                }
            ?>
            <div class="row">
                <div class="col s12">
                    <a class="btn modal-trigger" href="#crearInstalacion">Crear instalación</a>
                </div>
            </div>
        </div>
        <br>
        <br>
    </main>
    <!-- Modals -->
    <div id="crearInstalacion" class="modal">
        <div class="modal-content">
            <h4>Crear instalación</h4>
            <div class="row">
                <form id="creacionInstalacion">
                    <div class="col s2">
                        <?php echo mountSelect('RANGO', null, 'Rango', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s5">
                        <?php echo mountSelect('TIPO_INSTALACION', null, 'Tipo instalación', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s5">
                        <?php echo mountSelect('NOMBRE_INSTALACION', null, 'Nombre instalación', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s2">
                        <?php echo mountSelect('TIPO_MATERIAL', null, 'Material', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s5">
                        <?php echo mountSelect('TIPO_RESIDUO', null, 'Tipo residuo', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s7">
                        <?php echo mountSelect('TIPO_UBICACION', null, 'Tipo ubicación', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s1">
                        <?php echo mountSelect('TIPO_PISO', null, 'Piso', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s2">
                        <?php echo mountSelect('TIPO_PUERTA', null, 'Puerta', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s2">
                        <?php echo mountSelect('TIPO_ESCALERA', null, 'Escalera', $listDesplegables, 'Nueva'); ?>
                    </div>
                    <div class="col s12">
                        <div class="input-field col s12">
                            <textarea id="observacionesNueva" name="observacionesNueva" class="materialize-textarea"></textarea>
                            <label for="observacionesNueva">Observaciones</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="crearInstalacionBtn" idPS="<?php echo $idPS; ?>" idLloc="<?php echo $idLloc; ?>">Crear instalación</a>
        </div>
    </div>
    <?php
        // Modals imágenes PS
        if (count($arrayImagesPS)>0) {
            foreach ($arrayImagesPS as $key => $imagenPS) {                
                echo '<div id="modalInstalacion'.$key.'" class="modal modal-fixed-footer modalFotografias">
                        <div class="modal-content">
                            <h4>
                                 Fotografías
                            </h4>
                            <div class="row">
                                <div class="col s12">
                                        <div class="row">';
                                            if(isset($imagenPS[0]->new)) {
                                                switch ($imagenPS[0]->idEstadoValidacion) {
                                                    case '2':
                                                        $validarHide = 'hide';
                                                        $rechazarHide = '';
                                                        break;
                                                    case '3':
                                                        $validarHide = '';
                                                        $rechazarHide = 'hide';
                                                        break;
                                                    default:
                                                        $validarHide = '';
                                                        $rechazarHide = '';
                                                        break;
                                                }
                                                echo '<div class="col s4">
                                                        <div class="col s12">
                                                        <div class="card">
                                                        <div class="card-action accionesImg'.$imagenPS[0]->id.' accionesInst'.$key.'" idEstadoValidacion="'.$imagenPS[0]->idEstadoValidacion.'">
                                                        <a href="#" class="text-green validarFotoInstalacion '.$validarHide.'" idDocumento="'.$imagenPS[0]->id.'" idInstalacion="'.$key.'">Validar</a>
                                                        <a href="#" class="text-red rechazarFotoInstalacion '.$rechazarHide.'" idDocumento="'.$imagenPS[0]->id.'" idInstalacion="'.$key.'">Rechazar</a>
                                                    </div>
                                                        <div class="card-image">
                                                            <img class="materialboxed" src="'.$imagenPS[0]->new.'">
                                                            <span class="card-title">Nueva foto de portada</span>
                                                        </div>';
                                                if ($imagenPS[0]->descripcion != '') {
                                                    echo '<div class="card-content">
                                                                <p>'.$imagenPS[0]->descripcion.'</p>
                                                            </div>';
                                                }
                                                    echo '</div>
                                                    </div>
                                                    </div>';
                                            }
                                            if(isset($imagenPS[1]->new)) {
                                                switch ($imagenPS[1]->idEstadoValidacion) {
                                                    case '2':
                                                        $validarHide = 'hide';
                                                        $rechazarHide = '';
                                                        break;
                                                    case '3':
                                                        $validarHide = '';
                                                        $rechazarHide = 'hide';
                                                        break;
                                                    default:
                                                        $validarHide = '';
                                                        $rechazarHide = '';
                                                        break;
                                                }
                                                echo '<div class="col s4">
                                                        <div class="col s12">
                                                        <div class="card">
                                                        <div class="card-action accionesImg'.$imagenPS[1]->id.' accionesInst'.$key.'" idEstadoValidacion="'.$imagenPS[1]->idEstadoValidacion.'">
                                                        <a href="#" class="text-green validarFotoInstalacion '.$validarHide.'" idDocumento="'.$imagenPS[1]->id.'" idInstalacion="'.$key.'">Validar</a>
                                                        <a href="#" class="text-red rechazarFotoInstalacion '.$rechazarHide.'" idDocumento="'.$imagenPS[1]->id.'" idInstalacion="'.$key.'">Rechazar</a>
                                                    </div>
                                                        <div class="card-image">
                                                            <img class="materialboxed" src="'.$imagenPS[1]->new.'">
                                                            <span class="card-title">Antes de la intervención</span>
                                                        </div>';
                                                if ($imagenPS[1]->descripcion != '') {
                                                    echo '<div class="card-content">
                                                                <p>'.$imagenPS[1]->descripcion.'</p>
                                                            </div>';
                                                }
                                                echo '</div>
                                                    </div>
                                                    </div>';
                                            }
                                            if(isset($imagenPS[2]->new)) {
                                                switch ($imagenPS[2]->idEstadoValidacion) {
                                                    case '2':
                                                        $validarHide = 'hide';
                                                        $rechazarHide = '';
                                                        break;
                                                    case '3':
                                                        $validarHide = '';
                                                        $rechazarHide = 'hide';
                                                        break;
                                                    default:
                                                        $validarHide = '';
                                                        $rechazarHide = '';
                                                        break;
                                                }
                                                echo '<div class="col s4">
                                                        <div class="col s12">
                                                        <div class="card">
                                                        <div class="card-action accionesImg'.$imagenPS[2]->id.' accionesInst'.$key.'" idEstadoValidacion="'.$imagenPS[2]->idEstadoValidacion.'">
                                                            <a href="#" class="text-green validarFotoInstalacion '.$validarHide.'" idDocumento="'.$imagenPS[2]->id.'" idInstalacion="'.$key.'">Validar</a>
                                                            <a href="#" class="text-red rechazarFotoInstalacion '.$rechazarHide.'" idDocumento="'.$imagenPS[2]->id.'" idInstalacion="'.$key.'">Rechazar</a>
                                                        </div>
                                                        <div class="card-image">
                                                            <img class="materialboxed" src="'.$imagenPS[2]->new.'">
                                                            <span class="card-title">Después de la intervención</span>
                                                        </div>';
                                                if ($imagenPS[2]->descripcion != '') {
                                                    echo '<div class="card-content">
                                                                <p>'.$imagenPS[2]->descripcion.'</p>
                                                            </div>';
                                                }
                                                echo '</div>
                                                    </div>
                                                    </div>';
                                            }
                                        echo '</div>
                                    </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat ">Cerrar</a>
                        </div>
                    </div>';
            }
        }
        // Modals imágenes Portada
        if (count($arrayImagesPortada)>0) {
            foreach ($arrayImagesPortada as $key => $imagenPortada) {                
                echo '<div id="modalInstalacion'.$key.'" class="modal modal-fixed-footer modalFotografias">
                        <div class="modal-content">
                            <h4>
                                 Fotografía de portada
                            </h4>
                            <div class="row">
                                <div class="col s12">
                                    <div class="row">';
                                    if(isset($imagenPortada[0]->new)) {
                                        echo '<div class="col s6">
                                            <div class="col s10">
                                                        <div class="card">
                                                        <div class="card-action accionesImg'.$imagenPortada[0]->id.'">
                                                            <a href="#" class="text-green validarFotoPortada" idDocumento="'.$imagenPortada[0]->id.'" idInstalacion="'.$key.'">Validar</a>
                                                            <a href="#" class="text-red rechazarFotoPortada" idDocumento="'.$imagenPortada[0]->id.'" idInstalacion="'.$key.'">Rechazar</a>
                                                        </div>
                                                        <div class="card-image">
                                                            <img class="materialboxed" src="'.$imagenPortada[0]->new.'">
                                                            <span class="card-title ">Nueva foto de portada</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>';
                                    }
                                    if(isset($imagenPortada[1]->old)) {
                                        echo '<div class="col s6">
                                        <div class="col s10">
                                                    <div class="card">
                                                        <div class="card-image">
                                                            <img class="materialboxed" src="'.$imagenPortada[1]->old.'">
                                                            <span class="card-title">Foto de portada en sistema</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>';
                                    }
                                    echo '</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat ">Cerrar</a>
                        </div>
                    </div>';
            }
        }
    ?>
    <div id="modalValidarPS" class="modal">
        <div class="modal-content">
            <h4>Validar PS <?php echo $idPS; ?></h4>
            <div class="row">
                <div class="col s12">
                    <h5>
                        ¿Seguro que quieres validar la PS <?php echo $idPS; ?>?
                    </h5>
                    <p>
                        Esta acción no se puede deshacer
                    </p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="validarBtn" idPS="<?php echo $idPS; ?>">Validar</a>
        </div>
    </div>
    <div id="modalValidarInstalaciones" class="modal">
        <div class="modal-content">
            <h4>Validar Todas las instalaciones de la PS <?php echo $idPS; ?></h4>
            <div class="row">
                <div class="col s12">
                    <h5>
                        ¿Seguro que quieres validar las instalaciones de PS <?php echo $idPS; ?>?
                    </h5>
                    <p>
                        Esta acción no se puede deshacer
                    </p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="validarBtn" idUsuario="<?php echo $_COOKIE['AquaCoordinadorTokenID']; ?>" idPS="<?php echo $idPS; ?>">Validar</a>
        </div>
    </div>
    <div id="editarUrgencia" class="modal">
        <div class="modal-content">
            <div class="row">   
                <div class="col s12 centered">
                    <h4>Modificar la urgencia de la PS</h4>
                </div>
                <div class="col s12 centered">
                    <?php echo mountSelect('URGENCIA', $infoPS->urgencia_id, 'Urgencia', $listDesplegables, $infoPS->id); ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
           
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarUrgenciaBtn" idPS="<?php echo $idPS; ?>">Modificar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
        </div>

    </div>
    <div id="editarEstado" class="modal">
        <div class="modal-content">
           <div class="row ">
                <div class="col s12 centered">
                    <h4>Modificar el estado de la PS</h4>
                </div>
                <div class="col s12 centered">
                    <?php echo mountSelect('ESTADO', $infoPS->estado_id, 'Estado', $listDesplegables, $infoPS->id); ?>
                </div>
            </div>
        </div>
        <div class="modal-footer col s12">
        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarEstadoBtn" idPS="<?php echo $idPS; ?>">Modificar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
           
        </div>
    </div>
    <div id="editarNaturaleza" class="modal">
        <div class="modal-content">
            <div class="row">
                <div class="col s12 centered">
                    <h4>Modificar la naturaleza de la PS</h4>
                </div>
                <div class="col s12 centered">
                    <?php echo mountSelect('NATURALEZA', $infoPS->naturaleza_id, 'Naturaleza', $listDesplegables, $infoPS->id); ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarNaturalezaBtn" idPS="<?php echo $idPS; ?>">Modificar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
            
        </div>
    </div>

    <div id="editarEquipo" class="modal display-flex">
        <div class="modal-content">
            <div class="row">
                <div class="col s12 centered">
                    <h4>Modificar el Equipo</h4>
                </div>
                <div class="col s12 centered">
                    <?php echo mountSelect('EQUIPO', $infoPS->equipo_id, 'Equipo', $listDesplegables, $infoPS->id); ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="editarEquipoBtn" idPS="<?php echo $idPS; ?>">Modificar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
           
        </div>
    </div>


    <div id="editarOperario" class="modal">
        <div class="modal-content">
            <div class="row">
                <div class="col s12 centered">
                    <h4>Modificar Operarios del Equipo</h4>
                </div>
                <div class="col s12 centered">
                    <?php
                    // function mountSelect($tipo, $valor, $label, $listOptions, $idInstalacion){
                    $count=0;
                    foreach ($infoPS->componentes AS $operario) {
                        echo "<div style='display: inline-block;'>";
                            echo mountSelect('OPERARIOS', $operario->ID, $operario->NOMBRE, $listDesplegables, $operario->ID);
                           echo '<a id="editarOperarioBtn'.$count.'" href="#!" style="display:none;" class="modal-action waves-effect waves-green btn" idPS="'.$idPS.'" idOperario="'.$operario->ID.'">Modificar</a>';
                           //echo '<a id="editarOperarioBtn" href="#!" class="modal-action waves-effect waves-green btn" idPS="'.$idPS.'" idOperario="'.$operario->ID.'">Modificar</a>';
                           echo "</div>";
                        echo "<div id='callback_".$operario->ID."' style='display:inline-block;'></div>";
                       $count++;
                    }
//                    echo mountSelect('EMPLEADO', $infoPS->empleado_id, 'Estado', $listDesplegables, $infoPS->id); 
                    ?>
                </div>
            </div>
        </div>
        <div class="modal-footer">
        <a href="#!" class="modal-action modal-close waves-effect waves-green btn" cantidad_botones="<?php echo $count; ?>" id="guardarCambiosOperarios">Guardar</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
            
        </div>
    </div>

    <div id="modalOfertarServicio" class="modal">
        <div class="modal-content">
            <h4 class="centered">Ofertar servicio</h4>

           
                <form action="#" class="col 12" id="ofertarServicioForm">
                    
                        <div class="input-field">
                            <select id="personalSelect">
                                <option value="" disabled selected>Selecciona destinatario</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <textarea id="ofertaServicio" class="materialize-textarea"></textarea>
                            <label for="ofertaServicio">Servicio a ofertar</label>
                        </div>
                    
                </form>
            

            <form class="col 12" target="_blank" id="PS_To_Ofs" name="PS_To_Ofs" method="POST" action="<?php echo AQUAACTION; ?>" style="display:inline;">
                <input class="" type="hidden" id="SOURCE" name="SOURCE" value="OFERTA_SERVICIO">
                <input class="" type="hidden" id="ACTION" name="ACTION" value="CREAR_OFERTA_SERVICIO_FROM_PS">
                <input class="" type="hidden" id="PS" name="PS" value="<?php echo $idPS; ?>">
            </form>

        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="ofertarFormBtn">Crear oferta</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="tareaServicioBtn" idPS="<?php echo $idPS; ?>">Crear tarea</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
        </div>

    </div>

    <div id="modalOfertarMantenimiento" class="modal">
        <div class="modal-content">



            
                <div class="col 12 centered">
                    <h4>Ofertar mantenimiento</h4>
                </div>
          
                
            <form action="#" class="col 12" id="ofertarServicioForm">
                
                    <div class=" input-field ">
                        <select id="personalSelectMantenimiento">
                            <option value="" disabled selected>Selecciona destinatario</option>
                        </select>
                    </div>
                    <div class=" input-field">
                        <textarea id="ofertaMantenimiento" class="materialize-textarea"></textarea>
                        <label for="ofertaMantenimiento">Mantenimiento a ofertar</label>
                    </div>
                
            </form>
           
           
            <form target="_blank" id="PS_To_OfM" name="PS_To_OfM" method="POST" action="<?php echo AQUAACTION; ?>" style="display:inline;">
                <input type="hidden" id="SOURCE" name="SOURCE" value="OFERTA_MANTENIMIENTO">
                <input type="hidden" id="ACTION" name="ACTION" value="CREAR_OFERTA_MANTENIMIENTO_FROM_PS">
                <input type="hidden" id="PS" name="PS" value="<?php echo $idPS; ?>">
            </form>
            
            
        </div>

        <div class="row modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="ofertarMantFormBtn">Crear oferta</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="tareaMantenimientoBtn" idPS="<?php echo $idPS; ?>">Crear tarea</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
        </div>
    </div>


    <div id="modalInformeTV" class="modal">
        <div class="modal-content">
            <h4>Informe técnico</h4>
            <div class="row">
                <form action="#" class="col s12" id="ofertarServicioForm">
                    <div class="row">
                        <div class="input-field col s4">
                            <select id="personalSelectInforme">
                                <option value="" disabled selected>Selecciona destinatario</option>
                            </select>
                        </div>
                        <div class="input-field col s8">
                            <textarea id="ofertaInforme" name="ofertaInforme" class="materialize-textarea"></textarea>
                            <label for="ofertaInforme">Informe a ofertar</label>
                        </div>
                    </div>
                </form>
            </div>
            <form id="crear_informe_de_ps" name="informetec_create1" method="POST" action="<?php echo AQUAACTION; ?>" style="display:inline;" target="_blank">
                <input type="hidden" id="informetec_crea_source1" name="SOURCE" value="INFORMES_TEC">
                <input type="hidden" id="informetec_crea_action1" name="ACTION" value="CREAR">
                <input type="hidden" id="informetec_crea_PS" name="PS" value="<?php echo $idPS; ?>">
            </form>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="informeFormBtn">Crear informe</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-green btn" id="tareaInformeBtn" idPS="<?php echo $idPS; ?>">Crear tarea</a>
            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
        </div>
    </div>
    <?php 
        echo isset($modalsValidarNuevasInstalaciones)?str_replace("None","",$modalsValidarNuevasInstalaciones):'';
        echo isset($modalsRechazarNuevasInstalaciones)?str_replace("None","",$modalsRechazarNuevasInstalaciones):'';
        echo isset($modalsEditarInstalaciones)?str_replace("None","",$modalsEditarInstalaciones):'';
        echo isset($modalValidarAtributos)?str_replace("None","",$modalValidarAtributos):'';
        echo isset($modalRechazarAtributos)?str_replace("None","",$modalRechazarAtributos):'';
        echo isset($modalEditarAtributos)?str_replace("None","",$modalEditarAtributos):'';
        echo isset($modalsCrearRevisiones)?str_replace("None","",$modalsCrearRevisiones):'';
        echo isset($modalsEliminarInstalaciones)?str_replace("None","",$modalsEliminarInstalaciones):'';
        echo isset($modalsIntervenirInstalaciones)?str_replace("None","",$modalsIntervenirInstalaciones):'';
        echo isset($modalEditarObservaciones)?str_replace("None","",$modalEditarObservaciones):'';
        echo isset($modalsMantenimientoInstalaciones)?str_replace("None","",$modalsMantenimientoInstalaciones):'';

        include 'scripts.php';
    }


echo "<script>";
for($counter=0; $counter<=$count; $counter++) {
    ?>
    $("#editarOperario").on("click", "#editarOperarioBtn<?php echo $counter; ?>", function (a) {
        var i1 = $(this).attr("idOperario"),
            a = $(this).attr("idPS"),
            i2 = $("#OPERARIOS" + i1).val();
        
        editarOperarioPS(a, i1, i2);
    })
    <?php
    if ($counter<$count) {
        echo ",";
    }
}

echo "</script>";
?>
</body>

</html>
