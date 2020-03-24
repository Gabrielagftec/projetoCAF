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
class Produto {

    public $id;
    public $id_universal;
    public $nome;
    public $categoria;
    public $liquido;
    public $unidade;
    public $quantidade_unidade;
    public $excluido;
    public $habilitado;
    public $empresa;
    public $valor_base;
    public $lucro_consignado;
    public $custo;
    public $ncm;
    public $peso_liquido;
    public $peso_bruto;
    public $estoque;
    public $ativo;
    public $concentracao;
    public $disponivel;
    public $transito;
    public $ofertas;
    public $grade;
    public $imagem;
    public $fabricante;
    public $classe_risco;
    public $logistica;
    public $sistema_lotes;
    public $nota_usuario;
    public $codigo;
    public $mais_fotos;
    public $id_empresa_vendas;
    public $perfeicao;
    public $aceitacao;
    public $troca;
    public $dia_semana;
    public $grau_perfeicao;
    public $grau_aceitacao;
    public $classificacao_saida;
    public $emite_receita;
    public $estoque_minimo;
    public $estoque_ideal;
    public $estoque_maximo;
    public $link;
    public $valor_cotacao;
    public $observacao;
    public $cr;
    
    public $formula_preco;

    public $nome_fantasia;

    public $imagem_venda;
    public $imagem_leilao;
    public $imagem_armazenagem;

    public $tipo;
    public $ponto_fulgor;

    public $permissao;
    
    function __construct() {

        $this->permissao = false;
        $this->id = 0;
        $this->id_universal = 0;
        $this->categoria = null;
        $this->liquido = false;
        $this->quantidade_unidade = 1;
        $this->excluido = false;
        $this->habilitado = true;
        $this->empresa = null;
        $this->valor_base = 0;
        $this->custo = 0;
        $this->valor_cotacao = 0;
        $this->estoque_maximo = 0;
        $this->estoque_minimo = 0;
        $this->estoque_ideal = 0;
        $this->cr = 0;

        $this->observacao = "";

        $this->nome_fantasia = "";

        $this->imagem_venda = "";
        $this->imagem_leilao = "";
        $this->imagem_armazenagem = "";

        $this->peso_bruto = 0;
        $this->lucro_consignado = 0;
        $this->peso_liquido = 0;
        $this->estoque = 0;
        $this->disponivel = 0;
        $this->classificacao_saida = 0;
        $this->transito = 0;
        $this->grade = new Grade("1");
        $this->ofertas = array();
        $this->classe_risco = 0;
        $this->ativo = "";
        $this->concentracao = "";
        $this->logistica = null;
        $this->sistema_lotes = true;
        $this->nota_usuario = 5;
        $this->ncm = "000000";
        $this->unidade = "Ob";
        $this->codigo = 0;
        $this->id_empresa_vendas = 0;
        $this->mais_fotos = array();
        $this->troca = 0;
        $this->perfeicao = 0;
        $this->aceitacao = 0;
        $this->dia_semana = 1;
        $this->emite_receita = -1;
        
        $this->tipo = "";
        $this->ponto_fulgor = -1;

        $this->grau_aceitacao = array(
            new GrauProduto(10,"Produtos para descarte"),
            new GrauProduto(20,"Produtos para doacao"),
            new GrauProduto(30,"Produtos de baixo nivel de interesse"),
            new GrauProduto(40,"Produtos fora de uso para atividades de Museu"),
            new GrauProduto(50,"Produtos fora de epoca podendo ser novos. Tipo reliquia etc"),
            new GrauProduto(60,"Produtos dirigidos a grupos especificos tipo uniformes, bandeiras, roupas tipicas, folclore etc"),
            new GrauProduto(70,"Produtos importantes mas que podem ser substituidos como vestuario, materiais escolares e de escritorio"),
            new GrauProduto(80,"Produtos especificos em atividades gerais. Ferramentas, maquinas de forma geral tipo serralheria,marcenaria,hospitalares"),
            new GrauProduto(90,"Produtos utilizados em diferentes atividades tipo: motocicleta, bicicleta, roupas, materiais esportivos."),
            new GrauProduto(100,"Produto muito utilizado em qualquer lugar, circunstancia, esfera ou cultura. Ex: cadeira, mesa, celular, microondas, geladeira, veiculo, ar condicionado, etc...")
        );


        $this->grau_perfeicao = array(
            new GrauProduto(10,"Sucata"),
            new GrauProduto(20,"Pode ser sucata sem desmante"),
            new GrauProduto(30,"Produto para desmante, pecas boas"),
            new GrauProduto(40,"Produto muito sujo. Sem funcionamento"),
            new GrauProduto(50,"Produto sujo. Sem funcionamento"),
            new GrauProduto(90,"Produto sujo com bom funcionamento"),
            new GrauProduto(92,"Bonito funcionando muito bem"),
            new GrauProduto(94,"Bom estado funcionando muito bem"),
            new GrauProduto(96,"Semi novo com garantia Help"),
            new GrauProduto(98,"Semi novo fora da embalagem com garantia Help"),
            new GrauProduto(99,"Novo fora da embalagem com garantia Help"),
            new GrauProduto(100,"Novo na caixa com Garantia")
        );

        $this->formula_preco = "";
        
    }

     public function getProdutosFornecedor($con){

        $inf = "(-1";

        $this->produtos = array();

        $ps = $con->getConexao()->prepare("SELECT id,id_fornecedor,preco1,comis1,preco2,comis2,preco3,comis3,preco4,comis4,UNIX_TIMESTAMP(validade)*1000 FROM produto_fornecedor WHERE id_produto = $this->id");
        $ps->execute();
        $ps->bind_result($id,$id_fornecedor,$preco1,$comis1,$preco2,$comis2,$preco3,$comis3,$preco4,$comis4,$validade);
        while($ps->fetch()){

            $p = new ProdutoFornecedor();
            $p->id = $id;
            $p->produto = $this;
            $p->preco1 = $preco1;
            $p->id_fornecedor = $id_fornecedor;
            $p->comis1 = $comis1;
            $p->preco2 = $preco2;
            $p->comis2 = $comis2;
            $p->preco3 = $preco3;
            $p->comis3 = $comis3;
            $p->preco4 = $preco4;
            $p->comis4 = $comis4;
            $p->validade = $validade;

            $this->produtos[] = $p;

            $inf .= ",$id_produto";

        }
        $ps->close();

        $inp .= ")";

        $p = $this->empresa->getFornecedores($con,0,1000,"fornecedor.id IN $inf","");

        foreach ($this->produtos as $key => $value) {
            
            foreach ($p as $k => $v2) {
                
                if($v2->id === $value->id_fornecedor){

                    $value->fornecedor = $v2;
                    continue 2;

                }

            }

        }


        return $this->produtos;

    }

    public function temPermissao($con,$usuario){

        $ps = $con->getConexao()->prepare("SELECT id_produto FROM solicitacao_permissao_preco WHERE id_produto=$this->id AND id_usuario=$usuario->id AND aceita=1");
        $ps->execute();
        $ps->bind_result($idp);

        if($ps->fetch()){

            $ps->close();
            return true;

        }

        $ps->close();
        return false;

    }

    public function solicitarPermissao($con,$usuario,$obs){

        $obs = $this->nome.", $obs";

        $ps = $con->getConexao()->prepare("INSERT INTO solicitacao_permissao_preco(id_produto,id_usuario,aceita,observacao) VALUES($this->id,$usuario->id,0,'".addslashes($obs)."')");
        $ps->execute();
        $ps->close();

    }

    public function desativar($con,$produto){

    	$relac = ";P$this->id;";

    	$ps = $con->getConexao()->prepare("UPDATE itens_kim_go SET desativa_media=CONCAT(desativa_media,'".addslashes($relac)."') WHERE id=$produto->id");
    	$ps->execute();
    	$ps->close();


    	$produto->desativado = true;

    }

    public function inserirAproximado($con,$aproximado){

        $relac = ";P$this->id;";

        $sql = "INSERT INTO itens_kim_go(nome,imagem,link,preco,desativa_media,relacoes,empresa,id_empresa,usado) VALUES('".addslashes($aproximado->nome)."','".addslashes($aproximado->imagem)."','".addslashes($aproximado->link)."',".$aproximado->preco.",0,'$relac','".addslashes($aproximado->empresa)."',".$this->empresa->id.",".($aproximado->usado?"true":"false").")";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        
        $aproximado->id = $ps->insert_id;

        $ps->close();

        return $aproximado;

    }

    public function getAproximados($con){

        $relac = ";P$this->id;";

        $ps = $con->getConexao()->prepare("SELECT id,nome,imagem,link,preco,desativa_media LIKE '%$relac%',empresa,usado FROM itens_kim_go WHERE relacoes LIKE '%".addslashes($relac)."%' AND desativa_media NOT LIKE '%P".$this->id."%' ORDER BY id DESC");
        $ps->execute();
        $ps->bind_result($id,$nome,$imagem,$link,$preco,$desativado,$empresa,$usado);

        $res = array();

        while($ps->fetch()){

            $p = new stdClass();
            $p->id = $id;
            $p->imagem = $imagem;
            $p->nome = $nome;
            $p->link = $link;
            $p->preco = $preco;
            $p->desativado = $desativado == 1;
            $p->empresa = $empresa;
            $p->usado = $usado==1;

            $res[] = $p;

        }

        $ps->close();

        return $res;

    }

    public function setFichaEmergencia($con,$ficha){

        $ps = $con->getConexao()->prepare("DELETE FROM ficha_emergencia WHERE id_produto=$this->id");
        $ps->execute();
        $ps->close();

        $ps = $con->getConexao()->prepare("INSERT INTO ficha_emergencia(id_produto,link_ficha) VALUES($this->id,'$ficha')");
        $ps->execute();
        $ps->close();

    }

    public function getFichaEmergencia($con){

        $ps = $con->getConexao()->prepare("SELECT link_ficha FROM ficha_emergencia WHERE id_produto=$this->id");
        $ps->execute();
        $ps->bind_result($link);
        if($ps->fetch()){
            $ps->close();

            return $link;

        }
        $ps->close();

        return "";

    }
    
    public function passarParaOutrasEmpresas($con){
        
         if($this->categoria === null){
            
            $cats = Sistema::getCategoriaProduto($this->empresa);
            
            $this->categoria = $cats[0];
            
            $this->merge($con);
            
        }
        
        $clone = Utilidades::copyId0($this);
        $clone->logistica = null;
        $clone->codigo = 0;
        $clone->estoque = 0;
        $clone->disponivel = 0;
        $clone->transito = 0;
        
        $filiais = array($this->empresa);//$this->empresa->getFiliais($con);
        
        foreach($filiais as $key=>$value){
            
            //if($value->id === $this->empresa->id)continue;
            
            $clone2 = Utilidades::copyId0($clone);
            
            $clone2->empresa = $value;
            
            //$cats = Sistema::getCategoriaProduto($value);
            
            //$clone2->categoria = $cats[0];
            
            $clone2->merge($con);
            
        }
        
    }

    public function setMaisFotos($con, $fotos) {

        $ps = $con->getConexao()->prepare("DELETE FROM mais_fotos_produto WHERE id_produto=$this->id");
        $ps->execute();
        $ps->close();

        foreach ($fotos as $key => $value) {

            $ps = $con->getConexao()->prepare("INSERT INTO mais_fotos_produto(id_produto,imagem,tipo) VALUES($this->id,'$value->imagem',$value->tipo)");
            $ps->execute();
            $ps->close();
        }
    }

    public function getReduzido() {

        $p = new ProdutoReduzido();
        $p->id = $this->id;
        $p->codigo = $this->codigo;
        $p->nome = $this->nome;
        $p->imagem = $this->imagem;
        $p->quantidade_unidade = $this->quantidade_unidade;

        return $p;
    }

    public function attDiaSemana($con){

        $ps = $con->getConexao()->prepare("UPDATE produto SET dia_semana=$this->dia_semana WHERE codigo=$this->codigo AND id_empresa=".$this->empresa->id);
        $ps->execute();
        $ps->close();

    }

    public function merge($con, $verifica = true) {

        if ($this->id_universal === 0) {

            $ps = $con->getConexao()->prepare("SELECT IFNULL(MAX(id_universal)+1,0) FROM produto");
            $ps->execute();
            $ps->bind_result($idn);

            if ($ps->fetch()) {

                $this->id_universal = $idn;
            }

            $ps->close();
        }

        if ($this->codigo === 0) {

            $ps = $con->getConexao()->prepare("SELECT IFNULL(MAX(codigo)+1,0) FROM produto WHERE id_empresa=" . $this->empresa->id);
            $ps->execute();
            $ps->bind_result($idn);

            if ($ps->fetch()) {

                $this->codigo = $idn;
            }

            $ps->close();

        }

        $troca_antes = 0;

        $ps = $con->getConexao()->prepare("SELECT troca FROM produto WHERE id=$this->id");
        $ps->execute();
        $ps->bind_result($t);
        if($ps->fetch()){
            
            $troca_antes = $t;

        }
        $ps->close();

        $diff = $this->troca-$troca_antes;

        $this->disponivel -= $diff;

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO produto(id_universal,nome,id_categoria,liquido,quantidade_unidade,excluido,habilitado,id_empresa,valor_base,custo,peso_bruto,peso_liquido,estoque,disponivel,transito,grade,unidade,ncm,lucro_consignado,ativo,concentracao,classe_risco,fabricante,imagem,id_logistica,sistema_lotes,nota_usuario,codigo,perfeicao,aceitacao,troca,dia_semana,tipo,ponto_fulgor,estoque_minimo,estoque_ideal,estoque_maximo,imagem_venda,imagem_leilao,imagem_armazenagem,nome_fantasia,observacao,cr,link,formula_preco) VALUES($this->id_universal,'" . addslashes($this->nome) . "'," . $this->categoria->id . "," . ($this->liquido ? "true" : "false") . ",$this->quantidade_unidade,false," . ($this->habilitado ? "true" : "false") . "," . $this->empresa->id . ",$this->valor_base,$this->custo,$this->peso_bruto,$this->peso_liquido,$this->estoque,$this->disponivel,$this->transito,'" . $this->grade->str . "','" . addslashes($this->unidade) . "','" . addslashes($this->ncm) . "',$this->lucro_consignado,'$this->ativo','$this->concentracao','$this->classe_risco','$this->fabricante','$this->imagem'," . ($this->logistica !== null ? $this->logistica->id : 0) . "," . ($this->sistema_lotes ? "true" : "false") . ",$this->nota_usuario,$this->codigo,$this->perfeicao,$this->aceitacao,$this->troca,$this->dia_semana,'".addslashes($this->tipo)."',$this->ponto_fulgor,$this->estoque_minimo,$this->estoque_ideal,$this->estoque_maximo,'$this->imagem_venda','$this->imagem_leilao','$this->imagem_armazenagem','".addslashes($this->nome_fantasia)."','".addslashes($this->observacao)."',$this->cr,'".addslashes($this->link)."','".addslashes($this->formula_preco)."')");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        } else {
            
            if(!$this->sistema_lotes){
                
                $ps = $con->getConexao()->prepare("SELECT sistema_lotes FROM produto WHERE id=$this->id");
                $ps->execute();
                $ps->bind_result($sistema_lotes_antigo);
                if($ps->fetch()){
                    $ps->close();
                    if($sistema_lotes_antigo == 1){
                        $ses = new SessionManager();
                        $usuario = $ses->get("usuario");
                        if($usuario !== null){
                            
                            $t = new Tarefa();
                            $t->descricao = "O produto $this->codigo-$this->nome,"
                                    . " foi alterado para nao utilizar sistema de lotes, "
                                    . "se isso foi uma alteracao para suprir uma necessidade pontual, "
                                    . "coloque SIM, novamente no cadastro desse produto quanto a utilizacao "
                                    . "do sistema de lotes";
                            $t->titulo = "Alteracao critica no cadastro do produto $this->codigo-$this->nome";
                            $t->tipo_entidade_relacionada = "PRO";
                            $t->id_entidade_relacionada = $this->id;
                            $t->prioridade = 100;
                            $t->tipo_tarefa = Sistema::TT_ATIVIDADE_COMUM($usuario->empresa->id);
                            
                            $usuario->addTarefa($con, $t);
                            
                        }
                    }
                }else{
                    $ps->close();
                }
                
            }

            if ($this->sistema_lotes) {
                $ps = $con->getConexao()->prepare("SELECT SUM(quantidade_real) FROM lote WHERE id_produto=$this->id");
                $ps->execute();
                $ps->bind_result($quantidade);
                if ($ps->fetch()) {
                    $ps->close();
                    if ($this->disponivel < $quantidade) {
                        //throw new Exception("Estoque invalido, nao pode ser menor que a quantidade de lotes cadastrada $quantidade");
                    }
                } else {
                    $ps->close();
                }
            }
            if ($verifica) {
                $ps = $con->getConexao()->prepare("SELECT SUM(influencia_reserva)-SUM(influencia_estoque) FROM produto_pedido_saida WHERE id_produto=$this->id");
                $ps->execute();
                $ps->bind_result($d);
                if ($ps->fetch()) {
                    $ps->close();
                    if ($this->disponivel !== $this->estoque + $d) {
                        //throw new Exception("Existem " . (-1 * $d) . " produtos reservados, portanto o estoque($this->estoque) e disponivel($this->disponivel) nao batem");
                    }
                } else {
                    $ps->close();
                }
            }

            if ($this->estoque < $this->disponivel) {
                //throw new Exception("O estoque nao pode ser menor que o disponivel");
            }

            $ps = $con->getConexao()->prepare("UPDATE produto SET codigo=$this->codigo WHERE id=$this->id");
            $ps->execute();
            $ps->close();

            $ps = $con->getConexao()->prepare("UPDATE produto SET nome = '" . addslashes($this->nome) . "', id_universal=$this->id_universal, id_categoria=" . $this->categoria->id . ",liquido=" . ($this->liquido ? "true" : "false") . ", valor_base=" . $this->valor_base . ",custo=$this->custo,peso_bruto=$this->peso_bruto,peso_liquido=$this->peso_liquido,grade='" . $this->grade->str . "',unidade='" . addslashes($this->unidade) . "',ncm='" . addslashes($this->ncm) . "',quantidade_unidade=$this->quantidade_unidade,lucro_consignado=$this->lucro_consignado, ativo='$this->ativo', concentracao='$this->concentracao',classe_risco='$this->classe_risco',fabricante='$this->fabricante',imagem='$this->imagem',sistema_lotes=" . ($this->sistema_lotes ? "true" : "false") . ",nota_usuario=$this->nota_usuario, codigo=$this->codigo,perfeicao=$this->perfeicao,aceitacao=$this->aceitacao,dia_semana=$this->dia_semana,tipo='".addslashes($this->tipo)."',ponto_fulgor=$this->ponto_fulgor,estoque_minimo=$this->estoque_minimo,estoque_ideal=$this->estoque_ideal,estoque_maximo=$this->estoque_maximo,imagem_venda='".addslashes($this->imagem_venda)."',imagem_leilao='".addslashes($this->imagem_leilao)."',imagem_armazenagem='".addslashes($this->imagem_armazenagem)."', nome_fantasia='".addslashes($this->nome_fantasia)."',observacao='".addslashes($this->observacao)."',cr=$this->cr,link='".addslashes($this->link)."',formula_preco='".addslashes($this->formula_preco)."' WHERE codigo = " . $this->codigo . " AND id_empresa=" . $this->empresa->id);


            $ps->execute();
            $ps->close();

            $ps = $con->getConexao()->prepare("UPDATE produto SET id_empresa=" . $this->empresa->id . ",estoque=" . $this->estoque . ",disponivel=" . $this->disponivel . ",transito=" . $this->transito . ",habilitado=" . ($this->habilitado ? "true" : "false") . ",id_logistica=" . ($this->logistica === null ? "0" : $this->logistica->id) . ",troca=$this->troca WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }
        
        if($this->emite_receita >= 0){
            
            $ps = $con->getConexao()->prepare("UPDATE produto SET emite_receita=$this->emite_receita WHERE id=$this->id");
            $ps->execute();
            $ps->close();
            
        }

    }

    public function atualizarEstoque($con) {

        $ps = $con->getConexao()->prepare("SELECT estoque,disponivel,transito,troca FROM produto WHERE id = $this->id");
        $ps->execute();
        $ps->bind_result($estoque, $disponivel, $transito,$troca);

        if ($ps->fetch()) {

            $this->estoque = $estoque;
            $this->disponivel = $disponivel;
            $this->transito = $transito;
            $this->troca = $troca;

        }

        $ps->close();
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("UPDATE produto SET excluido = true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

    public function getLotes($con, $filtro = null, $ordem = null) {

        $sql = "SELECT lote.id,lote.numero,lote.rua,lote.altura, UNIX_TIMESTAMP(lote.validade)*1000, UNIX_TIMESTAMP(lote.data_entrada)*1000, lote.quantidade_inicial, lote.grade, lote.quantidade_real, lote.codigo_fabricante, retirada.retirada FROM lote LEFT JOIN retirada ON lote.id=retirada.id_lote WHERE lote.excluido=false AND lote.id_produto=$this->id";
        if ($filtro != null && $filtro != "") {

            $sql .= " AND (" . addslashes($filtro) . ")";
        }

        if ($ordem != null && $ordem != "") {

            $sql .= " ORDER BY " . addslashes($ordem);
        }

        $lotes = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $numero, $rua, $altura, $validade, $entrada, $quantidade_inicial, $grade, $quantidade_real, $codigo_fabricante, $retirada);

        while ($ps->fetch()) {

            if (!isset($lotes[$id])) {

                $lote = new Lote();
                $lote->id = $id;
                $lote->numero = $numero;
                $lote->rua = $rua;
                $lote->altura = $altura;
                $lote->validade = $validade;
                $lote->entrada = $entrada;
                $lote->quantidade_inicial = $quantidade_inicial;
                $lote->grade = new Grade($grade);
                $lote->quantidade_real = $quantidade_real;
                $lote->produto = $this;
                $lote->codigo_fabricante = $codigo_fabricante;

                $lotes[$id] = $lote;
            }

            if ($retirada != null) {

                $ret = explode(',', $retirada);
                foreach ($ret as $key => $value) {

                    $ret[$key] = intval($ret[$key]);
                }

                $lotes[$id]->retiradas[] = $ret;
            }
        }

        $ps->close();

        $retorno = array();

        foreach ($lotes as $key => $value) {

            $retorno[] = $value;
        }

        return $retorno;
    }

    public function getReceituario($con) {

        $receituarios = array();

        $ps = $con->getConexao()->prepare("SELECT receituario.id, receituario.instrucoes, cultura.id, cultura.nome, praga.id, praga.nome, IFNULL(receituario.tipo_plantacao,0),"
                . "IFNULL(receituario.total_calda_ha,0),"
                . "IFNULL(receituario.tipo_total_calda_ha,0),"
                . "IFNULL(receituario.carencia,0),"
                . "IFNULL(receituario.qtd_calda,0),"
                . "IFNULL(receituario.unidade_qtd_calda,0),"
                . "IFNULL(receituario.unidade_usada,0),"
                . "IFNULL(receituario.dosagem_max,0),"
                . "IFNULL(receituario.tipo_dosagem_max,0),"
                . "IFNULL(receituario.epoca_aplicacao,''),"
                . "IFNULL(receituario.diagnostico,''),"
                . "IFNULL(receituario.manejo_integrado,''),"
                . "IFNULL(receituario.precaucoes,''),"
                . "IFNULL(receituario.epi,''),"
                . "IFNULL(receituario.informacoes_adcionais,'') FROM receituario INNER JOIN produto p ON p.id=receituario.id_produto INNER JOIN praga ON praga.id=receituario.id_praga INNER JOIN cultura ON cultura.id=receituario.id_cultura AND receituario.excluido=false AND (p.id_empresa=".$this->empresa->id.($this->empresa->id==1799?" OR true":"").") AND p.codigo=$this->codigo");
        $ps->execute();
        $ps->bind_result($id, $instrucoes, $id_cultura, $nome_cultura, $id_praga, $nome_praga,$tipo_plantacao,$total_calda,$tipo_total_calda_ha,$carencia,$qtd_calda,$unidade_qtd_calda,$unidade_usada,$dosagem_max,$tipo_dosagem_max,$epoca_aplicacao,$diagnostico,$manejo_integrado,$precaucoes,$epi,$informacoes_adcionais);

        while ($ps->fetch()) {

            $r = new Receituario();
            $r->id = $id;
            $r->instrucoes = $instrucoes;
            
            $r->carencia = $carencia;
            $r->diagnostico = $diagnostico;
            $r->dosagem_max = $dosagem_max;
            $r->epi = $epi;
            $r->epoca_aplicacao = $epoca_aplicacao;
            $r->informacoes_adcionais = $informacoes_adcionais;
            $r->manejo_integrado = $manejo_integrado;
            $r->precaucoes = $precaucoes;
            $r->qtd_calda = $qtd_calda;
            $r->tipo_dosagem_max = $tipo_dosagem_max;
            $r->tipo_plantacao = $tipo_plantacao;
            $r->tipo_total_calda_ha = $tipo_total_calda_ha;
            $r->total_calda_ha = $total_calda;
            $r->unidade_qtd_calda = $unidade_qtd_calda;
            $r->unidade_usada = $unidade_usada;
            
            foreach(Receituario::getMedidas() as $key=>$value){
                if($value[0] === $r->tipo_dosagem_max){
                    $r->tipo_dosagem_max = $value;
                }
                if($value[0] === $r->unidade_qtd_calda){
                    $r->unidade_qtd_calda = $value;
                }
                if($value[0] === $r->tipo_total_calda_ha){
                    $r->tipo_total_calda_ha = $value;
                }
            }
            
            foreach(Receituario::getTiposPlantacao() as $key=>$value){
                if($value[0] === $r->tipo_plantacao){
                    $r->tipo_plantacao = $value;
                }
            }
            
            $r->produto = $this;

            $c = new Cultura();
            $c->id = $id_cultura;
            $c->nome = $nome_cultura;

            $r->cultura = $c;

            $p = new Praga();
            $p->id = $id_praga;
            $p->nome = $nome_praga;

            $r->praga = $p;

            $receituarios[] = $r;
        }

        $ps->close();

        return $receituarios;
    }

}
