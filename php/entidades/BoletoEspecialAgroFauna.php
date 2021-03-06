<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Boleto
 *
 * @author T-Gamer
 */
class BoletoEspecialAgroFauna extends FormaPagamento {

    function __construct() {

        $this->id = 2;
        $this->nome = "Boleto Agro Fauna";
    }

    public function aoFinalizarPedido($pedido) {

        $con = new ConnectionFactory();

        $r = new stdClass();

        $r->codigoEmpresa = "J0476265100001320000031366";
        $r->chave = "QL10ZP72VY83WO59";

        $r->urlRetorna = "novo_rtc_web/acompanhar-pedidos.php";



        $inscricao = $pedido->cliente->cnpj->valor;

        if ($pedido->cliente->pessoa_fisica) {
            $inscricao = $pedido->cliente->cpf->valor;
        }

        $cep = $pedido->cliente->endereco->cep->valor;

        if ($inscricao === "") {
            $inscricao = "00001";
        }

        if ($cep === "") {
            $cep = "1234";
        }



        $bairro = $pedido->cliente->endereco->bairro;
        $cep = str_replace(array("-"), array(""), $cep);
        $estado = $pedido->cliente->endereco->cidade->estado->sigla;
        $inscricao = substr(str_replace(array(".", "-", "/"), array("", "", ""), $inscricao), 0, 14);
        $nome = $pedido->cliente->razao_social;
        $documento = $pedido->id;
        $cidade = $pedido->cliente->endereco->cidade->nome;
        $logadouro = $pedido->cliente->endereco->rua;
        $valor = 0;
        foreach ($pedido->produtos as $key => $value) {
            $valor += $value->quantidade * ($value->valor_base + $value->ipi + $value->frete + $value->juros + $value->icms);
        }

        $ret = "";
        $valor = round($valor / max(1, $pedido->parcelas), 2);


        $r->numeroInscricao = $inscricao;

        $r->enderecoSacado = $logadouro;

        $r->bairroSacado = $bairro;

        $r->cepSacado = $cep;

        $r->cidadeSacado = $cidade;

        $r->estadoSacado = $estado;

        $r->nomeSacado = $nome;

        if ($pedido->cliente->pessoa_fisica) {
            $r->codigoInscricao = "01";
        } else {
            $r->codigoInscricao = "02";
        }


        for ($i = 0; $i < max(1, $pedido->parcelas); $i++) {

            $id = 0;
            $ps = $con->getConexao()->prepare("SELECT IFNULL(MAX(id_boleto),0)+1 FROM boleto_shopline");
            $ps->execute();
            $ps->bind_result($id_boleto);
            if ($ps->fetch()) {
                $id = $id_boleto;
            }
            $ps->close();

            $ps = $con->getConexao()->prepare("INSERT INTO boleto_shopline(id_boleto,id_pedido) VALUES($id,$pedido->id)");
            $ps->execute();
            $ps->close();

            $r->pedido = $id . "";

            $fat = $pedido->prazo / max(1, $pedido->parcelas);

            $agora = round(microtime(true) * 1000);
            $momento = $agora + ($fat * ($i + 1) + 1) * 24 * 60 * 60 * 1000;

            if ($bairro === "") {
                $bairro = "sem bairro";
            }

            if ($nome === "") {
                $nome = "sem nome";
            }

            if ($logadouro === "") {
                $logadouro = "sem rua";
            }

            $momento = date('dmY', $momento / 1000);

            $r->valor = str_replace('.', ',', round($valor, 2) . "");

            while (strpos($r->valor, ",") !== false && strlen(explode(',', $r->valor)[1]) < 2) {
                $r->valor .= "0";
            }

            $r->observacao = "Boleto referente a pedido $pedido->id do RTC";
            $r->dataVencimento = $momento;

            $r = Utilidades::removeAcentos($r);

            $codigo = Sistema::getMicroServicoJava('ItauShopline', addslashes(Utilidades::toJson($r)));

            $ret .= "<form action='https://shopline.itau.com.br/shopline/shopline.aspx' method='post' name='shopline_$i' target='SHOPLINE'>"
                . "<input type='hidden' name='DC' value='$codigo'><br>"
                . "<input type='submit' name='Shopline' value='' style='width:199px;height:89px;background-image: url(https://rtcagro.com.br/assets/images/itau_boleto.jpg);border: none;cursor: pointer;'>"
                . "<br><br><input type='submit' name='Shopline' value='Clique aqui para acessar o Boleto Banc&#225;rio' />"
                . "<p><a class='button' href='https://shopline.itau.com.br/shopline/shopline.aspx?DC=$codigo' name='shopline_$i' target='SHOPLINE'>Clique aqui para acessar o Boleto Banc&#225;rio</a><br />Depois de recebermos a confirma&#231;&#227;o do pagamento, seu pedido ser&#225; processado.</p>" 
                . "</form><hr>";  
        }

        return $ret;
    }

    public function habilitada($pedido) {

        return strpos($pedido->empresa->nome, 'Agro') !== false && strpos($pedido->empresa->nome, 'Fauna') !== false;
    }

}
