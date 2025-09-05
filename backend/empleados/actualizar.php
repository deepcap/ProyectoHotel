<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit('Método no permitido'); }

$id = (int)($_POST['id_empleado'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellidoP = trim($_POST['apellidoP'] ?? '');
$apellidoM = trim($_POST['apellidoM'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$areaInput = trim($_POST['area'] ?? '');   // debería venir ID del select
$turnoRaw  = trim($_POST['turno'] ?? '');

$err=[];
if($id<=0) $err[]='ID inválido';
if($nombre==='') $err[]='Nombre requerido';
if($apellidoP==='') $err[]='Apellido paterno requerido';
if($telefono==='') $err[]='Teléfono requerido';
if($areaInput==='') $err[]='Área requerida';
if($turnoRaw===''||!ctype_digit($turnoRaw)) $err[]='Turno inválido';
$turnoId=(int)$turnoRaw;
if(!in_array($turnoId,[1,2,3], true)) $err[]='Turno fuera de rango';
if($err){ http_response_code(422); exit(implode("\n",$err)); }

// Detectar columnas/tipos
$cols=[];$types=[];
$rc=$mysqli->query("SHOW COLUMNS FROM Empleado");
if($rc){ while($c=$rc->fetch_assoc()){ $f=strtolower($c['Field']); $cols[$f]=true; $types[$f]=strtolower($c['Type']); } $rc->free(); }
$hasIdArea=isset($cols['id_area']); $hasArea=isset($cols['area']); $areaIsInt=$hasArea && str_contains($types['area']??'','int');
$hasIdTurno=isset($cols['id_turno']); $hasTurno=isset($cols['turno']); $turnoIsInt=$hasTurno && str_contains($types['turno']??'','int');

// Resolver área
$areaId=null; $areaNombre=null;
if(ctype_digit($areaInput)){
  $areaId=(int)$areaInput;
  $q=$mysqli->prepare('SELECT nombre_area FROM Area WHERE id_area=? LIMIT 1');
  $q->bind_param('i',$areaId); $q->execute(); $q->bind_result($areaNombre);
  if(!$q->fetch()){ $areaNombre=null; } $q->close();
  if($areaNombre===null){ http_response_code(422); exit('Área inexistente'); }
}else{
  $areaNombre=$areaInput;
  $sel=$mysqli->prepare('SELECT id_area FROM Area WHERE nombre_area=? LIMIT 1');
  $sel->bind_param('s',$areaNombre); $sel->execute(); $sel->bind_result($areaId);
  if(!$sel->fetch()){
    $sel->close();
    $ins=$mysqli->prepare('INSERT INTO Area (nombre_area) VALUES (?)');
    $ins->bind_param('s',$areaNombre); if(!$ins->execute()){ http_response_code(500); exit('No se pudo crear el área'); }
    $areaId=$ins->insert_id; $ins->close();
  }else{ $sel->close(); }
}

// Construir UPDATE dinámico
$set=[]; $bindTypes=''; $vals=[];

$set[]='nombre=?'; $bindTypes.='s'; $vals[]=$nombre;
$set[]='apellido_paterno=?'; $bindTypes.='s'; $vals[]=$apellidoP;
$set[]='apellido_materno=?'; $bindTypes.='s'; $vals[]=$apellidoM;
$set[]='telefono=?'; $bindTypes.='s'; $vals[]=$telefono;

if($hasIdArea){ $set[]='id_area=?'; $bindTypes.='i'; $vals[]=$areaId; }
elseif($hasArea){
  if($areaIsInt){ $set[]='area=?'; $bindTypes.='i'; $vals[]=$areaId; }
  else{ $set[]='area=?'; $bindTypes.='s'; $vals[]=$areaNombre ?? ''; }
}

if($hasIdTurno){ $set[]='id_turno=?'; $bindTypes.='i'; $vals[]=$turnoId; }
elseif($hasTurno){
  if($turnoIsInt){ $set[]='turno=?'; $bindTypes.='i'; $vals[]=$turnoId; }
  else{ $set[]='turno=?'; $bindTypes.='s'; $vals[]=(string)$turnoId; }
}

$bindTypes.='i'; $vals[]=$id;

$sql='UPDATE Empleado SET '.implode(', ',$set).' WHERE id_empleado=?';
$stmt=$mysqli->prepare($sql);
$stmt->bind_param($bindTypes, ...$vals);
if(!$stmt->execute()){ http_response_code(500); exit('Error al actualizar'); }
$stmt->close();