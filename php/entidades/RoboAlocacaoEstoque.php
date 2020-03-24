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
class RoboAlocacaoEstoque {

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

        $empresas = array();

        $ps = $con->getConexao()->prepare("SELECT id,tipo_empresa FROM empresa WHERE id=1735"); //alterar essa gambiarra depois pfv.
        $ps->execute();
        $ps->bind_result($id_empresa, $tipo);
        while ($ps->fetch()) {
            $empresas[$id_empresa] = $tipo;
        }
        $ps->close();

        foreach ($empresas as $id_empresa => $tipo) {

            $empresas[$id_empresa] = Sistema::getEmpresa($tipo, $id_empresa, $con);
        }

        foreach ($empresas as $ke => $empresa) {

            $alocados = array();
            $alteracoes = array();

            $armazem = Aloc_Armazem::getArmazemFromEmpresa($con, $empresa);

            $pedidos_compra = $empresa->getPedidosEntrada($con, 0, 10, "pedido_entrada.id NOT IN (SELECT pe.id_pedido FROM previsao_alocacao pa INNER JOIN produto_pedido_entrada pe ON pa.id_produto_pedido_entrada=pe.id) AND pedido_entrada.id_status NOT IN (4,5) AND pedido_entrada.id > 953");

            if ($empresa->tipo_empresa === 1) {

                $clientes = $empresa->getEmpresasClientes($con);

                foreach ($clientes as $kc => $cliente) {

                    $tmp = $cliente->getPedidosEntrada($con, 0, 10, "pedido_entrada.id NOT IN (SELECT pe.id_pedido FROM previsao_alocacao pa INNER JOIN produto_pedido_entrada pe ON pa.id_produto_pedido_entrada=pe.id) AND pedido_entrada.id_status NOT IN (4,5) AND pedido_entrada.id > 953");

                    foreach ($tmp as $kt => $ptmp) {

                        $pedidos_compra[] = $ptmp;
                    }
                }
            }

            //==========================================

            foreach ($pedidos_compra as $kp => $pedido) {

                $produtos = $pedido->getProdutos($con);
                $pedido->produtos = $produtos;

                foreach ($produtos as $kpp => $produto) {

                    if (!$produto->produto->sistema_lotes)
                        continue;

                    $por_lote = $produto->quantidade;

                    $ps = $con->getConexao()->prepare("SELECT quantidade_inicial FROM lote WHERE excluido=false AND id_produto=" . $produto->produto->id . " ORDER BY id DESC");
                    $ps->execute();
                    $ps->bind_result($qi);
                    if ($ps->fetch()) {
                        $por_lote = $qi;
                    }
                    $ps->close();

                    $itens = array();

                    $qq = $produto->quantidade;
                    while ($qq > 0) {

                        $q = $por_lote;

                        $qq -= $por_lote;

                        if ($qq < 0) {

                            $q += $qq;
                        }

                        $pa = new Aloc_Produto();
                        $pa->id = $produto->produto->id;
                        $pa->categorias = array($produto->produto->empresa->id . "", $produto->produto->tipo . "", $produto->produto->classe_risco . "");
                        $pa->estado = ($produto->produto->liquido ? Aloc_Produto::$LIQUIDO : Aloc_Produto::$PO);
                        $pa->nivel_saida = $produto->produto->classificacao_saida * 10;
                        $pa->nome = $produto->produto->nome;
                        $pa->peso = $produto->produto->peso_bruto;
                        $pa->real = false;
                        $pa->ponto_fulgor = $produto->produto->ponto_fulgor;
                        $pa->quantidade = $q;
                        $pa->id_auxiliar = $produto->id;

                        $pilha = array($pa);

                        for ($i = 0; $i < count($pilha); $i++) {

                            $dif = $armazem->alocarItem($pilha[$i]);

                            /*
                              if($dif == null){

                              $npa1 = clone $pilha[$i];
                              $npa2 = clone $pilha[$i];

                              $npa1->quantidade = floor($npa1->quantidade/2);
                              $npa2->quantidade = ceil($npa1->quantidade/2);

                              $pilha[] = $npa1;
                              $pilha[] = $npa2;

                              continue;
                              }
                             */

                            $alocados[$pilha[$i]->uid] = $pilha[$i];


                            $armazem->applyRes($dif, array($pilha[$i]));


                            foreach ($dif[1] as $kd => $df) {
                                $alteracoes[$df[1]] = $df;
                            }
                        }
                    }
                }
            }

            //============================================

            foreach ($alocados as $kpp => $prod) {
                $ps = $con->getConexao()->prepare(
                        "INSERT INTO previsao_alocacao(altura,rua,numero,id_produto_pedido_entrada,quantidade) "
                        . "VALUES($prod->altura,$prod->rua,$prod->numero," . $prod->id_auxiliar . "," . $prod->quantidade . ")");
                $ps->execute();
                $ps->close();
            }

            $descricao_tarefa = "";

            foreach ($alteracoes as $key => $value) {
                if($armazem->produtos[$value[0]][$value[1]]->real){
                    $descricao_tarefa .= "Mover item " . $armazem->produtos[$value[0]][$value[1]]->nome . ": <br>"
                            . "DE: <strong>(Altura: ".($value[5]+1).",Rua: ".($value[6]+1).",Numero: ".($value[7]+1).")<strong><br> "
                            . "PARA: <strong>(Altura: ".($value[2]+1).",Rua: ".($value[3]+1).",Numero: ".($value[4]+1).")</strong><hr>";
                }else{
                    $descricao_tarefa .= "Quando o item " . $armazem->produtos[$value[0]][$value[1]]->nome . " chegar, coloque-o em: <br>"
                            . "<strong>(Altura: ".($value[2]+1).",Rua: ".($value[3]+1).",Numero: ".($value[4]+1).")</strong><hr>";
                }
            }

            if ($descricao_tarefa !== "") {
                $tarefa = new Tarefa();
                $tarefa->titulo = "Movimento de intens armazem";
                $tarefa->descricao = $descricao_tarefa;
                $tarefa->tipo_tarefa = Sistema::TT_MOVIMENTACAO_ARMAZEM($empresa->id);
                $tarefa->tipo_entidade_relacionada = 'EMP';
                $tarefa->id_entidade_relacionada = $empresa->id;

                Sistema::novaTarefaEmpresa($con, $tarefa, $empresa);
            }
            $tps = null;
            $tp = $empresa->getTiposProtocolo($con);
            foreach ($tp as $key => $value) {
                if (strpos(strtolower($value->nome), 'serv') !== false) {
                    $tps = $value;
                    break;
                }
            }


            if ($tps !== null) {

                $cargos = array(
                    Logistica::CF_SEPARADOR($empresa),
                    Logistica::CF_ESTAGIARIO_LOGISTICA($empresa),
                    Logistica::CF_FATURISTA($empresa),
                    Logistica::CF_DIRETOR($empresa),
                    Logistica::CF_ENCARREGADO_LOGISTICA($empresa),
                    Logistica::CF_COORDENADOR_LOGISTICA($empresa),
                    Logistica::CF_SUPERVISOR_LOGISTICA($empresa)
                );

                $inc = "(-1";

                foreach ($cargos as $kc => $cc) {
                    $inc .= ",$cc->id";
                }

                $inc .= ")";

                $usuarios = $empresa->getUsuarios($con, 0, 10, "usuario.id_cargo IN $inc");

                foreach ($pedidos_compra as $kpc => $pedido) {

                    $m = "Entrada referente a pedido de compra $pedido->id, segue abaixo: <br>";

                    foreach ($pedido->produtos as $kppc => $produto) {

                        $m .= $produto->produto->codigo . " - " . $produto->produto->nome . ", quantidade: " . $produto->quantidade . " <br>";
                    }

                    $prot = new Protocolo();
                    $prot->aprovado = true;
                    $prot->precedente = $m;
                    $prot->descricao = $m;
                    $prot->tipo = $tps;
                    $prot->tipo_entidade = 'PEC';
                    $prot->id_entidade = $pedido->id;
                    $prot->empresa = $empresa;
                    $prot->titulo = "Entrada de mercadoria";
                    $prot->iniciado_por = "CFG";
                    $prot->usuarios = $usuarios;

                    $prot->merge($con);
                }
            }
        }
    }

}
