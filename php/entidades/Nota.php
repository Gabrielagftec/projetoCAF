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
class Nota {

    public static $NORMAL = 1;
    public static $COMPLEMENTAR = 2;
    public static $AJUSTE = 3;
    public static $DEVOLUCAO = 4;
    public $id;
    public $transportadora;
    public $fornecedor;
    public $cliente;
    public $saida;
    public $empresa;
    public $data_emissao;
    public $excluida;
    public $interferir_estoque;
    public $produtos;
    public $observacao;
    public $vencimentos;
    public $frete_destinatario_remetente;
    public $forma_pagamento;
    public $emitida;
    public $xml;
    public $danfe;
    public $chave;
    public $numero;
    public $ficha;
    public $cancelada;
    public $protocolo;
    public $finalidade;
    public $chave_devolucao;
    public $validar;
    public $baixa_total;
    public $id_pedido;
    public $sem_erro;
    public $aux_import;
    public $volumes_emissao;
    public $recorrencia;
    public $visto;
    
    function __construct() {

        $this->id = 0;
        $this->volumes_emissao = 0;
        $this->fornecedor = null;
        $this->cliente = null;
        $this->validar = true;
        $this->chave = "";
        $this->transportadora = null;
        $this->saida = true;
        $this->excluida = false;
        $this->empresa = null;
        $this->data_emissao = round(microtime(true) * 1000);
        $this->produtos = null;
        $this->vencimentos = null;
        $this->interferir_estoque = true; //true;
        $this->forma_pagamento = null;
        $this->frete_destinatario_remetente = false;
        $this->emitida = false;
        $this->numero = 0;
        $this->ficha = 0;
        $this->chave = "";
        $this->protocolo = "";
        $this->cancelada = false;
        $this->baixa_total = 0;
        $this->finalidade = Nota::$NORMAL;
        $this->id_pedido = 0;
        $this->observacao = "";
        $this->xml = "";       
        $this->danfe = "";
        $this->sem_erro = false;
        $this->forma_pagamento = Sistema::getFormasPagamento();
        $this->forma_pagamento = $this->forma_pagamento[0];
        $this->aux_import = "";
        $this->recorrencia = 0;
        $this->visto = "";

    }
    
    private $tmp_aux_import = null;

    public function vistar($con){

        $ses = new SessionManager();

        $u=$ses->get('usuario');

        if($u !== null){

            $this->visto .= ";$u->id-$u->nome";

            $ps = $con->getConexao()->prepare("UPDATE nota SET data_emissao=data_emissao,visto='$this->visto' WHERE id=$this->id AND IFNULL(visto,'') NOT LIKE '%;$u->id-$u->nome%'");
            $ps->execute();
            $ps->close();
            
            $tarefas = $u->getTarefas($con, "tarefa.id_entidade_relacionada=$this->id AND tarefa.tipo_entidade_relacionada='NF'");
            
            if(count($tarefas)>0){
                
                $tarefa = $tarefas[0];
                
                $obs = new ObservacaoTarefa();
                $obs->porcentagem = 100;
                $obs->observacao = "Nota vistada";
                
                $tarefa->addObservacao($con, $u, $obs);
                
            }
            
        }


    }
    
    
    public function setAuxImport($con,$au){
        
        if($this->id === 0){
        
            $this->tmp_aux_import = $au;
            
        }else{

            $ps = $con->getConexao()->prepare("UPDATE nota SET aux_import='$au' WHERE id=$this->id");
            $ps->execute();
            $ps->close();

        }
        
    }
    
    public function getAuxImport($con){
        
        $ps = $con->getConexao()->prepare("SELECT aux_import FROM nota WHERE id=$this->id");
        $ps->execute();
        $ps->bind_result($ai);
        if($ps->fetch()){
            $ps->close();
            return $ai;
        }
        $ps->close();
        return 0;
        
    }

    public function igualaVencimento() {

        $totp = 0;

        foreach ($this->produtos as $key => $value) {
            $totp += $value->valor_total;
        }

        $vencimento = new Vencimento();
        $vencimento->nota = $this;
        $vencimento->valor = $totp;

        $this->vencimentos = array($vencimento);
    }

    public function inverteOperacao($con, $empresa) {

        $gt = new Getter($empresa);
        $nota = Utilidades::copyId0($this);
        unset($nota->inverter);
        $nota->emitida = false;
        
        if ($nota->saida) {

            $fornecedor = $gt->getFornecedorViaEmpresa($con, $nota->empresa);
            $nota->empresa = $empresa;
            $nota->cliente = null;
            $nota->fornecedor = $fornecedor;
        } else {

            $cliente = $gt->getClienteViaEmpresa($con, $nota->empresa);
            $nota->empresa = $empresa;
            $nota->fornecedor = null;
            $nota->cliente = $cliente;
        }

        $nota->saida = !$nota->saida;

        foreach ($nota->produtos as $key => $value) {
            $prod = Utilidades::copyId0($value);
            $prod->nota = $nota;
            $nota->produtos[$key] = $prod;
        }

        foreach ($nota->vencimentos as $key => $value) {
            $venc = Utilidades::copyId0($value);
            $venc->nota = $nota;
            $nota->vencimentos[$key] = $venc;
        }

        return $nota;
    }

    public function calcularImpostosAutomaticamente() {


        $est = ($this->saida) ? $this->cliente->endereco->cidade->estado : $this->fornecedor->endereco->cidade->estado;

        $suf = false;

        if ($this->saida) {
            if ($this->cliente->suframado) {
                $suf = true;
            }
        }

        foreach ($this->produtos as $key => $value) {

            $cat = $value->produto->categoria;
            $value->base_calculo = ($cat->base_calculo / 100) * $value->valor_unitario;
            if ($est->sigla !== $this->empresa->endereco->cidade->estado->sigla && !$suf) {
                if ($cat->icms_normal) {
                    $icm = Sistema::getIcmsEstado($est);
                    $value->icms = $value->base_calculo * ($icm / 100);
                } else {
                    $value->icms = $value->base_calculo * ($cat->icms / 100);
                }
            } else {
                $value->icms = 0;
            }
        }
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

        $ps = $con->getConexao()->prepare("SELECT produto_nota.id,"
                . "produto_nota.informacao_adicional,"
                . "produto_nota.quantidade,"
                . "produto_nota.valor_unitario,"
                . "produto_nota.valor_total,"
                . "produto_nota.base_calculo,"
                . "produto_nota.cfop,"
                . "produto_nota.icms,"
                . "produto_nota.ipi,"
                . "produto_nota.influencia_estoque,"
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
                . " FROM produto_nota "
                . "INNER JOIN produto ON produto_nota.id_produto=produto.id "
                . "INNER JOIN empresa ON produto.id_empresa=empresa.id "
                . "INNER JOIN endereco ON endereco.id_entidade=empresa.id AND endereco.tipo_entidade='EMP' "
                . "INNER JOIN email ON email.id_entidade=empresa.id AND email.tipo_entidade='EMP' "
                . "INNER JOIN telefone ON telefone.id_entidade=empresa.id AND telefone.tipo_entidade='EMP' "
                . "INNER JOIN cidade ON endereco.id_cidade=cidade.id "
                . "INNER JOIN estado ON cidade.id_estado = estado.id "
                . " WHERE produto_nota.id_nota=$this->id");

        $ps->execute();
        $ps->bind_result($id, $info_adic, $quantidade, $valor_unitario, $valor_total, $base_calculo, $cfop, $icms, $ipi, $influencia_estoque, $id_pro, $cod_pro, $id_log, $classe_risco, $fabricante, $imagem, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $est, $disp, $tr, $gr, $uni, $ncm, $nome, $lucro, $ativo, $conc, $sistema_lotes, $nota_usuario, $cat_id, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        $retorno = array();


        while ($ps->fetch()) {

            $p = new Produto();
            $p->logistica = $id_log;
            $p->id = $id_pro;
            $p->codigo = $cod_pro;
            $p->sistema_lotes = $sistema_lotes == 1;
            $p->nota_usuario = $nota_usuario;
            $p->nome = $nome;
            $p->classe_risco = $classe_risco;
            $p->fabricante = $fabricante;
            $p->imagem = $imagem;
            $p->id_universal = $id_uni;
            $p->liquido = $liq;
            $p->quantidade_unidade = $qtd_un;
            $p->habilitado = $hab;
            $p->valor_base = $vb;
            $p->ativo = $ativo;
            $p->concentracao = $conc;
            $p->custo = $cus;
            $p->peso_bruto = $pb;
            $p->peso_liquido = $pl;
            $p->estoque = $est;
            $p->disponivel = $disp;
            $p->transito = $tr;
            $p->grade = new Grade($gr);
            $p->unidade = $uni;
            $p->ncm = $ncm;
            $p->lucro_consignado = $lucro;
            $p->empresa = $this->empresa;
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

            $pp = new ProdutoNota();
            $pp->id = $id;
            $pp->informacao_adicional = $info_adic;
            $pp->quantidade = $quantidade;
            $pp->valor_total = $valor_total;
            $pp->valor_unitario = $valor_unitario;
            $pp->icms = $icms;
            $pp->ipi = $ipi;
            $pp->base_calculo = $base_calculo;
            $pp->influencia_estoque = $influencia_estoque;
            $pp->produto = $p;
            $pp->cfop = $cfop;
            $pp->nota = $this;


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

    public function manifestar($con) {

        if ($this->emitida) {

            throw new Exception("Nota ja esta manifestada");
        }

        $base = $this->empresa->getParametrosEmissao($con)->getComandoBase($con);
        $base->acao = "MANIFESTAR";
        $base->chave = $this->chave;
        $base = Utilidades::removerLacunas($base);

        $agora = round(microtime(true) * 1000);
        $arquivo = "correcao_$agora.json";
        $comando = Utilidades::toJson($base);
        Sistema::mergeArquivo($arquivo, $comando, false);
        $endereco = Sistema::$ENDERECO . "php/uploads/" . $arquivo;

        $ret = Utilidades::fromJson(Sistema::getMicroServicoJava('EmissorRTC', $endereco));

        $ps = $con->getConexao()->prepare("UPDATE nota SET emitida=true WHERE id=$this->id");
        $ps->execute();
        $ps->close();

        if ($ret->sucesso) {
            $this->emitida = true;
            $this->merge($con);
            Logger::gerarLog($this, "Manifestada na sefaz");
            return true;
        } else {
            Logger::gerarLog($this, "Falha ao manifestar nota");
            return false;
        }
    }

    public function emitir($con) {

        if ($this->produtos === null) {
            $this->produtos = $this->getProdutos($con);
        }

        if ($this->id_pedido > 0) {

            $this->calcularImpostosAutomaticamente();
            
        }

        if (count($this->produtos) > 0) {

            $base = $this->empresa->getParametrosEmissao($con)->getComandoBase($con);
            $base->acao = "EMITIR";
            $base->pedido = $this->id;
            $base->operacao = CFOP::descricao($this->produtos[0]->cfop);
            $base->cfop = intval(Utilidades::removeMask($this->produtos[0]->cfop));
            $base->saida_entrada = $this->saida;
            $base->finalidade = $this->finalidade;
            $base->consumidor = $this->cliente->pessoa_fisica ? 1 : 0;
            $base->frete = 0;
            $base->frete_cif_fob = $this->frete_destinatario_remetente;
            $base->suframado = $this->cliente->suframado;
            if ($base->suframado) {
                $base->inscricao_suframa = $this->cliente->inscricao_suframa;
            }

            $volumes = 0;

            foreach ($this->produtos as $key => $value) {

                $volumes += ceil($value->quantidade / $value->produto->grade->gr[0]);
            }

            if($this->volumes_emissao > 0){
                
                $volumes = $this->volumes_emissao;
                
            }
            
            $base->volumes = $volumes;
            $base->informacoes_adcionais = $this->observacao;

            if ($this->finalidade === Nota::$COMPLEMENTAR || $this->finalidade === Nota::$DEVOLUCAO) {

                $base->chave_devolucao = $this->chave_devolucao;
            }

            $dest = new stdClass();
            $dest->id = $this->cliente->id;
            $dest->estado = $this->cliente->endereco->cidade->estado->sigla;
            if (!$this->cliente->pessoa_fisica) {
                $dest->cnpj = Utilidades::removeMask($this->cliente->cnpj->valor);
            } else {
                $dest->cnpj = Utilidades::removeMask($this->cliente->cpf->valor);
            }
            $dest->nome = Utilidades::ifn($this->cliente->razao_social, "sem nome");
            $dest->bairro = Utilidades::ifn($this->cliente->endereco->bairro, "nao cadastrado");
            
            $dest->logadouro = Utilidades::ifn($this->cliente->endereco->rua, "nao cadastrado")." - ".Utilidades::ifn($this->cliente->endereco->numero . "", "nao cadastrado", 1);

            $dest->numero = Utilidades::ifn($this->cliente->endereco->numero . "", "nao cadastrado", 1);
            $dest->municipio = Utilidades::ifn($this->cliente->endereco->cidade->nome, "sem cidade", 1);
            $dest->cep = Utilidades::removeMask($this->cliente->endereco->cep->valor);
            $dest->pais = "Brasil";
            $dest->telefone = new Telefone("11111111");
            $dest->telefone = $dest->telefone->numero;

            if (count($this->cliente->telefones) > 0) {

                $tel = new Telefone("11111111");
                $dest->telefone = Utilidades::ifn($this->cliente->telefones[0]->numero . "", $tel->numero);
            }

            $dest->telefone = Utilidades::removeMask($dest->telefone);


            $dest->ie = Utilidades::removeMask($this->cliente->inscricao_estadual);
            $dest->email = "rtc@rtc.com.br";

            if ($this->cliente->suframado) {
                $dest->inscricao_suframa = $this->cliente->inscricao_suframa;
            }

            $base->destinatario = $dest;

            $trans = new stdClass();
            $trans->id = $this->transportadora->id;
            $trans->cnpj = Utilidades::removeMask($this->transportadora->cnpj->valor);
            $trans->nome = Utilidades::ifn($this->transportadora->razao_social, "sem nome");
            $trans->ie = Utilidades::removeMask($this->transportadora->inscricao_estadual);
            $trans->endereco = Utilidades::ifn($this->transportadora->endereco->rua, "nao cadastrada");
            $trans->municipio = Utilidades::ifn($this->transportadora->endereco->cidade->nome, "nao cadastrada");
            $trans->estado = $this->transportadora->endereco->cidade->estado->sigla;

            if ($trans->nome == "O MESMO" || $trans->nome == "FRETE FOB") {

                $trans->cnpj = $dest->cnpj;
                $trans->nome = $dest->nome;
                $trans->ie = $dest->ie;
                $trans->endereco = $dest->logadouro;
                $trans->municipio = $dest->municipio;
                $trans->estado = $dest->estado;

                $base->frete_cif_fob = false;
            }

            $base->transportadora = $trans;
            $base->produtos = array();


            $total = 0;

            for ($j = 0; $j < count($this->produtos); $j++) {

                $p = $this->produtos[$j];

                $produto = new stdClass();

                $produto->codigo = $p->produto->id;
                $produto->ncm = Utilidades::removeMask($p->produto->ncm);
                $produto->unidade = $p->produto->unidade;
                $produto->nome = Utilidades::ifn($p->produto->nome, "sem nome");
                $produto->quantidade = $p->quantidade;
                $produto->valor = $p->valor_unitario;
                $produto->informacao_adcional = $p->informacao_adicional;

                if ($produto->informacao_adcional === "" || $produto->informacao_adcional === null) {
                    $produto->informacao_adcional = "Sem informacoes adcionais";
                }

                $produto->pesoB = $p->produto->peso_bruto;
                $produto->pesoL = $p->produto->peso_liquido;
                $produto->ipi = $p->ipi;

                $produto->reducao_base_calculo = 100 - ($p->base_calculo * 100 / ($p->valor_unitario));
                $produto->icms = round(($p->base_calculo == 0) ? 0 : (($p->icms / $p->base_calculo) * 100));
                
                
                if (!$p->produto->categoria->icms_normal) {
                    $produto->cst200 = true;
                }

                if ($this->cliente->suframado || $p->icms==0) {
                    $produto->sem_icms = true;
                }


                $total += $p->quantidade * $p->valor_unitario;

                $base->produtos[] = $produto;
            }

            $base->vencimentos = array();

            $this->vencimentos = $this->getVencimentos($con);

            $minima = round(microtime(true) * 1000) + 100000;

            $del = 0;
            foreach ($this->vencimentos as $key => $value) {

                $v = new stdClass();
                $v->data = $value->data + $del;
                if ($v->data < $minima) {
                    $del = $minima - $v->data;
                    $v->data = $minima;
                }
                $v->valor = $value->valor;
                $base->vencimentos[] = $v;
            }


            $base = Utilidades::removerLacunas($base);


            $agora = round(microtime(true) * 1000);
            $arquivo = "emissao_$agora.json";
            $comando = Utilidades::toJson($base);
            Sistema::mergeArquivo($arquivo, $comando, false);
            $endereco = Sistema::$ENDERECO . "php/uploads/" . $arquivo;
    
            
        }

        $ret = Utilidades::fromJson(Sistema::getMicroServicoJava('EmissorRTC', $endereco));

        if ($ret === null) {

            Sistema::avisoDEVS("Falha emissao xml");
            Logger::gerarLog($this, "Falha grave na emissao, verificar certificado digital.");
            return "";
        }
        
        if ($ret->sucesso) {

            $this->emitida = true;
            $this->cancelada = false;
            $this->danfe = Sistema::$ENDERECO . "php/controler/" . $ret->danfe;
            $this->xml = Sistema::$ENDERECO . "php/controler/" . $ret->xml;
            $this->numero = $ret->nf;
            $this->chave = $ret->chave;
            $this->protocolo = $ret->protocolo;
            $this->merge($con);

            $this->empresa->email->enviarEmail($this->empresa->email->filtro(Email::$LOGISTICA), "Emissao de NF $this->numero do pedido $this->id_pedido", "DANFE: <a href='$this->danfe'>VISUALIZAR</a><hr>XML: <a href='$this->xml'>VISUALIZAR</a><br>CHAVE: $this->chave");
            $this->empresa->email->enviarEmail($this->cliente->email->filtro(Email::$LOGISTICA), "Emissao de NF $this->numero do pedido $this->id_pedido", "DANFE: <a href='$this->danfe'>VISUALIZAR</a><hr>XML: <a href='$this->xml'>VISUALIZAR</a><br>CHAVE: $this->chave");

            Logger::gerarLog($this, "Nota emitida, $this->numero");
        } else {
            
            Sistema::avisoDEVS("Falha emissao link XML: <a href='" . Sistema::$ENDERECO . "php/controler/" . $ret->falha . "'>LINK</a>");
            Logger::gerarLog($this, "Falha na emissao da nota, ficha $this->ficha");

            $this->empresa->email->enviarEmail($this->empresa->email->filtro(Email::$LOGISTICA), "Falha na emissao de NF do Pedido $this->id_pedido", "Problema: $ret->mensagem");
            
            $this->merge($con);
            
        }
        
        return $ret->mensagem;
    }

    public function cancelar($con, $motivo = "Nota emitida indevidamente") {

        if (!$this->emitida) {

            throw new Exception("Nota nao esta emitida para cancelar");
        }

        $base = $this->empresa->getParametrosEmissao($con)->getComandoBase($con);
        $base->acao = "CANCELAR";
        $base->chave = $this->chave;
        $base->motivo = $motivo;
        $base->protocolo = $this->protocolo;

        $base = Utilidades::removerLacunas($base);

        $agora = round(microtime(true) * 1000);
        $arquivo = "cancelamento_$agora.json";
        $comando = Utilidades::toJson($base);
        Sistema::mergeArquivo($arquivo, $comando, false);
        $endereco = Sistema::$ENDERECO . "php/uploads/" . $arquivo;
        
        $ret = Utilidades::fromJson(Sistema::getMicroServicoJava('EmissorRTC', $endereco));

        if ($ret->sucesso) {
            $this->cancelada = true;
            $this->merge($con);
            Logger::gerarLog($this, "Nota cancelada na Sefaz");
            return true;
        } else {
            Logger::gerarLog($this, $ret->mensagem);
            return false;
        }
    }
    
    public function getCartasCorrecao($con){
        
        $sql = "SELECT id,motivo,protocolo,UNIX_TIMESTAMP(data)*1000 FROM carta_correcao WHERE id_nota=$this->id";
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id,$motivo,$protocolo,$data);
        
        $cartas = array();
        
        while($ps->fetch()){
            
            $cc = new CartaCorrecao();
            $cc->id = $id;
            $cc->motivo = $motivo;
            $cc->protocolo = $protocolo;
            $cc->nota = $this;
            $cc->data = $data;
            
            $cartas[] = $cc;
            
        }
        
        $ps->close();
        
        return $cartas;
        
    }

    public function corrigir($con, $correcao) {



        if (!$this->emitida) {

            throw new Exception("Nota nao esta emitida para fazer carta de correcao");

        }

        $base = $this->empresa->getParametrosEmissao($con)->getComandoBase($con);
        $base->acao = "CARTA_CORRECAO";
        $base->chave = $this->chave;
        $base->descricao = $correcao;
        $base->protocolo = $this->protocolo;

        $numero = 0;
        $ps = $con->getConexao()->prepare("SELECT numero_carta_correcao FROM nota WHERE id=$this->id");
        $ps->execute();
        $ps->bind_result($n);
        if ($ps->fetch()) {
            $numero = $n + 1;
        }
        $ps->close();

        $ps = $con->getConexao()->prepare("UPDATE nota SET numero_carta_correcao=$numero,data_emissao=data_emissao WHERE id=$this->id");
        $ps->execute();
        $ps->close();

        $base->numero_sequencial = $numero;

        $base = Utilidades::removerLacunas($base);

        $agora = round(microtime(true) * 1000);
        $arquivo = "correcao_$agora.json";
        $comando = Utilidades::toJson($base);
        Sistema::mergeArquivo($arquivo, $comando, false);
        $endereco = Sistema::$ENDERECO . "php/uploads/" . $arquivo;

        
        $ret = Utilidades::fromJson(Sistema::getMicroServicoJava('EmissorRTC', $endereco));

        if ($ret->sucesso) {
            $this->merge($con);
            
            $cc = new CartaCorrecao();
            $cc->nota = $this;
            $cc->motivo = $correcao;
            $cc->protocolo = $ret->protocolo;
            $cc->merge($con);
            
            Logger::gerarLog($this, "Carta de corracao emitida na Sefaz: $correcao");
            return true;
        } else {
            Logger::gerarLog($this, "Falha ao emitir carta de correcao: $correcao. $ret->mensagem");
            return false;
        }
    }

    public function getTotal(){
        
        $totp = 0;
        
        foreach ($this->produtos as $key => $value) {

            $totp += $value->valor_total;
            
        }
        
        return $totp;
        
    }
    
    public function getTotalVencimentos(){
        
        $totv = 0;

        foreach ($this->vencimentos as $key => $value) {

            $totv += $value->valor;
        }
        
        return $totv;
        
    }
    
    public function merge($con) {


        
        $vencimentos = $this->getVencimentos($con);

        if ($this->vencimentos === null) {

            $this->vencimentos = $vencimentos;
        }

        $prods = $this->getProdutos($con);

        if ($this->produtos === null) {

            $this->produtos = $prods;
        }

        $totv = 0;

        foreach ($this->vencimentos as $key => $value) {

            $totv += $value->valor;
        }

        $totp = 0;

        foreach ($this->produtos as $key => $value) {

            $totp += $value->valor_total;
        }

        if ((($totv > ($totp + 0.1)) || ($totv < ($totp - 0.1))) && $this->validar) {
            
            if(!$this->sem_erro){
                throw new Exception('Somatorio das parcelas difere do valor da nota Total:' . $totp . ', Somatorio: ' . $totv);
            }
            
        }

        $ses = new SessionManager();
        if($ses->get('usuario') == null){
            return;
        }



        if ($this->emitida && $this->ficha == 0) {

            $ps = $con->getConexao()->prepare("SELECT MAX(ficha) FROM nota WHERE id_empresa = " . $this->empresa->id);
            $ps->execute();
            $ps->bind_result($ficha);
            if ($ps->fetch()) {
                $ps->close();
                $this->ficha = $ficha + 1;
            } else {
                $ps->close();
                $this->ficha = 1;
            }
        }

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO nota(saida,chave,id_cliente,id_fornecedor,observacao,id_empresa,data_emissao,excluida,influenciar_estoque,id_transportadora,id_forma_pagamento,frete_destinatario_remetente,emitida,numero,ficha,cancelada,danfe,xml,protocolo,baixa_total,recorrencia) VALUES(" . ($this->saida ? "true" : "false") . ",'$this->chave'," . ($this->cliente != null ? $this->cliente->id : 0) . "," . ($this->fornecedor != null ? $this->fornecedor->id : 0) . ",'$this->observacao'," . $this->empresa->id . ",FROM_UNIXTIME($this->data_emissao/1000),false," . ($this->interferir_estoque ? "true" : "false") . "," . $this->transportadora->id . "," . $this->forma_pagamento->id . "," . ($this->frete_destinatario_remetente ? "true" : "false") . "," . ($this->emitida ? "true" : "false") . ",$this->numero,$this->ficha," . ($this->cancelada ? "true" : "false") . ",'$this->danfe','$this->xml','$this->protocolo',$this->baixa_total,$this->recorrencia)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
            if($this->tmp_aux_import !== null){
                
                $this->setAuxImport($con, $this->tmp_aux_import);
                $this->tmp_aux_import = null;
                
            }
            
            $ps = $con->getConexao()->prepare("SELECT empresa_adm FROM empresa WHERE id=".$this->empresa->id);
            $ps->execute();
            $ps->bind_result($adm);
            
            if($ps->fetch()){
                
                $ps->close();
                
                $e = new Administracao($adm, $con);
                
                $t = new Tarefa();
                
                if($this->ficha > 0){
                
                    $t->descricao = "Viste a nota de ficha $this->ficha do pedido $this->id_pedido";
                
                }else if($this->id_pedido>0){
                    
                    $t->descricao = "Acompanhe a emissao da nota do pedido $this->id_pedido";
                    
                }else if($this->saida){
                    
                    $t->descricao = "Acompanhe a emissao da nota do cliente ".$this->cliente->razao_social;
                    
                }else{
                    
                    $t->descricao = "Acompanhe a manifestacao da nota do fornecedor ".$this->fornecedor->nome;
                    
                }
                
                $t->descricao .= ", da empresa ".$this->empresa->nome;
                
                $t->titulo = "Visto de nota";
                $t->prioridade = 1000;
                $t->tipo_entidade_relacionada = "NF";
                $t->id_entidade_relacionada = $this->id;
                $t->tipo_tarefa = Sistema::TT_VERIFICA_NOTA($e->id);
                
                try{
                    
                    Sistema::novaTarefaEmpresa($con, $t, $e);
                
                }catch(Exception $ex){
                    
                    
                }
                
            }else{
                
                $ps->close();
                
            }
            
        } else {

            $ps = $con->getConexao()->prepare("UPDATE nota SET saida=" . ($this->saida ? "true" : "false") . ",chave='$this->chave',id_cliente=" . ($this->cliente != null ? $this->cliente->id : 0) . ",id_fornecedor=" . ($this->fornecedor != null ? $this->fornecedor->id : 0) . ",observacao='$this->observacao',id_empresa=" . $this->empresa->id . ",data_emissao=FROM_UNIXTIME($this->data_emissao/1000),excluida=false,influenciar_estoque=" . ($this->interferir_estoque ? "true" : "false") . ", id_transportadora=" . $this->transportadora->id . ", id_forma_pagamento=" . $this->forma_pagamento->id . ",frete_destinatario_remetente=" . ($this->frete_destinatario_remetente ? "true" : "false") . ", emitida=" . ($this->emitida ? "true" : "false") . ",numero=$this->numero,ficha=$this->ficha,cancelada=" . ($this->cancelada ? "true" : "false") . ",danfe='$this->danfe',xml='$this->xml',protocolo='$this->protocolo',baixa_total=$this->baixa_total,recorrencia=$this->recorrencia WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }





        foreach ($prods as $key => $value) {

            foreach ($this->produtos as $key2 => $value2) {

                if ($value->id == $value2->id) {

                    continue 2;
                }
            }

            $value->delete($con);
        }
        $erro = "";
        foreach ($this->produtos as $key2 => $value2) {

            try {

                $value2->merge($con);
            } catch (Exception $ex) {
                
                $erro = $ex->getMessage() . ", produto cod: " . $value2->produto->id . ", estoque: " . $value2->produto->estoque . ", disponivel: " . $value2->produto->disponivel . ", quantidade: " . $value2->quantidade;
            }
        }



        foreach ($vencimentos as $key => $v) {

            foreach ($this->vencimentos as $key2 => $v2) {

                if ($v->id == $v2->id) {

                    continue 2;
                }
            }

            $v->delete($con);
        }

        foreach ($this->vencimentos as $key2 => $v2) {

            $v2->merge($con);
        }

        if ($erro != "") {
            if(!$this->sem_erro){
                throw new Exception($erro);
            }
        }

    }


    public function delete($con) {

        $this->interferir_estoque = false;

        $erro = "";

        $this->produtos = $this->getProdutos($con);
        foreach ($this->produtos as $key2 => $value2) {

            try {

                $value2->merge($con);
            } catch (Exception $ex) {

                $erro = $ex->getMessage() . ", produto cod: " . $value2->produto->id . ", estoque: " . $value2->produto->estoque . ", disponivel: " . $value2->produto->disponivel . ", quantidade: " . $value2->quantidade;
            }
        }


        $ps = $con->getConexao()->prepare("UPDATE nota SET excluida=true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();

        if ($erro != "") {
            throw new Exception($erro);
        }
    }

    public function getVencimentos($con) {

        $vencimentos = array();

        $ps = $con->getConexao()->prepare("SELECT vencimento.id,vencimento.valor,UNIX_TIMESTAMP(vencimento.data)*1000,movimento.id,UNIX_TIMESTAMP(movimento.data)*1000,movimento.saldo_anterior,movimento.valor,movimento.juros,movimento.descontos,movimento.estorno,historico.id,historico.nome,operacao.id,operacao.nome,operacao.debito,banco.id,banco.nome,banco.conta,banco.saldo,banco.codigo,movimento.baixa_total FROM vencimento LEFT JOIN movimento ON movimento.id=vencimento.id_movimento AND movimento.id NOT IN (SELECT m.estorno FROM movimento m) LEFT JOIN banco ON movimento.id_banco=banco.id LEFT JOIN operacao ON operacao.id=movimento.id_operacao LEFT JOIN historico ON historico.id=movimento.id_historico WHERE id_nota=$this->id ORDER BY vencimento.data ASC");
        $ps->execute();
        $ps->bind_result($id, $valor, $data, $id_mov, $data_mov, $sal_mov, $val_mov, $mov_jur, $mov_desc, $mov_estorno, $hist_id, $hist_nom, $op_id, $op_nom, $op_deb, $ban_id, $ban_nom, $ban_cont, $ban_sal, $ban_cod, $baixa_total);

        while ($ps->fetch()) {

            $v = new Vencimento();
            $v->id = $id;
            $v->valor = $valor;
            $v->data = $data;
            $v->nota = $this;

            if ($id_mov != null) {

                $m = new Movimento();

                $m->id = $id_mov;
                $m->data = $data_mov;
                $m->saldo_anterior = $sal_mov;
                $m->valor = $val_mov;
                $m->juros = $mov_jur;
                $m->estorno = $mov_estorno;
                $m->descontos = $mov_desc;
                $m->vencimento = $v;
                $m->baixa_total = $baixa_total;

                $h = new Historico();
                $h->id = $hist_id;
                $h->nome = $hist_nom;

                $o = new Operacao();
                $o->id = $op_id;
                $o->nome = $op_nom;
                $o->debito = $op_deb;

                $b = new Banco();
                $b->id = $ban_id;
                $b->codigo = $ban_cod;
                $b->conta = $ban_cont;
                $b->nome = $ban_nom;
                $b->saldo = $ban_sal;
                $b->empresa = $this->empresa;

                $m->historico = $h;
                $m->operacao = $o;
                $m->banco = $b;

                $v->movimento = $m;
            }

            $vencimentos[] = $v;
        }
        return $vencimentos;
    }

}
