<?php 


if(isset($_POST['_f_']) && isset($_POST['id_cliente']) && isset($_POST['_motivo_'])){

	//include("includes.php");

	$id_cliente = $_POST['id_cliente'];
	$motivo = $_POST['_motivo_'];

	$c = new ConnectionFactory();

	$ps = $c->getConexao()->prepare("INSERT INTO gerenciador_email(momento,id_cliente,motivo) VALUES(CURRENT_TIMESTAMP,$id_cliente,$motivo)";
	$ps->execute();
	$ps->close();

    echo "Motivo enviado com sucesso! Obrigado pela informação.<br>";
    exit;
}

?>

<?php 

if(isset($_GET['id_cliente'])){

?>

<!doctype html>

<html lang="en" ng-app="appRtc">

<head>


    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Descadastramento de email</title>
    <!-- Bootstrap CSS -->
    <script src="../../js/angular.min.js"></script>
    <script src="../../js/rtc.js?<?php echo date('dmYH',microtime(true)); ?>"></script>

        <script src="../../js/filters.js?<?php echo date('dmYH',microtime(true)); ?>"></script>
        <script src="../../js/services.js?<?php echo date('dmYH',microtime(true)); ?>"></script>
        <script src="../../js/controllers.js?<?php echo date('dmYH',microtime(true)); ?>"></script>
        <script src="../../assets/vendor/jquery/jquery-3.3.1.min.js"></script>

        <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
        <link href="../../assets/vendor/fonts/circular-std/style.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/libs/css/style.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
        <style>
            html,
            body {
                height: 100%;
            }
            
            body {
                display: -ms-flexbox;
                display: flex;
                -ms-flex-align: center;
                align-items: center;
                background-color: #414141;
                background-position: center;
                background-size: cover;
            }
            
            .wrapper {
                width: 100%;
                height: auto;
                min-height: 100vh;
                padding: 50px 20px;
                padding-top: 300px;
                display: flex;
                background-image: linear-gradient(-20deg, #df885e 0%, #f8d56b 100%);
                background-image: linear-gradient(-20deg, #df885e 0%, #f8d56b 100%);
            }
            
            .splash-description {
                margin-top: 25px;
                padding-bottom: 0px;
            }
            
            a:hover {
                color: #4aaf51;
                text-decoration: none;
            }
            
            .splash-container {
                max-width: 700px;
                padding: 35px;
                border-radius: 10px;
            }
            
            .splash-container .card {
                box-shadow: 0px 8px 30px -10px rgba(13, 28, 39, 0.6);
                -webkit-border-radius: 10px;
                -moz-border-radius: 10px;
                border-radius: 10px;
            }
        </style>

</head>

<body ng-controller="crtLogin">
    <div class="wrapper">
        <!-- ============================================================== -->
        <!-- login page  -->
        <!-- ============================================================== -->
        <div class="splash-container" style="border-radius: 10px;">
            <div class=" card" style="border-radius: 10px;">
                <div class="card-header text-center" style="padding: 0px; border-radius: 10px;">
                    <img class="logo-img" src="https://www.rtcagro.com.br/imagens/topo_descadastrar.jpg" alt="logo" style="width: 630px; border-top-left-radius: 5px;border-top-right-radius: 5px;"></div>
                <div class="card-body" style="padding: 3rem;">

                    <?php 
                //CAPTURA AS VARRIAVEIS ENVIADAS
                $email = $_GET['email'];
                $id_user = $_GET['id_cliente'];
                ?>

                    <p style="font-size: 15px;">O seu email <strong><?php echo $id_user; ?><?php echo $email; ?></strong> estrará sendo removido em instantes de nossa lista de envio!</p>
                    <form id="main-contact-form" class="contact-form" name="contact-form" method="post">
                        <div class="form-group">
                            <label for=""><b>Lamentamos não tê-lo mais conosco, por favor, nos diga por que você cancelou:</b></label><br>
                            <div class="custom-control custom-radio custom-control-inline" style="margin-top: 5px;">
                                <input type="radio" id="1" name="mot" value="Eu não quero mais receber estes e-mails" class="custom-control-input">
                                <label class="custom-control-label" for="1">Eu não quero mais receber estes e-mails</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline" style="margin-top: 5px;">
                                <input type="radio" id="2" name="mot" value="Eu nunca autorizei o recebimento destes emails" class="custom-control-input">
                                <label class="custom-control-label" for="2">Eu nunca autorizei o recebimento destes emails</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline" style="margin-top: 5px;">
                                <input type="radio" id="3" name="mot" value="Não tenho interesse nesse tipo de produto" class="custom-control-input">
                                <label class="custom-control-label" for="3">Não tenho interesse nesse tipo de produto</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline" style="margin-top: 5px;">
                                <input type="radio" id="4" name="mot" value="Os e-mails são spam e devem ser reportados" class="custom-control-input">
                                <label class="custom-control-label" for="4">Os e-mails são spam e devem ser reportados</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline" style="margin-top: 5px;">
                                <input type="radio" id="5" name="mot" value="Outro (preencha o motivo abaixo)" class="custom-control-input">
                                <label class="custom-control-label" for="5">Outro (preencha o motivo abaixo)</label>
                            </div>

                            <div class="form-row">
                                <div class="col">
                                    <textarea maxlength="250" style="margin-top: 10px;" class="form-control" rows="4" id="comment"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <button id="motivo" class="btn btn-primary col-12 text-center" style="margin:auto;margin-top: 10px;"><i class="far fa-comment m-r-10"></i>Enviar motivo</button>
                        </div>
                    </form>

                </div>
                <div class="card-footer bg-white p-0 text-center">
                    <div class="card-footer-item card-footer-item-bordered">
                        <a class="footer-link" href="https://www.rtcagro.com.br/">Precisa de ajuda? Fale com nossos Assistentes Virtuais</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- end login page  -->
    <!-- ============================================================== -->
    <!-- Optional JavaScript -->

    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.0.4/socket.io.js"></script>
    <script type="text/javascript">
        //================================


        function motivo() {
            let mot = $("input[name='mot']:checked").val();
            let comment = $("#comment").val();
            let motivo = mot + '<br>' + comment;
            alert(motivo);
            window.location.href = "remove.php?_f_=2&id_cliente=<?php print $id_user; ?>&_motivo_="+motivo+""; 
            
            //alert("Motivo enviado com sucesso! Obrigado pela informação.");
            //$("#comment").val("");
            // $('input[type=radio]').prop('checked', false);
            //$("#main-contact-form").css("display", "none");
        }

        $("#motivo").bind("click", motivo);
    </script>
</body>

</html>

<?php
    exit;
}

?>
