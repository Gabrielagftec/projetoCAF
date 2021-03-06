<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CotacaoEntrada
 *
 * @author Renan
 */
class OBS_NFE {

    public static $RETORNO_REMESSA = 1;
    public static $TRANSFERENCIA = 2;
    public $obss;

    public function __construct($empresa, $pedido, $op = 0) {

        $tp = (isset($empresa->tipo_empresa) ? $empresa->tipo_empresa : -1);

        if ($tp == 0) {
            $this->obss = "Os produtos estao adequadamente acondicionados para suportar os riscos normais das etapas necessarias a uma operacao de transporte.".($pedido->logistica !== null?"Saida das mercadorias do deposito fechado ".$pedido->logistica->nome.", localizado a  Rua Antonio Mestriner, 194, Guarulhos, CNPJ: 31.985.540/0001-03":"")
                    . " e que atende a regulamentacao em vigor. Conf. Lei n. 9974"
                    . " de 06 06 00 reg. pelo decreto 4072 02 de 04 01 2002, as embalagens adquiridas nesta NF deverao ser devolvidas no prazo de 1 (um) ano,"
                    . " perfuradas e com a triplice lavagem realizada, na unidade de recebimento: "
                    . "Agro-Fauna Com. Ins. Ltda. R.Coutinho Cavalcanti, 1171 - Jd Alto Alegre - S J Rio Preto-SP. "
                    . "ARIA - ASSOC DE REVENDEDORES DE INSUMOS AGROPECUARIOS Rua Gino Cecconi, 400 Distrito Industrial Carlos de Arnaldo Silva - Sao Jose do Rio Preto SP "
                    . "Declaro que os produtos perigosos estao adequadamente classificados, embalados, identificados, e estivados para suportar os riscos das operacoes de transporte "
                    . "e que atendem as exigencias da regulamentacal BASE DE CALCULO DO ICMS REDUZIDA EM 60% CONFORME CONVENIO 100/97 - IPI ALIQUOTA ZERO"
                    . ". Pedido: " . $pedido->id;

            if ($pedido->cliente->suframado) {
                $this->obss .= ".Reducao da base de calculo conforme artigo 9 Anexo ll do Decreto 45.490 00 do RICMS SP. Isento conforme artigo 41 Item I do Anexo I do Decreto 45490 00 do RICMS-SP. Suframa: " . $pedido->cliente->inscricao_suframa;
            }
        } else if ($tp == 1) {
            if ($op == OBS_NFE::$RETORNO_REMESSA) {
                $this->obss = "Nota emitida referente a processamento de pedido do " . $empresa->nome .
                        " para a " . $empresa->nome . ". conforme artigo 41 item I do anexo I do decreto 45490 00 do RICMS - SP. Pedido:" . $pedido->id.". Nao incidencia de ICMS conforme artigo 7 incso III o RICMS 00";
            }
        } else {

            $this->obss = "";
        }
    }

    public function getObs() {

        return $this->obss;
    }

}
