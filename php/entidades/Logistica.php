<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Logistica
 *
 * @author Renan
 */
class Logistica extends Empresa {

    function __construct($id = 0, $con = null) {

        parent::__construct($id, $con);

        $this->permissoes_especiais[] = array(
            Sistema::P_PRODUTO_CLIENTE(),
            Sistema::P_EMPRESA_PEDIDO(),
            Sistema::P_RELATORIO_PRODUTO_LOGISTICA(),
            Sistema::P_RELATORIO_MAX_PALET());
    }

    public function getTabelaLogistica($con){


        $tabela = new TabelaLogistica();
        $tabela->empresa = $this;
        
        $ps = $con->getConexao()->prepare("
            SELECT 
                id,
                taxa_minima,
                numero_palets,
                toxicidade,
                largura,
                altura,
                comprimento,
                peso_medio,
                peso_maximo,
                valor_estoque,
                cintagem,
                aplicacao,
                strech,
                despaletizado,
                notas_por_veiculo,
                media_embalagem_veiculo,
                palets_veiculo,
                palets_mes,
                palets_dia,
                carreta_dia,
                carreta_mes,
                pico_carretas,
                strech_film,
                arquivo_movimentacao_diaria,
                separacao_palet_fechado,
                separacao_fracionada,
                embalagens_por_pedido,
                sku_por_pedido,
                pico_pedidos,
                despaletizado_saida,
                paletizado_saida,
                carregamento_transportadora_cd,
                tipos_veiculo_saida,
                arquivo_movimentacao_saida,
                rembalagens,
                etiquetagem FROM tabela_logistica WHERE id_empresa=$this->id");

        $ps->execute();
        $ps->bind_result($id,$taxa_minima,$numero_palets,$toxicidade,$largura,$altura,$comprimento,$peso_medio,$peso_maximo,$valor_estoque,$cintagem,$aplicacao,$strech,$despaletizado,$notas_por_veiculo,$media_embalagem_veiculo,$palets_veiculo,$palets_mes,$palets_dia,$carreta_dia,$carreta_mes,$pico_carretas,$strech_film,$arquivo_movimentacao_diaria,$separacao_palet_fechado,$separacao_fracionada,$embalagens_por_pedido,$sku_por_pedido,$pico_pedidos,$despaletizado_saida,$paletizado_saida,$carregamento_transportadora_cd,$tipos_veiculo_saida,$arquivo_movimentacao_saida,$rembalagens,$etiquetagem);

        if($ps->fetch()){

                $tabela->id=$id;
                $tabela->taxa_minima=$taxa_minima;
                $tabela->numero_palets=$numero_palets;
                $tabela->toxicidade=$toxicidade;
                $tabela->largura=$largura;
                $tabela->altura=$altura;
                $tabela->comprimento=$comprimento;
                $tabela->peso_medio=$peso_medio;
                $tabela->peso_maximo=$peso_maximo;
                $tabela->valor_estoque=$valor_estoque;
                $tabela->cintagem=$cintagem;
                $tabela->aplicacao=$aplicacao;
                $tabela->strech=$strech;
                $tabela->despaletizado=$despaletizado;
                $tabela->notas_por_veiculo=$notas_por_veiculo;
                $tabela->media_embalagem_veiculo=$media_embalagem_veiculo;
                $tabela->palets_veiculo=$palets_veiculo;
                $tabela->palets_mes=$palets_mes;
                $tabela->palets_dia=$palets_dia;
                $tabela->carreta_dia=$carreta_dia;
                $tabela->carreta_mes=$carreta_mes;
                $tabela->pico_carretas=$pico_carretas;
                $tabela->strech_film=$strech_film;
                $tabela->arquivo_movimentacao_diaria=$arquivo_movimentacao_diaria;
                $tabela->separacao_palet_fechado=$separacao_palet_fechado;
                $tabela->separacao_fracionada=$separacao_fracionada;
                $tabela->embalagens_por_pedido=$embalagens_por_pedido;
                $tabela->sku_por_pedido=$sku_por_pedido;
                $tabela->pico_pedidos=$pico_pedidos;
                $tabela->despaletizado_saida=$despaletizado_saida;
                $tabela->paletizado_saida=$paletizado_saida;
                $tabela->carregamento_transportadora_cd=$carregamento_transportadora_cd;
                $tabela->tipos_veiculo_saida=$tipos_veiculo_saida;
                $tabela->arquivo_movimentacao_saida=$arquivo_movimentacao_saida;
                $tabela->rembalagens=$rembalagens;
                $tabela->etiquetagem=$etiquetagem;


        }
        $ps->close();


        return $tabela;

    }

    //@Override
    public function getCadastroLotesPendentesPedidoCompra($con) {

        $categorias = "(-1";

        $c = Sistema::getCategoriaProduto(null);

        foreach ($c as $key => $value) {
            if ($value->loja) {
                $categorias .= ",$value->id";
            }
        }

        $categorias .= ")";

        $sql = "SELECT "
                . "produto.id,"
                . "produto.nome,"
                . "SUM(pp.quantidade),"
                . "produto.grade, "
                . "pp.id, "
                . "pe.id "
                . "FROM produto "
                . "INNER JOIN produto_pedido_entrada pp ON pp.id_produto=produto.id "
                . "INNER JOIN pedido_entrada pe ON pe.id=pp.id_pedido AND pp.lote_cadastrado='' AND pe.id_status=1 AND pe.id>=971 AND pe.excluido=false "
                . "WHERE (produto.id_empresa = $this->id OR produto.id_logistica=$this->id) AND produto.excluido = false AND produto.sistema_lotes=true AND produto.id_categoria IN $categorias GROUP BY pe.id,pp.id_produto";


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $nome, $quantidade, $grade,$idp,$id_pedido);

        $p = array();

        while ($ps->fetch()) {

            $c = new CadastroLotePendente();
            $c->id_produto = $id;
            $c->id_produto_pedido = $idp;
            $c->id_pedido = $id_pedido;
            $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", "");
            $replacements = array("", "", "", "", "", "", "", "");
            $result = str_replace($escapers, $replacements, $nome);
            $c->nome_produto = $result;

            $c->quantidade = $quantidade;
            $c->grade = new Grade($grade);

            $p[] = $c;
        }

        $ps->close();

        return $p;
    }

    function getEmpresasClientes($con) {

        $ids = "";

        $ps = $con->getConexao()->prepare("SELECT id_empresa FROM produto WHERE id_logistica=$this->id GROUP BY id_empresa");
        $ps->execute();
        $ps->bind_result($id_empresa);
        while ($ps->fetch()) {
            $ids .= ",$id_empresa";
        }
        $ps->close();

        $ids = substr($ids, 1);

        if($ids ==  ""){
            
            return array();
            
        }

        $ps = $con->getConexao()->prepare("SELECT "
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
                . "FROM empresa "
                . "INNER JOIN endereco ON endereco.id_entidade=empresa.id AND endereco.tipo_entidade='EMP' "
                . "INNER JOIN email ON email.id_entidade=empresa.id AND email.tipo_entidade='EMP' "
                . "INNER JOIN telefone ON telefone.id_entidade=empresa.id AND telefone.tipo_entidade='EMP' "
                . "INNER JOIN cidade ON endereco.id_cidade=cidade.id "
                . "INNER JOIN estado ON cidade.id_estado = estado.id "
                . "WHERE empresa.id IN ($ids) AND empresa.id <> $this->id");
        $ps->execute();
        $clientes = array();
        $ps->bind_result($id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        while ($ps->fetch()) {

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

            $clientes[] = $empresa;
        }

        $ps->close();

        return $clientes;
    }

    public function getMovimentosProduto($con, $filtro = "") {

        $movimentos = array();


        $f = $filtro;

        $sql = "SELECT pr.id,pr.estoque,pr.disponivel,pr.nome,pp.influencia_estoque,pp.influencia_reserva,pp.valor_base,pp.juros,pp.base_calculo,pp.icms,pp.ipi,pp.frete,p.id,c.razao_social,UNIX_TIMESTAMP(IFNULL(n.data_emissao,p.data))*1000,n.ficha,n.numero,e.nome "
                . "FROM produto_pedido_saida pp "
                . "INNER JOIN pedido p ON p.id=pp.id_pedido "
                . "LEFT JOIN nota n ON n.id_pedido=p.id AND n.cancelada=false AND n.excluida=false "
                . "INNER JOIN cliente c ON c.id=p.id_cliente "
                . "INNER JOIN produto pr ON pr.id=pp.id_produto "
                . "LEFT JOIN empresa e ON e.id=pr.id_logistica "
                . "WHERE (p.id_empresa=$this->id OR pr.id_logistica = $this->id) AND (n.id_empresa=p.id_empresa OR n.id IS NULL) AND (pp.influencia_estoque <> 0 OR pp.influencia_reserva <> 0)";


        $filtro = str_replace(array('!data_emissao!'), array('IFNULL(n.data_emissao,p.data)'), $f);

        if ($filtro !== "") {

            $sql .= " AND $filtro";
        }

        $sql .= " GROUP BY pp.id";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_produto, $estoque, $disponivel, $nome_produto, $influencia_estoque, $influencia_reserva, $valor, $juros, $bc, $icms, $ipi, $frete, $id_pedido, $nome_cliente, $data, $ficha, $numero, $emp);

        while ($ps->fetch()) {

            $m = new MovimentoProduto();
            $m->id_produto = $id_produto;
            $m->nome_produto = $nome_produto;
            $m->influencia_estoque = $influencia_estoque;
            $m->influencia_reserva = $influencia_reserva;
            $m->valor_base = $valor;
            $m->juros = $juros;
            $m->base_calculo = $bc;
            $m->icms = $icms;
            $m->ipi = $ipi;
            $m->estoque_atual = $estoque;
            $m->disponivel_atual = $disponivel;
            $m->frete = $frete;
            $m->id_pedido = $id_pedido;
            $m->pessoa = $nome_cliente;
            $m->momento = $data;
            $m->ficha = $ficha;
            $m->numero_nota = $numero;

            if ($emp !== null) {

                $m->armazen = $emp;
            } else {

                $m->armazen = $this->nome;
            }

            $movimentos[] = $m;
        }

        $ps->close();

        $sql = "SELECT pr.id,pr.estoque,pr.disponivel,pr.nome,pn.influencia_estoque,pn.influencia_estoque,pn.valor_unitario,0,pn.base_calculo,pn.icms,pn.ipi,0,0,IFNULL(f.nome,c.razao_social),UNIX_TIMESTAMP(n.data_emissao)*1000,n.ficha,n.numero,e.nome "
                . "FROM produto_nota pn "
                . "INNER JOIN nota n ON n.id=pn.id_nota "
                . "LEFT JOIN fornecedor f ON n.id_fornecedor=f.id "
                . "LEFT JOIN cliente c ON n.id_cliente=c.id "
                . "INNER JOIN produto pr ON pr.id=pn.id_produto "
                . "LEFT JOIN empresa e ON pr.id_logistica=e.id "
                . "WHERE (n.id_empresa=$this->id OR pr.id_logistica=$this->id) AND pn.influencia_estoque <> 0 ";

        $filtro = str_replace(array('!data_emissao!'), array('n.data_emissao'), $f);

        if ($filtro !== "") {

            $sql .= "AND $filtro";
        }

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_produto, $estoque, $disponivel, $nome_produto, $influencia_estoque, $influencia_reserva, $valor, $juros, $bc, $icms, $ipi, $frete, $id_pedido, $nome_cliente, $data, $ficha, $numero, $emp);

        while ($ps->fetch()) {

            $m = new MovimentoProduto();
            $m->id_produto = $id_produto;
            $m->nome_produto = $nome_produto;
            $m->influencia_estoque = $influencia_estoque;
            $m->influencia_reserva = $influencia_reserva;
            $m->valor_base = $valor;
            $m->juros = $juros;
            $m->base_calculo = $bc;
            $m->icms = $icms;
            $m->ipi = $ipi;
            $m->frete = $frete;
            $m->estoque_atual = $estoque;
            $m->disponivel_atual = $disponivel;
            $m->id_pedido = $id_pedido;
            $m->pessoa = $nome_cliente;
            $m->momento = $data;
            $m->ficha = $ficha;
            $m->numero_nota = $numero;

            if ($emp !== null) {

                $m->armazen = $emp;
            } else {

                $m->armazen = $this->nome;
            }

            $movimentos[] = $m;
        }

        $ps->close();

        $sql = "SELECT pr.id,pr.estoque,pr.disponivel,pr.nome,pp.influencia_estoque,pp.influencia_estoque,pp.valor,0,0,0,0,0,0,f.nome,UNIX_TIMESTAMP(IFNULL(n.data_emissao,p.data))*1000,n.ficha,IFNULL(n.numero,0),e.nome "
                . "FROM produto_pedido_entrada pp "
                . "INNER JOIN pedido_entrada p ON pp.id_pedido=p.id "
                . "LEFT JOIN nota n ON n.id=p.id_nota AND n.cancelada=false AND n.excluida=false "
                . "LEFT JOIN fornecedor f ON p.id_fornecedor=f.id "
                . "INNER JOIN produto pr ON pr.id=pp.id_produto "
                . "LEFT JOIN empresa e ON pr.id_logistica=e.id "
                . "WHERE (p.id_empresa=$this->id OR pr.id_logistica=$this->id) AND pp.influencia_estoque <> 0 AND (n.id_empresa=$this->id OR n.id_empresa IS NULL)";

        $filtro = str_replace(array('!data_emissao!'), array('IFNULL(n.data_emissao,p.data)'), $f);

        if ($filtro !== "") {

            $sql .= "AND $filtro";
        }

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_produto, $estoque, $disponivel, $nome_produto, $influencia_estoque, $influencia_reserva, $valor, $juros, $bc, $icms, $ipi, $frete, $id_pedido, $nome_cliente, $data, $ficha, $numero, $emp);

        while ($ps->fetch()) {

            $m = new MovimentoProduto();
            $m->id_produto = $id_produto;
            $m->nome_produto = $nome_produto;
            $m->influencia_estoque = $influencia_estoque;
            $m->influencia_reserva = $influencia_reserva;
            $m->valor_base = $valor;
            $m->juros = $juros;
            $m->base_calculo = $bc;
            $m->estoque_atual = $estoque;
            $m->disponivel_atual = $disponivel;
            $m->icms = $icms;
            $m->ipi = $ipi;
            $m->frete = $frete;
            $m->id_pedido = $id_pedido;
            $m->pessoa = $nome_cliente;
            $m->momento = $data;
            $m->ficha = $ficha;
            $m->numero_nota = $numero;

            if ($emp !== null) {

                $m->armazen = $emp;
            } else {

                $m->armazen = $this->nome;
            }

            $movimentos[] = $m;
        }


        for ($i = 1; $i < count($movimentos); $i++) {
            for ($j = $i; $j > 0 && $movimentos[$j]->momento > $movimentos[$j - 1]->momento; $j--) {
                $k = $movimentos[$j];
                $movimentos[$j] = $movimentos[$j - 1];
                $movimentos[$j - 1] = $k;
            }
        }

        return $movimentos;
    }

    public function getCountProdutoClienteLogistic($con, $filtro = "") {

        $categorias = "(-1";

        $c = Sistema::getCategoriaProduto(null);

        foreach ($c as $key => $value) {
            if ($value->loja) {
                $categorias .= ",$value->id";
            }
        }

        $categorias .= ")";

        $sql = "SELECT COUNT(*) FROM (SELECT produto.id_universal "
                . "FROM produto "
                . "INNER JOIN empresa ON empresa.id=produto.id_empresa "
                . "WHERE produto.id_logistica=$this->id AND produto.id_categoria IN $categorias ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        $sql .= "GROUP BY produto.id_universal) l";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);

        if ($ps->fetch()) {
            $ps->close();
            return $qtd;
        }
        $ps->close();
        return 0;
    }

    public function getProdutosInventario($con){
        
        return $this->getProdutos($con, 0, 100000,"produto.id_categoria=".Sistema::CATP_AGRICOLA()->id);
        
    }

    public function getProdutoClienteLogistic($con, $x1, $x2, $filtro = "", $ordem = "") {


        $sql = "SELECT "
                . "produto.id_universal, "
                . "produto.nome, "
                . "produto.estoque, "
                . "produto.disponivel, "
                . "produto.transito, "
                . "produto.id_categoria, "
                . "CONCAT(CONCAT(empresa.id,'-'),empresa.nome) "
                . "FROM produto "
                . "INNER JOIN empresa ON empresa.id=produto.id_empresa "
                . "WHERE produto.id_logistica=$this->id ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();
        $ps->bind_result($id, $nome, $estoque, $disponivel, $transito, $cat_id, $empresa);

        $empresas = array();

        $produtos = array();

        while ($ps->fetch()) {

            if ($estoque === 0)
                continue;

            if (!isset($produtos[$id])) {

                $p = new ProdutoClienteLogistic();
                $p->id = $id;
                $p->nome = $nome;
                $categoria = Sistema::getCategoriaProduto(null, $cat_id);
                $p->categoria = $categoria->nome;

                $produtos[$id] = $p;
            }

            $pro = $produtos[$id];

            $pro->estoques[$empresa] = $estoque;
            $pro->disponiveis[$empresa] = $disponivel;
            $pro->transitos[$empresa] = $transito;

            foreach ($empresas as $key => $value) {

                if ($value === $empresa) {

                    continue 2;
                }
            }

            $empresas[] = $empresa;
        }

        $ps->close();

        $retorno = array();

        foreach ($produtos as $key => $value) {

            $pem = array();
            $pe = array();
            $pd = array();
            $pt = array();

            foreach ($empresas as $key2 => $value2) {

                if (isset($value->estoques[$value2])) {

                    $pem[] = $value2;
                    $pe[] = $value->estoques[$value2];
                    $pd[] = $value->disponiveis[$value2];
                    $pt[] = $value->transitos[$value2];
                } else {

                    $pem[] = $value2;
                    $pe[] = 0;
                    $pd[] = 0;
                    $pt[] = 0;
                }
            }

            $value->empresas = $pem;
            $value->estoques = $pe;
            $value->transitos = $pt;
            $value->disponiveis = $pd;

            $retorno[] = $value;
        }


        $rr = array();

        for ($i = $x1; $i < $x2 && $i < count($retorno); $i++) {
            $rr[] = $retorno[$i];
        }

        return $rr;
    }

    //@Override
    public function getCadastroLotesPendentes($con) {

        $categorias = "(-1";

        $c = Sistema::getCategoriaProduto(null);

        foreach ($c as $key => $value) {
            if ($value->loja) {
                $categorias .= ",$value->id";
            }
        }

        $categorias .= ")";

        $sql = "SELECT "
                . "produto.id,"
                . "produto.nome,"
                . "(produto.disponivel-IFNULL(l.quantidade,0)),"
                . "produto.grade "
                . "FROM produto "
                . "LEFT JOIN (SELECT lote.id_produto,SUM(lote.quantidade_real) as 'quantidade' FROM lote WHERE lote.excluido=false GROUP BY lote.id_produto) l ON l.id_produto=produto.id "
                . "WHERE (produto.id_empresa = $this->id OR produto.id_logistica = $this->id) AND produto.excluido = false AND (produto.disponivel-IFNULL(l.quantidade,0))>0 AND produto.sistema_lotes=true AND produto.id_categoria In $categorias ";


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $nome, $quantidade, $grade);

        $p = array();

        while ($ps->fetch()) {

            $c = new CadastroLotePendente();
            $c->id_produto = $id;
            $c->nome_produto = $nome;
            $c->quantidade = $quantidade;
            $c->grade = new Grade($grade);

            $p[] = $c;
        }

        $ps->close();

        return $p;
    }

    //@Override
    public function getCountLotes($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM lote INNER JOIN produto ON produto.id=lote.id_produto WHERE (produto.id_empresa=$this->id OR produto.id_logistica=$this->id) AND lote.excluido=false ";

        if ($filtro != "") {

            $sql .= "AND $filtro";
        }

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);

        if ($ps->fetch()) {

            $ps->close();
            return $qtd;
        }

        $ps->close();
        return 0;
    }

    //@Override
    public function getLotes($con, $x1, $x2, $filtro = "", $ordem = "") {

        $campanhas = array();
        $ofertas = array();

        $ps = $con->getConexao()->prepare("SELECT "
                . "campanha.id,"
                . "campanha.inicio,"
                . "campanha.fim,"
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
        $ps->bind_result($id, $inicio, $fim, $prazo, $parcelas, $cliente, $id_produto_campanha, $id_produto, $validade, $limite, $valor, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        while ($ps->fetch()) {

            if (!isset($campanhas[$id])) {

                $campanhas[$id] = new Campanha();
                $campanhas[$id]->id = $id;
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

            $campanhas[$id]->produtos[] = $p;
        }
        $ps->close();

        $sql = "SELECT "
                . "lote.id,"
                . "lote.numero,"
                . "lote.rua,"
                . "lote.altura,"
                . "UNIX_TIMESTAMP(lote.validade)*1000,"
                . "UNIX_TIMESTAMP(lote.data_entrada)*1000,"
                . "lote.grade,"
                . "lote.quantidade_inicial,"
                . "lote.quantidade_real,"
                . "lote.codigo_fabricante,"
                . "GROUP_CONCAT(retirada.retirada separator ';'),"
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
                . "telefone.numero "
                . "FROM lote "
                . "LEFT JOIN retirada ON lote.id=retirada.id_lote "
                . "INNER JOIN produto ON lote.id_produto=produto.id "
                . "INNER JOIN empresa ON empresa.id = produto.id_empresa "
                . "INNER JOIN telefone ON telefone.id_entidade=empresa.id AND telefone.tipo_entidade='EMP' "
                . "INNER JOIN email ON email.id_entidade = empresa.id AND email.tipo_entidade='EMP' "
                . "INNER JOIN endereco ON endereco.id_entidade = empresa.id AND endereco.tipo_entidade='EMP' "
                . "INNER JOIN cidade ON endereco.id_cidade = cidade.id "
                . "INNER JOIN estado ON cidade.id_estado = estado.id "
                . "WHERE (produto.id_empresa = $this->id OR produto.id_logistica = $this->id) AND lote.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        $sql .= "GROUP BY lote.id ";

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1," . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $numero_lote, $rua_lote, $altura, $validade, $entrada, $grade, $quantidade_inicial, $quantidade_real, $codigo_fabricante, $retirada, $id_pro, $cod_pro, $id_log, $classe_risco, $fabricante, $imagem, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $est, $disp, $tr, $gr, $uni, $ncm, $nome, $lucro, $ativo, $conc, $cat_id, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        $lotes = array();

        $produtos = array();

        while ($ps->fetch()) {

            if (!isset($produtos[$id_pro])) {

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

                $p = new Produto();
                $p->logistica = $id_log;
                $p->id = $id_pro;
                $p->codigo = $cod_pro;
                $p->classe_risco = $classe_risco;
                $p->fabricante = $fabricante;
                $p->imagem = $imagem;
                $p->nome = $nome;
                $p->id_universal = $id_uni;
                $p->liquido = $liq == 1;
                $p->quantidade_unidade = $qtd_un;
                $p->habilitado = $hab;
                $p->empresa = $empresa;
                $p->valor_base = $vb;
                $p->custo = $cus;
                $p->peso_bruto = $pb;
                $p->peso_liquido = $pl;
                $p->estoque = $est;
                $p->disponivel = $disp;
                $p->ativo = $ativo;
                $p->concentracao = $conc;
                $p->transito = $tr;
                $p->grade = new Grade($gr);
                $p->unidade = $uni;
                $p->ncm = $ncm;
                $p->lucro_consignado = $lucro;
                $p->ofertas = (!isset($ofertas[$p->id]) ? array() : $ofertas[$p->id]);

                foreach ($p->ofertas as $key => $oferta) {

                    $oferta->produto = $p;
                }

                $p->categoria = Sistema::getCategoriaProduto(null, $cat_id);

                $produtos[$id_pro] = $p;
            }


            $lote = new Lote();
            $lote->id = $id;
            $lote->numero = $numero_lote;
            $lote->rua = $rua_lote;
            $lote->altura = $altura;
            $lote->validade = $validade;
            $lote->entrada = $entrada;
            $lote->quantidade_inicial = $quantidade_inicial;
            $lote->grade = new Grade($grade);
            $lote->quantidade_real = $quantidade_real;
            $lote->produto = $produtos[$id_pro];
            $lote->codigo_fabricante = $codigo_fabricante;

            if ($retirada != null) {

                $rets = explode(';', $retirada);

                foreach ($rets as $key => $value) {

                    $ret = explode(',', $value);
                    foreach ($ret as $key => $value) {

                        $ret[$key] = intval($ret[$key]);
                    }

                    $lote->retiradas[] = $ret;
                }
            }

            $lotes[] = $lote;
        }

        $ps->close();

        foreach ($produtos as $key => $value) {
            $value->logistica = Sistema::getLogisticaById($con, $value->logistica);
        }

        return $lotes;
    }

    public function getPedidos($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "pedido.id,"
                . "pedido.etapa_frete,"
                . "pedido.id_logistica, "
                . "pedido.id_nota, "
                . "pedido.frete_inclusao, "
                . "UNIX_TIMESTAMP(pedido.data)*1000, "
                . "pedido.prazo, "
                . "pedido.parcelas, "
                . "pedido.id_status, "
                . "pedido.id_forma_pagamento, "
                . "pedido.frete, "
                . "pedido.observacoes, "
                . "cliente.id,"
                . "cliente.codigo, "
                . "cliente.razao_social, "
                . "cliente.nome_fantasia, "
                . "cliente.limite_credito, "
                . "UNIX_TIMESTAMP(cliente.inicio_limite)*1000, "
                . "UNIX_TIMESTAMP(cliente.termino_limite)*1000, "
                . "cliente.pessoa_fisica, "
                . "cliente.cpf, "
                . "cliente.cnpj, "
                . "cliente.rg, "
                . "cliente.inscricao_estadual, "
                . "cliente.suframado, "
                . "cliente.inscricao_suframa, "
                . "categoria_cliente.id, "
                . "categoria_cliente.nome, "
                . "endereco_cliente.id, "
                . "endereco_cliente.rua, "
                . "endereco_cliente.numero, "
                . "endereco_cliente.bairro, "
                . "endereco_cliente.cep, "
                . "cidade_cliente.id, "
                . "cidade_cliente.nome, "
                . "estado_cliente.id, "
                . "estado_cliente.sigla, "
                . "transportadora.id,"
                . "transportadora.codigo, "
                . "transportadora.razao_social, "
                . "transportadora.nome_fantasia, "
                . "transportadora.despacho, "
                . "transportadora.cnpj, "
                . "transportadora.habilitada, "
                . "transportadora.inscricao_estadual,"
                . "endereco_transportadora.id, "
                . "endereco_transportadora.rua, "
                . "endereco_transportadora.numero, "
                . "endereco_transportadora.bairro, "
                . "endereco_transportadora.cep, "
                . "cidade_transportadora.id, "
                . "cidade_transportadora.nome, "
                . "estado_transportadora.id, "
                . "estado_transportadora.sigla, "
                . "usuario.id, "
                . "usuario.nome, "
                . "usuario.login, "
                . "usuario.senha, "
                . "usuario.cpf, "
                . "endereco_usuario.id, "
                . "endereco_usuario.rua, "
                . "endereco_usuario.numero, "
                . "endereco_usuario.bairro, "
                . "endereco_usuario.cep, "
                . "cidade_usuario.id, "
                . "cidade_usuario.nome, "
                . "estado_usuario.id, "
                . "estado_usuario.sigla,"
                . "email_cliente.id,"
                . "email_cliente.endereco,"
                . "email_cliente.senha, "
                . "email_tra.id, "
                . "email_tra.endereco, "
                . "email_tra.senha, "
                . "email_usu.id, "
                . "email_usu.endereco,"
                . "email_usu.senha, "
                . "empresa.id,"
                . "empresa.tipo_empresa,"
                . "empresa.nome,"
                . "empresa.inscricao_estadual,"
                . "empresa.consigna,"
                . "empresa.aceitou_contrato,"
                . "empresa.juros_mensal,"
                . "empresa.cnpj,"
                . "endereco_empresa.numero,"
                . "endereco_empresa.id,"
                . "endereco_empresa.rua,"
                . "endereco_empresa.bairro,"
                . "endereco_empresa.cep,"
                . "cidade_empresa.id,"
                . "cidade_empresa.nome,"
                . "estado_empresa.id,"
                . "estado_empresa.sigla,"
                . "email_empresa.id,"
                . "email_empresa.endereco,"
                . "email_empresa.senha,"
                . "telefone_empresa.id,"
                . "telefone_empresa.numero "
                . "FROM pedido "
                . "INNER JOIN cliente ON cliente.id=pedido.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN (SELECT GROUP_CONCAT(pro.nome separator ';') as 'produtos',ped.id as 'id_pedido'  FROM produto pro INNER JOIN produto_pedido_saida pp ON pro.id=pp.id_produto INNER JOIN pedido ped ON ped.id=pp.id_pedido GROUP BY ped.id) k ON k.id_pedido=pedido.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN transportadora ON transportadora.id = pedido.id_transportadora "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN usuario ON usuario.id=pedido.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "INNER JOIN email email_tra ON email_tra.id_entidade=transportadora.id AND email_tra.tipo_entidade='TRA' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "INNER JOIN empresa ON pedido.id_empresa=empresa.id "
                . "INNER JOIN endereco endereco_empresa ON endereco_empresa.id_entidade=empresa.id AND endereco_empresa.tipo_entidade='EMP' "
                . "INNER JOIN email email_empresa ON email_empresa.id_entidade=empresa.id AND email_empresa.tipo_entidade='EMP' "
                . "INNER JOIN telefone telefone_empresa ON telefone_empresa.id_entidade=empresa.id AND telefone_empresa.tipo_entidade='EMP' "
                . "INNER JOIN cidade cidade_empresa ON endereco_empresa.id_cidade=cidade_empresa.id "
                . "INNER JOIN estado estado_empresa ON cidade_empresa.id_estado = estado_empresa.id "
                . "WHERE (pedido.id_empresa = $this->id OR pedido.id_logistica=$this->id) AND pedido.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pedido, $etapa_frete, $id_log, $id_nota, $frete_incluso, $data, $prazo, $parcelas, $id_status, $id_forma_pagamento, $frete, $obs, $id_cliente, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_usu, $nome_usu, $login_usu, $senha_usu, $cpf_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_cli_id, $email_cli_end, $email_cli_senha, $email_tra_id, $email_tra_end, $email_tra_senha, $email_usu_id, $email_usu_end, $email_usu_senha, $id_empresa_empresa, $tipo_empresa_empresa, $nome_empresa_empresa, $inscricao_empresa_empresa, $consigna_empresa, $aceitou_contrato_empresa, $juros_mensal_empresa, $cnpj_empresa, $numero_endereco_empresa, $id_endereco_empresa, $rua_empresa, $bairro_empresa, $cep_empresa, $id_cidade_empresa, $nome_cidade_empresa, $id_estado_empresa, $nome_estado_empresa, $id_email_empresa, $endereco_email_empresa, $senha_email_empresa, $id_telefone_empresa, $numero_telefone_empresa);


        $pedidos = array();
        $transportadoras = array();
        $usuarios = array();
        $clientes = array();

        while ($ps->fetch()) {

            $empresa = Sistema::getEmpresa($tipo_empresa_empresa);

            $empresa->id = $id_empresa_empresa;
            $empresa->cnpj = new CNPJ($cnpj_empresa);
            $empresa->inscricao_estadual = $inscricao_empresa_empresa;
            $empresa->nome = $nome_empresa_empresa;
            $empresa->aceitou_contrato = $aceitou_contrato_empresa;
            $empresa->juros_mensal = $juros_mensal_empresa;
            $empresa->consigna = $consigna_empresa;

            $endereco_empresa = new Endereco();
            $endereco_empresa->id = $id_endereco_empresa;
            $endereco_empresa->rua = $rua_empresa;
            $endereco_empresa->bairro = $bairro_empresa;
            $endereco_empresa->cep = new CEP($cep_empresa);
            $endereco_empresa->numero = $numero_endereco_empresa;

            $cidade_empresa = new Cidade();
            $cidade_empresa->id = $id_cidade_empresa;
            $cidade_empresa->nome = $nome_cidade_empresa;

            $estado_empresa = new Estado();
            $estado_empresa->id = $id_estado_empresa;
            $estado_empresa->sigla = $nome_estado_empresa;

            $cidade_empresa->estado = $estado_empresa;

            $endereco_empresa->cidade = $cidade_empresa;

            $empresa->endereco = $endereco_empresa;

            $email_empresa = new Email($endereco_email_empresa);
            $email_empresa->id = $id_email_empresa;
            $email_empresa->senha = $senha_email_empresa;

            $empresa->email = $email_empresa;

            $telefone_empresa = new Telefone($numero_telefone_empresa);
            $telefone_empresa->id = $id_telefone_empresa;

            $empresa->telefone = $telefone_empresa;

            //------------------------

            $cliente = new Cliente();
            $cliente->id = $id_cliente;
            $cliente->codigo = $cod_cli;
            $cliente->cnpj = new CNPJ($cnpj);
            $cliente->cpf = new CPF($cpf);
            $cliente->rg = new RG($rg);
            $cliente->pessoa_fisica = $pessoa_fisica == 1;
            $cliente->nome_fantasia = $nome_fantasia_cliente;
            $cliente->razao_social = $nome_cliente;
            $cliente->email = new Email($email_cli_end);
            $cliente->email->id = $email_cli_id;
            $cliente->email->senha = $email_cli_senha;
            $cliente->categoria = new CategoriaCliente();
            $cliente->categoria->id = $cat_id;
            $cliente->categoria->nome = $cat_nome;
            $cliente->inicio_limite = $inicio;
            $cliente->termino_limite = $fim;
            $cliente->limite_credito = $limite;
            $cliente->inscricao_suframa = $i_suf;
            $cliente->suframado = $suf == 1;
            $cliente->empresa = $empresa;
            $cliente->inscricao_estadual = $ie;

            $end = new Endereco();
            $end->id = $end_cli_id;
            $end->bairro = $end_cli_bairro;
            $end->cep = new CEP($end_cli_cep);
            $end->numero = $end_cli_numero;
            $end->rua = $end_cli_numero;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_cli_id;
            $end->cidade->nome = $cid_cli_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_cli_id;
            $end->cidade->estado->sigla = $est_cli_nome;

            $cliente->endereco = $end;

            if (!isset($clientes[$cliente->id])) {

                $clientes[$cliente->id] = array();
            }

            $clientes[$cliente->id][] = $cliente;

            $transportadora = new Transportadora();
            $transportadora->id = $tra_id;
            $transportadora->codigo = $cod_tra;
            $transportadora->cnpj = new CNPJ($tra_cnpj);
            $transportadora->despacho = $tra_despacho;
            $transportadora->email = new Email($email_tra_end);
            $transportadora->email->id = $email_tra_id;
            $transportadora->email->senha = $email_tra_senha;
            $transportadora->habilitada = $tra_habilitada == 1;
            $transportadora->inscricao_estadual = $tra_ie;
            $transportadora->nome_fantasia = $tra_nome_fantasia;
            $transportadora->razao_social = $tra_nome;
            $transportadora->empresa = $empresa;

            $end = new Endereco();
            $end->id = $end_tra_id;
            $end->bairro = $end_tra_bairro;
            $end->cep = new CEP($end_tra_cep);
            $end->numero = $end_tra_numero;
            $end->rua = $end_tra_rua;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_tra_id;
            $end->cidade->nome = $cid_tra_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_tra_id;
            $end->cidade->estado->sigla = $est_tra_nome;

            $transportadora->endereco = $end;

            if (!isset($transportadoras[$transportadora->id])) {

                $transportadoras[$transportadora->id] = array();
            }

            $transportadoras[$transportadora->id][] = $transportadora;

            $usuario = new Usuario();

            $usuario->cpf = new CPF($cpf_usu);
            $usuario->email = new Email($email_usu_end);
            $usuario->email->id = $email_usu_id;
            $usuario->email->senha = $email_usu_senha;
            $usuario->empresa = $this;
            $usuario->id = $id_usu;
            $usuario->login = $login_usu;
            $usuario->senha = $senha_usu;
            $usuario->nome = $nome_usu;

            $end = new Endereco();
            $end->id = $end_usu_id;
            $end->bairro = $end_usu_bairro;
            $end->cep = new CEP($end_usu_cep);
            $end->numero = $end_usu_numero;
            $end->rua = $end_usu_numero;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_usu_id;
            $end->cidade->nome = $cid_usu_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_usu_id;
            $end->cidade->estado->sigla = $est_usu_nome;

            $usuario->endereco = $end;

            if (!isset($usuarios[$usuario->id])) {

                $usuarios[$usuario->id] = array();
            }

            $usuarios[$usuario->id][] = $usuario;


            $pedido = new Pedido();

            $pedido->logistica = $id_log;
            $pedido->etapa_frete = $etapa_frete;
            $pedido->cliente = $cliente;
            $pedido->data = $data;
            $pedido->empresa = $empresa;
            $pedido->id_nota = $id_nota;

            $formas_pagamento = Sistema::getFormasPagamento();

            foreach ($formas_pagamento as $key => $forma) {
                if ($forma->id == $id_forma_pagamento) {
                    $pedido->forma_pagamento = $forma;
                    break;
                }
            }

            $pedido->frete = $frete;
            $pedido->frete_incluso = $frete_incluso == 1;
            $pedido->id = $id_pedido;
            $pedido->observacoes = $obs;
            $pedido->parcelas = $parcelas;
            $pedido->prazo = $prazo;

            $status = Sistema::getStatusPedidoSaida();

            foreach ($status as $key => $st) {
                if ($st->id == $id_status) {
                    $pedido->status = $st;
                    break;
                }
            }

            $pedido->transportadora = $transportadora;

            $pedido->usuario = $usuario;

            $pedidos[] = $pedido;
        }

        $ps->close();

        foreach ($pedidos as $key => $value) {
            $value->logistica = Sistema::getLogisticaById($con, $value->logistica);
        }

        $in_tra = "-1";
        $in_usu = "-1";
        $in_cli = "-1";

        foreach ($clientes as $id => $cliente) {
            $in_cli .= ",";
            $in_cli .= $id;
        }

        foreach ($transportadoras as $id => $transportadora) {
            $in_tra .= ",";
            $in_tra .= $id;
        }

        foreach ($usuarios as $id => $usuario) {
            $in_usu .= ",";
            $in_usu .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN($in_tra) AND telefone.tipo_entidade='TRA') OR (telefone.id_entidade IN ($in_cli) AND telefone.tipo_entidade='CLI') OR (telefone.id_entidade IN ($in_usu) AND telefone.tipo_entidade='USU') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $clientes;
            if ($tipo_entidade == 'TRA') {
                $v = $transportadoras;
            } else if ($tipo_entidade == 'USU') {
                $v = $usuarios;
            }

            $telefone = new Telefone($numero);
            $telefone->id = $id;


            foreach ($v[$id_entidade] as $key => $ent) {

                $ent->telefones[] = $telefone;
            }
        }
        $ps->close();

        $ps = $con->getConexao()->prepare("SELECT tabela.id,tabela.prazo,tabela.nome,tabela.id_transportadora,regra_tabela.id,regra_tabela.condicional,regra_tabela.resultante FROM tabela INNER JOIN regra_tabela ON regra_tabela.id_tabela = tabela.id WHERE tabela.id_transportadora IN ($in_tra) AND tabela.excluida=false");
        $ps->execute();
        $ps->bind_result($id,$prazo, $nome, $id_tra, $idr, $cond, $res);
        while ($ps->fetch()) {

            $ts = $transportadoras[$id_tra];

            foreach ($ts as $key => $t) {

                if ($t->tabela == null) {

                    $t->tabela = new Tabela();
                    $t->tabela->nome = $nome;
                    $t->tabela->id = $id;
                    $t->tabela->prazo = $prazo;
                    
                }

                $regra = new RegraTabela();
                $regra->id = $idr;
                $regra->condicional = $cond;
                $regra->resultante = $res;

                $t->tabela->regras[] = $regra;
            }
        }

        $ps->close();

        foreach ($usuarios as $key => $value) {
            foreach ($value as $key2 => $value2) {
                $value2->permissoes = Sistema::getPermissoes($value2->empresa);
            }
        }

        $ps = $con->getConexao()->prepare("SELECT id_usuario, id_permissao,incluir,deletar,alterar,consultar FROM usuario_permissao WHERE id_usuario IN ($in_usu)");
        $ps->execute();
        $ps->bind_result($id_usuario, $id_permissao, $incluir, $deletar, $alterar, $consultar);

        while ($ps->fetch()) {

            foreach ($usuarios[$id_usuario] as $key => $usu) {

                $permissoes = $usu->permissoes;

                $p = null;

                foreach ($permissoes as $key => $perm) {
                    if ($perm->id == $id_permissao) {
                        $p = $perm;
                        break;
                    }
                }

                if ($p == null) {

                    continue;
                }

                $p->alt = $alterar == 1;
                $p->in = $incluir == 1;
                $p->del = $deletar == 1;
                $p->cons = $consultar == 1;
            }
        }

        $ps->close();

        $ids = "(-1";

        foreach ($pedidos as $key => $value) {
            $ids .= ",$value->id";
        }

        $ids .= ")";

        $fretes = array();
        $ps = $con->getConexao()->prepare("SELECT f.id,f.valor,f.ordem,f.id_pedido,f.id_empresa_destino,t.id,t.razao_social,t.cnpj FROM frete_intermediario f INNER JOIN transportadora t ON t.id=f.id_transportadora WHERE f.id_pedido IN $ids");
        $ps->execute();
        $ps->bind_result($id, $valor, $ordem, $id_pedido, $id_empresa_destino, $t_id, $t_nome, $t_cnpj);
        while ($ps->fetch()) {
            if (!isset($fretes[$id_pedido])) {
                $fretes[$id_pedido] = array();
            }
            $f = new FreteIntermediario();
            $f->id = $id;
            $f->valor = $valor;
            $f->ordem = $ordem;
            $f->id_empresa_destino = $id_empresa_destino;

            $t = new TransportadoraReduzida();
            $t->id = $t_id;
            $t->razao_social = $t_nome;
            $t->cnpj = new CNPJ($t_cnpj);

            $f->transportadora = $t;

            $fretes[$id_pedido][] = $f;
        }
        $ps->close();

        foreach ($pedidos as $key => $value) {
            if (isset($fretes[$value->id])) {
                $f = $fretes[$value->id];
                foreach ($f as $key2 => $value2) {
                    $value2->pedido = $value;
                }
                $value->fretes_intermediarios = $f;
            }
        }

        return $pedidos;
    }

    public function getCountPedidos($con, $filtro = "") {

        $sql = "SELECT COUNT(*) "
                . "FROM pedido "
                . "INNER JOIN cliente ON cliente.id=pedido.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN transportadora ON transportadora.id = pedido.id_transportadora "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN usuario ON usuario.id=pedido.id_usuario "
                . "INNER JOIN (SELECT GROUP_CONCAT(pro.nome separator ';') as 'produtos',ped.id as 'id_pedido'  FROM produto pro INNER JOIN produto_pedido_saida pp ON pro.id=pp.id_produto INNER JOIN pedido ped ON ped.id=pp.id_pedido GROUP BY ped.id) k ON k.id_pedido=pedido.id "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "INNER JOIN email email_tra ON email_tra.id_entidade=transportadora.id AND email_tra.tipo_entidade='TRA' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE (pedido.id_empresa = $this->id OR pedido.id_logistica=$this->id) AND pedido.excluido=false ";

        if ($filtro != "") {

            $sql .= " AND $filtro ";
        }

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($qtd);

        if ($ps->fetch()) {

            $ps->close();

            return $qtd;
        }

        $ps->close();

        return 0;
    }

}
