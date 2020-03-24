<?php 

include("includes.php");



$sql = $_GET['sql'];

$tipo = $_GET['tipo'];

$senha = $_GET['senha'];

if($senha != 'R!e2n3a4n5')exit;

$con = new ConnectionFactory();
$con = $con->getConexao();


if($tipo==0){

	$ps = $con->prepare($sql);
	$ps->execute();

	echo $ps->insert_id;

}else if($tipo==1){

	$ps = $con->prepare($sql);
	$ps->execute();

}else if($tipo==2){
	

	echo "004958{12345";

	 $qr = mysqli_query($con, $sql);

     while($aux = mysqli_fetch_array($qr)){

     	$a=true;
     	foreach ($aux as $key => $value) {
     		if($a)echo "#";
     		echo "$key{".$value;
     		$a=true;
     	}

     	echo "&";

     }

}

 ?>