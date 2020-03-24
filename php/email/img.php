<?php 

if(isset($_GET['id_cliente']) && isset($_GET['tipo']) && isset($_GET['origem'])){

	include("includes.php");

	$id_cliente = $_GET['id_cliente'];
	$tipo = $_GET['tipo'];
	$origem = $_GET['origem'];

	$c = new ConnectionFactory();

	$ps = $c->getConexao()->prepare("INSERT INTO gerenciador_email(momento,id_cliente,tipo) VALUES(CURRENT_TIMESTAMP,$id_cliente,$tipo)");
	$ps->execute();
	$ps->close();

}

header("location:https://www.rtcagro.com.br/imagens/seta.png");




 ?>