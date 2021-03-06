<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Fornecedor
 *
 * @author Renan
 */
class ProdutoPedidoSaida {

    public $id;
    public $produto;
    public $quantidade;
    public $validade_minima;
    public $valor_base;
    public $juros;
    public $icms;
    public $base_calculo;
    public $frete;
    public $pedido;
    public $retiradas;
    public $ipi;
    public $influencia_estoque;
    public $influencia_reserva;

    function __construct() {

        $this->id = 0;
        $this->produto = null;
        $this->quantidade = "";
        $this->validade_minima = round(microtime(true) * 1000);
        $this->pedido = null;
        $this->influencia_estoque = 0;
        $this->influencia_reserva = 0;
        $this->retiradas = array();
    }

    public function merge($con) {

        if ($this->produto->categoria->desconta_estoque) {
            // -------- atualizando produto ------------

            $ps = $con->getConexao()->prepare("SELECT estoque, disponivel FROM produto WHERE id=" . $this->produto->id);
            $ps->execute();
            $ps->bind_result($estoque, $disponivel);
            if ($ps->fetch()) {
                $this->produto->estoque = $estoque;
                $this->produto->disponivel = $disponivel;
            }
            $ps->close();

            //------------------------------------------

            $status_pedido = $this->pedido->status;

            $x_est = ($status_pedido->estoque ? -1 : 0) * $this->quantidade;
            $dif_est = $x_est - $this->influencia_estoque;

            $x_res = ($status_pedido->reserva ? -1 : 0) * $this->quantidade;
            $dif_res = $x_res - $this->influencia_reserva;

            if ($this->produto->disponivel + $dif_res < 0) {

                throw new Exception('Sem estoque disponivel para executar essa operacao');
            }

            if ($this->produto->estoque + $dif_est < 0) {

                throw new Exception('Sem estoque para executar essa operacao');
            }

            $this->produto->estoque += $dif_est;
            $this->produto->disponivel += $dif_res;

            $ps = $con->getConexao()->prepare("UPDATE produto SET estoque=" . $this->produto->estoque . ", disponivel=" . $this->produto->disponivel .
                    " WHERE id=" . $this->produto->id);
            $ps->execute();
            $ps->close();

            $this->influencia_estoque = $x_est;
            $this->influencia_reserva = $x_res;
        }
        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO produto_pedido_saida(id_produto,quantidade,validade_minima,valor_base,juros,icms,base_calculo,frete,id_pedido,influencia_estoque,influencia_reserva,ipi) VALUES(" . $this->produto->id . "," . $this->quantidade . ",FROM_UNIXTIME($this->validade_minima/1000),$this->valor_base,$this->juros,$this->icms,$this->base_calculo,$this->frete," . $this->pedido->id . ",$this->influencia_estoque,$this->influencia_reserva,$this->ipi)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE produto_pedido_saida SET id_produto = " . $this->produto->id . ", quantidade=$this->quantidade, validade_minima=FROM_UNIXTIME($this->validade_minima/1000),valor_base=$this->valor_base,juros=$this->juros,icms=$this->icms,base_calculo=$this->base_calculo,frete=$this->frete, id_pedido=" . $this->pedido->id . ", influencia_estoque=$this->influencia_estoque, influencia_reserva = $this->influencia_reserva, ipi=$this->ipi WHERE id = " . $this->id);
            $ps->execute();
            $ps->close();
        }

        if (($dif_res != 0 || $dif_est != 0) && $this->produto->sistema_lotes && $this->produto->categoria->desconta_estoque) {

            foreach ($this->retiradas as $key => $value) {

                $ps = $con->getConexao()->prepare("UPDATE lote SET quantidade_real = quantidade_real + " . $value[1] . " WHERE id = " . $value[0]);
                $ps->execute();
                $ps->close();
            }

            $ps = $con->getConexao()->prepare("DELETE FROM retirada WHERE id_produto_pedido = $this->id");
            $ps->execute();
            $ps->close();

            $this->retiradas = array();

            $lotes = $this->produto->getLotes($con, "MONTH(lote.validade) = MONTH(FROM_UNIXTIME($this->validade_minima/1000)) AND YEAR(lote.validade) = YEAR(FROM_UNIXTIME($this->validade_minima/1000)) AND lote.quantidade_real > 0", "lote.quantidade_real DESC");

            $qtd = max(abs($this->influencia_estoque), abs($this->influencia_reserva));

            $ls = array();

            $li = null;
            $is = array();

            foreach ($lotes as $key => $value) {

                if ($qtd == 0) {

                    break;
                }

                if ($value->quantidade_real <= $qtd) {

                    $ls[] = $value;
                    $qtd -= $value->quantidade_real;
                } else {

                    if ($li === null) {

                        $li = $value;
                    } else {

                        if ($li->quantidade_real > $value->quantidade_real) {

                            $li = $value;
                        }
                    }
                }
            }

            if ($qtd > 0 && count($ls) == count($lotes)) {

                $this->produto->estoque -= $this->influencia_estoque;
                $this->produto->disponivel -= $this->influencia_reserva;

                $ps = $con->getConexao()->prepare("UPDATE produto SET estoque=" . $this->produto->estoque . ", disponivel=" . $this->produto->disponivel .
                        " WHERE id=" . $this->produto->id);
                $ps->execute();
                $ps->close();

                $this->influencia_estoque = 0;
                $this->influencia_reserva = 0;

                foreach ($this->retiradas as $key => $value) {

                    $ps = $con->getConexao()->prepare("UPDATE lote SET quantidade_real = quantidade_real + " . $value[1] . " WHERE id = " . $value[0]);
                    $ps->execute();
                    $ps->close();
                }

                $ps = $con->getConexao()->prepare("DELETE FROM retirada WHERE id_produto_pedido = $this->id");
                $ps->execute();
                $ps->close();

                $this->retiradas = array();

                if ($this->id > 0) {
                    $ps = $con->getConexao()->prepare("UPDATE produto_pedido_saida SET influencia_estoque=0,influencia_reserva=0,validade_minima=validade_minima WHERE id=$this->id");
                    $ps->execute();
                    $ps->close();
                }

                throw new Exception('Nao existem lotes suficientes para essa quantidade');
            } else if ($qtd > 0) {

                $i = $li->getItem()->filhos;

                while ($qtd > 0) {


                    for ($j = 1; $j < count($i); $j++) {
                        if ($i[$j] === null)
                            continue;
                        for ($k = $j; $k > 0; $k--) {
                            if ($i[$k - 1] !== null) {
                                if (!($i[$k]->quantidade > $i[$k - 1]->quantidade))
                                    break;
                            }
                            $t = $i[$k];
                            $i[$k] = $i[$k - 1];
                            $i[$k - 1] = $t;
                        }
                    }

                    for ($j = 0; $j < count($i) && $qtd > 0; $j++) {

                        if ($i[$j] === null)
                            continue;

                        if ($i[$j]->quantidade <= $qtd) {

                            $qtd -= $i[$j]->quantidade;
                            $is[] = $i[$j];
                            $i[$j] = null;
                        }
                    }

                    $min = -1;

                    for ($j = 0; $j < count($i); $j++) {
                        if ($i[$j] !== null) {
                            if ($min < 0) {
                                $min = $j;
                            } else if ($i[$min]->quantidade >= $i[$j]->quantidade) {
                                $min = $j;
                            }
                        }
                    }

                    if ($qtd > 0) {

                        $th = $min < 0;
                        if (!$th) {
                            $th = $i[$min]->quantidade_filhos === 0;
                        }

                        if ($th) {

                            $this->produto->estoque -= $this->influencia_estoque;
                            $this->produto->disponivel -= $this->influencia_reserva;
                            $ps = $con->getConexao()->prepare("UPDATE produto SET estoque=" . $this->produto->estoque . ", disponivel=" . $this->produto->disponivel .
                                    " WHERE id=" . $this->produto->id);
                            $ps->execute();
                            $ps->close();

                            $this->influencia_estoque = 0;
                            $this->influencia_reserva = 0;

                            foreach ($this->retiradas as $key => $value) {

                                $ps = $con->getConexao()->prepare("UPDATE lote SET quantidade_real = quantidade_real + " . $value[1] . " WHERE id = " . $value[0]);
                                $ps->execute();
                                $ps->close();
                            }

                            $ps = $con->getConexao()->prepare("DELETE FROM retirada WHERE id_produto_pedido = $this->id");
                            $ps->execute();
                            $ps->close();

                            $this->retiradas = array();

                            if ($this->id > 0) {
                                $ps = $con->getConexao()->prepare("UPDATE produto_pedido_saida SET influencia_estoque=0,influencia_reserva=0,validade_minima=validade_minima WHERE id=$this->id");
                                $ps->execute();
                                $ps->close();
                            }

                            throw new Exception('Nao e possivel separar essa quantidade');
                        }

                        $i = $i[$min]->filhos;
                    }
                }
            }

            foreach ($ls as $key => $value) {
                if ($value->quantidade_inicial === $value->quantidade_real) {
                    $this->retiradas[] = array($value->id, $value->quantidade_real, 0);
                } else {
                    $i = $value->getItem();
                    $i->lote_maior = $value;
                    $is[] = $i;
                }
            }

            if (count($is) > 0) {

                for ($i = 0; $i < count($is); $i++) {

                    $it = $is[$i];

                    if ($it === null)
                        continue;

                    $a = false;
                    $j = $it->filhos;
                    for ($k = 0; $k < count($j); $k++) {
                        if ($j[$k] === null) {
                            $a = true;
                            break;
                        }
                        foreach ($j[$k]->filhos as $key => $value) {
                            $j[] = $value;
                        }
                    }
                    if ($a) {
                        $is[$i] = null;
                        foreach ($it->filhos as $key => $value) {
                            if ($value === null)
                                continue;
                            if (isset($it->lote_maior)) {
                                $value->lote_maior = $it->lote_maior;
                            }
                            $is[] = $value;
                        }
                    }
                }

                foreach ($is as $key => $value) {

                    if ($value === null)
                        continue;

                    $r = array((!isset($value->lote_maior)) ? $li->id : $value->lote_maior->id, $value->quantidade);

                    foreach ($value->numero as $key2 => $value2) {

                        $r[] = $value2;
                    }

                    $this->retiradas[] = $r;
                }
            }

            foreach ($this->retiradas as $key => $value) {

                $str = "";


                foreach ($value as $key2 => $value2) {
                    if ($key2 < 2)
                        continue;
                    if ($str != "")
                        $str .= ",";
                    $str .= $value2;
                }

                $ps = $con->getConexao()->prepare("INSERT INTO retirada(id_lote,retirada,id_produto_pedido,quantidade) VALUES(" . $value[0] . ",'$str',$this->id," . $value[1] . ")");
                $ps->execute();
                $ps->close();

                $ps = $con->getConexao()->prepare("UPDATE lote SET quantidade_real = quantidade_real - " . $value[1] . " WHERE id = " . $value[0]);
                $ps->execute();
                $ps->close();
            }
        }
    }

    public function atualizarCustos() {



        if ($this->id === 0) {

            $campanha = null;
            $valor_oferta = 0;
            
            /*
            foreach ($this->produto->ofertas as $key => $value) {
                $kc = $value->validade == $this->validade_minima;
                if (!$kc && $this->validade_minima > $value->validade) {
                    $agora = round(microtime(true) * 1000) + (Sistema::getMesesValidadeCurta() * 30 * 24 * 60 * 60 * 1000);
                    $kc = $value->validade > $agora;
                }
                if ($kc) {
                    $campanha = $value->campanha;
                    $valor_oferta = $value->valor;
                    break;
                }
            }
            */

            if ($campanha !== null) {
                if ($campanha->parcelas > 0 && $campanha->prazo >= 0) {
                    if ($this->pedido->prazo > $campanha->prazo) {
                        $this->retirou_promocao = $valor_oferta;
                        $this->valor_base = $this->produto->valor_base;
                    } else {
                        unset($this->retirou_promocao);
                        $this->valor_base = $valor_oferta;
                    }
                }
            }
        }
        
        $ses = new SessionManager();
        $u = $ses->get('usuario');

        if ($this->pedido->cliente !== null) {
            $con = new ConnectionFactory();
            $pe = $this->pedido->cliente->getPrecoEspecial($con);
            if(strpos(strtolower($u->nome), 'elias') !== false){

            }else{
                if ($pe > 0) {
                    $this->valor_base = round($this->produto->custo / $pe, 2);
                }
            }
        }
        
        $cat = $this->produto->categoria;

        $emp = $this->pedido->empresa;

        if ($this->pedido->logistica !== null) {

            $emp = $this->pedido->logistica;
        }

        $juros_mes = 1 + $this->pedido->empresa->juros_mensal / 100;

        $juros_dia = pow($juros_mes, 1 / 30);

        $periodo = $this->pedido->prazo/$this->pedido->parcelas;

        $this->juros = round($this->valor_base * (((pow($juros_dia, $periodo)-1)/2)*($this->pedido->parcelas+1)), 2);

        if ($this->pedido->cliente != null) {

            $this->base_calculo = ($cat->base_calculo / 100) * ($this->valor_base + $this->juros);

            $icms = Sistema::getIcmsEstado($this->pedido->cliente->endereco->cidade->estado);

            if ($emp->endereco->cidade->estado->id == $this->pedido->cliente->endereco->cidade->estado->id || $this->pedido->cliente->suframado) {

                $this->icms = 0;
            } else {

                $base = ($cat->base_calculo / 100) * ($icms / 100);

                if (!$this->produto->categoria->icms_normal) {

                    $base = ($cat->base_calculo / 100) * ($this->produto->categoria->icms / 100);
                }

                $base = (1 - $base);
                $icms = round(($this->valor_base + $this->juros) / $base, 2);
                $icms = $icms - $this->valor_base - $this->juros;

                $this->icms = $icms;
            }
        }

        $this->ipi = ($this->valor_base + $this->juros + $this->icms) * ($this->produto->categoria->ipi / 100);

        if ($this->pedido->frete_incluso) {

            $total = 0;

            foreach ($this->pedido->produtos as $produto) {

                $total += $produto->valor_base * $produto->quantidade;
            }

            if ($total > 0) {

                $perc = ($this->valor_base * $this->quantidade) / $total;

                $this->frete = round((($this->pedido->frete * $perc) / $this->quantidade), 2);
            }
        } else {

            if($this->pedido->transportadora->despacho>0){

                $total = 0;

                foreach ($this->pedido->produtos as $produto) {

                    $total += $produto->valor_base * $produto->quantidade;
                }

                if ($total > 0) {

                    $perc = ($this->valor_base * $this->quantidade) / $total;

                    $this->frete = round((($this->pedido->transportadora->despacho * $perc) / $this->quantidade), 2);
                }

            }else{

                $this->frete = 0;

            }

    
        }

    }

    public function delete($con) {

        if ($this->produto->categoria->desconta_estoque) {
            $this->produto->atualizarEstoque($con);
            $this->produto->estoque -= $this->influencia_estoque;
            $this->produto->disponivel -= $this->influencia_reserva;
            $ps = $con->getConexao()->prepare("UPDATE produto SET estoque=" . $this->produto->estoque . ", disponivel=" . $this->produto->disponivel .
                    " WHERE id=" . $this->produto->id);
            $ps->execute();
            $ps->close();
            $this->influencia_estoque = 0;
            $this->influencia_reserva = 0;

            foreach ($this->retiradas as $key => $value) {

                $ps = $con->getConexao()->prepare("UPDATE lote SET quantidade_real = quantidade_real + " . $value[1] . " WHERE id = " . $value[0]);
                $ps->execute();
                $ps->close();
            }


            $ps = $con->getConexao()->prepare("DELETE FROM retirada WHERE id_produto_pedido = $this->id");
            $ps->execute();
            $ps->close();
        }
        $ps = $con->getConexao()->prepare("DELETE FROM produto_pedido_saida WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
