<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Aloc_Produto
 *
 * @author T-Gamer
 */
class Aloc_Armazem {

    public $armazem;
    public $produtos;

    public function __construct() {

        $this->armazem = array();
        $this->produtos = array();
    }

    public static function getArmazemFromEmpresa($con, $empresa) {

        $armazem = new Aloc_Armazem();

        $arms = $empresa->getArmazens($con);

        if (count($arms) === 0) {

            return $armazem;
        }

        $arm = $arms[0];

        foreach ($arm->porta_palets as $kp => $pp) {

            for ($a = 0; $a < $pp->altura; $a++) {
                for ($r = $pp->rua_inicial - 1; $r < $pp->rua_inicial - 1 + $pp->largura; $r++) {
                    for ($n = $pp->numero_inicial - 1; $n < $pp->numero_inicial - 1 + $pp->comprimento; $n++) {

                        foreach ($pp->tuneis as $kt => $t) {
                            if ($a >= $t->y_inicial - 1 && $a <= $t->y_final - 1 && $r >= $t->x_inicial - 1 && $r <= $t->x_final - 1 && $n >= $t->z_inicial - 1 && $n <= $t->z_final - 1) {
                                continue 2;
                            }
                        }

                        if (!isset($armazem->armazem[$a])) {
                            $armazem->armazem[$a] = array();
                        }

                        if (!isset($armazem->armazem[$a][$r])) {
                            $armazem->armazem[$a][$r] = array();
                        }

                        $pos = new Aloc_Posicao();
                        $pos->altura = $a;
                        $pos->numero = $n;
                        $pos->rua = $r;
                        $pos->produto = null;
                        $pos->zona_inflamavel = $pp->inflamavel;

                        $armazem->armazem[$a][$r][$n] = $pos;
                    }
                }
            }
        }


        $ps = $con->getConexao()->prepare("SELECT l.altura-1,l.rua-1,l.numero-1,SUM(l.quantidade_real),p.nome,p.id,p.tipo,p.id_empresa,p.classe_risco,p.ponto_fulgor,p.classificacao_saida,p.peso_bruto FROM lote l INNER JOIN produto p ON l.id_produto=p.id WHERE (p.id_logistica=$empresa->id OR p.id_empresa=$empresa->id) AND p.excluido=false AND l.quantidade_real>0 AND l.excluido=false GROUP BY l.altura,l.rua,l.numero,p.id");
        $ps->execute();
        $ps->bind_result($altura, $rua, $numero, $quantidade, $nome, $id, $tipo, $id_empresa, $classe_risco, $ponto_fulgor, $classificacao_saida, $peso_bruto);

        while ($ps->fetch()) {
            if (isset($armazem->armazem[$altura][$rua][$numero])) {
                if ($armazem->armazem[$altura][$rua][$numero]->produto === null) {

                    $p = new Aloc_Produto();
                    $p->id = $id;
                    $p->altura = $altura;
                    $p->rua = $rua;
                    $p->numero = $numero;
                    $p->quantidade = $quantidade;
                    $p->nome = $nome;
                    $p->categorias = array($id_empresa . "", $tipo . "", $classe_risco . "");
                    $p->ponto_fulgor = $ponto_fulgor;
                    $p->nivel_saida = $classificacao_saida * 10;
                    $p->peso = $peso_bruto;

                    if (!isset($armazem->produtos[$p->id])) {
                        $armazem->produtos[$p->id] = array();
                    }
                    $armazem->produtos[$p->id][$p->uid] = $p;

                    $armazem->armazem[$altura][$rua][$numero]->produto = $p;
                }
            }
        }

        $ps->close();


        $ps = $con->getConexao()->prepare("SELECT l.altura-1,l.rua-1,l.numero-1,l.quantidade,p.nome,p.id,p.tipo,p.id_empresa,p.classe_risco,p.ponto_fulgor,p.classificacao_saida,p.peso_bruto FROM previsao_alocacao l INNER JOIN produto_pedido_entrada pp ON pp.id=l.id_produto_pedido_entrada INNER JOIN produto p ON pp.id_produto=p.id WHERE (p.id_logistica=$empresa->id OR p.id_empresa=$empresa->id) AND p.excluido=false");
        $ps->execute();
        $ps->bind_result($altura, $rua, $numero, $quantidade, $nome, $id, $tipo, $id_empresa, $classe_risco, $ponto_fulgor, $classificacao_saida, $peso_bruto);

        while ($ps->fetch()) {
            if (isset($armazem->armazem[$altura][$rua][$numero])) {
                if ($armazem->armazem[$altura][$rua][$numero]->produto === null) {

                    $p = new Aloc_Produto();
                    $p->id = $id;
                    $p->altura = $altura;
                    $p->rua = $rua;
                    $p->numero = $numero;
                    $p->quantidade = $quantidade;
                    $p->nome = $nome;
                    $p->real = false;
                    $p->categorias = array($id_empresa . "", $tipo . "", $classe_risco . "");
                    $p->ponto_fulgor = $ponto_fulgor;
                    $p->nivel_saida = $classificacao_saida * 10;
                    $p->peso = $peso_bruto;

                    if (!isset($armazem->produtos[$p->id])) {
                        $armazem->produtos[$p->id] = array();
                    }
                    $armazem->produtos[$p->id][$p->uid] = $p;

                    $armazem->armazem[$altura][$rua][$numero]->produto = $p;
                }
            }
        }

        $ps->close();

        return $armazem;
    }

    public function getBeneficio($produto) {

        $b = 0;

        $altura_justificada = false;
        if ($produto->altura === 0) {
            if (isset($this->produtos[$produto->id])) {
                $p = $this->produtos[$produto->id];

                $outros = 1;
                foreach ($p as $key => $value) {
                    if ($value->altura === 0 && $value->id === $produto->id && $value->uid !== $produto->uid) {
                        $outros++;
                        break;
                    }
                }

                $b += 400 / $outros;

                if (isset($this->armazem[$produto->altura + 1][$produto->rua][$produto->numero])) {
                    $ppoo = $this->armazem[$produto->altura + 1][$produto->rua][$produto->numero];
                    if ($ppoo->produto !== null) {
                        if ($ppoo->produto->id === $produto->id) {

                            $b += 100;
                        }
                    }
                }
            }
        } else {

            $p = $this->produtos[$produto->id];

            foreach ($p as $key => $value) {
                if ($value->altura === 0 && $value->id === $produto->id && $value->uid !== $produto->uid) {
                    if ($value->numero === $produto->numero && $value->rua === $produto->rua) {
                        $b += 500;
                        $altura_justificada = true;
                    }
                    break;
                }
            }
        }

        $n = ($produto->nivel_saida / 100) * 200;

        $np = 100;

        if ($produto->altura > 0 && !$altura_justificada) {

            $np -= 20 + (10 * $produto->altura);
        }

        $np -= ($produto->rua + $produto->numero)/10;

        $np = max($np, 1);


        $b += $n * ($np / 100);

        return $b;
    }

    public function isPossivel($altura, $rua, $numero, $produto) {

        if (!isset($this->armazem[$altura][$rua][$numero])) {

            return false;
        }

        if ($altura > 0) {

            $suporte_peso = 1000 - (100 * $altura);

            if ($suporte_peso < $produto->peso * $produto->quantidade) {

                return false;
            }
        }

        if (($produto->ponto_fulgor < 30 && $produto->ponto_fulgor != 0) !== ($this->armazem[$altura][$rua][$numero]->zona_inflamavel == 1)) {

            return false;
        }

        if ($produto->estado == Aloc_Produto::$LIQUIDO && isset($this->armazem[$altura - 1][$rua][$numero])) {
            $pab = $this->armazem[$altura - 1][$rua][$numero];
            if ($pab->produto !== null) {
                if ($pab->produto->estado == Aloc_Produto::$PO) {
                    return false;
                }
            }
        }

        if ($produto->estado == Aloc_Produto::$PO && isset($this->armazem[$altura + 1][$rua][$numero])) {
            $pab = $this->armazem[$altura + 1][$rua][$numero];
            if ($pab->produto !== null) {
                if ($pab->produto->estado == Aloc_Produto::$LIQUIDO) {
                    return false;
                }
            }
        }

        return true;
    }

    public function applyRes($res, $produtos = array()) {

        foreach ($produtos as $key => $value) {

            if (!isset($this->produtos[$value->id])) {
                $this->produtos[$value->id] = array();
            }
            $this->produtos[$value->id][$value->uid] = $value;
        }


        foreach ($res[1] as $key => $value) {
            
            $pp = $this->produtos[$value[0]][$value[1]];
            
            $estava = true;
            
            foreach($produtos as $kp=>$produto){
                if($produto->uid === $pp->uid){
                    $estava = false;
                    break;
                }
            }
            
            if($estava){
                $this->armazem[$pp->altura][$pp->rua][$pp->numero]->produto = null;
            }
            
            $pp->rua = $value[3];
            $pp->numero = $value[4];
            $pp->altura = $value[2];

            $this->armazem[$value[2]][$value[3]][$value[4]]->produto = $pp;
        }
    }

    public function alocarItem($produto, $alteracoes = array(),$limite = 3, $level = 0) {

        if ($level == $limite) {

            return null; //maximo de cinco deslocamentos.
        }

        $existentes = array();

        if (isset($this->produtos[$produto->id])) {

            foreach ($this->produtos[$produto->id] as $key => $value) {
                $existentes[] = $value;
            }
        } else {

            $p = array();

            foreach ($this->produtos as $k => $v) {
                foreach ($v as $key => $value) {
                    $p[] = $value;
                }
            }

            foreach ($produto->categorias as $kc => $cat) {
                $unseted = array();
                foreach ($p as $key => $value) {
                    if ($value->categorias[$kc] !== $cat) {
                        $unseted[] = $value;
                        unset($p[$key]);
                    }
                }
                if (count($p) === 0) {
                    if ($kc > 0) {
                        $p = $unseted;
                    }
                    break;
                }
            }

            $existentes = $p;
        }


        $pp = array();
        $total = false;

        if (count($existentes) > 0) {

            foreach ($existentes as $key => $value) {

                $posic = array($value->altura, $value->rua, $value->numero);

                $k = array(
                    array(0, 0, 0),
                    array(1, 0, 0),
                    array(-1, 0, 0),
                    array(0, 1, 0),
                    array(0, -1, 0),
                    array(0, 0, 1),
                    array(0, 0, -1)
                );

                for ($i = 0; $i < count($k); $i++) {

                    $pos = Utilidades::copyArray($posic);

                    $pos[0] += $k[$i][0];
                    $pos[1] += $k[$i][1];
                    $pos[2] += $k[$i][2];

                    if (!isset($this->armazem[$pos[0]][$pos[1]][$pos[2]])) {
                        continue;
                    }

                    if (!isset($pp[$pos[0]])) {
                        $pp[$pos[0]] = array();
                    }
                    if (!isset($pp[$pos[0]][$pos[1]])) {
                        $pp[$pos[0]][$pos[1]] = array();
                    }
                    if (!isset($pp[$pos[0]][$pos[1]][$pos[2]])) {
                        $pp[$pos[0]][$pos[1]][$pos[2]] = true;
                    }
                }
            }

            foreach ($existentes as $key => $value) {
                unset($pp[$value->altura][$value->rua][$value->numero]);
            }

            $total = false;
        } else {

            $pp = $this->armazem;
            $total = true;
        }


        $melhor = null;

        while (true) {
            foreach ($pp as $altura => $ruas) {
                foreach ($ruas as $rua => $numeros) {
                    foreach ($numeros as $numero => $value) {
                        if ($this->isPossivel($altura, $rua, $numero, $produto)) {

                            $pos = $this->armazem[$altura][$rua][$numero];
                            if ($pos->produto !== null) {
                                if ($pos->produto->alg_aux) {
                                    continue;
                                } else {
                                    $pr = $pos->produto;

                                    $br = -1 * $this->getBeneficio($pr);

                                    $br -= 100; //pelo deslocamento; 

                                    unset($this->produtos[$pr->id][$pr->uid]);

                                    //----------------------------------------------------
                                    
                                    $alt = Utilidades::copyArray($alteracoes);
                                    $alt[] = array($produto->id, $produto->uid, $altura, $rua, $numero,$produto->altura,$produto->rua,$produto->numero);
                                    
                                    $produto->rua = $rua;
                                    $produto->altura = $altura;
                                    $produto->numero = $numero;
                                    $produto->alg_aux = true;
                                    $this->armazem[$altura][$rua][$numero]->produto = $produto;
                                    $pos->produto = $produto;

                                    if (!isset($this->produtos[$produto->id])) {
                                        $this->produtos[$produto->id] = array();
                                    }
                                    $this->produtos[$produto->id][$produto->uid] = $produto;
                                    //-------------------------------------------------------

                                    $br += $this->getBeneficio($produto);

                                    $rec = $this->alocarItem($pr, $alt,$limite, $level + 1);

                                    if ($rec == null) {
                                        continue;
                                    }

                                    $rec[0] += $br;

                                    if ($melhor == null) {
                                        $melhor = $rec;
                                    } else {
                                        if ($melhor[0] < $rec[0]) {
                                            $melhor = $rec;
                                        }
                                    }

                                    $produto->aux_alg = false;
                                    $this->armazem[$altura][$rua][$numero]->produto = $pr;
                                    $pos->produto = $pr;
                                    unset($this->produtos[$produto->id][$produto->uid]);

                                    $this->produtos[$pr->id][$pr->uid] = $pr;
                                }
                            } else {
                                
                                $alt = Utilidades::copyArray($alteracoes);
                                $alt[] = array($produto->id, $produto->uid, $altura, $rua, $numero,$produto->altura,$produto->rua,$produto->numero);

                                //--------------------------------------------------
                                $produto->rua = $rua;
                                $produto->altura = $altura;
                                $produto->numero = $numero;
                                $produto->alg_aux = true;
                                $this->armazem[$altura][$rua][$numero]->produto = $produto;
                                $pos->produto = $produto;
                                if (!isset($this->produtos[$produto->id])) {
                                    $this->produtos[$produto->id] = array();
                                }
                                $this->produtos[$produto->id][$produto->uid] = $produto;
                                //----------------------------------------------------


                                $br = $this->getBeneficio($produto);

                                $it = array($br, $alt);


                                if ($melhor == null) {
                                    $melhor = $it;
                                } else {
                                    if ($melhor[0] < $it[0]) {
                                        $melhor = $it;
                                    }
                                }

                                $produto->aux_alg = false;
                                $pos->produto = null;
                                unset($this->produtos[$produto->id][$produto->uid]);
                            }
                        }
                    }
                }
            }
            if(!$total && $melhor == null){
                $pp = $this->armazem;
                $total = true;
                continue;
            }
            break;
        }


        return $melhor;
    }

}
