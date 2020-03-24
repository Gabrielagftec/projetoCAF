<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RegraTabela
 *
 * @author Renan
 */
class RoboRelacionadorPedidoEntradaNota {

    public $dia;
    public $mes;
    public $ano;
    public $hora;
    public $minuto;
    public $segundo;
    public $momento;

    public function __construct() {

        date_default_timezone_set("America/Sao_Paulo");

        $this->momento = round(microtime(true) * 1000);

        $str = explode(':', date('d:m:Y:H:i:s', $this->momento / 1000));
        $this->dia = intval($str[0]);
        $this->mes = intval($str[1]);
        $this->ano = intval($str[2]);
        $this->hora = intval($str[3]);
        $this->minuto = intval($str[4]);
        $this->segundo = intval($str[5]);
    }

    public function executar($con) {

        $ids_status = "(-1";

        $status = Sistema::getStatusPedidoEntrada();

        foreach ($status as $key => $value) {
            if ($value->estoque) {
                $ids_status .= ",$value->id";
            }
        }

        $ids_status .= ")";

        $empresas = array();

        $ps = $con->getConexao()->prepare("SELECT id FROM empresa WHERE rtc>=4 AND id <> 1733");
        $ps->execute();
        $ps->bind_result($id);
        while ($ps->fetch()) {
            $empresas[] = $id;
        }
        $ps->close();

        foreach ($empresas as $k => $ide) {

            $empresa = new Empresa($ide, $con);

            $pedidos = $empresa->getPedidosEntrada($con, 0, 100, "id_status IN $ids_status AND id_nota=0");

            foreach ($pedidos as $key => $value) {

                $value->produtos = $value->getProdutos($con);

                $total = 0;

                foreach ($value->produtos as $key2 => $value2) {

                    $total += $value2->quantidade * $value2->valor;
                }

                $ps = $con->getConexao()->prepare("SELECT k.id FROM (SELECT SUM(pn.valor_unitario*pn.quantidade) as 'total',n.id as 'id',n.id_transportadora as 'id_transportadora',n.id_fornecedor as 'id_fornecedor',n.id_empresa as 'id_empresa',n.id_pedido_entrada as 'id_pedido_entrada' FROM nota n INNER JOIN produto_nota pn ON pn.id_nota=n.id GROUP BY n.id ORDER BY n.id DESC) k "
                        . "WHERE k.id_empresa=$empresa->id AND k.id_transportadora=" . $value->transportadora->id . " "
                        . "AND k.id_fornecedor=" . $value->fornecedor->id . " AND "
                        . "(ABS(k.total-$total)/$total)<0.01 AND k.id_pedido_entrada=0"); //divergencia aceitavel de ate 1%
                $ps->execute();
                $ps->bind_result($id_nota);
                if ($ps->fetch()) {
                    $ps->close();

                    $ps = $con->getConexao()->prepare("UPDATE nota SET data_emissao=data_emissao,id_pedido_entrada=$value->id WHERE id=$id_nota");
                    $ps->execute();
                    $ps->close();

                    $ps = $con->getConexao()->prepare("UPDATE pedido_entrada SET data=data, id_nota=$id_nota WHERE id=$value->id");
                    $ps->execute();
                    $ps->close();
                } else {
                    $ps->close();
                }
            }
        }
    }

}
