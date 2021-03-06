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
class PedidoEntrada {

    public $id;
    public $fornecedor;
    public $frete;
    public $status;
    public $excluido;
    public $usuario;
    public $transportadora;
    public $data;
    public $produtos;
    public $frete_incluso;
    public $nota;
    public $observacoes;
    public $prazo;
    public $parcelas;
    public $empresa;
    public $enviar_emails;
    public $entrega;

    function __construct() {

        $this->id = 0;
        $this->fornecedor = null;
        $this->frete = 0;
        $this->frete_incluso = false;
        $this->status = null;
        $this->excluido = false;
        $this->usuario = null;
        $this->empresa = null;
        $this->data = round(microtime(true) * 1000);
        $this->produtos = null;
        $this->prazo = 0;
        $this->parcelas = 1;
        $this->observacoes = "";
        $this->enviar_emails = true;
        $this->entrega = 0;

    }

    public function getProdutos($con) {

        $campanhas = array();
        $ofertas = array();

        $ps = $con->getConexao()->prepare("SELECT "
                . "campanha.id,"
                . "campanha.nome,"
                . "UNIX_TIMESTAMP(campanha.inicio)*1000,"
                . "UNIX_TIMESTAMP(campanha.fim)*1000,"
                . "campanha.prazo,"
                . "campanha.parcelas,"
                . "campanha.cliente_expression,"
                . "produto_campanha.id,"
                . "produto_campanha.id_produto,"
                . "UNIX_TIMESTAMP(produto_campanha.validade)*1000,"
                . "produto_campanha.limite,"
                . "produto_campanha.valor, "
                . "empresa.id,"
                . "empresa.tipo_empresa,"
                . "empresa.nome,"
                . "empresa.inscricao_estadual,"
                . "empresa.consigna,"
                . "empresa.aceitou_contrato,"
                . "empresa.juros_mensal,"
                . "empresa.cnpj,"
                . "endereco.numero,"
                . "endereco.id,"
                . "endereco.rua,"
                . "endereco.bairro,"
                . "endereco.cep,"
                . "cidade.id,"
                . "cidade.nome,"
                . "estado.id,"
                . "estado.sigla,"
                . "email.id,"
                . "email.endereco,"
                . "email.senha,"
                . "telefone.id,"
                . "telefone.numero "
                . "FROM campanha "
                . "INNER JOIN produto_campanha ON campanha.id = produto_campanha.id_campanha "
                . "INNER JOIN empresa ON campanha.id_empresa=empresa.id "
                . "INNER JOIN endereco ON endereco.id_entidade=empresa.id AND endereco.tipo_entidade='EMP' "
                . "INNER JOIN email ON email.id_entidade=empresa.id AND email.tipo_entidade='EMP' "
                . "INNER JOIN telefone ON telefone.id_entidade=empresa.id AND telefone.tipo_entidade='EMP' "
                . "INNER JOIN cidade ON endereco.id_cidade=cidade.id "
                . "INNER JOIN estado ON cidade.id_estado = estado.id "
                . " WHERE campanha.inicio<=CURRENT_TIMESTAMP AND campanha.fim>=CURRENT_TIMESTAMP AND campanha.excluida=false");

        $ps->execute();
        $ps->bind_result($id, $camp_nome, $inicio, $fim, $prazo, $parcelas, $cliente, $id_produto_campanha, $id_produto, $validade, $limite, $valor, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        while ($ps->fetch()) {

            if (!isset($campanhas[$id])) {

                $campanhas[$id] = new Campanha();
                $campanhas[$id]->id = $id;
                $campanhas[$id]->nome = $camp_nome;
                $campanhas[$id]->inicio = $inicio;
                $campanhas[$id]->fim = $fim;
                $campanhas[$id]->prazo = $prazo;
                $campanhas[$id]->parcelas = $parcelas;
                $campanhas[$id]->cliente_expression = $cliente;

                $empresa = Sistema::getEmpresa($tipo_empresa);

                $empresa->id = $id_empresa;
                $empresa->cnpj = new CNPJ($cnpj);
                $empresa->inscricao_estadual = $inscricao_empresa;
                $empresa->nome = $nome_empresa;
                $empresa->aceitou_contrato = $aceitou_contrato;
                $empresa->juros_mensal = $juros_mensal;
                $empresa->consigna = $consigna;

                $endereco = new Endereco();
                $endereco->id = $id_endereco;
                $endereco->rua = $rua;
                $endereco->bairro = $bairro;
                $endereco->cep = new CEP($cep);
                $endereco->numero = $numero_endereco;

                $cidade = new Cidade();
                $cidade->id = $id_cidade;
                $cidade->nome = $nome_cidade;

                $estado = new Estado();
                $estado->id = $id_estado;
                $estado->sigla = $nome_estado;

                $cidade->estado = $estado;

                $endereco->cidade = $cidade;

                $empresa->endereco = $endereco;

                $email = new Email($endereco_email);
                $email->id = $id_email;
                $email->senha = $senha_email;

                $empresa->email = $email;

                $telefone = new Telefone($numero_telefone);
                $telefone->id = $id_telefone;

                $empresa->telefone = $telefone;

                $campanhas[$id]->empresa = $empresa;
                
                $campanhas[$id] = $campanhas[$id]->getReduzida();
                
            }

            $campanha = $campanhas[$id];

            $p = new ProdutoCampanha();
            $p->id = $id_produto_campanha;
            $p->validade = $validade;
            $p->limite = $limite;
            $p->valor = $valor;
            $p->campanha = $campanha;

            if (!isset($ofertas[$id_produto])) {

                $ofertas[$id_produto] = array();
            }

            $ofertas[$id_produto][] = $p;
        }

        $ps->close();

        $ps = $con->getConexao()->prepare("SELECT produto_pedido_entrada.id,"
                . "UNIX_TIMESTAMP(produto_pedido_entrada.validade)*1000,"
                . "produto_pedido_entrada.quantidade,"
                . "produto_pedido_entrada.valor,"
                . "produto_pedido_entrada.influencia_estoque,"
                . "produto_pedido_entrada.influencia_transito,"
                . "produto.id,"
                . "produto.codigo,"
                . "produto.id_logistica,"
                . "produto.classe_risco,"
                . "produto.fabricante,"
                . "produto.imagem,"
                . "produto.id_universal,"
                . "produto.liquido,"
                . "produto.quantidade_unidade,"
                . "produto.habilitado,"
                . "produto.valor_base,"
                . "produto.custo,"
                . "produto.peso_bruto,"
                . "produto.peso_liquido,"
                . "produto.estoque,"
                . "produto.disponivel,"
                . "produto.transito,"
                . "produto.grade,"
                . "produto.unidade,"
                . "produto.ncm,"
                . "produto.nome,"
                . "produto.lucro_consignado,"
                . "produto.ativo,"
                . "produto.concentracao,"
                . "produto.sistema_lotes,"
                . "produto.nota_usuario,"
                . "produto.id_categoria,"
                . "empresa.id,"
                . "empresa.tipo_empresa,"
                . "empresa.nome,"
                . "empresa.inscricao_estadual,"
                . "empresa.consigna,"
                . "empresa.aceitou_contrato,"
                . "empresa.juros_mensal,"
                . "empresa.cnpj,"
                . "endereco.numero,"
                . "endereco.id,"
                . "endereco.rua,"
                . "endereco.bairro,"
                . "endereco.cep,"
                . "cidade.id,"
                . "cidade.nome,"
                . "estado.id,"
                . "estado.sigla,"
                . "email.id,"
                . "email.endereco,"
                . "email.senha,"
                . "telefone.id,"
                . "telefone.numero"
                . " FROM produto_pedido_entrada "
                . "INNER JOIN produto ON produto_pedido_entrada.id_produto=produto.id "
                . "INNER JOIN empresa ON produto.id_empresa=empresa.id "
                . "INNER JOIN endereco ON endereco.id_entidade=empresa.id AND endereco.tipo_entidade='EMP' "
                . "INNER JOIN email ON email.id_entidade=empresa.id AND email.tipo_entidade='EMP' "
                . "INNER JOIN telefone ON telefone.id_entidade=empresa.id AND telefone.tipo_entidade='EMP' "
                . "INNER JOIN cidade ON endereco.id_cidade=cidade.id "
                . "INNER JOIN estado ON cidade.id_estado = estado.id "
                . " WHERE produto_pedido_entrada.id_pedido=$this->id");


        $ps->execute();
        $ps->bind_result($id,$validade, $quantidade, $valor, $ie, $it, $id_pro, $cod_pro, $id_log, $classe_risco, $fabricante, $imagem, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $est, $disp, $tr, $gr, $uni, $ncm, $nome, $lucro, $ativo, $conc, $sistema_lotes, $nota_usuario, $cat_id, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        $retorno = array();


        while ($ps->fetch()) {

            $p = new Produto();
            $p->logistica = $id_log;
            $p->id = $id_pro;
            $p->validade = $validade;
            $p->codigo = $cod_pro;
            $p->classe_risco = $classe_risco;
            $p->fabricante = $fabricante;
            $p->imagem = $imagem;
            $p->nome = $nome;
            $p->id_universal = $id_uni;
            $p->liquido = $liq;
            $p->ativo = $ativo;
            $p->concentracao = $conc;
            $p->quantidade_unidade = $qtd_un;
            $p->habilitado = $hab;
            $p->valor_base = $vb;
            $p->custo = $cus;
            $p->sistema_lotes = $sistema_lotes == 1;
            $p->nota_usuario = $nota_usuario;
            $p->peso_bruto = $pb;
            $p->peso_liquido = $pl;
            $p->estoque = $est;
            $p->disponivel = $disp;
            $p->transito = $tr;
            $p->grade = new Grade($gr);
            $p->unidade = $uni;
            $p->ncm = $ncm;
            $p->lucro_consignado = $lucro;
            $p->ofertas = (!isset($ofertas[$p->codigo]) ? array() : $ofertas[$p->codigo]);

            foreach ($p->ofertas as $key => $oferta) {

                $oferta->produto = $p->getReduzido();
            }

            $p->categoria = Sistema::getCategoriaProduto(null, $cat_id);

            $empresa = Sistema::getEmpresa($tipo_empresa);

            $empresa->id = $id_empresa;
            $empresa->cnpj = new CNPJ($cnpj);
            $empresa->inscricao_estadual = $inscricao_empresa;
            $empresa->nome = $nome_empresa;
            $empresa->aceitou_contrato = $aceitou_contrato;
            $empresa->juros_mensal = $juros_mensal;
            $empresa->consigna = $consigna;

            $endereco = new Endereco();
            $endereco->id = $id_endereco;
            $endereco->rua = $rua;
            $endereco->bairro = $bairro;
            $endereco->cep = new CEP($cep);
            $endereco->numero = $numero_endereco;

            $cidade = new Cidade();
            $cidade->id = $id_cidade;
            $cidade->nome = $nome_cidade;

            $estado = new Estado();
            $estado->id = $id_estado;
            $estado->sigla = $nome_estado;

            $cidade->estado = $estado;

            $endereco->cidade = $cidade;

            $empresa->endereco = $endereco;

            $email = new Email($endereco_email);
            $email->id = $id_email;
            $email->senha = $senha_email;

            $empresa->email = $email;

            $telefone = new Telefone($numero_telefone);
            $telefone->id = $id_telefone;

            $empresa->telefone = $telefone;


            $p->empresa = $empresa;

            $pp = new ProdutoPedidoEntrada();
            $pp->id = $id;
            $pp->influencia_estoque = $ie;
            $pp->influencia_transito = $it;
            $pp->quantidade = $quantidade;
            $pp->valor = $valor;
            $pp->pedido = $this;
            $pp->produto = $p;


            $retorno[$pp->id] = $pp;
        }

        $ps->close();

        foreach ($retorno as $key => $value) {
            $value->produto->logistica = Sistema::getLogisticaById($con, $value->produto->logistica);
        }

        $real_ret = array();

        foreach ($retorno as $key => $value) {

            $real_ret[] = $value;
        }
        
        return $real_ret;
    }

    public function merge($con) {

        if ($this->id == 0) {
        
            $ps = $con->getConexao()->prepare("INSERT INTO pedido_entrada(id_fornecedor,id_transportadora,frete,observacoes,frete_inclusao,id_empresa,data,excluido,id_usuario,id_nota,prazo,parcelas,id_status,entrega) VALUES(" . $this->fornecedor->id . "," . $this->transportadora->id . "," . $this->frete . ",'" . $this->observacoes . "'," . ($this->frete_incluso ? "true" : "false") . "," . $this->empresa->id . ",FROM_UNIXTIME($this->data/1000),false," . $this->usuario->id . "," . ($this->nota != null ? $this->nota->id : 0) . ",$this->prazo,$this->parcelas," . $this->status->id . ",$this->entrega)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE pedido_entrada SET id_fornecedor=" . $this->fornecedor->id . ",id_transportadora=" . $this->transportadora->id . ",frete=" . $this->frete . ",observacoes='" . $this->observacoes . "',frete_inclusao=" . ($this->frete_incluso ? "true" : "false") . ",id_empresa=" . $this->empresa->id . ",data=FROM_UNIXTIME($this->data/1000),excluido=false,id_usuario=" . $this->usuario->id . ",id_nota=" . ($this->nota != null ? $this->nota->id : 0) . ",prazo=$this->prazo,parcelas=$this->parcelas,id_status=" . $this->status->id . ",entrega=$this->entrega WHERE id = $this->id");
            $ps->execute();
            $ps->close();
        }

        $prods = $this->getProdutos($con);

        if ($this->produtos === null) {

            $this->produtos = $prods;
        }

        foreach ($prods as $key => $value) {

            foreach ($this->produtos as $key2 => $value2) {

                if ($value->id == $value2->id) {

                    continue 2;
                }
            }

            $value->delete($con);
        }

        foreach ($this->produtos as $key2 => $value2) {

            $value2->merge($con);
        }

        if($this->status->id === 1){

            $ps = $con->getConexao()->prepare("UPDATE lote l SET excluido=false WHERE (SELECT MAX(pp.id) FROM produto_pedido_entrada pp WHERE pp.lote_cadastrado LIKE CONCAT(CONCAT('%',CONCAT(CONCAT(';',l.id),';')),'%') AND pp.id_pedido=$this->id)>0");
            $ps->execute();
            $ps->close();

        }

        if ($this->status->envia_email && $this->enviar_emails) {

            try {

                $obj = Utilidades::copy($this);

                if($this->transportadora->razao_social==="O MESMO"){

                    $obj->transportadora->cnpj = $obj->fornecedor->cnpj;
                    $obj->transportadora->endereco = $obj->fornecedor->endereco;
                    $obj->transportadora->email = $obj->fornecedor->email;

                }
                
                $html = Sistema::getHtml('visualizar-pedidos-compra', $obj);
 
                Sistema::avisoDEVS_MASTER($html);
                
                $this->empresa->email->enviarEmail(array(
                	$this->fornecedor->email->filtro(Email::$VENDAS),
                	$this->empresa->email->filtro(Email::$LOGISTICA),
                	$this->empresa->email->filtro(Email::$COMPRAS)
                ), "Pedido de Compra", $html);
                
                
            } catch (Exception $ex) {
                throw new Exception($ex);
            }
        }
    }

    public function delete($con) {

        $this->status = Sistema::getStatusCanceladoPedidoEntrada();

        $prods = $this->getProdutos($con);

        foreach ($prods as $key2 => $value2) {

            $value2->merge($con);
        }

        $ps = $con->getConexao()->prepare("UPDATE pedido_entrada SET excluido=true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
