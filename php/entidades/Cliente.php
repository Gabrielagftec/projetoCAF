<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cliente
 *
 * @author Renan
 */
class Cliente {

    public $id;
    public $razao_social;
    public $nome_fantasia;
    public $email;
    public $limite_credito;
    public $termino_limite;
    public $inicio_limite;
    public $pessoa_fisica;
    public $cpf;
    public $cnpj;
    public $rg;
    public $inscricao_estadual;
    public $telefones;
    public $endereco;
    public $suframado;
    public $inscricao_suframa;
    public $empresa;
    public $categoria;
    public $codigo;
    public $codigo_contimatic;
    public $classe_virtual;
    public $cobranca_emocional;
    public $recebe_whats;
    public $produtos;
    public $emite_receita;
    public $id_vendedor;

    public $produtos_cliente;

    public $historico_habilitado;
    public $preferencial_mix;
    public $super_mix;
    public $observacao;

    public function getHistorico($con){
        
        
        $ps = $con->getConexao()->prepare("SELECT id,historico FROM historico_cliente WHERE id_cliente=$this->id");
        $ps->execute();
        $ps->bind_result($id,$historico);
        if($ps->fetch()){
            $ps->close();
            
            $h = new HistoricoCliente($historico);
            $h->id = $id;
            
            return $h;
            
        }
        $ps->close();
        
        return new HistoricoCliente();
        
    }
    
    function __construct() {

        $this->id = 0;
        $this->email = new Email("");
        $this->cpf = new CPF("");
        $this->cnpj = new CNPJ("");
        $this->rg = new RG("");
        $this->endereco = new Endereco();
        $this->empresa = null;
        $this->categoria = null;
        $this->pessoa_fisica = false;
        $this->telefones = array();
        $this->excluido = false;
        $this->suframado = false;
        $this->limite_credito = 0;
        $this->codigo_contimatic = 0;
        $this->classe_virtual = 0;
        $this->recebe_whats = false;
        $this->produtos = null;
        $this->id_vendedor = 0;

        $this->historico_habilitado = false;
        $this->preferencial_mix = false;
        $this->super_mix = false;

        $this->observacao = "";

        $this->inicio_limite = round(microtime(true) * 1000);
        $this->termino_limite = round(microtime(true) * 1000);
        $this->cobranca_emocional = false;
        $this->codigo = 0;

         $this->produtos = null;

         $this->produto_cliente = null;

    }

    public function getProdutosCliente($con){


        $inp = "(-1";

        $this->produtos_cliente = array();

        $ps = $con->getConexao()->prepare("SELECT id,id_produto,preco1,comis1,preco2,comis2,preco3,comis3,preco4,comis4 FROM promocao_cliente WHERE id_cliente = $this->id");
        $ps->execute();
        $ps->bind_result($id,$id_produto,$preco1,$comis1,$preco2,$comis2,$preco3,$comis3,$preco4,$comis4);
        while($ps->fetch()){

            $p = new ProdutoCliente();
            $p->id = $id;
            $p->id_produto = $id_produto;
            $p->preco1 = $preco1;
            $p->cliente = $this;
            $p->comis1 = $comis1;
            $p->preco2 = $preco2;
            $p->comis2 = $comis2;
            $p->valor = $valor;
            $p->preco3 = $preco3;
            $p->comis3 = $comis3;
            $p->preco4 = $preco4;
            $p->comis4 = $comis4;

            $this->produtos_cliente[] = $p;

            $inp .= ",$id_produto";

        }
        $ps->close();

        $inp .= ")";

        $p = $this->empresa->getProdutos($con,0,1000,"produto.id IN $inp","");

        foreach ($this->produtos_cliente as $key => $value) {
            
            foreach ($p as $k => $v2) {
                
                if($v2->id === $value->id_produto){

                    $value->produto = $v2;
                    continue 2;

                }

            }

        }


        return $this->produtos_cliente;

    }

    public function getProdutos($con){

        $ids = "(-1";

        $ps = $con->getConexao()->prepare("SELECT id_produto FROM produto_cliente WHERE id_cliente=$this->id");
        $ps->execute();
        $ps->bind_result($id_produto);

        while($ps->fetch()){

            $ids .= ",$id_produto";

        }

        $ps->close();

        $ids .= ")";


        $produtos = $this->empresa->getProdutos($con,0,200,"produto.id IN $ids","produto.nome");

        $this->produtos = $produtos;

        return $produtos;

    }

    public function setCobrancaEmocional($con){

        $ps = $con->getConexao()->prepare("UPDATE cliente SET cobranca_emocional=not cobranca_emocional WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }

    public function setRecebeWhats($con){

        $ps = $con->getConexao()->prepare("UPDATE cliente SET recebe_whats=(CASE WHEN recebe_whats=1 THEN 0 ELSE 1 END) WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }

    public function enviarHtmlModulo0($con){

        $ses = new SessionManager();
        
        $usuario = $ses->get('usuario');
        
        if($usuario !== null){
            
            $t = new Tarefa();
            $t->tipo_tarefa = Sistema::TT_SUPORTE_CLIENTE($usuario->empresa->id);
            $t->titulo = "Envio de proposta de modulo 0";
            $t->descricao = "Proposta de modulo 0 enviada para o cliente '$this->codigo - $this->razao_social'";
            $t->tipo_entidade_relacionada = 'CLI';
            $t->id_entidade_relacionada = $this->id;

            Sistema::novaTarefaUsuario($con, $t, $usuario);
            
        }
        
        $html = $this->getHtmlModulo0($con);

        $this->empresa->email->enviarEmail($this->email,"Campanha de Modulo 0 RTC",$html);

        Sistema::avisoDEVS_MASTER($html);

        $email = new Email("elias@agrofauna.com.br");
        $email2 = new Email("tania.dias@agrofauna.com.br");

        $this->empresa->email->enviarEmail(array($email,$email2),"Campanha de Modulo 0 RTC",$html);

    }

    
    
    public function enviarHtmlModulo2($con){

        $ses = new SessionManager();
        
        $usuario = $ses->get('usuario');
        
        $html = $this->getHtmlModulo2($con);

        $this->empresa->email->enviarEmail($this->email,"Campanha de Modulo 2 RTC",$html);

        Sistema::avisoDEVS_MASTER($html);

        $email = new Email("elias@agrofauna.com.br");
        $email2 = new Email("tania.dias@agofauna.com.br");
        $this->empresa->email->enviarEmail(array($email,$email2),"Campanha de Modulo 2 RTC",$html);

    }

    public function getHtmlModulo2($con){

        $banners = $this->empresa->getBanners($con,0,5,"banner.data_inicial <= CURRENT_TIMESTAMP AND banner.data_final >= CURRENT_TIMESTAMP AND banner.tipo=6");

        if(count($banners) === 0){

            return "";

        }

        $banner = $banners[rand(0,count($banners)-1)];

        $html = $banner->getHTML($this);

        return $html;
        
    }

    public function getHtmlModulo0($con){

        $banners = $this->empresa->getBanners($con,0,5,"banner.data_inicial <= CURRENT_TIMESTAMP AND banner.data_final >= CURRENT_TIMESTAMP AND banner.tipo=4");

        if(count($banners) === 0){

            return "";

        }

        $banner = $banners[rand(0,count($banners)-1)];

        $html = $banner->getHTML($this);

        return $html;
        
    }

    public function enviarHtmlBoasVindas($con){

        $ses = new SessionManager();
        
        $usuario = $ses->get('usuario');
        
        if($usuario !== null){
            
            $t = new Tarefa();
            $t->tipo_tarefa = Sistema::TT_SUPORTE_CLIENTE($usuario->empresa->id);
            $t->titulo = "Envio de campanha boas vindas";
            $t->descricao = "Campanha boas vindas  enviada para o cliente '$this->codigo - $this->razao_social'";
            $t->tipo_entidade_relacionada = 'CLI';
            $t->id_entidade_relacionada = $this->id;

            Sistema::novaTarefaUsuario($con, $t, $usuario);
            
        }
        
        
            
        
        $html = $this->getHtmlBoasVindas($con);

        
        
        $this->empresa->email->enviarEmail($this->email,"Campanha de Boas Vindas ao RTC.",$html);
        
        Sistema::avisoDEVS_MASTER($html);
        
        
            $email = new Email("elias@agrofauna.com.br");
            $this->empresa->email->enviarEmail($email,"Campanha de Boas Vindas ao RTC.",$html);

            $email = new Email("faleconosco@agrofauna.com.br");
            $this->empresa->email->enviarEmail($email,"Campanha de Boas Vindas ao RTC.",$html);
           /*
            $email = new Email("cintia.monteiro@agrofauna.com.br");
            $this->empresa->email->enviarEmail($email,"Campanha de Boas Vindas ao RTC.",$html);

            $email = new Email("tania.dias@agrofauna.com.br");
            $this->empresa->email->enviarEmail($email,"Campanha de Boas Vindas ao RTC.",$html);

            $email = new Email("rodrigo.porto@agrofauna.com.br");
            $this->empresa->email->enviarEmail($email,"Campanha de Boas Vindas ao RTC.",$html); */


        
        $ps = $con->getConexao()->prepare("UPDATE cliente SET boas_vindas_enviada=true WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }

    public function getHtmlBoasVindas($con){

        $banners = $this->empresa->getBanners($con,0,5,"banner.data_inicial <= CURRENT_TIMESTAMP AND banner.data_final >= CURRENT_TIMESTAMP AND banner.tipo=2 AND banner.boas_vindas=true");

        if(count($banners) === 0){

            return "";

        }

        $banner = $banners[rand(0,count($banners)-1)];
       
        $html = $banner->getHTML($this);
        return $html;
        
    }

    public function getLimiteCredito() {

        $agora = round(microtime(true) * 1000);

        if ($this->inicio_limite <= $agora && $this->termino_limite >= $agora) {

            return $this->limite_credito;
        }

        return -1;
    }

    public function getPrecoEspecial($con) {
        $ps = $con->getConexao()->prepare("SELECT valor FROM preco_especial WHERE id_cliente=$this->id");
        $ps->execute();
        $ps->bind_result($valor);
        if ($ps->fetch()) {
            $ps->close();

            return $valor;
        }
        $ps->close();

        return -1;
    }

    public function getDividas($con,$vencida_quantos_dias=-1) {

        $valor = 0;

        $ps = $con->getConexao()->prepare("SELECT vencimento.valor-SUM(IFNULL(movimento.valor,0)) "
                . "FROM nota "
                . "INNER JOIN vencimento ON vencimento.id_nota=nota.id "
                . "LEFT JOIN movimento ON movimento.id_vencimento=vencimento.id "
                . "WHERE nota.cancelada=false "
                . "AND nota.excluida=false "
                . "AND nota.saida=true "
                . "AND nota.id_cliente=$this->id".($vencida_quantos_dias>=0?" AND vencimento.data < DATE_SUB(CURRENT_DATE,INTERVAL $vencida_quantos_dias DAY)":"")." GROUP BY vencimento.id");
        $ps->execute();
        $ps->bind_result($divida);
        while ($ps->fetch()) {

            $valor += $divida;
        }
        $ps->close();

        return $valor;
    }

    public function setCategoriasProspeccao($con, $categorias) {

        $ps = $con->getConexao()->prepare("DELETE FROM cliente_categoria_prospeccao WHERE id_cliente=$this->id");
        $ps->execute();
        $ps->close();


        foreach ($categorias as $key => $value) {

            $ps = $con->getConexao()->prepare("INSERT INTO cliente_categoria_prospeccao(id_categoria,id_cliente) VALUES($value->id,$this->id)");
            $ps->execute();
            $ps->close();
        }
    }

    public function getCategoriasProspeccao($con) {

        $categorias = array();
        $ps = $con->getConexao()->prepare("SELECT c.id,c.nome FROM categoria_prospeccao c INNER JOIN cliente_categoria_prospeccao cc ON c.id=cc.id_categoria AND cc.id_cliente=$this->id");
        $ps->execute();
        $ps->bind_result($id, $nome);

        while ($ps->fetch()) {

            $cat = new CategoriaProspeccao();
            $cat->id = $id;
            $cat->nome = $nome;
            $categorias[] = $cat;
        }

        $ps->close();

        return $categorias;
    }

    public function resetCredito($con){

        $ps = $con->getConexao()->prepare("UPDATE cliente SET inicio_limite=DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 1 HOUR),termino_limite=DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 1 HOUR),limite_credito=0 WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }

    public function merge($con) {

        $this->categoria->merge($con);

        if ($this->codigo === 0) {

            $ps = $con->getConexao()->prepare("SELECT IFNULL(MAX(codigo)+1,0) FROM cliente WHERE id_empresa=" . $this->empresa->id);
            $ps->execute();
            $ps->bind_result($idn);

            if ($ps->fetch()) {

                $this->codigo = $idn;
            }

            $ps->close();
        }

        if ($this->id == 0) {
            $ps = $con->getConexao()->prepare("INSERT INTO cliente(razao_social,nome_fantasia,limite_credito,inicio_limite,termino_limite,pessoa_fisica,cpf,rg,cnpj,excluido,id_categoria,id_empresa,inscricao_estadual,suframado,inscricao_suframa,codigo,classe_virtual,cobranca_emocional,emite_receita,id_vendedor,historico_habilitado,observacao) VALUES('" . addslashes($this->razao_social) . "','" . addslashes($this->nome_fantasia) . "','$this->limite_credito',FROM_UNIXTIME($this->inicio_limite/1000),FROM_UNIXTIME($this->termino_limite/1000)," . ($this->pessoa_fisica ? "true" : "false") . ",'" . addslashes($this->cpf->valor) . "','" . addslashes($this->rg->valor) . "','" . $this->cnpj->valor . "',false," . $this->categoria->id . "," . $this->empresa->id . ",'$this->inscricao_estadual'," . ($this->suframado ? "true" : "false") . ",'" . addslashes($this->inscricao_suframa) . "',$this->codigo,$this->classe_virtual,".($this->cobranca_emocional?"true":"false").",".($this->emite_receita?"true":"false").",$this->id_vendedor,".($this->historico_habilitado?"true":"false").",'".addslashes($this->observacao)."')");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE cliente SET razao_social='" . addslashes($this->razao_social) . "', nome_fantasia='" . addslashes($this->nome_fantasia) . "', limite_credito=$this->limite_credito, inicio_limite=FROM_UNIXTIME($this->inicio_limite/1000), termino_limite=FROM_UNIXTIME($this->termino_limite/1000), pessoa_fisica=" . ($this->pessoa_fisica ? "true" : "false") . ", cpf='" . addslashes($this->cpf->valor) . "', rg='" . addslashes($this->rg->valor) . "', cnpj='" . addslashes($this->cnpj->valor) . "', excluido= false, id_categoria=" . $this->categoria->id . ", id_empresa=" . $this->empresa->id . ", inscricao_estadual='" . addslashes($this->inscricao_estadual) . "',suframado=" . ($this->suframado ? "true" : "false") . ", inscricao_suframa='$this->inscricao_suframa', codigo=$this->codigo,classe_virtual=$this->classe_virtual, cobranca_emocional=".($this->cobranca_emocional?"true":"false").",emite_receita=".($this->emite_receita?"true":"false").",id_vendedor=$this->id_vendedor,preferencial_mix=".($this->preferencial_mix?"true":"false").",super_mix=".($this->super_mix?"true":"false").",historico_habilitado=".($this->historico_habilitado?"true":"false").",observacao='".addslashes($this->observacao)."' WHERE id = " . $this->id);
            $ps->execute();
            $ps->close();

            if ($this->getLimiteCredito() !== $this->limite_credito) {
                $ps = $con->getConexao()->prepare("UPDATE cliente SET limite_credito=$this->limite_credito, inicio_limite=CURRENT_DATE,termino_limite=DATE_ADD(CURRENT_DATE,INTERVAL 10 DAY) WHERE id = " . $this->id);
                $ps->execute();
                $ps->close();
            }
        }

         if($this->produtos != null){

            $ps = $con->getConexao()->prepare("DELETE FROM produto_cliente WHERE id_cliente=$this->id");
            $ps->execute();
            $ps->close();

            foreach ($this->produtos as $pid => $prod) {
                
                $ps = $con->getConexao()->prepare("INSERT INTO produto_cliente(id_produto,id_cliente) VALUES($prod->id,$this->id)");
                $ps->execute();
                $ps->close();

            }

        }

         if($this->produtos_cliente != null){

            $atuais = array();

            $ps = $con->getConexao()->prepare("SELECT id FROM promocao_cliente WHERE id_cliente=$this->id");
            $ps->execute();
            $ps->bind_result($id);
            while($ps->fetch()){

                $p = new ProdutoCliente();
                $p->id = $id;
                $atuais[] = $p;

            }
            $ps->close();

            foreach ($atuais as $key => $value) {
            
                foreach ($this->produtos_cliente as $key2 => $value2) {
                    
                    if($value->id === $value2->id)
                        continue 2;

                }

                $value->delete($con);

            }

            foreach ($this->produtos_cliente as $key => $value) {

                $value->merge($con);

            }

        }

        if ($this->codigo_contimatic > 0) {

            $ps = $con->getConexao()->prepare("UPDATE cliente SET codigo_contimatic=$this->codigo_contimatic WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }

        $this->endereco->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE endereco SET tipo_entidade='CLI', id_entidade=$this->id WHERE id=" . $this->endereco->id);
        $ps->execute();
        $ps->close();

        $this->email->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE email SET tipo_entidade='CLI', id_entidade=$this->id WHERE id=" . $this->email->id);
        $ps->execute();
        $ps->close();


        $tels = array();
        $ps = $con->getConexao()->prepare("SELECT id,numero FROM telefone WHERE tipo_entidade='CLI' AND id_entidade=$this->id AND excluido=false");
        $ps->execute();
        $ps->bind_result($idt, $numerot);
        while ($ps->fetch()) {
            $t = new Telefone($numerot);
            $t->id = $idt;
            $tels[] = $t;
        }

        foreach ($tels as $key => $value) {

            foreach ($this->telefones as $key2 => $value2) {

                if ($value->id == $value2->id) {

                    continue 2;
                }
            }

            $value->delete($con);
        }

        foreach ($this->telefones as $key => $value) {

            $value->merge($con);

            $ps = $con->getConexao()->prepare("UPDATE telefone SET tipo_entidade='CLI', id_entidade=$this->id WHERE id=" . $value->id);
            $ps->execute();
            $ps->close();
        }
    }

    public function setDocumentos($docs, $con) {

        $ps = $con->getConexao()->prepare("UPDATE documento SET id_entidade=0 WHERE tipo_entidade='CLI' AND id_entidade=$this->id");
        $ps->execute();
        $ps->close();

        foreach ($docs as $key => $doc) {

            $doc->merge($con);

            $ps = $con->getConexao()->prepare("UPDATE documento SET tipo_entidade='CLI', id_entidade=$this->id WHERE id=$doc->id");
            $ps->execute();
            $ps->close();
        }
    }

    public function getDocumentos($con) {

        $categorias_documento = Sistema::getCategoriaDocumentos();

        $docs = array();

        $ps = $con->getConexao()->prepare("SELECT id,UNIX_TIMESTAMP(data_insercao)*1000,id_categoria,numero,link FROM documento WHERE tipo_entidade='CLI' AND id_entidade=$this->id AND excluido=false");
        $ps->execute();
        $ps->bind_result($id, $data, $id_categoria, $numero, $link);

        while ($ps->fetch()) {

            $d = new Documento();

            $d->id = $id;
            $d->data_insercao = $data;
            $d->numero = $numero;
            $d->link = $link;

            foreach ($categorias_documento as $key => $value) {
                if ($value->id == $id_categoria) {

                    $d->categoria = $value;

                    $docs[] = $d;

                    continue 2;
                }
            }
        }

        $ps->close();

        return $docs;
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("UPDATE cliente SET excluido=true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
