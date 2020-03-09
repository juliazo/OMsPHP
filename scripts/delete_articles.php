<?php
/*
This Script can delete items(articulos) from the Microsip/copyright Database structure
it will not delete the article if it has sales already registered for that i hope to upload
another script
i hope is usefull to you. 
P.S since most of the users of microsip write code in spanish i did it this way
*/

#Coneccion pdo requerida
require "../config.php";

$clave = "XXXX";

$query_articulos = "SELECT ARTICULO_ID FROM ARTICULOS WHERE NOMBRE LIKE '$clave%%'";
$Q_articulos = $pdo_firebird->prepare($query_articulos);
$Q_articulos->execute();

#iteramos sobre los articulos
while ($articulo = $Q_articulos->fetch()){ 
	
	#primero necesitamos todos los documentos de inventario relacionados con el articulo
	$query_doctosindet = "SELECT DOCTO_IN_DET_ID FROM DOCTOS_IN_DET WHERE ARTICULO_ID = :articulo_id";
	$Q_doctosindet = $pdo_firebird->prepare($query_doctosindet);
	$Q_doctosindet->bindParam(":articulo_id",$articulo['ARTICULO_ID']);
	$Q_doctosindet->execute();

	#usamos un fetchAll() en esta linea porque vamos a iterar sobre la misma informacion 2 veces
    $Doctosindet_data = $Q_doctosindet->fetchAll();
    #primera vez
	foreach ($Doctosindet_data as $doctoindet){
		#el ultimo eslabon de la cadena para borrar un articulo sin ventas es esta tabla USOS_CAPAS_COSTOS
		#y se borra la relacion al documento del inventario
		$delete_usoscostos = "DELETE FROM USOS_CAPAS_COSTOS WHERE DOCTO_IN_DET_ID  = :doctoindet_id";
		$D_usoscostos = $pdo_firebird->prepare($delete_usoscostos);
		$D_usoscostos->bindParam(":doctoindet_id",$doctoindet['DOCTO_IN_DET_ID']);
		$D_usoscostos->execute();
	}

	#reiniciamos el Array para volver a iterar
	reset($Doctosindet_data);
	#segunda vez
	foreach ($Doctosindet_data as $doctoindet){
		#seleccionamos los documentos del inventario
		$query_doctosin = "SELECT DOCTO_IN_ID FROM DOCTOS_IN WHERE DOCTO_IN_DET_ID = '{$doctoindet['DOCTO_IN_DET_ID']}'";
		$Q_doctosin = $pdo_firebird->prepare($delete_doctosin);
		$Q_doctosin->execute();

		#borramos el detalle del inventario 
		$delete_doctosindet = "DELETE FROM DOCTOS_IN_DET WHERE DOCTO_IN_DET_ID = '{$doctoindet['DOCTO_IN_DET_ID']}'";
		$D_doctosindet = $pdo_firebird->prepare($delete_doctosindet);
		$D_doctosindet->execute();

		#borramos los documentos en el inventario
		$query_doctosin = "SELECT DOCTO_IN_ID FROM DOCTOS_IN WHERE DOCTO_IN_DET_ID = '{$doctoindet['DOCTO_IN_DET_ID']}'";
		$Q_doctosin = $pdo_firebird->prepare($delete_doctosin);
		$Q_doctosin->execute();

	}

	#************************************PUNTO DONDE EL ARTICULO YA NO TIENE INVENTARIO *********************************		
	$delete_saldosin = "DELETE FROM SALDOS_IN WHERE ARTICULO_ID = :articulo_id";
	$D_saldosin = $pdo_firebird->prepare($delete_saldosin);
	$D_saldosin->bindParam(":articulo_id",$articulo['ARTICULO_ID']);
	$D_saldosin->execute();

	$delete_capascostos = "DELETE FROM CAPAS_COSTOS WHERE ARTICULO_ID = '{$articulo['ARTICULO_ID']}'";
	$D_capascostos = $pdo_firebird->prepare($delete_capascostos);
	$D_capascostos->execute();

	$delete_articulo = "DELETE FROM ARTICULOS WHERE ARTICULO_ID = '{$articulo['ARTICULO_ID']}'"; 
	$D_articulo = $pdo_firebird->prepare($delete_articulo);
	
	if (!$D_articulo->execute())
	{
		#si el articulo no se borra es porque tiene ventas y otros registros pendientes
		echo "no se borro";
	}
	else
	{
		#si el articulo se borra se borra tambien la clave
		$delete_claves = "DELETE FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = '{$articulo['ARTICULO_ID']}'";
		$D_claves = $pdo_firebird->prepare($delete_claves);
		$D_claves->execute();
	}
}

?>