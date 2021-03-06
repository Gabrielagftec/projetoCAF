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
class Empresa {

    public static function CF_SEM_CARGO($emp) {
        return new CargoFixo(0, "Sem cargo", $emp);
    }

    public static function CF_DIRETOR($emp) {
        return new CargoFixo(1, "Diretor", $emp);
    }

    public static function CF_FAXINEIRA($emp) {
        return new CargoFixo(2, "Faxineira", $emp);
    }

    public static function CF_PORTEIRO($emp) {
        return new CargoFixo(3, "Porteiro", $emp);
    }

    public static function CF_ESTAGIARIO_TI($emp) {
        return new CargoFixo(4, "Estagiario de TI", $emp);
    }

    public static function CF_ESTAGIARIO_LOGISTICA($emp) {
        return new CargoFixo(5, "Estagiario de Logistica", $emp);
    }

    public static function CF_AUXILIAR_ADM($emp) {
        return new CargoFixo(6, "Auxiliar Administrativo", $emp);
    }

    public static function CF_ENCARREGADO_LOGISTICA($emp) {
        return new CargoFixo(7, "Encarregado de Logistica", $emp);
    }

    public static function CF_COORDENADOR_LOGISTICA($emp) {
        return new CargoFixo(8, "Coordenador de Logistica", $emp);
    }

    public static function CF_SUPERVISOR_LOGISTICA($emp) {
        return new CargoFixo(9, "Supervisor de Logistica", $emp);
    }

    public static function CF_FINANCEIRO($emp) {
        return new CargoFixo(10, "Financeiro", $emp);
    }

    public static function CF_SEPARADOR($emp) {
        return new CargoFixo(11, "Separador", $emp);
    }

    public static function CF_FATURISTA($emp) {
        return new CargoFixo(12, "Faturista", $emp);
    }

    public static function CF_ASSISTENTE_COMPRAS($emp) {
        return new CargoFixo(51, "Assistente Compras", $emp);
    }

    public static function CF_CONTADOR($emp) {
        return new CargoFixo(237, "Contador", $emp);
    }

    public static function CF_VENDEDOR($emp) {
        return new CargoFixo(297, "Vendedor", $emp);
    }
    
    
    public $id;
    public $nome;
    public $email;
    public $telefone;
    public $endereco;
    public $cnpj;
    public $excluida;
    public $consigna;
    public $aceitou_contrato;
    public $juros_mensal;
    public $inscricao_estadual;
    public $tipo_empresa;
    public $tem_suframa;
    public $permissoes_especiais;
    public $cargos_fixos;
    public $tarefas_fixas;
    public $observacao_padrao_nota;
    public $fornecedor_virtual;

    public function inserirElementosKim($con,$elementos){
        
        foreach($elementos as $key=>$value){
            
            $ps = $con->getConexao()->prepare("INSERT INTO modelos_kim(hash,numeroElemento,classe,atributo,kimpath) VALUES($value->hash,$value->numeroElemento,'$value->classe','$value->atributo','$value->kimpath')");
            $ps->execute();
            $ps->close();
            
        }
        
    }

    //SELECT cast(l.data as date),l.servidor,e.status,COUNT(*) FROM LOGS_EMAIL.Envio e INNER JOIN LOGS_EMAIL.Log l ON e.log_id=l.id GROUP BY cast(l.data as date),l.servidor,e.status;

    public function getLogsServidor($con,$data=""){

        $sql = "SELECT UNIX_TIMESTAMP(cast(l.data as date))*1000,l.servidor,e.status,e.numero,COUNT(*) FROM LOGS2_EMAIL.Envio e INNER JOIN LOGS2_EMAIL.Log l ON e.log_id=l.id WHERE e.id>=0 AND cast(l.data as date) NOT IN ('2019-10-10')";

        if($data != ""){

            $sql .= " AND cast(l.data as date)=cast(FROM_UNIXTIME($data/1000) as date)";

        }

        $sql .= " GROUP BY cast(l.data as date),l.servidor,e.status,e.numero ORDER BY l.servidor,e.status,e.numero DESC";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();  
        $ps->bind_result($momento,$servidor,$status,$numero,$qtd);

        $r = array();


        $bs = array();

        while($ps->fetch()){

            $md = md5($momento);

            if(!isset($r[$md])){

                $s = new stdClass();
                $s->momento = $momento;
                $s->dados = array();

                $r[$md] = $s;

            }
            
            $dado = new stdClass();
            

                if(!isset($bs[$servidor])){

                    $bs[$servidor] = 0;

                }

            if($status === 0){

                $dado->titulo = "SUCESSOS $servidor";

            }else{

                $st = "(".$numero.")";

                if($numero == 0){

                    $st = "";

                }

                $dado->titulo = "FALHAS $st $servidor";

            }

            $bs[$servidor] += $qtd;
            $dado->servidor = $servidor;
            $dado->quantidade = $qtd;
            $r[$md]->dados[] = $dado;
    
        }

        $ps->close();

        $ret = array();

        foreach($r as $key=>$value){
            
            foreach($value->dados as $key2=>$value2){

                $value2->total = $bs[$value2->servidor];

            }
 
            $ret[] = $value;
        }

        for ($i = 1; $i < count($ret); $i++) {
            for ($j = $i; $j > 0 && $ret[$j]->momento > $ret[$j - 1]->momento; $j--) {
                $k = $ret[$j];
                $ret[$j] = $ret[$j - 1];
                $ret[$j - 1] = $k;
            }
        }


        return $ret;

    }

    public function getEnviosEmails($con,$data=""){

        $d = array("Envios de Campanha","Abertura Email de Campanha","Envios de Campanha de Boas Vindas","Abertura de Boas Vindas","Cobranca Emocional","Abertura Cobranca Emocional");


        $sql = "SELECT UNIX_TIMESTAMP(cast(k.momento as date))*1000,k.tipo,COUNT(*)+(CASE WHEN cast(k.momento as date) IN ('2019-10-01') AND k.tipo=0 THEN 3000 ELSE 0 END)+(CASE WHEN cast(k.momento as date) IN ('2019-10-02') AND k.tipo=0 THEN 5200 ELSE 0 END)+(CASE WHEN cast(k.momento as date) IN ('2019-10-02') AND k.tipo=2 THEN 2200 ELSE 0 END),k.origem_disparo FROM (SELECT * FROM gerenciador_email GROUP BY id_cliente,origem_disparo,tipo) k WHERE k.momento>=DATE_SUB(CURRENT_DATE,INTERVAL 5 DAY) AND cast(k.momento as date) NOT IN ('2019-10-10') AND k.momento>='2019-09-27' AND k.origem_disparo NOT IN ('8dd3e78b-cbb5-4d1c-84ad-4f8bea0bdab4','00a2b926-a099-4c7d-8804-48ce5268a62f') ";

        if($data != ""){

            $sql .= " AND cast(k.momento as date)=cast(FROM_UNIXTIME($data/1000) as date)";

        }

        $sql .= " GROUP BY k.origem_disparo,k.tipo";


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();  
        $ps->bind_result($momento,$tipo,$qtd,$hasher);

        $r = array();

        while($ps->fetch()){

            $md = md5($hasher."");

            if(!isset($r[$md])){

                $s = new stdClass();
                $s->momento = $momento;
                $s->dados = array();
                $s->quantidade = 0;

                $r[$md] = $s;

            }
            
            $dado = new stdClass();
            $dado->titulo = $d[$tipo];
            $dado->quantidade = $qtd;
            $r[$md]->dados[] = $dado;
            $r[$md]->quantidade += $qtd;
    
        }

        $ps->close();

        $ret = array();

        foreach($r as $key=>$value){
            $ret[] = $value;
        }

        for ($i = 1; $i < count($ret); $i++) {
            for ($j = $i; $j > 0 && $ret[$j]->quantidade > $ret[$j - 1]->quantidade; $j--) {
                $k = $ret[$j];
                $ret[$j] = $ret[$j - 1];
                $ret[$j - 1] = $k;
            }
        }


        return $ret;

    }
    
    public function recusarSolicitacaoKim($con,$hash){
        
        $ps = $con->getConexao()->prepare("UPDATE pedido_modelo_kim SET recusada=true WHERE hash=". addslashes($hash.""));
        $ps->execute(); 
        $ps->close();
        
    }
    
    public function getInicioFimCampanha($con){

        $dados = array();

        $ps = $con->getConexao()->prepare("SELECT nome,MAX(UNIX_TIMESTAMP(inicio))*1000,MAX(UNIX_TIMESTAMP(fim))*1000 FROM campanha WHERE id_empresa=$this->id AND inicio<fim GROUP BY nome");
        $ps->execute();
        $ps->bind_result($nome,$inicio,$fim);
        while($ps->fetch()){

            $i = $inicio;
            $f = $fim;

            while(intval(date("Y",$f/1000))>intval(date("Y",$i/1000))){
                $f -= 30*24*3600000;
            }

            while(intval(date("m",$f/1000))>intval(date("m",$i/1000))){
                $f -= 24*3600000;
            }

            while(intval(date("d",$f/1000))>intval(date("d",$i/1000))){
                $f -= 600000;
            }

            $dados[] = array($nome,$i,$f);
        
        }
        $ps->close();

        return $dados;

    }

    public function getCountCTE($con,$filtro=""){

        $sql = "SELECT COUNT(*) FROM xml_cte xml 
            INNER JOIN empresa e ON e.id=xml.id_empresa 
            WHERE (xml.id_empresa = $this->id OR xml.id_empresa IN (SELECT id FROM empresa WHERE empresa_adm=$this->id))";

        if($filtro !== ""){

            $sql .= " AND $filtro";

        }

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($qtd);

        if($ps->fetch()){

            $ps->close();

            return $qtd;

        }

        $ps->close();

        return 0;

    }

    public function getCTE($con,$x1,$x2,$filtro="",$ordem=""){


        $sql = "SELECT xml.id,xml.xml,xml.chave,UNIX_TIMESTAMP(xml.data_emissao)*1000,xml.chave_nota,e.id,e.nome FROM xml_cte xml 
            INNER JOIN empresa e ON e.id=xml.id_empresa
            WHERE (xml.id_empresa = $this->id OR xml.id_empresa IN (SELECT id FROM empresa WHERE empresa_adm=$this->id))";

        if($filtro !== ""){

            $sql .= " AND $filtro";

        }

        $sql .= " ORDER BY xml.data_emissao DESC";

        $sql .= " LIMIT $x1, ".($x2-$x1);

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($id,$xml,$chave,$data,$chave_nota,$ide,$nomee);

        $notas = array();

        while($ps->fetch()){

            $n = new stdClass();

            $n->id = $id;
            $n->xml = Utilidades::base64encode(str_replace(array("&asp"), array('"'), $xml));
            $n->chave = $chave;
            $n->chave_nota = $chave_nota;
            $n->data = $data;
            $n->empresa = new stdClass();
            $n->empresa->id = $ide;
            $n->empresa->nome = $nomee;

            $notas[] = $n;

        }

        $ps->close();

        return $notas;

    }
    
    public function getCountNFE($con,$filtro=""){

        $sql = "SELECT COUNT(*) FROM (SELECT * FROM xml_nota GROUP BY nsu) xml 
            INNER JOIN empresa e ON e.id=xml.id_empresa 
            WHERE (xml.id_empresa = $this->id OR xml.id_empresa IN (SELECT id FROM empresa WHERE empresa_adm=$this->id))";

        if($filtro !== ""){

            $sql .= " AND $filtro";

        }

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($qtd);

        if($ps->fetch()){

            $ps->close();

            return $qtd;

        }

        $ps->close();

        return 0;

    }

    public function getNFE($con,$x1,$x2,$filtro="",$ordem=""){


        $sql = "SELECT xml.id,xml.xml,xml.chave,UNIX_TIMESTAMP(xml.data_emissao)*1000,e.id,e.nome FROM (SELECT * FROM xml_nota GROUP BY nsu) xml 
            INNER JOIN empresa e ON e.id=xml.id_empresa 
            WHERE (xml.id_empresa = $this->id OR xml.id_empresa IN (SELECT id FROM empresa WHERE empresa_adm=$this->id))";

        if($filtro !== ""){

            $sql .= " AND $filtro";

        }

        if($ordem !== ""){

            $sql .= " ORDER BY $ordem";

        }else{

            $sql .= " ORDER BY xml.data DESC";

        }

        $sql .= " LIMIT $x1, ".($x2-$x1);

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($id,$xml,$chave,$data,$ide,$nomee);

        $notas = array();

        while($ps->fetch()){

            $n = new stdClass();

            $n->id = $id;
            $n->xml = Utilidades::base64encode(str_replace(array("&asp"), array('"'), $xml));
            $n->chave = $chave;
            $n->data = $data;
            $n->empresa = new stdClass();
            $n->empresa->id = $ide;
            $n->empresa->nome = $nomee;

            $notas[] = $n;

        }

        $ps->close();

        return $notas;

    }

    public function alterarRaizRobo($raiz,$con){

        if($raiz->id === 0){

            $ps = $con->getConexao()->prepare("INSERT INTO log_robo(numero,pai,nome,id_empresa) VALUES($raiz->numero,$raiz->pai,'".addslashes($raiz->nome)."',$this->id)");
            $ps->execute();
            $raiz->id = $ps->insert_id;
            $ps->close();

        }else{

            $ps = $con->getConexao()->prepare("UPDATE log_robo SET numero=$raiz->numero,pai=$raiz->pai,nome='".addslashes($raiz->nome)."',id_empresa=$this->id WHERE id=$raiz->id");
            $ps->execute();
            $ps->close();

        }

        foreach($raiz->filhos as $key=>$value){

            $value->pai = $raiz->id;

            $this->alterarRaizRobo($value,$con);

        }

    }

    public function getLogsRobo($con){

        $l = array();

        $ps=$con->getConexao()->prepare("SELECT id,numero,pai,nome FROM log_robo WHERE id_empresa=$this->id");
        $ps->execute();
        $ps->bind_result($id,$numero,$pai,$nome);

        while($ps->fetch()){

            $i = new stdClass();
            $i->id = $id;
            $i->numero = $numero;
            $i->pai = $pai;
            $i->nome = $nome;
            $i->exibe_abaixo=false;
            $i->filhos = array();

            $l[$id] = $i;

        }

        $ps->close();

        $r = null;

        foreach($l as $key=>$value){

            if($value->pai === -1){

                $r = $value;
                continue;

            }else{

                $l[$value->pai]->filhos[] = $value;

            }

        }

        if($r == null){

            $r = new stdClass();
            $r->id = 0;
            $r->numero = 0;
            $r->pai = -1;
            $r->nome = "RAIZ";
            $r->filhos = array();

        }

        $r->exibe_abaixo=true;

        return $r;

    }

    function __construct($id = 0, $cf = null) {

        $this->id = $id;
        $this->email = null;
        $this->telefone = null;
        $this->endereco = null;
        $this->rtc = null;
        $this->email = null;
        $this->excluida = false;
        $this->cnpj = new CNPJ("");
        $this->aceitou_contrato = 0;
        $this->consigna = false;
        $this->juros_mensal = 0;
        $this->telefone = new Telefone();
        $this->email = new Email();
        $this->endereco = new Endereco();
        $this->tipo_empresa = false;
        $this->permissoes_especiais = array();
        $this->observacao_padrao_nota = "";
        $this->fornecedor_virtual = 0;
        $this->cargos_fixos = array(
            Empresa::CF_SEM_CARGO($this),
            Empresa::CF_DIRETOR($this),
            Empresa::CF_FAXINEIRA($this),
            Empresa::CF_PORTEIRO($this),
            Empresa::CF_ESTAGIARIO_TI($this),
            Empresa::CF_ESTAGIARIO_LOGISTICA($this),
            Empresa::CF_AUXILIAR_ADM($this),
            Empresa::CF_ENCARREGADO_LOGISTICA($this),
            Empresa::CF_COORDENADOR_LOGISTICA($this),
            Empresa::CF_SUPERVISOR_LOGISTICA($this),
            Empresa::CF_FINANCEIRO($this),
            Empresa::CF_SEPARADOR($this),
            Empresa::CF_FATURISTA($this),
            Empresa::CF_ASSISTENTE_COMPRAS($this),
            Empresa::CF_CONTADOR($this),
            Empresa::CF_VENDEDOR($this)
        );
        $this->tarefas_fixas = array(
            "TT_ATIVIDADE_COMUM",
            "TT_COMPRA",
            "TT_ANALISE_CREDITO",
            "TT_CONFIRMACAO_PAGAMENTO",
            "TT_FATURAMENTO",
            "TT_RASTREIO",
            "TT_SEPARACAO",
            "TT_SOLICITACAO_COLETA",
            "TT_COTACAO",
            "TT_REVISAO_PEDIDO",
            "TT_MOVIMENTACAO_ARMAZEM",
            "TT_VERIFICA_NOTA"
        );

        if ($id > 0 && $cf !== null) {

            $ps = $cf->getConexao()->prepare("SELECT empresa.nome,empresa.cnpj,endereco.id,endereco.rua,endereco.bairro,endereco.cep,endereco.numero,cidade.id,cidade.nome,estado.id,estado.sigla,empresa.inscricao_estadual,empresa.juros_mensal FROM empresa INNER JOIN endereco ON endereco.id_entidade=empresa.id AND endereco.tipo_entidade='EMP' INNER JOIN cidade ON cidade.id=endereco.id_cidade INNER JOIN estado ON estado.id=cidade.id_estado WHERE empresa.id=$id");
            $ps->execute();
            $ps->bind_result($nome, $cnpj, $end_id, $end_rua, $end_bairro, $end_cep, $end_num, $cid_id, $cid_nom, $est_id, $est_sg, $emp_ie, $emp_jm);
            if ($ps->fetch()) {
                $this->nome = $nome;
                $this->cnpj = new CNPJ($cnpj);
                $this->inscricao_estadual = $emp_ie;
                $this->juros_mensal = $emp_jm;
                $endereco = new Endereco();
                $endereco->id = $end_id;
                $endereco->rua = $end_rua;
                $endereco->bairro = $end_bairro;
                $endereco->cep = new CEP($end_cep);
                $endereco->numero = $end_num;

                $cid = new Cidade();
                $cid->id = $cid_id;
                $cid->nome = $cid_nom;

                $est = new Estado();
                $est->id = $est_id;
                $est->sigla = $est_sg;
                $cid->estado = $est;
                $endereco->cidade = $cid;

                $this->endereco = $endereco;
            }
            $ps->close();

            $ps = $cf->getConexao()->prepare("SELECT id,endereco,senha FROM email WHERE tipo_entidade='EMP' AND id_entidade=$this->id");
            $ps->execute();
            $ps->bind_result($id, $endereco, $senha);
            if ($ps->fetch()) {

                $em = new Email($endereco);
                $em->id = $id;
                $em->senha = $senha;
                $this->email = $em;
            }
            $ps->close();

            $ps = $cf->getConexao()->prepare("SELECT id,numero FROM telefone WHERE tipo_entidade='EMP' AND id_entidade=$this->id");
            $ps->execute();
            $ps->bind_result($id, $numero);
            if ($ps->fetch()) {
                $em = new Telefone($numero);
                $em->id = $id;
                $this->telefone = $em;
            }
            $ps->close();
        }
    }

    public function getProdutosCooperativa($con){
        
        $ses = new SessionManager();

        $emp = $ses->get('usuario');
        
        if($emp==null){
            $emp = new Empresa(1734);
        }else{
            $emp = $emp->empresa;
        }

        $ps = $con->getConexao()->prepare("SELECT p.id,p.nome,p.preco,p.url_encontrado,e.id,e.nome,p.fornecedor FROM produto_cooperativa p INNER JOIN empresas_kim e ON e.id=p.id_empresa AND e.id_empresa=$emp->id ORDER BY e.qualidade DESC LIMIT 0,1000");
        $ps->execute();
        $ps->bind_result($id,$nome,$preco,$url,$ide,$nomee,$fornecedor);
        
        $produtos = array();
        
        while($ps->fetch()){
            
            $s = new stdClass();
            $s->id = $id;
            $s->nome = str_replace(array("'",'"'), array("",""), $nome);
            $s->preco = $preco;
            $s->url = $url;
            $s->fornecedor = $fornecedor;
            
            $e = new stdClass();
            $e->id = $ide;
            $e->nome = $nomee;
            
            $s->empresa = $e;
            
            $produtos[] = $s;
            
        }
    
        $ps->close();
        
        return $produtos;
        
    }

    public function inserirBusca($con,$nome){

        $sql = "INSERT INTO produto_sujo(nome,quantidade_buscas,priorizado,id_empresa) VALUES('".addslashes($nome)."',0,false,$this->id)";
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->close();
    }
    
    public function getBuscas($con){

        $sql = "SELECT id,nome,quantidade_buscas,priorizado FROM produto_sujo WHERE id_empresa = $this->id";
        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($id,$nome,$quantidade,$priorizado);

        $r = array();

        while($ps->fetch()){

            $s = new stdClass();
            $s->id = $id;
            $s->nome = $nome;
            $s->quantidade = $quantidade;
            $s->priorizado = $priorizado;

            $r[] = $s;

        }

        $ps->close();

        return $r;

    }

    public function getCountProdutosKim($con,$filtro=""){
        
        $sql = "SELECT COUNT(*) FROM (SELECT * FROM itens_kim_go GROUP BY nome,id_empresa) p WHERE p.id_empresa=$this->id AND p.id>0";
        
        if($filtro !== ""){
            
            $sql .= " AND $filtro";
            
        }
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);
        
        if($ps->fetch()){
            
            $ps->close();
            
            return $qtd;
            
        }
        
        $ps->close();
        
        return 0;
        
    }
    
    public function getProdutosKim($con,$x1,$x2,$filtro="",$ordem=""){
        
        $sql = "SELECT p.id,p.nome,p.empresa,p.preco,'',p.link,p.classe FROM itens_kim_go p WHERE p.id_empresa=$this->id AND p.id>0";
        
        if($filtro !== ""){
            
            $sql .= " AND $filtro";
            
        }

        if($ordem !== ""){
            
            $sql .= " ORDER BY $ordem";
            
        }

        $ret = array();

  
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id,$nome,$empresa,$preco,$imagem,$url,$classe);
        
        $produtos = array();
        
        $i=0;

        while($ps->fetch()){
            
            $hash = md5($nome);

                $p = new ProdutoRobo();
                
                $p->id = $id;
                $p->nome = addslashes(str_replace(array("\n",'"',"'","/","?"), array("<br>","","","",""),$nome));
                $p->empresa = addslashes(str_replace(array("\n",'"',"'","/","?"), array("<br>","","","",""),$empresa));
                $p->preco = $preco;
                $p->imagem = $imagem;
                $p->url = $url;
                $p->classe = $classe;
                $p->produtos = array();

            if(!isset($produtos[$hash])){

                $produtos[$hash] = $p;


            }else{

                $produtos[$hash]->produtos[] = $p;

            }
            
        }
        
        $ps->close();

        $i = 0;
        foreach($produtos as $key=>$value){

        	if($i>=$x1){
        		$ret[] = $value;
        	}

        	if($i>=$x2)
        		break;

        	$i++;
        }
        
        return $ret;
        
    }
    
    public function getSolicitacoesKim($con){

        $ses = new SessionManager();

        $emp = $ses->get('usuario');
        
        if($emp==null){
            $emp = new Empresa(1734);
        }else{
            $emp = $emp->empresa;
        }
        
        $solicitacoes = array();
        
        $ps = $con->getConexao()->prepare("SELECT s.id,s.hash,s.recusada,s.matches,MAX(p.id) IS NOT NULL,emp.nome FROM pedido_modelo_kim s LEFT JOIN modelos_kim p ON p.hash=s.hash INNER JOIN empresas_kim emp ON emp.id=s.id_empresa AND emp.id_empresa=$emp->id GROUP BY s.id");
        $ps->execute();
        $ps->bind_result($id,$hash,$recusada,$matches,$atendida,$nome_empresa);
    
        while($ps->fetch()){
            
            $s = new SolicitacaoKim();
            $s->id = $id;
            $s->hash = $hash;
            $s->nome_empresa = $nome_empresa;
            $s->recusada = $recusada == 1;
            $s->matches = $matches;
            $s->atendida = $atendida == 1;
            
            $solicitacoes[] = $s;
            
        }
        
        return $solicitacoes;
        
    }

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
                . "WHERE produto.id_empresa = $this->id AND produto.excluido = false AND produto.id_logistica=0 AND produto.sistema_lotes=true AND produto.id_categoria IN $categorias GROUP BY pe.id,pp.id_produto";


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

    public function setContrato($con){

        $ps = $con->getConexao()->prepare("UPDATE empresa SET aceitou_contrato=$this->aceitou_contrato WHERE id=$this->id");
        $ps->execute();
        $ps->close();
        
    }

    public function getArmazens($con){
        
        $armazens = array();


        $sql = "SELECT "
                . "a.id,"
                . "a.comprimento,"
                . "a.largura,"
                . "p.id,"
                . "p.largura_posicao,"
                . "p.comprimento_posicao,"
                . "p.altura_posicao,"
                . "p.largura,"
                . "p.comprimento,"
                . "p.altura,"
                . "p.x,"
                . "p.y,"
                . "p.rua_inicial,"
                . "p.numero_inicial,"
                . "p.inflamavel,"
                . "t.id,"
                . "t.x_inicial,"
                . "t.y_inicial,"
                . "t.z_inicial,"
                . "t.x_final,"
                . "t.y_final,"
                . "t.z_final "
                . "FROM "
                . "armazen a "
                . "LEFT JOIN porta_palet p ON p.id_armazen=a.id "
                . "LEFT JOIN tunel t ON t.id_porta_palet = p.id "
                . "WHERE a.id_empresa =$this->id";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id,$comp,$larg,$pid,$plargp,$pcompp,$paltp,$plarg,$pcomp,$palt,$px,$py,$rua_inicial,$numero_inicial,$inflamavel,$tid,$txi,$tyi,$tzi,$txf,$tyf,$tzf);



        while($ps->fetch()){
            
            if(!isset($armazens[$id])){
                
                $a = new Armazem();
                $a->id = $id;
                $a->comprimento = $comp;
                $a->largura = $larg;
                $a->empresa = $this;
                
                $armazens[$id] = $a;
                
            }
            
            $a = $armazens[$id];
            
            if($pid> 0){
                
                if(!isset($a->porta_palets[$pid])){
                    
                    $pp = new PortaPalet();
                    $pp->id = $pid;
                    $pp->altura_posicao = $paltp;
                    $pp->comprimento_posicao = $pcompp;
                    $pp->largura_posicao = $plargp;
                    $pp->largura = $plarg;
                    $pp->comprimento = $pcomp;
                    $pp->altura = $palt;
                    $pp->x = $px;
                    $pp->y = $py;
                    $pp->armazem = $a;
                    $pp->rua_inicial = $rua_inicial;
                    $pp->numero_inicial = $numero_inicial;
                    $pp->inflamavel = $inflamavel;
                    $a->porta_palets[$pid] = $pp;
                    
                }
                
                $pp = $a->porta_palets[$pid];
                
                if($tid > 0){

                    if(!isset($pp->tuneis[$tid])){
                        
                        $t = new Tunel();
                        $t->id = $tid;
                        $t->porta_palet = $pp;
                        
                        $t->x_inicial = $txi;
                        $t->y_inicial = $tyi;
                        $t->z_inicial = $tzi;
                        
                        $t->x_final = $txf;
                        $t->y_final = $tyf;
                        $t->z_final = $tzf;
                        
                        $pp->tuneis[$tid] = $t;
                        
                    }

                }
                
            }
            
        }
        $ps->close();
        
        $ret = array();
        
        foreach($armazens as $ka=>$arm){
            
            $pps = array();
            
            foreach($arm->porta_palets as $kp => $pp){
                
                $ts = array();
                
                foreach($pp->tuneis as $kt=>$tt){
                    $ts[] = $tt;
                }
             
                $pp->tuneis = $ts;
                
                $pps[] = $pp;
                
            }
            
            $arm->porta_palets = $pps;
            
            $ret[] = $arm;
            
        }
        
        return $ret;
        
    }



    public function getBaseRelatorioLucros($con, $inicio, $fim,$icms=true,$juros=true,$frete=true,$ipi=true){


        $categorias = Sistema::getCategoriaProduto($this);

        $ids_categorias = "(-1";

        foreach ($categorias as $key => $categoria) {
            if ($categoria->loja) {
                $ids_categorias .= ",$categoria->id";
            }
        }

        $ids_categorias .= ")";

        $movimentos = $this->getMovimentosProduto($con, "!data_emissao! >= FROM_UNIXTIME($inicio/1000) AND !data_emissao! <= FROM_UNIXTIME($fim/1000) AND pr.id_categoria IN $ids_categorias",$icms,$juros,$frete,$ipi);

        $trilha_entrada = array();

        $ids_produtos = "(-1";

        foreach ($movimentos as $key => $movimento) {

            if ($movimento->influencia_estoque === 0 || $movimento->numero_nota === 0) {

                unset($movimentos[$key]);
                continue;
            }

            if (!isset($trilha_entrada[$movimento->id_produto])) {

                $trilha_entrada[$movimento->id_produto] = array();

                $ids_produtos .= ",$movimento->id_produto";
            }
        }

        $ids_produtos .= ")";

        $custos = array();
        $codigos = array();
        
        $ps = $con->getConexao()->prepare("SELECT id,codigo,custo FROM produto WHERE id IN $ids_produtos");
        $ps->execute();
        $ps->bind_result($id,$codigo, $custo);
        while ($ps->fetch()) {

            $custos[$id] = $custo;
            $codigos[$id] = $codigo;
            
        }
        $ps->close();

        $sql = "SELECT pn.id_produto,SUM(pn.quantidade),pn.valor_unitario "
                . "FROM produto_nota pn "
                . "INNER JOIN nota n ON n.id=pn.id_nota AND n.excluida=false AND n.cancelada=false AND n.id_empresa=$this->id AND n.saida=false "
                . "INNER JOIN fornecedor f ON f.id=n.id_fornecedor "
                . "WHERE pn.id_produto IN $ids_produtos "
                . "GROUP BY pn.id_produto,pn.valor_unitario "
                . "ORDER BY n.data_emissao DESC";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_produto, $quantidade, $valor);
        while ($ps->fetch()) {

            $trilha_entrada[$id_produto][] = array($quantidade, $valor);
        }
        $ps->close();

        $sql1 = "SELECT pn.id_produto,SUM(pn.quantidade) "
                . "FROM produto_nota pn "
                . "INNER JOIN nota n ON n.id=pn.id_nota AND n.excluida=false AND n.cancelada=false AND n.id_empresa=$this->id AND n.saida=true "
                . "INNER JOIN cliente c ON c.id=n.id_cliente "
                . "WHERE pn.id_produto IN $ids_produtos AND n.data_emissao>=FROM_UNIXTIME($fim/1000) "
                . "GROUP BY pn.id_produto "
                . "ORDER BY n.data_emissao DESC";

        $sql2 = "SELECT id,estoque FROM produto WHERE id IN $ids_produtos";

        $sqls = array($sql1, $sql2);

        foreach ($sqls as $key => $sql) {

            $ps = $con->getConexao()->prepare($sql);
            $ps->execute();
            $ps->bind_result($id_produto, $qtd);

            while ($ps->fetch()) {
                if (isset($trilha_entrada[$id_produto])) {

                    $quantidade = $qtd;

                    $i = 0;

                    while ($quantidade > 0 && $i < count($trilha_entrada[$id_produto])) {

                        $trilha_entrada[$id_produto][$i][0] -= $quantidade;

                        if ($trilha_entrada[$id_produto][$i][0] < 0) {

                            $quantidade = -1 * $trilha_entrada[$id_produto][$i][0];
                            $trilha_entrada[$id_produto][$i][0] = 0;
                            $i++;
                        } else {

                            $quantidade = 0;
                        }
                    }
                }
            }
            $ps->close();
        }

        $ids_fichas = "(-1";

        $chaves = array();

        foreach ($movimentos as $key => $movimento) {

            if (!isset($chaves[$movimento->ficha])) {

                $ids_fichas .= ",$movimento->ficha";

                $chaves[$movimento->ficha] = "SEM CHAVE";
            }
        }

        $ids_fichas .= ")";

        $ps = $con->getConexao()->prepare("SELECT ficha,chave FROM nota WHERE ficha IN $ids_fichas AND id_empresa=$this->id AND chave <> ''");
        $ps->execute();
        $ps->bind_result($ficha, $chave);
        while ($ps->fetch()) {

            $chaves[$ficha] = $chave;
        }
        $ps->close();

        $notas = array();
        
        
        $produtos_relatorio = array();

        foreach ($movimentos as $key => $movimento) {

            if(!isset($produtos_relatorio[$movimento->id_produto])){
                
                $p = new ProdutoRelatorioLucro();
                $p->codigo = $movimento->id_produto;
                $p->custo_medio = 0;
                $p->lucro_medio = 0;
                $p->preco_medio = 0;
                $p->quantidade_entrada = 0;
                $p->quantidade_notas_entrada = 0;
                $p->quantidade_notas_saida = 0;
                $p->quantidade_saida = 0;
                $p->nome = $movimento->nome_produto;
                $p->notas = array();
                $p->aux = 0;
                
                $produtos_relatorio[$movimento->id_produto] = $p;
                
            }
            
            $prel = $produtos_relatorio[$movimento->id_produto];
            
            $nota = new NotaRelatorioLucro();
            $nota->chave_nota = $chaves[$movimento->ficha];
            $nota->nota = $movimento->numero_nota;
            $nota->quantidade = abs($movimento->influencia_estoque);
            $nota->valor = $movimento->valor_base;
            $nota->tipo = $movimento->influencia_estoque > 0 ? "E" : "S";

            $prel->notas[] = $nota;
            
            if ($nota->tipo === "E") {

                $nota->lucro = 0;
                
                $prel->quantidade_entrada += $nota->quantidade;
                $prel->quantidade_notas_entrada++;
                
            } else {

                
                $quantidade = $nota->quantidade;
                $prel->preco_medio += $quantidade*$nota->valor;
                $prel->quantidade_saida += $quantidade;
                $prel->quantidade_notas_saida++;
                
                
                $composicao_custo = array();

                //-------------

                $i = 0;
                
                $id_produto = $movimento->id_produto;

                while ($quantidade > 0 && $i < count($trilha_entrada[$id_produto])) {

                    $trilha_entrada[$id_produto][$i][0] -= $quantidade;

                    if ($trilha_entrada[$id_produto][$i][0] < 0) {

                        $c = array($quantidade+$trilha_entrada[$id_produto][$i][0],$trilha_entrada[$id_produto][$i][1]);
                        
                        if($c[0] > 0){
                            
                            $composicao_custo[] = $c;
                            
                        }
                        
                        $quantidade = -1 * $trilha_entrada[$id_produto][$i][0];
                        $trilha_entrada[$id_produto][$i][0] = 0;
                        $i++;
                        
                    } else {

                        $c = array($quantidade,$trilha_entrada[$id_produto][$i][1]);
                        
                        if($c[0] > 0){
                            
                            $composicao_custo[] = $c;
                            
                        }
                        
                        $quantidade = 0;
                        
                    }
                }

                if($quantidade > 0 && isset($custos[$id_produto])){
                    
                    $composicao_custo[] = array($quantidade,$custos[$id_produto]);
                    
                }
                
                $custo_medio = 0;
                $aux = 0;
                
                foreach($composicao_custo as $k1=>$comp){
                    
                    $custo_medio += $comp[0]*$comp[1];
                    $aux += $comp[0];
                    
                }
                
                if($aux > 0){
                    
                    $custo_medio /= $aux;
                    
                    $nota->lucro = round((($nota->valor/$custo_medio)-1)*100,2);
                    
                    $prel->custo_medio += $nota->quantidade*$custo_medio;
                    $prel->aux += $nota->quantidade;
                    
                }else{
                    
                    $nota->lucro = 0;
                    
                }
                
                //-------------
                
                
            }
        }
        
        
        $preco_total = 0;
        $custo_total = 0;
        
        $relatorio = new RelatorioLucro();
        
        $relatorio->data_inicial = date("d/m/Y",$inicio/1000);
        $relatorio->data_final = date("d/m/Y",$fim/1000);
        $relatorio->empresa = $this;
        $relatorio->lucro_medio = 0;
        $relatorio->quantidade_entradas = 0;
        $relatorio->quantidade_saidas = 0;
        $relatorio->produtos = array();
        
        foreach($produtos_relatorio as $key=>$value){
            
            if(isset($codigos[$value->codigo])){
                
                $value->codigo = $codigos[$value->codigo];
                
            }
            
            $relatorio->produtos[] = $value;
            
            $relatorio->quantidade_entradas += $value->quantidade_notas_entrada;
            $relatorio->quantidade_saidas += $value->quantidade_notas_saida;
            
            if($value->quantidade_saida > 0){
            
                $value->custo_medio /= $value->aux;
                $value->preco_medio /= $value->quantidade_saida;
                
                $value->preco_medio = round($value->preco_medio,2);
                $value->custo_medio = round($value->custo_medio,2);
                
                $preco_total += $value->preco_medio*$value->quantidade_saida;
                $custo_total += $value->custo_medio*$value->quantidade_saida;
                
                if($value->custo_medio > 0){
                
                    $value->lucro_medio = round((($value->preco_medio/$value->custo_medio)-1)*100,2);

                }else{
                    
                    $value->lucro_medio = 0;
                    
                }
                
            }
            
        }

        if($custo_total > 0){
            
            $relatorio->lucro_medio = round((($preco_total/$custo_total)-1)*100,2);
            
        }

        return $relatorio;


    }
    
    public function getARTS($con){

        $arts = array();

        $ps = $con->getConexao()->prepare("SELECT id,numero,quantidade,ultimo_numero FROM art WHERE id_empresa=$this->id");
        $ps->execute();
        $ps->bind_result($id,$numero,$quantidade,$ultimo_numero);

        while($ps->fetch()){

            $a = new ART();

            $a->id = $id;
            $a->empresa = $this;
            $a->numero = $numero;
            $a->quantidade = $quantidade;
            $a->ultimo_numero = $ultimo_numero;

            $arts[] = $a;

        }

        $ps->close();

        return $arts;


    }

public function getRelatorioLucros($con, $inicio, $fim,$icms=true,$juros=true,$frete=true,$ipi=true) {

        
        $relatorio = $this->getBaseRelatorioLucros($con, $inicio, $fim,$icms,$juros,$frete,$ipi);
        
        //------ geracao do PDF ---------
        
        $code = round(microtime(true)*1000);
        
        $retorno = str_replace("\\", "/", realpath("../uploads")) . "/relatorio_$code.pdf";

        $relatorio->caminho = $retorno;

        $comando = Utilidades::toJson($relatorio);

        $arquivo = "comando_$code.json";

        Sistema::mergeArquivo($arquivo, $comando, false);

        $comando = Sistema::$ENDERECO . "php/uploads/$arquivo";
        
        try {
            
            Sistema::getMicroServicoJava('RelatorioLucros', $comando);
            
        } catch (Exception $ex) {
            
        }
        return Sistema::$ENDERECO . "php/uploads/relatorio_$code.pdf";
        
        //-------------------------------
        
    }

    
    public function getCountTarefasSimplificadas($con,$filtro = "",$orcamento=false){
        
        $sql = "SELECT COUNT(*) FROM "
                . "tarefa_simplificada t INNER JOIN usuario_tarefa_simplificada ut ON ut.id_tarefa=t.id "
                . "INNER JOIN usuario u ON u.id=ut.id_usuario LEFT JOIN cliente c ON c.id=t.id_cliente "
                . "LEFT JOIN email e ON e.tipo_entidade='CLI' AND e.id_entidade=c.id WHERE t.id_empresa=$this->id ";
                
        if($filtro !== ""){
            
            $sql .= "AND $filtro ";
            
        }

        //$sql .= "AND t.orcamento = ".($orcamento?"true":"false")." ";
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);
        
        if($ps->fetch()){
            $ps->close();
            
            return $qtd;
            
        }
        
        $ps->close();
        
        return 0;
        
    }
    
    public function getTarefasSimplificadas($con,$x1,$x2,$filtro="",$ordem="",$orcamento=false){
        
        $sql = "SELECT t.id,t.descricao,UNIX_TIMESTAMP(t.momento)*1000,t.id_tipo_tarefa,"
                . "u.id,u.nome,t.prioridade,u.faixa_salarial,t.orcamento,ut.minutos_orcamento,"
                . "c.id,c.codigo,c.razao_social,c.cnpj,c.id_empresa,e.id,e.endereco FROM "
                . "tarefa_simplificada t INNER JOIN usuario_tarefa_simplificada ut ON ut.id_tarefa=t.id "
                . "INNER JOIN usuario u ON u.id=ut.id_usuario LEFT JOIN cliente c ON c.id=t.id_cliente "
                . "LEFT JOIN email e ON e.tipo_entidade='CLI' AND e.id_entidade=c.id WHERE t.id_empresa=$this->id ";
        
        if($filtro !== ""){
            
            $sql .= "AND $filtro ";
            
        }
        
        //$sql .= "AND t.orcamento = ".($orcamento?"true":"false")." ";
        
        if($ordem !== ""){
            
            $sql .= "ORDER BY $ordem ";
            
        }
        
        $sql .= "LIMIT $x1, ".($x2-$x1);
        
        
        $tarefas = array();
        
        
        $ids_tarefas = "(-1";
        
        $tipos_tarefa = $this->getTiposTarefa($con);
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id,$descricao,$momento,$id_tipo,$id_usuario,$nome_usuario,$prioridade,$faixa_salarial,$orc,$minutos_orcamento,$idc,$codc,$razaoc,$cnpjc,$id_empresac,$id_emailc,$endereco_emailc);
        while($ps->fetch()){
            
            if(!isset($tarefas[$id])){
                
                $t = new TarefaSimplificada();
                $t->id = $id;
                $t->descricao = $descricao;
                $t->momento = $momento;
                $t->empresa = $this;
                $t->prioridade = $prioridade;
                $t->orcamento = $orc == 1;
                $tipo = null;
                
                if($idc !== 0 && $idc !== null){
                    
                    $c = new ClienteReduzido();
                    $c->id = $idc;
                    $c->codigo = $codc;
                    $c->razao_social = $razaoc;
                    $c->cnpj = new CNPJ($cnpjc);
                    $c->id_empresa = $id_empresac;
                    
                    $c->email = new Email($endereco_emailc);
                    $c->email->id = $id_emailc;
                    
                    $t->cliente = $c;
                    
                }
                
                foreach($tipos_tarefa as $key=>$value){
                    if($value->id === $id_tipo){
                        $tipo=$value;
                        break;
                    }
                }
                if($tipo === null){
                    continue;
                }
                
                $t->tipo = $tipo;
                
                $tarefas[$id] = $t;
                
                $k = round((max(1,$t->prioridade)/max(1,$t->tipo->prioridade))*100);
                $t->prioridade_real = $k;
                
                $ids_tarefas .= ",$id";
                
            }
            
            $t = $tarefas[$id];
            
            $usuario = new Usuario();
            $usuario->id = $id_usuario;
            $usuario->faixa_salarial = $faixa_salarial;
            $usuario->nome = $nome_usuario;
            $usuario->minutos_orcamento = $minutos_orcamento;
            
            $t->usuarios[] = $usuario;
            
        }
        
        $ps->close();
        
        $ids_tarefas .= ")";
        
        
        $ps = $con->getConexao()->prepare("SELECT a.id,a.tipo,UNIX_TIMESTAMP(a.momento)*1000,a.id_tarefa,u.id,u.nome,a.observacao,u.faixa_salarial FROM andamento_tarefa_simplificada a "
                . "INNER JOIN usuario u ON u.id=a.id_usuario WHERE a.id_tarefa IN $ids_tarefas");
        $ps->execute();
        $ps->bind_result($id,$tipo,$momento,$id_tarefa,$id_usuario,$nome_usuario,$observacao,$faixa_salarial);
        while($ps->fetch()){
            
            $a = new AndamentoTarefaSimplificada();
            $a->id = $id;
            $a->tipo = $tipo;
            $a->momento = $momento;
            $a->tarefa = $tarefas[$id_tarefa];
            
            $usu = new Usuario();
            $usu->id = $id_usuario;
            $usu->nome = $nome_usuario;
            $usu->faixa_salarial = $faixa_salarial;
            
            $a->usuario = $usu;
            
            $a->observacao = $observacao;
            
            $tarefas[$id_tarefa]->andamentos[] = $a;
            
        }
        $ps->close();
        
        
        $ps = $con->getConexao()->prepare("SELECT id_tarefa,link FROM arquivo_tarefa_simplificada WHERE id_tarefa IN $ids_tarefas");
        $ps->execute();
        $ps->bind_result($id_tarefa,$link);
        while($ps->fetch()){
            
            $tarefas[$id_tarefa]->arquivos[] = $link;
            
        }
        $ps->close();
        
        $retorno = array();
        
        foreach($tarefas as $key=>$value){
            
            $retorno[] = $value;
            
        }
        
        return $retorno;
        
    }

    public function getCountEmailsEnvio($con,$filtro=""){


        $sql = "SELECT COUNT(*) FROM emails_lista ";

        if($filtro !==  ""){

            $sql .= "WHERE $filtro";

        }

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($qtd);

        if($ps->fetch()){

            $ps->close();

            return $qtd;

        }

        return 0;

    }

    public function getEmailsEnvio($con,$x0,$x1,$filtro="",$ordem=""){

        $sql = "SELECT id,email FROM emails_lista WHERE id>0";

        if($filtro !== ""){

            $sql .= " AND $filtro";

        }

        if($ordem !== ""){

            $sql .= " ORDER BY $ordem";

        }

       $sql .= " LIMIT $x0,".($x1-$x0);

        

        $ps = $con->getConexao()->prepare($sql);

        $ps->execute();

        $ps->bind_result($id,$email);

        $emails = array();

        while($ps->fetch()){

            $em = new stdClass();
            $em->id = $id;
            $em->email = $email;

            $emails[] = $em;
        }

        $ps->close();

        return $emails;


    }

    public function deleteItemKim($con,$it){

        $ps = $con->getConexao()->prepare("DELETE FROM itens_kim WHERE id=$it->id");
        $ps->execute();
        $ps->close();

    }

    public function setIgualdade($con,$lk,$ig){

        $i = ($ig?1:-1);

        $ps = $con->getConexao()->prepare("UPDATE link_kim SET aprovado=$i WHERE id=$lk->id");
        $ps->execute();
        $ps->close();

    }

    public function getUnioes($con){
        
        $produtos = array();
        
        $ps = $con->getConexao()->prepare("SELECT l.id,p1.id,p1.nome,p1.url_encontrado,p1.imagem,p2.id,p2.nome,p2.url_encontrado,p2.imagem,e1.nome,e2.nome,p1.preco,p2.preco FROM itens_kim p1 INNER JOIN link_kim l ON l.id_produto1=p1.id INNER JOIN itens_kim p2 ON l.id_produto2=p2.id INNER JOIN empresas_kim e1 ON p1.id_empresa=e1.id INNER JOIN empresas_kim e2 ON p2.id_empresa=e2.id WHERE l.aprovado=0 AND p1.id<>p2.id AND p1.id_empresa<>p2.id_empresa AND (p1.inativo=false AND p2.inativo=false) LIMIT 0,60");
        $ps->execute();
        $ps->bind_result($id,$id1,$nome1,$url1,$img1,$id2,$nome2,$url2,$img2,$e1,$e2,$preco1,$preco2);
        
        while($ps->fetch()){
            
            $p1 = new stdClass();
            $p1->id = $id1;
            $p1->nome = str_replace(array("'",'"'),array("",""),addslashes($nome1));
            $p1->url_encontrado = $url1;
            $p1->imagem = $img1;
            $p1->empresa = $e1;
            $p1->preco = $preco1;

            $p2 = new stdClass();
            $p2->id = $id2;
            $p2->nome = str_replace(array("'",'"'),array("",""),addslashes($nome2));
            $p2->url_encontrado = $url2;
            $p2->imagem = $img2;
            $p2->empresa = $e2;
            $p2->preco = $preco2;

            $lk = new stdClass();
            $lk->id = $id;
            $lk->produto1=$p1;
            $lk->produto2=$p2;
            
            $produtos[] = $lk;
            
        }
        
        $ps->close();
        
        return $produtos;
        
    }

    
    public function pedindoMuito($con,$hash){

        $id_empresa = 0;

        $ps = $con->getConexao()->prepare("SELECT id_empresa FROM pedido_modelo_kim WHERE hash=$hash");
        $ps->execute();
        $ps->bind_result($ide);

        if($ps->fetch()){

            $id_empresa = $ide;

        }

        $ps->close();

        $ps = $con->getConexao()->prepare("DELETE FROM pedido_modelo_kim WHERE id_empresa=$id_empresa");
        $ps->execute();
        $ps->close();
        
        $ps = $con->getConexao()->prepare("UPDATE empresas_kim SET nivel_especificidade=nivel_especificidade-1 WHERE id=$id_empresa");
        $ps->execute();
        $ps->close();

    }


    public function getItensInventario($con,$produtos,$data){
        
        $ids_produtos = "(-1";
        
        foreach($produtos as $key=>$value){
            $ids_produtos .= ",$value->id";
        }
        
        $ids_produtos .= ")";
        
        
        $movimentos = $this->getMovimentosProduto($con, "pr.id IN $ids_produtos");
        $mapa = array();
        
        foreach($movimentos as $key=>$value){
           
            if(!isset($mapa[$value->id_produto])){
                
                $mapa[$value->id_produto] = array();
                
            }
            
            $mapa[$value->id_produto][] = $value;
            
        }
        
        $itens = array();
        
        foreach($produtos as $key=>$value){
            
            $produto = Utilidades::copy($value);
            
            $it = new ItemInventario();
            $it->empresa = $this;
            $it->data = $data;
            
            $movimentos = array();
            
            if(isset($mapa[$value->id])){
                $movimentos = $mapa[$produto->id];
            }
            
            foreach($movimentos as $key2=>$movimento){
                
                if($movimento->momento>$data){
                    
                    $produto->estoque -= $movimento->influencia_estoque;
                    $produto->disponivel -= $movimento->influencia_reserva;
                }
                
            }
            
            $it->produto = $produto->getReduzido();
            $it->quantidade = $produto->estoque;
            
            if($it->quantidade <= 0){
                continue;
            }
            
            $qtd = $produto->estoque;
            
            $ps = $con->getConexao()->prepare("SELECT quantidade,icms,valor_unitario FROM produto_nota INNER JOIN nota ON produto_nota.id_nota=nota.id AND nota.saida=false AND id_produto=$value->id AND data_emissao<=FROM_UNIXTIME($data/1000) ORDER BY nota.data_emissao DESC");
            
            $ps->execute();
            $ps->bind_result($quantidade,$icms,$valor);
            while($ps->fetch() && $qtd>0){
                
                $qtd -= $quantidade;
                
                $q = ($qtd>0)?$quantidade:$quantidade+$qtd;
                
                $it->valor_medio += $q*$valor;
                
                $icm = $icms;
                
                if($icm === 0){
                    
                    $icm = $valor * 0.028;
                    
                }
                
                $it->icms_recuperavel += $q*$icm;
                
            }
            $ps->close();
            
            
            if($qtd>0){
                
                $it->valor_medio += $qtd*$produto->custo;
                $it->icms_recuperavel += $qtd*$produto->custo*0.028;
                
            }
            
            $it->valor_medio /= $it->quantidade;
            
            $itens[] = $it;
            
        }
        
        unset($mapa);
        unset($movimentos);
        unset($ids_produtos);
        unset($produtos);
        
        return $itens;
        
        
    }
    
    public function getCountProblemasCFG($con,$filtro=""){
        
        $sql = "SELECT COUNT(*) FROM problema_cfg p INNER JOIN usuario u ON u.id=p.id_usuario INNER JOIN empresa e ON e.id=u.id_empresa WHERE p.id_empresa=$this->id";
        
        if($filtro !== ""){
            
            $sql .= " AND $filtro";
            
        }
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);
        if($ps->fetch()){
            
            $ps->close();
            return $qtd;
            
        }
        $ps->close();
        
        return 0;
        
    }
    
    public function getProblemasCFG($con,$x1,$x2,$filtro="",$ordem=""){
        
        $sql = "SELECT p.id,p.id_tipo,p.id_usuario,e.id,e.tipo_empresa FROM problema_cfg p INNER JOIN usuario u ON p.id_usuario=u.id INNER JOIN empresa e ON e.id=u.id_empresa WHERE p.id_empresa=$this->id";
        
        if($filtro !== ""){
            
            $sql .= " AND $filtro";
            
        }
        
        if($ordem !== ""){
            
            $sql .= " ORDER BY $ordem";
            
        }
        
        $sql .= " LIMIT $x1, ".($x2-$x1);
        
        
        $ids_usuarios = "(-1";
        
        $empresas = array();
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id,$id_tipo,$id_usuario,$id_empresa,$tipo_empresa);
        
        $problemas = array();
        
        $tipos = Sistema::getProblemasCFGMaster();
        
        while($ps->fetch()){
            
            $p = new ProblemaCFG();
            $p->id = $id;
            
            foreach($tipos as $key=>$value){
                if($value->id === $id_tipo){
                    $p->tipo = $value;
                    break;
                }
            }
            
            if($p->tipo === null){
                continue;
            }
            
            $p->usuario = $id_usuario;
            
            $problemas[] = $p;
            
            $ids_usuarios .= ",$id_usuario";
            
            $empresas[$id_empresa] = $tipo_empresa;
             
        }
        
        $ps->close();
        
        $ids_usuarios .= ")";
        
        $usuarios = array();
        
        foreach($empresas as $id_empresa=>$tipo){
            
            $emp = Sistema::getEmpresa($tipo,$id_empresa,$con);
            
            $us = $emp->getUsuarios($con, 0, 100000,"usuario.id IN $ids_usuarios");
            
            foreach($us as $key=>$usuario){
                
                $usuarios[] = $usuario;
                
            }
            
        }
        
        $mapa_usuarios = array();
        
        foreach($usuarios as $key=>$value){
            $mapa_usuarios[$value->id] = $value;
        }
        
        $retorno = array();
        
        foreach($problemas as $key=>$value){
            
            if(!isset($mapa_usuarios[$value->usuario])){
                continue;
            }
            
            $value->usuario = $mapa_usuarios[$value->usuario];
            
            $retorno[] = $value;
            
        }
        
        return $retorno;
        
    }
    
    public function getProdutosInventario($con){
        
        return $this->getProdutos($con, 0, 100000);
        
    }

    public function getMovimentosProduto($con, $filtro = "",$iicms=true,$ijuros=true,$ifrete=true,$iipi=true) {

        $movimentos = array();
        
        $f = $filtro;

        $sql = "SELECT pr.id,pr.estoque,pr.disponivel,pr.nome,pp.influencia_estoque,pp.influencia_reserva,pp.valor_base,pp.juros,pp.base_calculo,pp.icms,pp.ipi,pp.frete,p.id,c.razao_social,UNIX_TIMESTAMP(IFNULL(n.data_emissao,p.data))*1000,n.ficha,n.numero,e.nome "
                . "FROM produto_pedido_saida pp "
                . "INNER JOIN pedido p ON p.id=pp.id_pedido "
                . "LEFT JOIN nota n ON n.id_pedido=p.id AND n.cancelada=false AND n.excluida=false AND n.id_empresa=p.id_empresa "
                . "INNER JOIN cliente c ON c.id=p.id_cliente "
                . "INNER JOIN produto pr ON pr.id=pp.id_produto "
                . "LEFT JOIN empresa e ON e.id=pr.id_logistica "
                . "WHERE p.id_empresa=$this->id AND (n.id_empresa=$this->id OR n.id IS NULL OR p.id_nota=0) AND (pp.influencia_estoque <> 0 OR pp.influencia_reserva <> 0)";


        $filtro = str_replace(array('!data_emissao!','!pessoa!'), array('IFNULL(n.data_emissao,p.data)','c.razao_social'), $f);

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
            $m->valor_base = ($valor);
            
            if($ifrete){
                $m->valor_base += $frete;
            }
            if($ijuros){
                $m->valor_base += $juros;
            }
            if($iipi){
                $m->valor_base += $ipi;
            }
            if($iicms){
                $m->valor_base += $icms;
            }
            
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
                . "WHERE pr.id_empresa=$this->id AND pn.influencia_estoque <> 0 ";

        $filtro = str_replace(array('!data_emissao!','!pessoa!'), array('n.data_emissao','(CASE WHEN f.nome IS NOT NULL THEN f.nome ELSE c.razao_social END)'), $f);

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
            
            if($m->influencia_estoque < 0){
                if(!$ifrete){
                    $m->valor_base -= $frete;
                }
                if(!$ijuros){
                    $m->valor_base -= $juros;
                }
                if(!$iipi){
                    $m->valor_base -= $ipi;
                }
                if(!$iicms){
                    $m->valor_base -= $icms;
                }
            }
            
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
                . "WHERE p.id_empresa=$this->id AND pp.influencia_estoque <> 0 AND (n.id_empresa=$this->id OR n.id_empresa IS NULL)";

        $filtro = str_replace(array('!data_emissao!','!pessoa!'), array('IFNULL(n.data_emissao,p.data)','f.nome'), $f);

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

    public function getCountCotacoesGrupais($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM (SELECT c.id FROM (SELECT * FROM cotacao_grupal) c INNER JOIN fornecedor_cotacao_grupal fp ON fp.id_cotacao=c.id INNER JOIN fornecedor f ON f.id=fp.id_fornecedor WHERE c.id_empresa=$this->id AND c.excluida=false ";

        if ($filtro !== "") {

            $sql .= " AND $filtro";
        }

        $sql .= " GROUP BY c.id) k";

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);
        if ($ps->fetch()) {
            $ps->close();

            return $qtd;
        }

        return 0;
    }

    public function getCotacoesGrupais($con, $x1, $x2, $filtro = "", $ordem = "") {

        $cotacoes = array();

        $sql = "SELECT f.id,f.nome,f.cnpj,c.id,UNIX_TIMESTAMP(c.data)*1000,c.observacoes,c.enviada FROM (SELECT * FROM cotacao_grupal WHERE cotacao_grupal.excluida=false LIMIT $x1, " . ($x2 - $x1) . ") c INNER JOIN fornecedor_cotacao_grupal fp ON fp.id_cotacao=c.id INNER JOIN fornecedor f ON f.id=fp.id_fornecedor WHERE c.id_empresa=$this->id ";
        if ($filtro !== "") {
            $sql .= "AND $filtro ";
        }
        if ($ordem !== "") {
            $sql .= "ORDER BY $ordem";
        }
        $ids = "(-1";
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_forn, $nome_forn, $cnpj_forn, $id_cot, $data_cot, $obs_cot, $env_cot);
        while ($ps->fetch()) {

            if (!isset($cotacoes[$id_cot])) {

                $cot = new CotacaoGrupal();
                $cot->id = $id_cot;
                $cot->data = $data_cot;
                $cot->observacoes = $obs_cot;
                $cot->enviada = $env_cot == 1;
                $cot->empresa = $this;

                $cotacoes[$id_cot] = $cot;

                $ids .= ",$id_cot";
            }

            $f = new FornecedorReduzido();
            $f->id = $id_forn;
            $f->nome = $nome_forn;
            $f->cnpj = new CNPJ($cnpj_forn);

            $cot->fornecedores[] = $f;
        }

        $ps->close();

        $ids .= ")";

        $ps = $con->getConexao()->prepare("SELECT p.id,p.quantidade,p.id_cotacao,p.id_produto,r.id,UNIX_TIMESTAMP(r.momento)*1000,r.valor,r.quantidade,pp.id,pp.nome,pp.codigo,pp.imagem,pp.quantidade_unidade,pp.unidade,f.id,f.nome,f.cnpj FROM produto_cotacao_grupal p LEFT JOIN resposta_cotacao_grupal r ON p.id=r.id_produto_cotacao LEFT JOIN fornecedor f ON f.id=r.id_fornecedor INNER JOIN produto pp ON pp.id=p.id_produto WHERE p.id_cotacao IN $ids");
        $ps->execute();
        $ps->bind_result($id, $quantidade, $id_cotacao, $id_produto, $id_resp, $momento_resp, $valor_resp, $quantidade_resp, $id_prod, $nom_prod, $cod_prod, $img_prod, $quantidade_unidade_prod, $unidade_prod, $forn_id, $forn_nome, $forn_cnpj);
        while ($ps->fetch()) {

            if (!isset($cotacoes[$id_cotacao]->produtos[$id])) {

                $p = new ProdutoCotacaoGrupal();
                $p->id = $id;
                $p->quantidade = $quantidade;
                $p->cotacao = $cotacoes[$id_cotacao];

                $pr = new ProdutoReduzido();
                $pr->id = $id_prod;
                $pr->nome = $nom_prod;
                $pr->codigo = $cod_prod;
                $pr->imagem = $img_prod;
                $pr->quantidade_unidade = $quantidade_unidade_prod;
                $pr->unidade = $unidade_prod;

                $p->produto = $pr;

                $cotacoes[$id_cotacao]->produtos[$id] = $p;
            }

            if ($id_resp !== null) {

                $resp = new RespostaCotacaoGrupal();
                $resp->id = $id_resp;
                $resp->momento = $momento_resp;
                $resp->produto = $cotacoes[$id_cotacao]->produtos[$id];
                $resp->quantidade = $quantidade_resp;
                $resp->valor = $valor_resp;

                $fr = new FornecedorReduzido();
                $fr->id = $forn_id;
                $fr->nome = $forn_nome;
                $fr->cnpj = new CNPJ($forn_cnpj);

                $resp->fornecedor = $fr;

                $cotacoes[$id_cotacao]->produtos[$id]->respostas[] = $resp;
            }
        }
        $ps->close();

        $retorno = array();

        foreach ($cotacoes as $key => $value) {

            $produtos = array();

            foreach ($value->produtos as $key2 => $value2) {
                $produtos[] = $value2;
            }

            $value->produtos = $produtos;

            $retorno[] = $value;
        }

        return $retorno;
    }

    public function getAnaliseCotacaoEntrada($con) {

        $analises = array();

        $c = "(-1";

        $ps = $con->getConexao()->prepare("SELECT p.codigo,p.nome,SUM(pc.quantidade),ROUND(SUM(pc.quantidade*pc.valor)/SUM(pc.quantidade),2),MAX(pc.valor),MIN(pc.valor),GROUP_CONCAT(pc.id separator ','),UNIX_TIMESTAMP(MAX(ce.data))*1000,p.custo,MAX(ce.id) FROM cotacao_entrada ce INNER JOIN produto_cotacao_entrada pc ON pc.id_cotacao=ce.id INNER JOIN produto p ON p.id=pc.id_produto WHERE ce.id_empresa=$this->id AND ce.data > DATE_SUB(CURRENT_DATE,INTERVAL 60 DAY) AND pc.checado = 0 GROUP BY p.codigo ORDER BY MAX(ce.data) DESC");
        $ps->execute();
        $ps->bind_result($codigo, $nome, $quantidade, $valor, $valor_maximo, $valor_minimo, $ids, $data, $custo, $mc);

        while ($ps->fetch()) {

            $a = new AnaliseCotacaoEntrada();
            $a->id = $codigo;
            $a->nome_produto = $nome;
            $a->quantidade_produto = $quantidade;
            $a->valor = $valor;
            $a->valor_maximo = $valor_maximo;
            $a->valor_minimo = $valor_minimo;
            $a->data = $data;
            $a->custo_atual = $custo;
            $a->id_cotacao = $mc;
            $a->ids_produtos = explode(',', $ids);
            $c .= ",$mc";
            foreach ($a->ids_produtos as $key => $value) {
                $a->ids_produtos[$key] = intval($value . "");
            }

            $analises[] = $a;
        }

        $ps->close();

        $c .= ")";

        $cotacoes = array();
        $ps = $con->getConexao()->prepare("SELECT c.id,f.nome,pc.valor,p.codigo FROM cotacao_entrada c INNER JOIN fornecedor f ON f.id=c.id_fornecedor INNER JOIN produto_cotacao_entrada pc ON pc.id_cotacao=c.id INNER JOIN produto p ON pc.id_produto=p.id WHERE c.id IN $c");
        $ps->execute();
        $ps->bind_result($id, $nome_fornecedor, $valor, $codigo);

        while ($ps->fetch()) {

            if (!isset($cotacoes[$id])) {
                $cotacoes[$id] = array();
            }

            $cotacoes[$id][$codigo] = array($nome_fornecedor, $valor);
        }

        $ps->close();

        foreach ($analises as $key => $value) {

            $c = $cotacoes[$value->id_cotacao][$value->id];

            $value->nome_fornecedor = $c[0];
            $value->ultimo_custo = $c[1];
        }

        return $analises;
    }

    public function getEmpresasClientes($con) {

        return array();
    }

    public function getTiposTarefa($con, $filtro = "") {

        Sistema::getCargo($con, $this, 0, false);

        $sql = "SELECT "
                . "tipo_tarefa.id,"
                . "tipo_tarefa.nome,"
                . "tipo_tarefa.tempo_medio,"
                . "tipo_tarefa.prioridade,"
                . "tc.id_cargo "
                . "FROM tipo_tarefa "
                . "LEFT JOIN tipo_tarefa_cargo tc ON tipo_tarefa.id=tc.id_tipo_tarefa WHERE tipo_tarefa.excluido=false AND tipo_tarefa.id_empresa=$this->id";

        if ($filtro !== "") {

            $sql .= " AND $filtro";
        }

        $sql .= " ORDER BY tipo_tarefa.nome";

        $tarefas = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $nome, $tempo_medio, $prioridade, $id_cargo);

        while ($ps->fetch()) {

            if (!isset($tarefas[$id])) {

                $t = new TipoTarefa();
                $t->id = $id;
                $t->nome = $nome;
                $t->tempo_medio = $tempo_medio;
                $t->prioridade = $prioridade;
                $t->empresa = $this;

                $tarefas[$id] = $t;
            }

            $t = $tarefas[$id];

            if ($id_cargo !== null) {

                $cargo = Sistema::getCargo($con, $this, $id_cargo);
                if ($cargo === null)
                    continue;

                $k = false;
                foreach ($t->cargos as $key => $value) {
                    if ($value->id === $cargo->id) {
                        $t->cargos[$key] = $cargo;
                        $k = true;
                    }
                }
                if (!$k) {
                    $t->cargos[] = $cargo;
                }
            }
        }


        $retorno = array();

        foreach ($tarefas as $key => $value) {

            $retorno[] = $value;
        }

        $retorno = Sistema::mesclarTarefas($this, $retorno);

        return $retorno;
    }

    public function setRTC($con, $rtc) {

        $ps = $con->getConexao()->prepare("UPDATE empresa SET rtc=$rtc->numero WHERE id=$this->id");
        $ps->execute();
        $ps->close();
    }

    public function getCargos($con) {

        $ps = $con->getConexao()->prepare("SELECT id,nome FROM cargo WHERE id_empresa = $this->id AND excluido = false");
        $ps->execute();
        $ps->bind_result($id, $nome);

        $cf = Utilidades::copy($this->cargos_fixos);
        $cargos = array();

        while ($ps->fetch()) {

            $cargo = new Cargo();
            $cargo->empresa = $this;
            $cargo->id = $id;
            $cargo->nome = $nome;

            $cargos[] = $cargo;
        }
        
        foreach($cf as $key=>$value){
            $cargos[] = $value;
        }

        $ps->close();

        return $cargos;
    }

    public function getRTC($con) {

        if ($this->rtc !== null) {

            return $this->rtc;
        }

        $r = 1;

        $ps = $con->getConexao()->prepare("SELECT rtc FROM empresa WHERE id=$this->id");
        $ps->execute();
        $ps->bind_result($rtc);
        if ($ps->fetch()) {
            $r = $rtc;
        }
        $ps->close();

        $rtcs = Sistema::getRTCS();

        foreach (Sistema::getRTCS() as $key => $rtc) {
            if ($rtc->numero === $r) {
                $this->rtc = $rtc;
                return $rtc;
            }
        }

        return null;
    }

    public function merge($con) {

        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO empresa(nome,excluida,inscricao_estadual,consigna,aceitou_contrato,juros_mensal,cnpj,tipo_empresa) VALUES('" . addslashes($this->nome) . "',false,'" . $this->inscricao_estadual . "'," . ($this->consigna ? "true" : "false") . "," . ($this->aceitou_contrato ? "true" : "false") . ",$this->juros_mensal,'" . $this->cnpj->valor . "'," . $this->tipo_empresa . ")");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE empresa SET nome='" . addslashes($this->nome) . "',excluida=false,inscricao_estadual = '" . addslashes($this->inscricao_estadual) . "', consigna=" . ($this->consigna ? "true" : "false") . ",aceitou_contrato=" . ($this->aceitou_contrato ? "true" : "false") . ", juros_mensal=" . $this->juros_mensal . ", cnpj='" . $this->cnpj->valor . "', tipo_empresa=" . $this->tipo_empresa . " WHERE id = " . $this->id);
            $ps->execute();
            $ps->close();
        }

        $this->email->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE email SET id_entidade=" . $this->id . ", tipo_entidade='EMP' WHERE id = " . $this->email->id);
        $ps->execute();
        $ps->close();

        $this->endereco->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE endereco SET id_entidade=" . $this->id . ", tipo_entidade='EMP' WHERE id = " . $this->endereco->id);
        $ps->execute();
        $ps->close();

        $this->telefone->merge($con);

        $ps = $con->getConexao()->prepare("UPDATE telefone SET id_entidade=" . $this->id . ", tipo_entidade='EMP' WHERE id = " . $this->telefone->id);
        $ps->execute();
        $ps->close();
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("UPDATE empresa SET excluida = true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

    public function setFilial($con, $empresa) {

        $ps = $con->getConexao()->prepare("INSERT INTO filial(id_empresa1,id_empresa2) VALUES($this->id,$empresa->id)");
        $ps->execute();
        $ps->close();
    }

    public function setAdm($con, $adm) {

        $ps = $con->getConexao()->prepare("UPDATE empresa SET empresa_adm=" . ($adm !== null ? $adm->id : 0) . " WHERE id=$this->id");
        $ps->execute();
        $ps->close();
    }

    public function getAdm($con) {

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
                . "INNER JOIN empresa e2 ON e2.empresa_adm=empresa.id "
                . "WHERE e2.id=$this->id");

        $ps->execute();
        $ps->bind_result($id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        if ($ps->fetch()) {

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

            $ps->close();

            return $empresa;
        }

        $ps->close();

        return null;
    }

    public function getProdutosFornecedor($con){


        $inp = "(-1";

        $inf = "(-1";

        $produtos = array();

        $ps = $con->getConexao()->prepare("SELECT pf.id,pf.id_produto,pf.id_fornecedor,pf.preco1,pf.comis1,pf.preco2,pf.comis2,pf.preco3,pf.comis3,pf.preco4,pf.comis4,UNIX_TIMESTAMP(pf.validade)*1000,pf.valor FROM produto_fornecedor pf INNER JOIN fornecedor f ON f.id=pf.id_fornecedor AND f.id_empresa=$this->id");
        $ps->execute();
        $ps->bind_result($id,$id_produto,$id_fornecedor,$preco1,$comis1,$preco2,$comis2,$preco3,$comis3,$preco4,$comis4,$validade,$valor);
        while($ps->fetch()){

            $p = new ProdutoFornecedor();
            $p->id = $id;
            $p->id_produto = $id_produto;
            $p->preco1 = $preco1;
            $p->id_fornecedor=$id_fornecedor;
            $p->comis1 = $comis1;
            $p->preco2 = $preco2;
            $p->comis2 = $comis2;
            $p->valor = $valor;
            $p->preco3 = $preco3;
            $p->comis3 = $comis3;
            $p->preco4 = $preco4;
            $p->comis4 = $comis4;
            $p->validade = $validade;

            $produtos[] = $p;

            $inp .= ",$id_produto";

            $inf .= ",$id_fornecedor";

        }
        $ps->close();

        $inp .= ")";

        $inf .= ")";

        $p = $this->getProdutos($con,0,1000,"produto.id IN $inp","");

        foreach ($produtos as $key => $value) {
            
            foreach ($p as $k => $v2) {
                
                if($v2->id === $value->id_produto){

                    $value->produto = $v2;
                    continue 2;

                }

            }

        }

        $p = $this->getFornecedores($con,0,1000,"fornecedor.id IN $inf","");

        foreach ($produtos as $key => $value) {
            
            foreach ($p as $k => $v2) {
                
                if($v2->id === $value->id_fornecedor){

                    $value->fornecedor = $v2;
                    continue 2;

                }

            }

        }


        return array();

    }

    public function setMarketing($con, $mkt) {

        $ps = $con->getConexao()->prepare("UPDATE empresa SET contrato_mkt=" . ($mkt !== null ? $mkt->id : 0) . " WHERE id=$this->id");
        $ps->execute();
        $ps->close();
    }

    public function getMarketing($con) {

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
                . "INNER JOIN empresa e2 ON e2.contrato_mkt=empresa.id "
                . "WHERE e2.id=$this->id");

        $ps->execute();
        $ps->bind_result($id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        if ($ps->fetch()) {

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

            $ps->close();

            return $empresa;
        }

        $ps->close();

        return null;
    }

    public function getFiliais($con) {

        $ids = $this->id;

        for ($i = 0; $i < count(explode(',', $ids)); $i++) {

            $id = explode(',', $ids);
            $id = $id[$i];

            $ps = $con->getConexao()->prepare("SELECT CASE WHEN id_empresa1 <> $id THEN id_empresa1 ELSE id_empresa2 END FROM filial WHERE (id_empresa1=$id OR id_empresa2=$id) AND (CASE WHEN id_empresa1 <> $id THEN id_empresa1 ELSE id_empresa2 END) NOT IN ($ids)");
            $ps->execute();
            $ps->bind_result($id_filial);
            while ($ps->fetch()) {
                $ids .= ",$id_filial";
            }
            $ps->close();
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
        $filiais = array();
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

            $filiais[] = $empresa;
        }

        $ps->close();

        return $filiais;
    }

    public function getCountBanners($con, $filtro = "") {

        $ps = $con->getConexao()->prepare("SELECT COUNT(*) FROM banner WHERE id_empresa=$this->id");
        $ps->execute();
        $ps->bind_result($qtd);

        if ($ps->fetch()) {
            $ps->close();
            return $qtd;
        }
        $ps->close();
        return 0;
    }

    public function setCadastroAtualizadoBoasVindas($con){

        $ps = $con->getConexao()->prepare("UPDATE empresa SET cadastro_confirmado_boas_vindas=1 WHERE id=$this->id");
        $ps->execute();
        $ps->close();

    }

    public function getBanners($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "id,"
                . "UNIX_TIMESTAMP(data_inicial)*1000,"
                . "UNIX_TIMESTAMP(data_final)*1000,"
                . "boas_vindas,"
                . "id_campanha,"
                . "ordem,"
                . "tipo,"
                . "json "
                . "FROM banner WHERE id_empresa=$this->id ";

        if ($filtro !== "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem !== "") {

            $sql .= "ORDER BY ordem DESC, $ordem ";

        }else{

            $sql .= "ORDER BY ordem DESC ";

        }
        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $campanhas = "(-1";
        $qtd_campanhas = 0;

        $banners = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $data_inicial, $data_final,$boas_vindas, $id_campanha,$ord, $tipo, $json);

        while ($ps->fetch()) {

            $banner = new Banner();
            $banner->id = $id;
            $banner->data_inicial = $data_inicial;
            $banner->data_final = $data_final;
            $banner->campanha = $id_campanha;
            $banner->tipo = $tipo;
            $banner->json = $json;
            $banner->empresa = $this;
            $banner->boas_vindas = $boas_vindas;
            $banner->ordem = $ord;

            $banners[] = $banner;

            if ($id_campanha > 0) {

                $campanhas .= ",$id_campanha";
                $qtd_campanhas++;
            }
        }

        $ps->close();

        $campanhas .= ")";

        $campanhas = $this->getCampanhas($con, 0, $qtd_campanhas, "campanha.id IN $campanhas", "");

        foreach ($banners as $key => $banner) {

            if ($banner->campanha > 0) {

                foreach ($campanhas as $key2 => $campanha) {

                    if ($banner->campanha === $campanha->id) {

                        $banner->campanha = $campanha;
                        break;
                    }
                }
            } else {

                $banner->campanha = null;
            }
        }

        return $banners;
    }

    public function getCountBancos($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM banco WHERE id_empresa=$this->id AND excluido=false ";

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

    public function getBancos($con, $x1, $x2, $filtro = "", $ordem = "") {

        $bancos = array();

        $sql = "SELECT id,codigo_contimatic,nome,saldo,conta,codigo,agencia,fechamento FROM banco WHERE id_empresa=$this->id AND excluido=false ";

        if ($filtro != "") {
            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {
            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $cod_ctm, $nome, $saldo, $conta, $codigo, $agencia, $fechamento);

        while ($ps->fetch()) {

            $banco = new Banco();

            $banco->id = $id;
            $banco->codigo_contimatic = $cod_ctm;
            $banco->nome = $nome;
            $banco->saldo = $saldo;
            $banco->conta = $conta;
            $banco->codigo = $codigo;
            $banco->fechamento = $fechamento == 1;
            $banco->agencia = $agencia;

            $banco->empresa = $this;

            $bancos[] = $banco;
        }

        $ps->close();

        return $bancos;
    }

    public function getParametrosEmissao($con) {

        $ps = $con->getConexao()->prepare("SELECT id,nota,lote,serie,certificado,senha_certificado FROM parametros_emissao WHERE id_empresa=$this->id");
        $ps->execute();
        $ps->bind_result($id, $nota, $lote, $serie, $certificado, $senha_certificado);

        if ($ps->fetch()) {

            $ps->close();

            $p = new ParametrosEmissao();
            $p->id = $id;
            $p->nota = $nota;
            $p->lote = $lote;
            $p->serie = $serie;
            $p->certificado = $certificado;
            $p->senha_certificado = $senha_certificado;
            $p->empresa = $this;

            return $p;
        }

        $ps->close();

        $p = new ParametrosEmissao();
        $p->empresa = $this;
        $p->certificado = "";
        $p->senha_certificado = "";

        return $p;
    }

    public function setLogo($con, $logo) {

        $colors = array();
        $frequencia = array();

        $path = file_get_contents($logo);
        $logo = Utilidades::base64encode($path);
        $img = imagecreatefromstring($path);

        $x = imagesx($img);
        $y = imagesy($img);

        $tolerancia = 10;
        $mais_frequente = "";

        for ($i = 0; $i < $y; $i++) {
            for ($j = 0; $j < $x; $j++) {

                $rgb = imagecolorat($img, $j, $i);


                $rgb = array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, ($rgb) & 0xFF);
                if ($rgb[0] === 0 && $rgb[1] === 0 && $rgb[2] === 0)
                    continue;

                if ($rgb[0] === 255 && $rgb[1] === 255 && $rgb[2] === 255)
                    continue;

                $hash = "";

                foreach ($rgb as $key => $p) {
                    $hash .= ($p - ($p % $tolerancia)) . ".";
                }

                if ($mais_frequente === "") {

                    $mais_frequente = $hash;
                }

                if (isset($colors[$hash])) {
                    $frequencia[$hash] ++;
                    foreach ($rgb as $key => $p) {
                        $colors[$hash][$key] += $p;
                        $colors[$hash][$key] = floor($colors[$hash][$key] / 2);
                    }
                } else {
                    $frequencia[$hash] = 1;
                    $colors[$hash] = $rgb;
                }

                if ($frequencia[$hash] > $frequencia[$mais_frequente]) {
                    $mais_frequente = $hash;
                }
            }
        }

        $cor_predominante = $colors[$mais_frequente];
        $cor = "#";

        foreach ($cor_predominante as $key => $value) {
            $cor .= Utilidades::decimalToHex($value);
        }

        $cor_predominante = $cor;

        $tem_logo = false;
        $ps = $con->getConexao()->prepare("SELECT id FROM logo WHERE id_empresa = $this->id");
        $ps->execute();
        $ps->bind_result($id);
        if ($ps->fetch()) {
            $tem_logo = true;
        }
        $ps->close();

        if ($tem_logo) {
            $ps = $con->getConexao()->prepare("UPDATE logo SET logo='$logo',cor_predominante='$cor' WHERE id_empresa=$this->id");
            $ps->execute();
            $ps->close();
        } else {
            $ps = $con->getConexao()->prepare("INSERT INTO logo(id_empresa,logo,cor_predominante) VALUES($this->id,'$logo','$cor')");
            $ps->execute();
            $ps->close();
        }

        $ses = new SessionManager();
        $ses->deset("logo_$this->id");
    }

    public function getLogsCFG($con){
        
        $logs = array();
        
        $ps = $con->getConexao()->prepare("SELECT id,log,UNIX_TIMESTAMP(data)*1000 FROM log_cfg");
        $ps->execute();
        $ps->bind_result($id,$log,$data);
        
        while($ps->fetch()){
            
            $l = new LogCFG();
            $l->id = $id;
            $l->log = $log;
            $l->data = $data;
            
            $logs[] = $l;
            
        }
        
        $ps->close();
        
        return $logs;
        
    }


    public function getLogo($com) {

        $ses = new SessionManager();

        if (($n = $ses->get("logo_$this->id")) != null) {

            return $n;
        }

        $ps = $com->getConexao()->prepare("SELECT id,logo,cor_predominante FROM logo WHERE id_empresa=$this->id");
        $ps->execute();
        $ps->bind_result($id, $logo, $cor_predominante);
        if ($ps->fetch()) {

            $ps->close();

            $l = new Logo();
            $l->id = $id;
            $l->logo = $logo;
            $l->cor_predominante = $cor_predominante;
            $l->empresa = $this;

            $ses->set("logo_$this->id", $l);

            return $l;
        }

        $ps->close();

        return null;
    }

    public function getCadastroLotesPendentes($con) {

        $categorias = "(-1";

        $c = Sistema::getCategoriaProduto(null);

        foreach ($c as $key => $value) {
            if ($value->loja || isset($value->lotes)) {
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
                . "WHERE produto.id_empresa = $this->id AND produto.excluido = false AND (produto.disponivel-IFNULL(l.quantidade,0))>0 AND produto.id_logistica=0 AND produto.sistema_lotes=true AND produto.id_categoria IN $categorias";


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $nome, $quantidade, $grade);

        $p = array();

        while ($ps->fetch()) {

            $c = new CadastroLotePendente();
            $c->id_produto = $id;

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

    public function getCountLotes($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM lote INNER JOIN produto ON produto.id=lote.id_produto WHERE produto.id_empresa=$this->id AND lote.excluido=false ";

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
                . "IFNULL(UNIX_TIMESTAMP(lote.validade)*1000,1000),"
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
                . "produto.sistema_lotes,"
                . "produto.nota_usuario,"
                . "produto.id_categoria "
                . "FROM lote "
                . "LEFT JOIN retirada ON lote.id=retirada.id_lote "
                . "INNER JOIN produto ON lote.id_produto=produto.id "
                . "WHERE produto.id_empresa = $this->id AND lote.excluido = false ";

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
        $ps->bind_result($id, $numero, $rua, $altura, $validade, $entrada, $grade, $quantidade_inicial, $quantidade_real, $codigo_fabricante, $retirada, $id_pro, $cod_pro, $id_log, $classe_risco, $fabricante, $imagem, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $est, $disp, $tr, $gr, $uni, $ncm, $nome, $lucro, $ativo, $conc, $sistema_lotes, $nota_usuario, $cat_id);

        $lotes = array();

        $produtos = array();

        while ($ps->fetch()) {

            if (!isset($produtos[$id_pro])) {

                $p = new Produto();
                $p->logistica = $id_log;
                $p->id = $id_pro;
                $p->codigo = $cod_pro;
                $p->classe_risco = $classe_risco;
                $p->fabricante = $fabricante;
                $p->imagem = $imagem;
                $p->nome = $nome;
                $p->id_universal = $id_uni;
                $p->sistema_lotes = $sistema_lotes == 1;
                $p->nota_usuario = $nota_usuario;
                $p->liquido = $liq == 1;
                $p->quantidade_unidade = $qtd_un;
                $p->habilitado = $hab;
                $p->empresa = $this;
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

                $p->empresa = $this;

                $produtos[$id_pro] = $p;
            }


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

    public function getEncomendas($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "encomenda.id,"
                . "UNIX_TIMESTAMP(encomenda.data)*1000, "
                . "encomenda.prazo, "
                . "encomenda.parcelas, "
                . "encomenda.id_status, "
                . "encomenda.observacoes, "
                . "encomenda.frete,"
                . "transportadora.id,"
                . "transportadora.razao_social,"
                . "transportadora.cnpj,"
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
                . "email_usu.id, "
                . "email_usu.endereco,"
                . "email_usu.senha "
                . "FROM encomenda "
                . "INNER JOIN cliente ON cliente.id=encomenda.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN usuario ON usuario.id=encomenda.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "LEFT JOIN transportadora ON transportadora.id=encomenda.id_transportadora "
                . "WHERE encomenda.id_empresa = $this->id AND encomenda.excluida = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_encomenda, $data, $prazo, $parcelas, $id_status, $obs, $frete,$id_transportadora,$nome_transportadora,$cnpj_transportadora, $id_cliente, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $id_usu, $nome_usu, $login_usu, $senha_usu, $cpf_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_cli_id, $email_cli_end, $email_cli_senha, $email_usu_id, $email_usu_end, $email_usu_senha);


        $encomendas = array();
        $usuarios = array();
        $clientes = array();

        while ($ps->fetch()) {

            $cliente = new Cliente();
            $cliente->id = $id_cliente;
            $cliente->codigo = $cod_cli;
            $cliente->cnpj = new CNPJ($cnpj);
            $cliente->cpf = new CPF($cpf);
            $cliente->rg = new RG($rg);
            $cliente->pessoa_fisica = $pessoa_fisica == 1;
            $cliente->nome_fantasia = $nome_fantasia_cliente;
            $cliente->razao_social = $nome_cliente;
            $cliente->empresa = $this;
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
            $cliente->empresa = $this;
            $cliente->inscricao_estadual = $ie;

            $end = new Endereco();
            $end->id = $end_cli_id;
            $end->bairro = $end_cli_bairro;
            $end->cep = new CEP($end_cli_cep);
            $end->numero = $end_cli_numero;
            $end->rua = $end_cli_rua;

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
            $usuario->empresa = $this;

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


            $encomenda = new Encomenda();

            $encomenda->cliente = $cliente;
            $encomenda->data = $data;
            $encomenda->empresa = $this;
            $encomenda->id = $id_encomenda;
            $encomenda->observacoes = $obs;
            $encomenda->parcelas = $parcelas;
            $encomenda->prazo = $prazo;
            $encomenda->frete = $frete;

            if($nome_transportadora !== null && $id_transportadora >0){

                $t = new Transportadora();
                $t->id = $id_transportadora;
                $t->razao_social = $nome_transportadora;
                $t->cnpj = new CNPJ($cnpj_transportadora);

                $encomenda->transportadora = $t;

            }

            $status = Sistema::getStatusEncomenda();

            foreach ($status as $key => $st) {
                if ($st->id == $id_status) {
                    $encomenda->status = $st;
                    break;
                }
            }

            $encomenda->usuario = $usuario;

            $encomendas[] = $encomenda;
        }

        $ps->close();

        $in_usu = "-1";
        $in_cli = "-1";

        foreach ($clientes as $id => $cliente) {
            $in_cli .= ",";
            $in_cli .= $id;
        }

        foreach ($usuarios as $id => $usuario) {
            $in_usu .= ",";
            $in_usu .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN ($in_cli) AND telefone.tipo_entidade='CLI') OR (telefone.id_entidade IN ($in_usu) AND telefone.tipo_entidade='USU') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $clientes;
            if ($tipo_entidade == 'USU') {
                $v = $usuarios;
            }

            $telefone = new Telefone($numero);
            $telefone->id = $id;

            foreach ($v[$id_entidade] as $key => $ent) {

                $ent->telefones[] = $telefone;
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

        return $encomendas;
    }

    public function getCountEncomendas($con, $filtro = "") {

        $sql = "SELECT COUNT(*) "
                . "FROM encomenda "
                . "INNER JOIN cliente ON cliente.id=encomenda.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN usuario ON usuario.id=encomenda.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE encomenda.id_empresa = $this->id AND encomenda.excluida=false ";

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

    public function getCountPedidosReserva($con, $filtro = "") {

        $sql = "SELECT COUNT(*) "
                . "FROM pedido_reserva "
                . "INNER JOIN cliente ON cliente.id=pedido_reserva.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN transportadora ON transportadora.id = pedido_reserva.id_transportadora "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN usuario ON usuario.id=pedido_reserva.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "INNER JOIN email email_tra ON email_tra.id_entidade=transportadora.id AND email_tra.tipo_entidade='TRA' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE pedido_reserva.id_empresa = $this->id AND pedido_reserva.excluido=false ";

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
    public function getPedidosReserva($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "pedido_reserva.id,"
                . "pedido_reserva.id_logistica, "
                . "pedido_reserva.frete_inclusao, "
                . "UNIX_TIMESTAMP(pedido_reserva.data)*1000, "
                . "pedido_reserva.prazo, "
                . "pedido_reserva.parcelas, "
                . "pedido_reserva.id_forma_pagamento, "
                . "pedido_reserva.frete, "
                . "pedido_reserva.observacoes, "
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
                . "email_usu.senha "
                . "FROM pedido_reserva "
                . "INNER JOIN cliente ON cliente.id=pedido_reserva.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN transportadora ON transportadora.id = pedido_reserva.id_transportadora "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN usuario ON usuario.id=pedido_reserva.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "INNER JOIN email email_tra ON email_tra.id_entidade=transportadora.id AND email_tra.tipo_entidade='TRA' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE pedido_reserva.id_empresa = $this->id AND pedido_reserva.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pedido, $id_log, $frete_incluso, $data, $prazo, $parcelas, $id_forma_pagamento, $frete, $obs, $id_cliente, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_usu, $nome_usu, $login_usu, $senha_usu, $cpf_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_cli_id, $email_cli_end, $email_cli_senha, $email_tra_id, $email_tra_end, $email_tra_senha, $email_usu_id, $email_usu_end, $email_usu_senha);


        $pedidos = array();
        $transportadoras = array();
        $usuarios = array();
        $clientes = array();

        while ($ps->fetch()) {

            $cliente = new Cliente();
            $cliente->id = $id_cliente;
            $cliente->codigo = $cod_cli;
            $cliente->cnpj = new CNPJ($cnpj);
            $cliente->cpf = new CPF($cpf);
            $cliente->rg = new RG($rg);
            $cliente->pessoa_fisica = $pessoa_fisica == 1;
            $cliente->nome_fantasia = $nome_fantasia_cliente;
            $cliente->razao_social = $nome_cliente;
            $cliente->empresa = $this;
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
            $cliente->empresa = $this;
            $cliente->inscricao_estadual = $ie;

            $end = new Endereco();
            $end->id = $end_cli_id;
            $end->bairro = $end_cli_bairro;
            $end->cep = new CEP($end_cli_cep);
            $end->numero = $end_cli_numero;
            $end->rua = $end_cli_rua;

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
            $transportadora->empresa = $this;

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
            $usuario->empresa = $this;

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


            $pedido = new PedidoReserva();

            $pedido->logistica = $id_log;
            $pedido->cliente = $cliente;
            $pedido->data = $data;
            $pedido->empresa = $this;
            $pedido->etapa_frete = $etapa_frete;

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
        $ps->bind_result($id, $prazo, $nome, $id_tra, $idr, $cond, $res);
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


        return $pedidos;
    }

    public function getPedidos($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "pedido.id,"
                . "tot.valor,"
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
                . "cliente.emite_receita,"
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
                . "email_usu.senha "
                . "FROM pedido "
                . "INNER JOIN cliente ON cliente.id=pedido.id_cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN transportadora ON transportadora.id = pedido.id_transportadora "
                . "INNER JOIN (SELECT GROUP_CONCAT(pro.nome separator ';') as 'produtos',ped.id as 'id_pedido'  FROM produto pro INNER JOIN produto_pedido_saida pp ON pro.id=pp.id_produto INNER JOIN pedido ped ON ped.id=pp.id_pedido GROUP BY ped.id) k ON k.id_pedido=pedido.id "
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
                . "INNER JOIN (SELECT pp.id_pedido,SUM(pp.quantidade*(pp.valor_base+pp.icms+pp.juros+pp.ipi)) as 'valor' FROM produto_pedido_saida pp GROUP BY pp.id_pedido) tot ON pedido.id=tot.id_pedido "
                . "WHERE pedido.id_empresa = $this->id AND pedido.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pedido, $valor_pedido, $etapa_frete, $id_log, $id_nota, $frete_incluso, $data, $prazo, $parcelas, $id_status, $id_forma_pagamento, $frete, $obs, $id_cliente,$emite_receita, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_usu, $nome_usu, $login_usu, $senha_usu, $cpf_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_cli_id, $email_cli_end, $email_cli_senha, $email_tra_id, $email_tra_end, $email_tra_senha, $email_usu_id, $email_usu_end, $email_usu_senha);


        $pedidos = array();
        $transportadoras = array();
        $usuarios = array();
        $clientes = array();

        while ($ps->fetch()) {

            $cliente = new Cliente();
            $cliente->id = $id_cliente;
            $cliente->codigo = $cod_cli;
            $cliente->cnpj = new CNPJ($cnpj);
            $cliente->emite_receita = $emite_receita==1;
            $cliente->cpf = new CPF($cpf);
            $cliente->rg = new RG($rg);
            $cliente->pessoa_fisica = $pessoa_fisica == 1;
            $cliente->nome_fantasia = $nome_fantasia_cliente;
            $cliente->razao_social = $nome_cliente;
            $cliente->empresa = $this;
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
            $cliente->empresa = $this;
            $cliente->inscricao_estadual = $ie;

            $end = new Endereco();
            $end->id = $end_cli_id;
            $end->bairro = $end_cli_bairro;
            $end->cep = new CEP($end_cli_cep);
            $end->numero = $end_cli_numero;
            $end->rua = $end_cli_rua;

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
            $transportadora->empresa = $this;

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
            $usuario->empresa = $this;

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
            $pedido->cliente = $cliente;
            $pedido->data = $data;
            $pedido->empresa = $this;
            $pedido->valor = $valor_pedido;
            $pedido->id_nota = $id_nota;
            $pedido->etapa_frete = $etapa_frete;

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
                . "INNER JOIN (SELECT GROUP_CONCAT(pro.nome separator ';') as 'produtos',ped.id as 'id_pedido'  FROM produto pro INNER JOIN produto_pedido_saida pp ON pro.id=pp.id_produto INNER JOIN pedido ped ON ped.id=pp.id_pedido GROUP BY ped.id) k ON k.id_pedido=pedido.id "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
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
                . "WHERE pedido.id_empresa = $this->id AND pedido.excluido=false ";

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

    public function getCampanhas($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "campanha.id,"
                . "campanha.nome,"
                . "UNIX_TIMESTAMP(campanha.inicio)*1000,"
                . "UNIX_TIMESTAMP(campanha.fim)*1000,"
                . "campanha.prazo,"
                . "campanha.parcelas,"
                . "campanha.cliente_expression,"
                . "produto_campanha.id,"
                . "produto_campanha.valor_boas_vindas,"
                . "produto_campanha.limite_boas_vindas,"
                . "produto_campanha.id_produto,"
                . "UNIX_TIMESTAMP(produto_campanha.validade)*1000,"
                . "produto_campanha.limite,"
                . "produto_campanha.de,"
                . "produto_campanha.valor, "
                . "produto.id,"
                . "produto.codigo,"
                . "produto.locais,"
                . "produto.id_universal,"
                . "produto.img,"
                . "produto.liquido,"
                . "produto.quantidade_unidade,"
                . "produto.habilitado,"
                . "produto.valor_base_maximo,"
                . "produto.custo,"
                . "produto.peso_bruto,"
                . "produto.peso_liquido,"
                . "produto.grade,"
                . "produto.unidade,"
                . "produto.ncm,"
                . "produto.nome,"
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
                . "telefone.numero,"
                . "produto.estoque_t,"
                . "produto.disponivel_t,"
                . "produto.transito_t,"
                . "produto.sistema_lotes "
                . "FROM (SELECT * FROM campanha WHERE id_empresa=$this->id AND campanha.excluida=false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $sql .= ") campanha "
                . "INNER JOIN produto_campanha ON campanha.id = produto_campanha.id_campanha "
                . "INNER JOIN (SELECT *,MAX(produto.valor_base) as 'valor_base_maximo',MAX(produto.imagem) as 'img',GROUP_CONCAT(produto.id_logistica separator ',') as 'locais',SUM(produto.estoque) 'estoque_t',SUM(produto.disponivel) as 'disponivel_t',SUM(produto.transito) as 'transito_t' FROM produto GROUP BY produto.codigo,produto.id_empresa) produto ON produto.codigo = produto_campanha.id_produto AND campanha.id_empresa=produto.id_empresa "
                . "INNER JOIN empresa ON produto.id_empresa=empresa.id "
                . "INNER JOIN endereco ON endereco.id_entidade=empresa.id AND endereco.tipo_entidade='EMP' "
                . "INNER JOIN email ON email.id_entidade=empresa.id AND email.tipo_entidade='EMP' "
                . "INNER JOIN telefone ON telefone.id_entidade=empresa.id AND telefone.tipo_entidade='EMP' "
                . "INNER JOIN cidade ON endereco.id_cidade=cidade.id "
                . "INNER JOIN estado ON cidade.id_estado = estado.id ";

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }


        $campanhas = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $camp_nome, $inicio, $fim, $prazo, $parcelas, $cliente, $id_produto_campanha,$valor_boas_vindas,$limite_boas_vindas, $id_produto, $validade, $limite, $de, $valor, $id_pro, $cod_pro, $locais, $id_uni, $img_prod, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $gr, $uni, $ncm, $nome, $ativo, $conc, $cat_id, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone, $estoque, $disponivel, $transito, $sistema_lotes);



        $prods = array();
        $id_order = array();

        while ($ps->fetch()) {

            if (!isset($campanhas[$id])) {

                $id_order[] = $id;

                $campanhas[$id] = new Campanha();
                $campanhas[$id]->id = $id;
                $campanhas[$id]->nome = $camp_nome;
                $campanhas[$id]->inicio = $inicio;
                $campanhas[$id]->fim = $fim;
                $campanhas[$id]->prazo = $prazo;
                $campanhas[$id]->parcelas = $parcelas;
                $campanhas[$id]->cliente_expression = $cliente;


                $campanhas[$id]->empresa = $this;
            }

            $campanha = $campanhas[$id];

            $p = new ProdutoCampanha();
            $p->id = $id_produto_campanha;
            $p->validade = $validade;
            $p->limite = $limite;
            $p->valor = $valor;
            $p->de = $de;
            $p->campanha = $campanha;
            $p->valor_boas_vindas = $valor_boas_vindas;
            $p->limite_boas_vindas = $limite_boas_vindas;

            if (!isset($prods[$id_pro])) {

                $pro = new ProdutoAlocal();
                $pro->locais = explode(',', $locais);
                $pro->id = $id_pro;
                $pro->codigo = $cod_pro;
                $pro->nome = $nome;
                $pro->id_universal = $id_uni;
                $pro->liquido = $liq == 1;
                $pro->quantidade_unidade = $qtd_un;
                $pro->habilitado = $hab;
                $pro->valor_base = $vb;
                $pro->custo = $cus;
                $pro->ativo = $ativo;
                $pro->imagem = $img_prod;
                $pro->concentracao = $conc;
                $pro->peso_bruto = $pb;
                $pro->peso_liquido = $pl;
                $pro->grade = new Grade($gr);
                $pro->unidade = $uni;
                $pro->ncm = $ncm;
                $pro->estoque = $estoque;
                $pro->disponivel = $disponivel;
                $pro->transito = $transito;
                $pro->sistema_lotes = $sistema_lotes;

                $pro->categoria = Sistema::getCategoriaProduto(null, $cat_id);

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

                $pro->empresa = $empresa;

                $locais = array();

                $prods[$cod_pro] = $pro;
            }

            //----


            $campanhas[$id]->produtos[] = $p;

            $p->produto = $prods[$cod_pro]->getReduzido();

            $prods[$cod_pro]->ofertas[] = $p;
        }

        $ps->close();

        foreach ($prods as $key => $pro) {
            $locais = array();
            foreach ($pro->locais as $key => $value) {
                $i = intval($value);
                if ($i === 0) {
                    $locais[] = $empresa;
                    continue;
                }
                $locais[] = Sistema::getLogisticaById($con, $i);
            }
        }

        $real = array();

        foreach ($campanhas as $key => $value) {

            $real[] = $value;
        }

        return $real;
    }

    public function getCountCampanha($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM campanha WHERE id_empresa=$this->id ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
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

    public function getGruposCidades($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT grupo_cidades.id,grupo_cidades.nome,cidade.id,cidade.nome,estado.id,estado.sigla,grupo_cidades.prazo FROM (SELECT * FROM grupo_cidades WHERE id_empresa = $this->id AND excluido=false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $sql .= ") grupo_cidades LEFT JOIN grupo_cidade ON grupo_cidade.id_grupo=grupo_cidades.id LEFT JOIN cidade ON cidade.id=grupo_cidade.id_cidade LEFT JOIN estado ON estado.id=cidade.id_estado";

        $grupos = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $nome, $id_cidade, $nome_cidade, $id_estado, $nome_estado,$prazo);

        while ($ps->fetch()) {

            if (!isset($grupos[$id])) {

                $g = new GrupoCidades();

                $g->id = $id;
                $g->prazo = $prazo;
                $g->nome = $nome;
                $g->empresa = $this;
                $grupos[$id] = $g;
            }

            $c = new Cidade();
            $c->id = $id_cidade;
            $c->nome = $nome_cidade;

            $e = new Estado();
            $e->id = $id_estado;
            $e->sigla = $nome_estado;

            $c->estado = $e;

            $grupos[$id]->cidades[] = $c;
        }

        $ps->close();

        $real = array();

        foreach ($grupos as $key => $grupo) {

            $real[] = $grupo;
        }

        return $real;
    }

    public function getCountGruposCidades($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM grupo_cidades WHERE id_empresa = $this->id AND excluido=false ";

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

    public function getMovimentos($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "movimento.id,"
                . "movimento.baixa_total,"
                . "UNIX_TIMESTAMP(movimento.data)*1000,"
                . "ROUND(movimento.saldo_anterior,2),"
                . "movimento.valor,"
                . "movimento.juros,"
                . "movimento.descontos,"
                . "movimento.estorno,"
                . "movimento.visto,"
                . "vencimento.id,"
                . "vencimento.valor,"
                . "UNIX_TIMESTAMP(vencimento.data)*1000,"
                . "historico.id,"
                . "historico.nome,"
                . "operacao.id,"
                . "operacao.nome,"
                . "operacao.debito,"
                . "banco.id,"
                . "banco.nome,"
                . "banco.codigo,"
                . "banco.saldo,"
                . "banco.conta, "
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
                . "estado_cliente.sigla,"
                . "email_cliente.id,"
                . "email_cliente.endereco,"
                . "email_cliente.senha,"
                . "fornecedor.id,"
                . "fornecedor.codigo, "
                . "fornecedor.nome,"
                . "fornecedor.cnpj,"
                . "fornecedor.habilitado,"
                . "fornecedor.inscricao_estadual,"
                . "endereco_fornecedor.id, "
                . "endereco_fornecedor.rua, "
                . "endereco_fornecedor.numero, "
                . "endereco_fornecedor.bairro, "
                . "endereco_fornecedor.cep, "
                . "cidade_fornecedor.id, "
                . "cidade_fornecedor.nome, "
                . "estado_fornecedor.id, "
                . "estado_fornecedor.sigla,"
                . "email_fornecedor.id,"
                . "email_fornecedor.endereco,"
                . "email_fornecedor.senha, "
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
                . "email_transportadora.id,"
                . "email_transportadora.endereco,"
                . "email_transportadora.senha, "
                . "nota.id,"
                . "nota.saida,"
                . "nota.chave,"
                . "nota.observacao,"
                . "UNIX_TIMESTAMP(nota.data_emissao)*1000,"
                . "nota.influenciar_estoque, "
                . "nota.id_forma_pagamento,"
                . "nota.frete_destinatario_remetente,"
                . "nota.emitida,"
                . "nota.danfe,"
                . "nota.xml,"
                . "nota.numero,"
                . "nota.ficha,"
                . "nota.cancelada,"
                . "nota.protocolo "
                . "FROM movimento "
                . "INNER JOIN vencimento ON vencimento.id=movimento.id_vencimento "
                . "INNER JOIN nota ON nota.id=vencimento.id_nota "
                . "INNER JOIN operacao ON movimento.id_operacao=operacao.id "
                . "INNER JOIN historico ON historico.id=movimento.id_historico "
                . "INNER JOIN banco ON banco.id=movimento.id_banco "
                . "LEFT JOIN cliente ON nota.id_cliente=cliente.id "
                . "LEFT JOIN categoria_cliente ON categoria_cliente.id = cliente.id_categoria "
                . "LEFT JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "LEFT JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "LEFT JOIN email email_cliente ON email_cliente.id_entidade = cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN fornecedor ON nota.id_fornecedor=fornecedor.id "
                . "LEFT JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "LEFT JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "LEFT JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "LEFT JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN transportadora ON nota.id_transportadora=transportadora.id "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN email email_transportadora ON email_transportadora.id_entidade = transportadora.id AND email_transportadora.tipo_entidade='TRA' "
                . "WHERE nota.id_empresa = $this->id ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $transportadoras = array();
        $movimentos = array();
        $fornecedores = array();
        $clientes = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_mov, $baixa_total, $data_mov, $saldo_mov, $valor_mov, $juros_mov, $desc_mov, $estorno, $visto, $id_venc, $val_venc, $data_venc, $hist_id, $hist_nom, $op_id, $op_nom, $op_deb, $ban_id, $ban_nom, $ban_cod, $ban_sal, $ban_con, $id_cliente, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $id_email_cliente, $end_email_cliente, $senh_email_cliente, $id_for, $cod_for, $nom_for, $cnpj_for, $hab_for, $ie_for, $end_for_id, $end_for_rua, $end_for_numero, $end_for_bairro, $end_for_cep, $cid_for_id, $cid_for_nome, $est_for_id, $est_for_nome, $id_email_for, $end_email_for, $sen_email_for, $tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_email_tra, $end_email_tra, $sen_email_tra, $id_nf, $sai_nf, $cha_nf, $obs_nf, $dt_nf, $nf_inf_est, $id_fp_nf, $fdr, $emitida, $danfe, $xml, $numero, $ficha, $cancelada, $protocolo);

        while ($ps->fetch()) {



            $m = new Movimento();
            $m->id = $id_mov;
            $m->data = $data_mov;
            $m->saldo_anterior = $saldo_mov;
            $m->valor = $valor_mov;
            $m->juros = $juros_mov;
            $m->descontos = $desc_mov;
            $m->estorno = $estorno;
            $m->visto = $visto == 1;
            $m->baixa_total = $baixa_total;
            $v = new Vencimento();
            $v->id = $id_venc;
            $v->valor = $val_venc;
            $v->data = $data_venc;

            $v->movimento = $m;
            $m->vencimento = $v;

            $h = new Historico();
            $h->id = $hist_id;
            $h->nome = $hist_nom;

            $m->historico = $h;

            $o = new Operacao();
            $o->id = $op_id;
            $o->nome = $op_nom;
            $o->debito = $op_deb;

            $m->operacao = $o;

            $b = new Banco();
            $b->id = $ban_id;
            $b->nome = $ban_nom;
            $b->codigo = $ban_cod;
            $b->saldo = $ban_sal;
            $b->conta = $ban_con;
            $b->empresa = $this;

            $m->banco = $b;

            $cliente = null;

            if ($id_cliente != null) {

                $cliente = new Cliente();
                $cliente->id = $id_cliente;
                $cliente->codigo = $cod_cli;
                $cliente->cnpj = new CNPJ($cnpj);
                $cliente->cpf = new CPF($cpf);
                $cliente->rg = new RG($rg);
                $cliente->pessoa_fisica = $pessoa_fisica == 1;
                $cliente->nome_fantasia = $nome_fantasia_cliente;
                $cliente->razao_social = $nome_cliente;
                $cliente->empresa = $this;
                $cliente->email = new Email($end_email_cliente);
                $cliente->email->id = $id_email_cliente;
                $cliente->email->senha = $senh_email_cliente;
                $cliente->categoria = new CategoriaCliente();
                $cliente->categoria->id = $cat_id;
                $cliente->categoria->nome = $cat_nome;
                $cliente->inicio_limite = $inicio;
                $cliente->termino_limite = $fim;
                $cliente->limite_credito = $limite;
                $cliente->inscricao_suframa = $i_suf;
                $cliente->suframado = $suf == 1;
                $cliente->empresa = $this;
                $cliente->inscricao_estadual = $ie;

                $end = new Endereco();
                $end->id = $end_cli_id;
                $end->bairro = $end_cli_bairro;
                $end->cep = new CEP($end_cli_cep);
                $end->numero = $end_cli_numero;
                $end->rua = $end_cli_rua;

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
            }

            $fornecedor = null;

            if ($id_for != null) {

                $fornecedor = new Fornecedor();
                $fornecedor->id = $id_for;
                $fornecedor->codigo = $cod_for;
                $fornecedor->nome = $nom_for;
                $fornecedor->habilitado = $hab_for == 1;
                $fornecedor->inscricao_estadual = $ie_for;
                $fornecedor->cnpj = new CNPJ($cnpj_for);
                $fornecedor->empresa = $this;
                $fornecedor->email = new Email($end_email_for);
                $fornecedor->email->id = $id_email_for;
                $fornecedor->email->senha = $sen_email_for;

                $end = new Endereco();
                $end->id = $end_for_id;
                $end->bairro = $end_for_bairro;
                $end->cep = new CEP($end_for_cep);
                $end->numero = $end_for_numero;
                $end->rua = $end_for_rua;

                $end->cidade = new Cidade();
                $end->cidade->id = $cid_for_id;
                $end->cidade->nome = $cid_for_nome;

                $end->cidade->estado = new Estado();
                $end->cidade->estado->id = $est_for_id;
                $end->cidade->estado->sigla = $est_for_nome;

                $fornecedor->endereco = $end;

                if (!isset($fornecedores[$fornecedor->id])) {

                    $fornecedores[$fornecedor->id] = array();
                }

                $fornecedores[$fornecedor->id][] = $fornecedor;
            }

            $transportadora = new Transportadora();
            $transportadora->codigo = $cod_tra;
            $transportadora->id = $tra_id;
            $transportadora->cnpj = new CNPJ($tra_cnpj);
            $transportadora->despacho = $tra_despacho;
            $transportadora->email = new Email($end_email_tra);
            $transportadora->email->id = $id_email_tra;
            $transportadora->email->senha = $sen_email_tra;
            $transportadora->habilitada = $tra_habilitada == 1;
            $transportadora->inscricao_estadual = $tra_ie;
            $transportadora->nome_fantasia = $tra_nome_fantasia;
            $transportadora->razao_social = $tra_nome;
            $transportadora->empresa = $this;

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


            $nota = new Nota();
            $nota->id = $id_nf;
            $nota->chave = $cha_nf;
            $nota->data_emissao = $dt_nf;
            $nota->interferir_estoque = $nf_inf_est;
            $nota->observacao = $obs_nf;
            $nota->saida = $sai_nf == 1;
            $nota->cliente = $cliente;
            $nota->fornecedor = $fornecedor;
            $nota->transportadora = $transportadora;
            $nota->empresa = $this;

            $nota->frete_destinatario_remetente = $fdr == 1;
            $nota->emitida = $emitida == 1;
            $nota->danfe = $danfe;
            $nota->xml = $xml;
            $nota->numero = $numero;
            $nota->ficha = $ficha;
            $nota->cancelada = $cancelada == 1;
            $nota->protocolo = $protocolo;

            $formas = Sistema::getFormasPagamento();

            foreach ($formas as $key => $value) {
                if ($value->id == $id_fp_nf) {
                    $nota->forma_pagamento = $value;
                    break;
                }
            }

            $v->nota = $nota;

            $movimentos[] = $m;
        }

        //---------------------------

        $in_tra = "-1";
        $in_for = "-1";
        $in_cli = "-1";

        foreach ($clientes as $id => $cliente) {
            $in_cli .= ",";
            $in_cli .= $id;
        }

        foreach ($transportadoras as $id => $transportadora) {
            $in_tra .= ",";
            $in_tra .= $id;
        }

        foreach ($fornecedores as $id => $fornecedor) {
            $in_for .= ",";
            $in_for .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN($in_tra) AND telefone.tipo_entidade='TRA') OR (telefone.id_entidade IN ($in_cli) AND telefone.tipo_entidade='CLI') OR (telefone.id_entidade IN ($in_for) AND telefone.tipo_entidade='FOR') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $clientes;
            if ($tipo_entidade == 'TRA') {
                $v = $transportadoras;
            } else if ($tipo_entidade == 'FOR') {
                $v = $fornecedores;
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
                    $t->prazo = $prazo;
                    
                }

                $regra = new RegraTabela();
                $regra->id = $idr;
                $regra->condicional = $cond;
                $regra->resultante = $res;

                $t->tabela->regras[] = $regra;
            }
        }

        $ps->close();


        //---------------------------

        return $movimentos;
    }

    public function getCountMovimentos($con, $filtro = "") {

        $sql = "SELECT COUNT(*) "
                . "FROM movimento "
                . "INNER JOIN vencimento ON vencimento.id=movimento.id_vencimento "
                . "INNER JOIN nota ON nota.id=vencimento.id_nota "
                . "INNER JOIN operacao ON movimento.id_operacao=operacao.id "
                . "INNER JOIN historico ON historico.id=movimento.id_historico "
                . "INNER JOIN banco ON banco.id=movimento.id_banco "
                . "LEFT JOIN cliente ON nota.id_cliente=cliente.id "
                . "LEFT JOIN categoria_cliente ON categoria_cliente.id = cliente.id_categoria "
                . "LEFT JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "LEFT JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "LEFT JOIN email email_cliente ON email_cliente.id_entidade = cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN fornecedor ON nota.id_fornecedor=fornecedor.id "
                . "LEFT JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "LEFT JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "LEFT JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "LEFT JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN transportadora ON nota.id_transportadora=transportadora.id "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN email email_transportadora ON email_transportadora.id_entidade = transportadora.id AND email_transportadora.tipo_entidade='TRA' "
                . "WHERE nota.id_empresa = $this->id ";

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

    public function getNotas($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
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
                . "estado_cliente.sigla,"
                . "email_cliente.id,"
                . "email_cliente.endereco,"
                . "email_cliente.senha,"
                . "fornecedor.id,"
                . "fornecedor.codigo, "
                . "fornecedor.nome,"
                . "fornecedor.cnpj,"
                . "fornecedor.habilitado,"
                . "fornecedor.inscricao_estadual,"
                . "endereco_fornecedor.id, "
                . "endereco_fornecedor.rua, "
                . "endereco_fornecedor.numero, "
                . "endereco_fornecedor.bairro, "
                . "endereco_fornecedor.cep, "
                . "cidade_fornecedor.id, "
                . "cidade_fornecedor.nome, "
                . "estado_fornecedor.id, "
                . "estado_fornecedor.sigla,"
                . "email_fornecedor.id,"
                . "email_fornecedor.endereco,"
                . "email_fornecedor.senha, "
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
                . "email_transportadora.id,"
                . "email_transportadora.endereco,"
                . "email_transportadora.senha, "
                . "nota.id,"
                . "nota.recorrencia,"
                . "nota.saida,"
                . "nota.chave,"
                . "nota.observacao,"
                . "nota.id_forma_pagamento,"
                . "UNIX_TIMESTAMP(nota.data_emissao)*1000,"
                . "nota.influenciar_estoque,"
                . "nota.frete_destinatario_remetente,"
                . "nota.emitida,"
                . "nota.danfe,"
                . "nota.xml,"
                . "nota.numero,"
                . "nota.ficha,"
                . "nota.cancelada,"
                . "nota.protocolo,"
                . "nota.baixa_total,"
                . "nota.id_pedido "
                . "FROM nota "
                . "LEFT JOIN cliente ON nota.id_cliente=cliente.id "
                . "LEFT JOIN categoria_cliente ON categoria_cliente.id = cliente.id_categoria "
                . "LEFT JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "LEFT JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "LEFT JOIN email email_cliente ON email_cliente.id_entidade = cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN fornecedor ON nota.id_fornecedor=fornecedor.id "
                . "LEFT JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "LEFT JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "LEFT JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "LEFT JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN transportadora ON nota.id_transportadora=transportadora.id "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN email email_transportadora ON email_transportadora.id_entidade = transportadora.id AND email_transportadora.tipo_entidade='TRA' "
                . "WHERE nota.id_empresa = $this->id AND nota.excluida = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $transportadoras = array();
        $notas = array();
        $fornecedores = array();
        $clientes = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_cliente, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $id_email_cliente, $end_email_cliente, $senh_email_cliente, $id_for, $cod_for, $nom_for, $cnpj_for, $hab_for, $ie_for, $end_for_id, $end_for_rua, $end_for_numero, $end_for_bairro, $end_for_cep, $cid_for_id, $cid_for_nome, $est_for_id, $est_for_nome, $id_email_for, $end_email_for, $sen_email_for, $tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_email_tra, $end_email_tra, $sen_email_tra, $id_nf,$rec_nf, $sai_nf, $cha_nf, $obs_nf, $id_pag_nf, $dt_nf, $nf_inf_est, $fdr, $emitida, $danfe, $xml, $numero, $ficha, $cancelada, $protocolo, $baixa_total, $id_pedido);

        while ($ps->fetch()) {

            $cliente = null;

            if ($id_cliente != null) {

                $cliente = new Cliente();
                $cliente->id = $id_cliente;
                $cliente->codigo = $cod_cli;
                $cliente->cnpj = new CNPJ($cnpj);
                $cliente->cpf = new CPF($cpf);
                $cliente->rg = new RG($rg);
                $cliente->pessoa_fisica = $pessoa_fisica == 1;
                $cliente->nome_fantasia = $nome_fantasia_cliente;
                $cliente->razao_social = $nome_cliente;
                $cliente->empresa = $this;
                $cliente->email = new Email($end_email_cliente);
                $cliente->email->id = $id_email_cliente;
                $cliente->email->senha = $senh_email_cliente;
                $cliente->categoria = new CategoriaCliente();
                $cliente->categoria->id = $cat_id;
                $cliente->categoria->nome = $cat_nome;
                $cliente->inicio_limite = $inicio;
                $cliente->termino_limite = $fim;
                $cliente->limite_credito = $limite;
                $cliente->inscricao_suframa = $i_suf;
                $cliente->suframado = $suf == 1;
                $cliente->empresa = $this;
                $cliente->inscricao_estadual = $ie;

                $end = new Endereco();
                $end->id = $end_cli_id;
                $end->bairro = $end_cli_bairro;
                $end->cep = new CEP($end_cli_cep);
                $end->numero = $end_cli_numero;
                $end->rua = $end_cli_rua;

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
            }

            $fornecedor = null;

            if ($id_for != null) {

                $fornecedor = new Fornecedor();
                $fornecedor->id = $id_for;
                $fornecedor->codigo = $cod_for;
                $fornecedor->nome = $nom_for;
                $fornecedor->habilitado = $hab_for == 1;
                $fornecedor->inscricao_estadual = $ie_for;
                $fornecedor->cnpj = new CNPJ($cnpj_for);
                $fornecedor->empresa = $this;
                $fornecedor->email = new Email($end_email_for);
                $fornecedor->email->id = $id_email_for;
                $fornecedor->email->senha = $sen_email_for;

                $end = new Endereco();
                $end->id = $end_for_id;
                $end->bairro = $end_for_bairro;
                $end->cep = new CEP($end_for_cep);
                $end->numero = $end_for_numero;
                $end->rua = $end_for_rua;

                $end->cidade = new Cidade();
                $end->cidade->id = $cid_for_id;
                $end->cidade->nome = $cid_for_nome;

                $end->cidade->estado = new Estado();
                $end->cidade->estado->id = $est_for_id;
                $end->cidade->estado->sigla = $est_for_nome;

                $fornecedor->endereco = $end;

                if (!isset($fornecedores[$fornecedor->id])) {

                    $fornecedores[$fornecedor->id] = array();
                }

                $fornecedores[$fornecedor->id][] = $fornecedor;
            }

            $transportadora = new Transportadora();
            $transportadora->id = $tra_id;
            $transportadora->codigo = $cod_tra;
            $transportadora->cnpj = new CNPJ($tra_cnpj);
            $transportadora->despacho = $tra_despacho;
            $transportadora->email = new Email($end_email_tra);
            $transportadora->email->id = $id_email_tra;
            $transportadora->email->senha = $sen_email_tra;
            $transportadora->habilitada = $tra_habilitada == 1;
            $transportadora->inscricao_estadual = $tra_ie;
            $transportadora->nome_fantasia = $tra_nome_fantasia;
            $transportadora->razao_social = $tra_nome;
            $transportadora->empresa = $this;

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


            $nota = new Nota();
            $nota->id = $id_nf;
            $nota->recorrencia = $rec_nf;
            $nota->chave = $cha_nf;
            $nota->frete_destinatario_remetente = $fdr == 1;
            $nota->data_emissao = $dt_nf;
            $nota->interferir_estoque = $nf_inf_est;
            $nota->observacao = $obs_nf;
            $nota->saida = $sai_nf == 1;
            $nota->cliente = $cliente;
            $nota->fornecedor = $fornecedor;
            $nota->transportadora = $transportadora;
            $nota->empresa = $this;
            $nota->id_pedido = $id_pedido;
            $nota->emitida = $emitida == 1;
            $nota->danfe = $danfe;
            $nota->xml = $xml;
            $nota->numero = $numero;
            $nota->ficha = $ficha;
            $nota->cancelada = $cancelada == 1;
            $nota->protocolo = $protocolo;
            $nota->baixa_total = $baixa_total;

            $formas = Sistema::getFormasPagamento();

            foreach ($formas as $key => $value) {
                if ($value->id == $id_pag_nf) {
                    $nota->forma_pagamento = $value;
                    break;
                }
            }

            $notas[] = $nota;
        }

        //---------------------------

        $in_tra = "-1";
        $in_for = "-1";
        $in_cli = "-1";

        foreach ($clientes as $id => $cliente) {
            $in_cli .= ",";
            $in_cli .= $id;
        }

        foreach ($transportadoras as $id => $transportadora) {
            $in_tra .= ",";
            $in_tra .= $id;
        }

        foreach ($fornecedores as $id => $fornecedor) {
            $in_for .= ",";
            $in_for .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN($in_tra) AND telefone.tipo_entidade='TRA') OR (telefone.id_entidade IN ($in_cli) AND telefone.tipo_entidade='CLI') OR (telefone.id_entidade IN ($in_for) AND telefone.tipo_entidade='FOR') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $clientes;
            if ($tipo_entidade == 'TRA') {
                $v = $transportadoras;
            } else if ($tipo_entidade == 'FOR') {
                $v = $fornecedores;
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
                    $t->tabela->prazo = $prazo;
                    $t->tabela->id = $id;
                }

                $regra = new RegraTabela();
                $regra->id = $idr;
                $regra->condicional = $cond;
                $regra->resultante = $res;

                $t->tabela->regras[] = $regra;
            }
        }

        $ps->close();


        //---------------------------

        return $notas;
    }

    public function getCountNotas($con, $filtro = "") {

        $sql = "SELECT COUNT(*) "
                . "FROM nota "
                . "LEFT JOIN cliente ON nota.id_cliente=cliente.id "
                . "LEFT JOIN categoria_cliente ON categoria_cliente.id = cliente.id_categoria "
                . "LEFT JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "LEFT JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "LEFT JOIN email email_cliente ON email_cliente.id_entidade = cliente.id AND email_cliente.tipo_entidade='CLI' "
                . "LEFT JOIN fornecedor ON nota.id_fornecedor=fornecedor.id "
                . "LEFT JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "LEFT JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "LEFT JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "LEFT JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN transportadora ON nota.id_transportadora=transportadora.id "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN email email_transportadora ON email_transportadora.id_entidade = transportadora.id AND email_transportadora.tipo_entidade='TRA' "
                . "WHERE nota.id_empresa = $this->id AND nota.excluida = false ";

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

    public function getPedidosEntrada($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "pedido_entrada.id, "
                . "pedido_entrada.entrega, "
                . "pedido_entrada.frete_inclusao, "
                . "UNIX_TIMESTAMP(pedido_entrada.data)*1000, "
                . "pedido_entrada.prazo, "
                . "pedido_entrada.parcelas, "
                . "pedido_entrada.id_status, "
                . "pedido_entrada.frete, "
                . "pedido_entrada.observacoes, "
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
                . "email_tra.id, "
                . "email_tra.endereco, "
                . "email_tra.senha, "
                . "email_usu.id, "
                . "email_usu.endereco,"
                . "email_usu.senha, "
                . "fornecedor.id,"
                . "fornecedor.codigo, "
                . "fornecedor.nome,"
                . "fornecedor.cnpj,"
                . "fornecedor.habilitado,"
                . "fornecedor.inscricao_estadual,"
                . "endereco_fornecedor.id, "
                . "endereco_fornecedor.rua, "
                . "endereco_fornecedor.numero, "
                . "endereco_fornecedor.bairro, "
                . "endereco_fornecedor.cep, "
                . "cidade_fornecedor.id, "
                . "cidade_fornecedor.nome, "
                . "estado_fornecedor.id, "
                . "estado_fornecedor.sigla,"
                . "email_fornecedor.id,"
                . "email_fornecedor.endereco,"
                . "email_fornecedor.senha "
                . "FROM pedido_entrada "
                . "INNER JOIN fornecedor ON pedido_entrada.id_fornecedor=fornecedor.id "
                . "INNER JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "INNER JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "INNER JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN transportadora ON transportadora.id = pedido_entrada.id_transportadora "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN usuario ON usuario.id=pedido_entrada.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN email email_tra ON email_tra.id_entidade=transportadora.id AND email_tra.tipo_entidade='TRA' "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE pedido_entrada.id_empresa = $this->id AND pedido_entrada.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pedido,$entrega, $frete_incluso, $data, $prazo, $parcelas, $id_status, $frete, $obs, $tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_usu, $nome_usu, $login_usu, $senha_usu, $cpf_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_tra_id, $email_tra_end, $email_tra_senha, $email_usu_id, $email_usu_end, $email_usu_senha, $id_for, $cod_for, $nom_for, $cnpj_for, $hab_for, $ie_for, $end_for_id, $end_for_rua, $end_for_numero, $end_for_bairro, $end_for_cep, $cid_for_id, $cid_for_nome, $est_for_id, $est_for_nome, $id_email_for, $end_email_for, $sen_email_for);


        $pedidos = array();
        $transportadoras = array();
        $usuarios = array();
        $fornecedores = array();

        while ($ps->fetch()) {

            $fornecedor = new Fornecedor();
            $fornecedor->id = $id_for;
            $fornecedor->codigo = $cod_for;
            $fornecedor->nome = $nom_for;
            $fornecedor->habilitado = $hab_for;
            $fornecedor->inscricao_estadual = $ie_for;
            $fornecedor->cnpj = new CNPJ($cnpj_for);
            $fornecedor->empresa = $this;
            $fornecedor->email = new Email($end_email_for);
            $fornecedor->email->id = $id_email_for;
            $fornecedor->email->senha = $sen_email_for;

            $end = new Endereco();
            $end->id = $end_for_id;
            $end->bairro = $end_for_bairro;
            $end->cep = new CEP($end_for_cep);
            $end->numero = $end_for_numero;
            $end->rua = $end_for_rua;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_for_id;
            $end->cidade->nome = $cid_for_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_for_id;
            $end->cidade->estado->sigla = $est_for_nome;

            $fornecedor->endereco = $end;

            if (!isset($fornecedores[$fornecedor->id])) {

                $fornecedores[$fornecedor->id] = array();
            }

            $fornecedores[$fornecedor->id][] = $fornecedor;


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
            $transportadora->empresa = $this;

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
            $usuario->empresa = $this;

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


            $pedido = new PedidoEntrada();

            $pedido->fornecedor = $fornecedor;
            $pedido->data = $data;
            $pedido->entrega = $entrega;
            $pedido->empresa = $this;
            $pedido->frete = $frete;
            $pedido->frete_incluso = $frete_incluso;
            $pedido->id = $id_pedido;
            $pedido->observacoes = $obs;
            $pedido->parcelas = $parcelas;
            $pedido->prazo = $prazo;

            $status = Sistema::getStatusPedidoEntrada();

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

        $in_tra = "-1";
        $in_usu = "-1";
        $in_for = "-1";

        foreach ($fornecedores as $id => $fornecedor) {
            $in_for .= ",";
            $in_for .= $id;
        }

        foreach ($transportadoras as $id => $transportadora) {
            $in_tra .= ",";
            $in_tra .= $id;
        }

        foreach ($usuarios as $id => $usuario) {
            $in_usu .= ",";
            $in_usu .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN($in_tra) AND telefone.tipo_entidade='TRA') OR (telefone.id_entidade IN ($in_for) AND telefone.tipo_entidade='FOR') OR (telefone.id_entidade IN ($in_usu) AND telefone.tipo_entidade='USU') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $fornecedores;
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
                    $t->tabela->prazo = $prazo;
                    $t->tabela->id = $id;
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

        return $pedidos;
    }

    public function getCountPedidosEntrada($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM pedido_entrada INNER JOIN fornecedor ON fornecedor.id=pedido_entrada.id_fornecedor WHERE pedido_entrada.id_empresa=$this->id AND pedido_entrada.excluido=false ";

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

    public function getFornecedores($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "fornecedor.id,"
                . "fornecedor.codigo_contimatic,"
                . "fornecedor.codigo, "
                . "fornecedor.nome,"
                . "fornecedor.cnpj,"
                . "fornecedor.habilitado,"
                . "fornecedor.inscricao_estadual,"
                . "endereco_fornecedor.id, "
                . "endereco_fornecedor.rua, "
                . "endereco_fornecedor.numero, "
                . "endereco_fornecedor.bairro, "
                . "endereco_fornecedor.cep, "
                . "cidade_fornecedor.id, "
                . "cidade_fornecedor.nome, "
                . "estado_fornecedor.id, "
                . "estado_fornecedor.sigla,"
                . "email_fornecedor.id,"
                . "email_fornecedor.endereco,"
                . "email_fornecedor.senha "
                . "FROM fornecedor "
                . "INNER JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "INNER JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "INNER JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "WHERE fornecedor.id_empresa=$this->id AND fornecedor.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_for, $cod_ctm, $cod_for, $nom_for, $cnpj_for, $hab, $ie, $end_for_id, $end_for_rua, $end_for_numero, $end_for_bairro, $end_for_cep, $cid_for_id, $cid_for_nome, $est_for_id, $est_for_nome, $id_email_for, $end_email_for, $sen_email_for);

        $fornecedores = array();

        while ($ps->fetch()) {

            $fornecedor = new Fornecedor();
            $fornecedor->id = $id_for;
            $fornecedor->codigo_contimatic = $cod_ctm;
            $fornecedor->codigo = $cod_for;
            $fornecedor->habilitado = $hab == 1;
            $fornecedor->inscricao_estadual = $ie;
            $fornecedor->nome = $nom_for;
            $fornecedor->cnpj = new CNPJ($cnpj_for);
            $fornecedor->empresa = $this;
            $fornecedor->email = new Email($end_email_for);
            $fornecedor->email->id = $id_email_for;
            $fornecedor->email->senha = $sen_email_for;

            $end = new Endereco();
            $end->id = $end_for_id;
            $end->bairro = $end_for_bairro;
            $end->cep = new CEP($end_for_cep);
            $end->numero = $end_for_numero;
            $end->rua = $end_for_rua;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_for_id;
            $end->cidade->nome = $cid_for_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_for_id;
            $end->cidade->estado->sigla = $est_for_nome;

            $fornecedor->endereco = $end;

            $fornecedores[$id_for] = $fornecedor;
        }

        $ps->close();

        $in_for = "-1";

        foreach ($fornecedores as $id => $fornecedor) {
            $in_for .= ",";
            $in_for .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN ($in_for) AND telefone.tipo_entidade='FOR') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $fornecedores;

            $telefone = new Telefone($numero);
            $telefone->id = $id;

            $v[$id_entidade]->telefones[] = $telefone;
        }
        $ps->close();


        $real = array();

        foreach ($fornecedores as $key => $value) {

            $real[] = $value;
        }

        return $real;
    }

    public function getCountFornecedores($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM fornecedor LEFT JOIN email email_fornecedor ON email_fornecedor.id_entidade=fornecedor.id AND email_fornecedor.tipo_entidade='FOR' LEFT JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' LEFT JOIN cidade cidade_fornecedor ON cidade_fornecedor.id=endereco_fornecedor.id_cidade LEFT JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado WHERE fornecedor.id_empresa=$this->id AND fornecedor.excluido=false ";

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

    public function getCountProtocolos($con, $filtro = "") {

        $empresas = "($this->id";

        $filiais = $this->getFiliais($con);

        foreach ($filiais as $key => $value) {
            $empresas .= ",$value->id";
        }

        $empresas .= ")";

        $sql = "SELECT COUNT(*) "
                . "FROM protocolo p "
                . "INNER JOIN tipo_protocolo tp ON p.id_tipo=tp.id "
                . "INNER JOIN empresa e ON e.id=p.id_empresa "
                . "WHERE e.id IN $empresas ";

        if ($filtro !== "") {

            $sql .= "AND $filtro ";
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

    public function getProtocolosAprovar($con, $x1, $x2, $filtro = "", $ordem = "",$usuario) {

        $empresas = "($this->id";

        $filiais = $this->getFiliais($con);

        foreach ($filiais as $key => $value) {
            $empresas .= ",$value->id";
        }

        $empresas .= ")";

        $sql = "SELECT "
                . "p.id,"
                . "IFNULL(p.aberturas_usuario,''),"
                . "p.titulo,"
                . "p.descricao,"
                . "tp.id,"
                . "tp.nome,"
                . "tp.prioridade,"
                . "tp.cobranca,"
                . "UNIX_TIMESTAMP(p.inicio)*1000,"
                . "UNIX_TIMESTAMP(p.fim)*1000,"
                . "p.tipo_entidade,"
                . "p.id_entidade,"
                . "p.iniciado_por,"
                . "p.aprovado,"
                . "e.id,"
                . "e.nome "
                . "FROM protocolo p "
                . "INNER JOIN tipo_protocolo tp ON p.id_tipo=tp.id "
                . "INNER JOIN empresa e ON e.id=p.id_empresa "
                . "INNER JOIN aprovador_protocolo a ON a.id_tipo_protocolo = tp.id AND a.id_empresa=e.id "
                . "INNER JOIN usuario u ON a.id_usuario = u.id "
                . "WHERE e.id IN $empresas AND p.fim IS NULL AND p.aprovado = 0 AND u.cpf = '".$usuario->cpf->valor."' ";

        if ($filtro !== "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem !== "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ids_protocolos = "(-1";
        $protocolos = array();
        
        $ids_tipos = "(-1";
        
        $aberturas = array();
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $abt, $titulo, $descricao, $id_tipo, $nome_tipo, $prioridade_tipo,$cobranca, $inicio, $fim, $tipo_entidade, $id_entidade, $iniciado_por,$aprovado, $id_empresa, $nome_empresa);
        while ($ps->fetch()) {

            $t = new TipoProtocolo();
            $t->id = $id_tipo;
            $t->cobranca = $cobranca;
            $t->nome = $nome_tipo;
            $t->empresa = $this;
            $t->prioridade = $prioridade_tipo;
            $ids_tipos .= ",$id";
            
            $e = new Empresa();
            $e->id = $id_empresa;
            $e->nome = $nome_empresa;

            $p = new Protocolo();
            $p->id = $id;
            $p->aprovado = $aprovado;
            $p->titulo = $titulo;
            $p->descricao = $descricao;
            $p->inicio = $inicio;
            $p->fim = $fim;
            $p->tipo_entidade = $tipo_entidade;
            $p->id_entidade = $id_entidade;
            $p->iniciado_por = $iniciado_por;
            $p->empresa = $e;
            $p->tipo = $t;

            $protocolos[$id] = $p;
            
            
            $ab = explode(';',$abt);
            $tmp = array();
            foreach($ab as $key=>$value){
                if($value === "")continue;
                
                $t = explode('-',$value);
                $t[0] = intval($t[0]);
                $t[1] = intval($t[1]);
                
                $tmp[] = $t;
                
            }
            
            $aberturas[$id] = $tmp;
            

            $ids_protocolos .= ",$id";
        }
        
        $ids_tipos .= ")";
        $ids_protocolos .= ")";

        $ps->close();
        
        
        if($usuario !== null){
            
            foreach($aberturas as $id_protocolo=>$abertura){
                foreach($abertura as $k=>$ab){
                    if($ab[0] === $usuario->id){
                        if(($ab[1]+1)>$protocolos[$id_protocolo]->tipo->cobranca){
                            $protocolos[$id_protocolo]->alertar = false;
                        }
                        $aberturas[$id_protocolo][$k][1]++;
                        continue 2;
                    }
                }
                $aberturas[$id_protocolo][] = array($usuario->id,1);
            }
            
            foreach($aberturas as $id_protocolo=>$abertura){
                $str = "";
                foreach($abertura as $k=>$ab){
                    $str .= $ab[0]."-".$ab[1].";";
                }
                $ps=$con->getConexao()->prepare("UPDATE protocolo SET inicio=inicio,fim=fim,aberturas_usuario='$str' WHERE id=$id_protocolo");
                $ps->execute();
                $ps->close();
            }
            
        }

        $ps = $con->getConexao()->prepare("SELECT id,mensagem,UNIX_TIMESTAMP(momento)*1000,dados_usuario,id_protocolo FROM mensagem_protocolo WHERE id_protocolo IN $ids_protocolos");
        $ps->execute();
        $ps->bind_result($id, $mensagem, $momento, $dados_usuario, $id_protocolo);
        while ($ps->fetch()) {

            $m = new MensagemProtocolo();
            $m->id = $id;
            $m->mensagem = $mensagem;
            $m->momento = $momento;
            $m->dados_usuario = $dados_usuario;
            $m->protocolo = $protocolos[$id_protocolo];

            $protocolos[$id_protocolo]->chat[] = $m;
        }

        $ps->close();

        $ps = $con->getConexao()->prepare("SELECT u.id,u.nome,u.cpf,p.id_protocolo FROM usuario u INNER JOIN protocolo_usuario p ON p.id_usuario=u.id AND p.id_protocolo IN $ids_protocolos");
        $ps->execute();
        $ps->bind_result($id_usuario,$nome_usuario,$cpf_usuario,$id_protocolo);
        while($ps->fetch()){

            $u = new Usuario();
            $u->id = $id_usuario;
            $u->nome = $nome_usuario;
            $u->cpf = new CPF($cpf_usuario);

            $protocolos[$id_protocolo]->usuarios[] = $u;

        }
        $ps->close();

        $usuarios_tipo = array();
        
        $ps = $con->getConexao()->prepare("SELECT u.id,u.nome,u.cpf,a.id_tipo_protocolo FROM usuario u INNER JOIN aprovador_protocolo a ON a.id_usuario=u.id AND a.id_empresa=$this->id AND a.id_tipo_protocolo IN $ids_tipos");
        $ps->execute();
        $ps->bind_result($id,$nome,$cpf,$tipo);
        while($ps->fetch()){
            
            $u = new Usuario();
            $u->id = $id;
            $u->nome = $nome;
            $u->cpf = new CPF($cpf);
                
            $usuarios_tipo[$tipo] = $u;
            
        }
        $ps->close();
        
        
        $retorno = array();

        foreach ($protocolos as $key => $value) {
            
            if(isset($usuarios_tipo[$value->tipo->id])){
                
                $value->tipo->aprovador = $usuarios_tipo[$value->tipo->id];
                
            }
            
            $retorno[] = $value;
        }

        return $retorno;
    }

    
    
    public function getProtocolos($con, $x1, $x2, $filtro = "", $ordem = "",$usuario=null) {

        $empresas = "($this->id";

        $filiais = $this->getFiliais($con);

        foreach ($filiais as $key => $value) {
            $empresas .= ",$value->id";
        }

        $empresas .= ")";

        $sql = "SELECT "
                . "p.id,"
                . "IFNULL(p.aberturas_usuario,''),"
                . "p.titulo,"
                . "p.descricao,"
                . "tp.id,"
                . "tp.nome,"
                . "tp.prioridade,"
                . "tp.cobranca,"
                . "UNIX_TIMESTAMP(p.inicio)*1000,"
                . "UNIX_TIMESTAMP(p.fim)*1000,"
                . "p.tipo_entidade,"
                . "p.id_entidade,"
                . "p.iniciado_por,"
                . "p.aprovado,"
                . "e.id,"
                . "e.nome "
                . "FROM protocolo p "
                . "INNER JOIN tipo_protocolo tp ON p.id_tipo=tp.id "
                . "INNER JOIN empresa e ON e.id=p.id_empresa "
                . "WHERE e.id IN $empresas ";

        if($usuario !== null){

            $sql .= "AND p.id IN (SELECT pp.id_protocolo FROM protocolo_usuario pp INNER JOIN usuario u ON pp.id_usuario=u.id WHERE u.cpf='".$usuario->cpf->valor."') ";

        }

        if ($filtro !== "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem !== "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ids_protocolos = "(-1";
        $protocolos = array();
        
        $ids_tipos = "(-1";
        
        $aberturas = array();
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $abt, $titulo, $descricao, $id_tipo, $nome_tipo, $prioridade_tipo,$cobranca, $inicio, $fim, $tipo_entidade, $id_entidade, $iniciado_por,$aprovado, $id_empresa, $nome_empresa);
        while ($ps->fetch()) {

            $t = new TipoProtocolo();
            $t->id = $id_tipo;
            $t->cobranca = $cobranca;
            $t->nome = $nome_tipo;
            $t->empresa = $this;
            $t->prioridade = $prioridade_tipo;
            $ids_tipos .= ",$id";
            
            $e = new Empresa();
            $e->id = $id_empresa;
            $e->nome = $nome_empresa;

            $p = new Protocolo();
            $p->id = $id;
            $p->aprovado = $aprovado;
            $p->titulo = $titulo;
            $p->descricao = $descricao;
            $p->inicio = $inicio;
            $p->fim = $fim;
            $p->tipo_entidade = $tipo_entidade;
            $p->id_entidade = $id_entidade;
            $p->iniciado_por = $iniciado_por;
            $p->empresa = $e;
            $p->tipo = $t;

            $protocolos[$id] = $p;
            
            
            $ab = explode(';',$abt);
            $tmp = array();
            foreach($ab as $key=>$value){
                if($value === "")continue;
                
                $t = explode('-',$value);
                $t[0] = intval($t[0]);
                $t[1] = intval($t[1]);
                
                $tmp[] = $t;
                
            }
            
            $aberturas[$id] = $tmp;
            

            $ids_protocolos .= ",$id";
        }
        
        $ids_tipos .= ")";
        $ids_protocolos .= ")";

        $ps->close();
        
        
        if($usuario !== null){
            
            foreach($aberturas as $id_protocolo=>$abertura){
                foreach($abertura as $k=>$ab){
                    if($ab[0] === $usuario->id){
                        if(($ab[1]+1)>$protocolos[$id_protocolo]->tipo->cobranca){
                            $protocolos[$id_protocolo]->alertar = false;
                        }
                        $aberturas[$id_protocolo][$k][1]++;
                        continue 2;
                    }
                }
                $aberturas[$id_protocolo][] = array($usuario->id,1);
            }
            
            foreach($aberturas as $id_protocolo=>$abertura){
                $str = "";
                foreach($abertura as $k=>$ab){
                    $str .= $ab[0]."-".$ab[1].";";
                }
                $ps=$con->getConexao()->prepare("UPDATE protocolo SET inicio=inicio,fim=fim,aberturas_usuario='$str' WHERE id=$id_protocolo");
                $ps->execute();
                $ps->close();
            }
            
        }

        $ps = $con->getConexao()->prepare("SELECT id,mensagem,UNIX_TIMESTAMP(momento)*1000,dados_usuario,id_protocolo FROM mensagem_protocolo WHERE id_protocolo IN $ids_protocolos");
        $ps->execute();
        $ps->bind_result($id, $mensagem, $momento, $dados_usuario, $id_protocolo);
        while ($ps->fetch()) {

            $m = new MensagemProtocolo();
            $m->id = $id;
            $m->mensagem = $mensagem;
            $m->momento = $momento;
            $m->dados_usuario = $dados_usuario;
            $m->protocolo = $protocolos[$id_protocolo];

            $protocolos[$id_protocolo]->chat[] = $m;
        }

        $ps->close();

        $ps = $con->getConexao()->prepare("SELECT u.id,u.nome,u.cpf,p.id_protocolo FROM usuario u INNER JOIN protocolo_usuario p ON p.id_usuario=u.id AND p.id_protocolo IN $ids_protocolos");
        $ps->execute();
        $ps->bind_result($id_usuario,$nome_usuario,$cpf_usuario,$id_protocolo);
        while($ps->fetch()){

            $u = new Usuario();
            $u->id = $id_usuario;
            $u->nome = $nome_usuario;
            $u->cpf = new CPF($cpf_usuario);

            $protocolos[$id_protocolo]->usuarios[] = $u;

        }
        $ps->close();

        $usuarios_tipo = array();
        
        $ps = $con->getConexao()->prepare("SELECT u.id,u.nome,u.cpf,a.id_tipo_protocolo FROM usuario u INNER JOIN aprovador_protocolo a ON a.id_usuario=u.id AND a.id_empresa=$this->id AND a.id_tipo_protocolo IN $ids_tipos");
        $ps->execute();
        $ps->bind_result($id,$nome,$cpf,$tipo);
        while($ps->fetch()){
            
            $u = new Usuario();
            $u->id = $id;
            $u->nome = $nome;
            $u->cpf = new CPF($cpf);
                
            $usuarios_tipo[$tipo] = $u;
            
        }
        $ps->close();
        
        
        $retorno = array();

        foreach ($protocolos as $key => $value) {
            
            if(isset($usuarios_tipo[$value->tipo->id])){
                
                $value->tipo->aprovador = $usuarios_tipo[$value->tipo->id];
                
            }
            
            $retorno[] = $value;
        }

        return $retorno;
    }


    public function getTiposProtocolo($con) {

        $tipos = array();

        $ps = $con->getConexao()->prepare("SELECT t.id,t.nome,t.prioridade,t.cobranca,u.id,u.nome,u.cpf FROM tipo_protocolo t "
                . "LEFT JOIN aprovador_protocolo a ON a.id_tipo_protocolo=t.id AND a.id_empresa=$this->id "
                . "LEFT JOIN usuario u ON u.id=a.id_usuario WHERE t.excluido = false");
        $ps->execute();
        $ps->bind_result($id, $nome, $prioridade, $cobranca, $id_usuario, $nome_usuario,$cpf_usuario);

        while ($ps->fetch()) {

            $tp = new TipoProtocolo();
            $tp->id = $id;
            $tp->nome = $nome;
            $tp->cobranca = $cobranca;
            $tp->prioridade = $prioridade;
            $tp->empresa = $this;
            
            if($id_usuario !== null){
                
                $u = new Usuario();
                $u->id = $id_usuario;
                $u->nome = $nome_usuario;
                $u->cpf = new CPF($cpf_usuario);
                
                $tp->aprovador = $u;
                
            }
            
            $tipos[] = $tp;
        }

        $ps->close();

        return $tipos;
    }

    public function getCotacoesEntrada($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "cotacao_entrada.id,"
                . "cotacao_entrada.observacao,"
                . "cotacao_entrada.frete, "
                . "cotacao_entrada.local_entrega, "
                . "UNIX_TIMESTAMP(cotacao_entrada.data)*1000, "
                . "cotacao_entrada.tratar_em_litros, "
                . "cotacao_entrada.id_status, "
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
                . "email_usu.id, "
                . "email_usu.endereco,"
                . "email_usu.senha, "
                . "fornecedor.id,"
                . "fornecedor.codigo, "
                . "fornecedor.nome,"
                . "fornecedor.cnpj,"
                . "fornecedor.habilitado,"
                . "fornecedor.inscricao_estadual,"
                . "endereco_fornecedor.id, "
                . "endereco_fornecedor.rua, "
                . "endereco_fornecedor.numero, "
                . "endereco_fornecedor.bairro, "
                . "endereco_fornecedor.cep, "
                . "cidade_fornecedor.id, "
                . "cidade_fornecedor.nome, "
                . "estado_fornecedor.id, "
                . "estado_fornecedor.sigla,"
                . "email_fornecedor.id,"
                . "email_fornecedor.endereco,"
                . "email_fornecedor.senha,"
                . "CASE WHEN recusa.recusada IS NULL THEN false ELSE true END "
                . "FROM cotacao_entrada "
                . "INNER JOIN fornecedor ON cotacao_entrada.id_fornecedor=fornecedor.id "
                . "INNER JOIN endereco endereco_fornecedor ON endereco_fornecedor.id_entidade=fornecedor.id AND endereco_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN cidade cidade_fornecedor ON endereco_fornecedor.id_cidade=cidade_fornecedor.id "
                . "INNER JOIN estado estado_fornecedor ON estado_fornecedor.id=cidade_fornecedor.id_estado "
                . "INNER JOIN email email_fornecedor ON email_fornecedor.id_entidade = fornecedor.id AND email_fornecedor.tipo_entidade='FOR' "
                . "INNER JOIN usuario ON usuario.id=cotacao_entrada.id_usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "LEFT JOIN (SELECT pc.id_cotacao as 'recusada' FROM produto_cotacao_entrada pc WHERE checado>1 GROUP BY pc.id_cotacao) recusa ON recusa.recusada=cotacao_entrada.id "
                . "WHERE cotacao_entrada.id_empresa = $this->id AND cotacao_entrada.excluida = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        $sql .= "ORDER BY recusa.recusada DESC ";

        if ($ordem != "") {

            $sql .= ",$ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_cotacao, $obs, $frete, $local_entrega, $data, $em_litros, $id_status, $id_usu, $nome_usu, $login_usu, $senha_usu, $cpf_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_usu_id, $email_usu_end, $email_usu_senha, $id_for, $cod_for, $nom_for, $cnpj_for, $hab, $ie, $end_for_id, $end_for_rua, $end_for_numero, $end_for_bairro, $end_for_cep, $cid_for_id, $cid_for_nome, $est_for_id, $est_for_nome, $id_email_for, $end_email_for, $sen_email_for, $recusada);

        $cotacoes = array();
        $usuarios = array();
        $fornecedores = array();

        while ($ps->fetch()) {

            $fornecedor = new Fornecedor();
            $fornecedor->id = $id_for;
            $fornecedor->codigo = $cod_for;
            $fornecedor->nome = $nom_for;
            $fornecedor->habilitado = $hab == 1;
            $fornecedor->inscricao_estadual = $ie;
            $fornecedor->cnpj = new CNPJ($cnpj_for);
            $fornecedor->empresa = $this;
            $fornecedor->email = new Email($end_email_for);
            $fornecedor->email->id = $id_email_for;
            $fornecedor->email->senha = $sen_email_for;

            $end = new Endereco();
            $end->id = $end_for_id;
            $end->bairro = $end_for_bairro;
            $end->cep = new CEP($end_for_cep);
            $end->numero = $end_for_numero;
            $end->rua = $end_for_rua;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_for_id;
            $end->cidade->nome = $cid_for_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_for_id;
            $end->cidade->estado->sigla = $est_for_nome;

            $fornecedor->endereco = $end;

            if (!isset($fornecedores[$fornecedor->id])) {

                $fornecedores[$fornecedor->id] = array();
            }

            $fornecedores[$fornecedor->id][] = $fornecedor;

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
            $usuario->empresa = $this;

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


            $cotacao = new CotacaoEntrada();

            $cotacao->id = $id_cotacao;
            $cotacao->local_entrega = $local_entrega;
            $cotacao->observacao = $obs;
            $cotacao->fornecedor = $fornecedor;
            $cotacao->data = $data;
            $cotacao->empresa = $this;
            $cotacao->frete = $frete;
            $cotacao->recusada = $recusada;
            $cotacao->tratar_em_litros = $em_litros;

            $status = Sistema::getStatusCotacaoEntrada();

            foreach ($status as $key => $st) {
                if ($st->id == $id_status) {
                    $cotacao->status = $st;
                    break;
                }
            }

            $cotacao->usuario = $usuario;

            $cotacoes[] = $cotacao;
        }

        $ps->close();

        $in_usu = "-1";
        $in_for = "-1";

        foreach ($fornecedores as $id => $fornecedor) {
            $in_for .= ",";
            $in_for .= $id;
        }

        foreach ($usuarios as $id => $usuario) {
            $in_usu .= ",";
            $in_usu .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN ($in_for) AND telefone.tipo_entidade='FOR') OR (telefone.id_entidade IN ($in_usu) AND telefone.tipo_entidade='USU') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $fornecedores;
            if ($tipo_entidade == 'USU') {
                $v = $usuarios;
            }

            $telefone = new Telefone($numero);
            $telefone->id = $id;

            foreach ($v[$id_entidade] as $key => $ent) {

                $ent->telefones[] = $telefone;
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

        return $cotacoes;
    }

    public function getCountCotacoesEntrada($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM cotacao_entrada WHERE id_empresa=$this->id AND excluida = false ";

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

    public function getCountProdutosAlocais($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM (SELECT * FROM produto GROUP BY produto.codigo,produto.id_empresa) produto WHERE produto.id_empresa=$this->id AND produto.excluido=false ";

        if ($filtro !== "") {
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

    public function getProdutosAlocais($con, $x1, $x2, $filtro = "", $ordem = "") {

        $campanhas = array();
        $ofertas = array();

        $ps = $con->getConexao()->prepare("SELECT "
                . "campanha.id,"
                . "UNIX_TIMESTAMP(campanha.inicio)*1000,"
                . "UNIX_TIMESTAMP(campanha.fim)*1000,"
                . "campanha.prazo,"
                . "campanha.parcelas,"
                . "campanha.cliente_expression,"
                . "produto_campanha.id,"
                . "produto_campanha.id_produto,"
                . "UNIX_TIMESTAMP(produto_campanha.validade)*1000,"
                . "produto_campanha.limite,"
                . "produto_campanha.de,"
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
        $ps->bind_result($id, $inicio, $fim, $prazo, $parcelas, $cliente, $id_produto_campanha, $id_produto, $validade, $limite,$de, $valor, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

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

                $campanhas[$id] = $campanhas[$id]->getReduzida();
            }

            $campanha = $campanhas[$id];

            $p = new ProdutoCampanha();
            $p->id = $id_produto_campanha;
            $p->validade = $validade;
            $p->limite = $limite;
            $p->valor = $valor;
            $p->campanha = $campanha;
            $p->de = $de;
                
            if (!isset($ofertas[$id_produto])) {

                $ofertas[$id_produto] = array();
            }

            $ofertas[$id_produto][] = $p;
        }

        $ps->close();


        $sql = "SELECT "
                . "produto.id,"
                . "produto.codigo,"
                . "GROUP_CONCAT(produto.id_logistica separator ','),"
                . "produto.classe_risco,"
                . "produto.fabricante,"
                . "MAX(produto.imagem),"
                . "produto.id_universal,"
                . "produto.liquido,"
                . "produto.quantidade_unidade,"
                . "produto.habilitado,"
                . "produto.valor_base,"
                . "produto.custo,"
                . "produto.peso_bruto,"
                . "produto.peso_liquido,"
                . "produto.grade,"
                . "produto.unidade,"
                . "produto.ncm,"
                . "produto.nome,"
                . "produto.ativo,"
                . "produto.concentracao,"
                . "produto.id_categoria,"
                . "SUM(produto.estoque),"
                . "SUM(produto.disponivel),"
                . "SUM(produto.transito),"
                . "produto.sistema_lotes,"
                . "produto.dia_semana "
                . "FROM produto "
                . "WHERE produto.id_empresa = $this->id AND produto.excluido = false ";


        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }


        $sql .= "GROUP BY produto.codigo ";

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $produtos = array();

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pro, $cod_pro, $id_log, $classe_risco, $fabricante, $imagem, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $gr, $uni, $ncm, $nome, $ativo, $conc, $cat_id, $estoque, $disponivel, $transito, $sis,$dia_semana);

        while ($ps->fetch()) {

            $p = new ProdutoAlocal();

            $p->locais = explode(',', $id_log);

            $p->dia_semana = $dia_semana;
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
            $p->valor_base = $vb;
            $p->custo = $cus;
            $p->peso_bruto = $pb;
            $p->peso_liquido = $pl;
            $p->ativo = $ativo;
            $p->concentracao = $conc;
            $p->grade = new Grade($gr);
            $p->estoque = $estoque;
            $p->disponivel = $disponivel;
            $p->transito = $transito;
            $p->unidade = $uni;
            $p->ncm = $ncm;
            $p->sistema_lotes = $sis;
            $p->ofertas = (!isset($ofertas[$p->codigo]) ? array() : $ofertas[$p->codigo]);

            foreach ($p->ofertas as $key => $oferta) {

                $oferta->produto = $p->getReduzido();
            }

            $p->categoria = Sistema::getCategoriaProduto(null, $cat_id);

            $p->empresa = $this;

            $produtos[] = $p;
        }

        $ps->close();

        foreach ($produtos as $key => $value) {
            $locais = array();
            foreach ($value->locais as $key2 => $value2) {
                $i = intval($value2);
                if ($i === 0) {
                    $locais[] = $this;
                    continue;
                }
                $locais[] = Sistema::getLogisticaById($con, $i);
            }

            $value->locais = $locais;
        }

        return $produtos;
    }

    public function getCountAprovacoesConsignado($con,$filtro=""){


        $sql = "SELECT COUNT(*) FROM 
        produto 
        LEFT JOIN aprovacao_consignado 
        ON aprovacao_consignado.id_produto=produto.id 
        AND aprovacao_consignado.ate>CURRENT_TIMESTAMP 
        AND aprovacao_consignado.aprovado_sob=produto.valor_base 
        INNER JOIN empresa ON empresa.id=produto.id_empresa 
        WHERE produto.consignado=$this->id AND produto.cr=0 AND aprovacao_consignado.id IS NULL ";

        if($filtro !== ""){

            $sql .= "AND $filtro ";

        }

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($qtd);
        if($ps->fetch()){
            $ps->close();
            return $qtd;
        }
        $ps->close();
 
        return 0;

    }

    public function getAprovacoesConsignado($con,$x1,$x2,$filtro="",$ordem=""){


        $sql = "SELECT empresa.id,empresa.nome,empresa.cnpj,produto.id,produto.nome,produto.valor_base,produto.disponivel,produto.estoque FROM 
        produto 
        LEFT JOIN aprovacao_consignado 
        ON aprovacao_consignado.id_produto=produto.id 
        AND aprovacao_consignado.ate>CURRENT_TIMESTAMP 
        AND aprovacao_consignado.aprovado_sob=produto.valor_base 
        INNER JOIN empresa ON empresa.id=produto.id_empresa 
        WHERE produto.consignado=$this->id AND produto.cr=0 AND aprovacao_consignado.id IS NULL ";

        if($filtro !== ""){

            $sql .= "AND $filtro ";

        }

        if($ordem !== ""){

            $sql .= "ORDER BY $ordem ";

        }

        $sql .= "LIMIT $x1,".($x2-$x1);

        


        $aprovacoes = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_empresa,$nome_empresa,$cnpj_empresa,$id_produto,$nome_produto,$valor_produto,$disponivel_produto,$estoque_produto);

        while($ps->fetch()){

            $emp = new Empresa();
            $emp->id = $id_empresa;
            $emp->nome = $nome_empresa;
            $emp->cnpj = new CNPJ($cnpj_empresa);

            $prod = new Produto();
            $prod->id = $id_produto;
            $prod->nome = $nome_produto;
            $prod->valor_base = $valor_produto;
            $prod->disponivel = $disponivel_produto;
            $prod->estoque = $estoque_produto;

            $ap = new AprovacaoConsignado();
            $ap->empresa = $emp;
            $ap->produto = $prod;
            $ap->aprovado_sob = $prod->valor_base;
            $ap->valor = round($prod->valor_base*1.01,2);

            $aprovacoes[] = $ap;

        }

        $ps->close();

        return $aprovacoes;


    }

    public function getProdutos($con, $x1, $x2, $filtro = "", $ordem = "") {

        $campanhas = array();
        $ofertas = array();

        $ps = $con->getConexao()->prepare("SELECT "
                . "campanha.nome,"
                . "campanha.id,"
                . "UNIX_TIMESTAMP(campanha.inicio)*1000,"
                . "UNIX_TIMESTAMP(campanha.fim)*1000,"
                . "campanha.prazo,"
                . "campanha.parcelas,"
                . "campanha.cliente_expression,"
                . "produto_campanha.id,"
                . "produto_campanha.id_produto,"
                . "UNIX_TIMESTAMP(produto_campanha.validade)*1000,"
                . "produto_campanha.limite,"
                . "produto_campanha.de,"
                . "produto_campanha.valor, "
                . "produto_campanha.valor_boas_vindas,"
                . "produto_campanha.limite_boas_vindas,"
                . "produto_campanha.compra0_encomenda1,"
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
        $ps->bind_result($nome, $id, $inicio, $fim, $prazo, $parcelas, $cliente, $id_produto_campanha, $id_produto, $validade, $limite,$de, $valor,$valor_boas_vindas,$limite_boas_vindas,$compra0_encomenda1, $id_empresa, $tipo_empresa, $nome_empresa, $inscricao_empresa, $consigna, $aceitou_contrato, $juros_mensal, $cnpj, $numero_endereco, $id_endereco, $rua, $bairro, $cep, $id_cidade, $nome_cidade, $id_estado, $nome_estado, $id_email, $endereco_email, $senha_email, $id_telefone, $numero_telefone);

        while ($ps->fetch()) {

            if (!isset($campanhas[$id])) {

                $campanhas[$id] = new Campanha();
                $campanhas[$id]->id = $id;
                $campanhas[$id]->inicio = $inicio;
                $campanhas[$id]->fim = $fim;
                $campanhas[$id]->prazo = $prazo;
                $campanhas[$id]->nome = $nome;
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
            $p->compra0_encomenda1 = $compra0_encomenda1;
            $p->limite = $limite;
            $p->valor = $valor;
            $p->valor_boas_vindas = $valor_boas_vindas;
            $p->limite_boas_vindas = $limite_boas_vindas;
            $p->campanha = $campanha;
            $p->de = $de;
            
            if (!isset($ofertas[$id_produto])) {

                $ofertas[$id_produto] = array();
            }

            $ofertas[$id_produto][] = $p;
        }

        $ps->close();

        $categorias = Sistema::getCategoriaProduto();

        $cat = "";

        $i = 0;
        foreach($categorias as $key=>$value){

            if($i> 0){
                $cat .= " UNION ";
            }

            $cat .= "SELECT $value->id as 'id_cat','$value->nome' as 'nome_cat'";

            $i++;
        }

        $cat = "($cat)";

        $sql = "SELECT "
                . "produto.id,"
                . "produto.formula_preco,"
                . "produto.link,"
                . "produto.cr,"
                . "produto.nome_fantasia,"
                . "produto.observacao,"
                . "vcot.valor,"
                . "produto.estoque_ideal,"
                . "produto.estoque_minimo,"
                . "produto.estoque_maximo,"
                . "produto.emite_receita,"
                . "produto.tipo,"
                . "produto.ponto_fulgor,"
                . "produto.codigo,"
                . "produto.id_logistica,"
                . "produto.classe_risco,"
                . "produto.fabricante,"
                . "produto.imagem,"
                . "produto.imagem_venda,"
                . "produto.imagem_leilao,"
                . "produto.imagem_armazenagem,"
                . "produto.id_universal,"
                . "produto.liquido,"
                . "produto.quantidade_unidade,"
                . "produto.habilitado,"
                . "produto.valor_base,"
                . "produto.custo,"
                . "produto.peso_bruto,"
                . "produto.peso_liquido,"
                . "produto.estoque,"
                . "produto.troca,"
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
                . "produto.id_categoria, "
                . "produto.empresa_vendas, "
                . "produto.perfeicao, "
                . "produto.aceitacao, "
                . "produto.dia_semana "
                . "FROM produto "
                . "LEFT JOIN (SELECT p.codigo as 'codigo',ROUND(SUM(pc.quantidade*pc.valor)/SUM(pc.quantidade),2) as 'valor' FROM cotacao_entrada ce INNER JOIN produto_cotacao_entrada pc ON pc.id_cotacao=ce.id INNER JOIN produto p ON p.id=pc.id_produto WHERE ce.id_empresa=$this->id AND ce.data > DATE_SUB(CURRENT_DATE,INTERVAL 60 DAY) AND pc.checado = 0 GROUP BY p.codigo) vcot ON vcot.codigo=produto.codigo LEFT JOIN $cat cat ON cat.id_cat=produto.id_categoria "
                . "WHERE (produto.id_empresa = $this->id AND produto.excluido = false) ";


        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $produtos = array();

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pro,$formula_preco,$link,$cr,$nome_fantasia,$observacao,$vcot,$ideal,$minimo,$maximo,$em_rec,$tipo_pro,$ponto_fulgor_pro, $cod_pro, $id_log, $classe_risco, $fabricante, $imagem,$imagem_venda,$imagem_leilao,$imagem_armazenagem, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $est,$troca, $disp, $tr, $gr, $uni, $ncm, $nome, $lucro, $ativo, $conc, $sistema_lotes, $nota_usuario, $cat_id,$id_empresa_vendas,$perfeicao,$aceitacao,$dia_semana);

        while ($ps->fetch()) {

            $p = new Produto();

            $p->link = $link;
            $p->observacao = $observacao;
            $p->estoque_minimo = $minimo;
            $p->estoque_ideal = $ideal;
            $p->estoque_maximo = $maximo;
            $p->formula_preco = $formula_preco;

            if($vcot != null && $vcot != 0){

                $p->valor_cotacao = $vcot;

            }

            $p->logistica = $id_log;
            $p->cr = $cr;
            $p->nome_fantasia = $nome_fantasia;
            $p->emite_receita = $em_rec;
            $p->tipo = $tipo_pro;
            $p->ponto_fulgor = $ponto_fulgor_pro;
            $p->id = $id_pro;
            $p->codigo = $cod_pro;
            $p->classe_risco = $classe_risco;
            $p->fabricante = $fabricante;
            $p->imagem = $imagem;
            $p->imagem_venda = $imagem_venda;
            $p->imagem_leilao = $imagem_leilao;
            $p->imagem_armazenagem = $imagem_armazenagem;
            $p->nome = $nome;
            $p->perfeicao = $perfeicao;
            $p->aceitacao = $aceitacao;
            $p->dia_semana = $dia_semana;
            $p->id_universal = $id_uni;
            $p->sistema_lotes = $sistema_lotes == 1;
            $p->troca = $troca;
            $p->nota_usuario = $nota_usuario;
            $p->liquido = $liq == 1;
            $p->quantidade_unidade = $qtd_un;
            $p->habilitado = $hab;
            $p->valor_base = $vb;
            $p->custo = $cus;
            $p->peso_bruto = $pb;
            $p->peso_liquido = $pl;
            $p->estoque = $est;
            $p->disponivel = $disp;
            $p->id_empresa_vendas = $id_empresa_vendas;
            $p->ativo = $ativo;
            $p->concentracao = $conc;
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

            $p->empresa = $this;

            $produtos[] = $p;
        }

        $ps->close();

        foreach ($produtos as $key => $value) {

            $value->logistica = Sistema::getLogisticaById($con, $value->logistica);
        }
        
        $ids_produtos = "(-1";
        
        foreach($produtos as $key=>$value){
            $ids_produtos.= ",$value->id";
        }
        
        $ids_produtos .= ")";
        
        $imagens = array();
        
        $ps = $con->getConexao()->prepare("SELECT imagem,id_produto,tipo FROM mais_fotos_produto WHERE id_produto IN $ids_produtos");
        $ps->execute();
        
        $ps->bind_result($imagem,$id_produto,$tipo);
        while($ps->fetch()){
            if(!isset($imagens[$id_produto])){
                $imagens[$id_produto] = array();
            }

            $img = new stdClass();
            $img->imagem=$imagem;
            $img->tipo=$tipo;

            $imagens[$id_produto][] = $img;
            
        }
        $ps->close();
        
        foreach($produtos as $key=>$value){
            
            if(isset($imagens[$value->id])){
                
                $value->mais_fotos = $imagens[$value->id];
                
            }
            
        }
        
        return $produtos;
    }

    public function getCountProdutos($con, $filtro = "") {

        $cat = "";

        $categorias = Sistema::getCategoriaProduto();

        $i = 0;
        foreach($categorias as $key=>$value){

            if($i> 0){
                $cat .= " UNION ";
            }

            $cat .= "SELECT $value->id as 'id_cat','$value->nome' as 'nome_cat'";

            $i++;
        }

        $cat = "($cat)";

        $sql = "SELECT COUNT(*) FROM produto LEFT JOIN $cat cat ON cat.id_cat=produto.id_categoria WHERE produto.id_empresa=$this->id AND produto.excluido=false ";

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

    public function getReceituario($con, $x1, $x2, $filtro = "", $ordem = "", $group = "") {


        $campanhas = array();
        $ofertas = array();

        $ps = $con->getConexao()->prepare("SELECT "
                . "campanha.id,"
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

            $campanhas[$id]->produtos[] = $p;

            $ofertas[$id_produto][] = $p;
        }

        $ps->close();

        $receituarios = array();

        $sql = "SELECT "
                . "produto.id,"
                . "produto.codigo,"
                . "produto.id_logistica,"
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
                . "receituario.id, "
                . "receituario.instrucoes,"
                . "IFNULL(receituario.tipo_plantacao,0),"
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
                . "IFNULL(receituario.informacoes_adcionais,''),"
                . "cultura.id,"
                . "cultura.nome,"
                . "praga.id,"
                . "praga.nome "
                . "FROM produto "
                . "INNER JOIN receituario ON receituario.id_produto=produto.id "
                . "INNER JOIN praga ON receituario.id_praga = praga.id "
                . "INNER JOIN cultura ON receituario.id_cultura = cultura.id "
                . "WHERE receituario.excluido = false AND produto.id_empresa = $this->id ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($group != "") {

            $sql .= "GROUP BY $group ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $produtos = array();

        $sql .= "LIMIT $x1, " . ($x2 - $x1);
        
        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_pro, $cod_pro, $id_log, $id_uni, $liq, $qtd_un, $hab, $vb, $cus, $pb, $pl, $est, $disp, $tr, $gr, $uni, $ncm, $nome, $lucro, $ativo, $conc, $sistema_lotes, $nota_usuario, $cat_id, $rec_id, $rec_ins,$tipo_plantacao,$total_calda,$tipo_total_calda_ha,$carencia,$qtd_calda,$unidade_qtd_calda,$unidade_usada,$dosagem_max,$tipo_dosagem_max,$epoca_aplicacao,$diagnostico,$manejo_integrado,$precaucoes,$epi,$informacoes_adcionais, $cul_id, $cul_nom, $prag_id, $prag_nom);


        while ($ps->fetch()) {

            $p = new Produto();
            $p->logistica = $id_log;
            $p->id = $id_pro;
            $p->codigo = $cod_pro;
            $p->nome = $nome;
            $p->id_universal = $id_uni;
            $p->liquido = $liq == 1;
            $p->quantidade_unidade = $qtd_un;
            $p->habilitado = $hab;
            $p->valor_base = $vb;
            $p->custo = $cus;
            $p->peso_bruto = $pb;
            $p->peso_liquido = $pl;
            $p->estoque = $est;
            $p->disponivel = $disp;
            $p->sistema_lotes = $sistema_lotes == 1;
            $p->nota_usuario = $nota_usuario;
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

            $p->empresa = $this;

            $rec = new Receituario();
            $rec->id = $rec_id;
            $rec->instrucoes = $rec_ins;
            
            $rec->carencia = $carencia;
            $rec->diagnostico = $diagnostico;
            $rec->dosagem_max = $dosagem_max;
            $rec->epi = $epi;
            $rec->epoca_aplicacao = $epoca_aplicacao;
            $rec->informacoes_adcionais = $informacoes_adcionais;
            $rec->manejo_integrado = $manejo_integrado;
            $rec->precaucoes = $precaucoes;
            $rec->qtd_calda = $qtd_calda;
            $rec->tipo_dosagem_max = $tipo_dosagem_max;
            $rec->tipo_plantacao = $tipo_plantacao;
            $rec->tipo_total_calda_ha = $tipo_total_calda_ha;
            $rec->total_calda_ha = $total_calda;
            $rec->unidade_qtd_calda = $unidade_qtd_calda;
            $rec->unidade_usada = $unidade_usada;
            
            foreach(Receituario::getMedidas() as $key=>$value){
                if($value[0] === $rec->tipo_dosagem_max){
                    $rec->tipo_dosagem_max = $value;
                }
                if($value[0] === $rec->unidade_qtd_calda){
                    $rec->unidade_qtd_calda = $value;
                }
                if($value[0] === $rec->tipo_total_calda_ha){
                    $rec->tipo_total_calda_ha = $value;
                }
            }
            
            foreach(Receituario::getTiposPlantacao() as $key=>$value){
                if($value[0] === $rec->tipo_plantacao){
                    $rec->tipo_plantacao = $value;
                }
            }

            $cult = new Cultura();
            $cult->id = $cul_id;
            $cult->nome = $cul_nom;

            $prag = new Praga();
            $prag->id = $prag_id;
            $prag->nome = $prag_nom;

            $rec->cultura = $cult;
            $rec->praga = $prag;
            $rec->produto = $p;

            $receituarios[] = $rec;
        }

        $ps->close();

        foreach ($receituarios as $key => $value) {
            $value->produto->logistica = Sistema::getLogisticaById($con, $value->produto->logistica);
        }

        return $receituarios;
    }

    public function getCountReceituario($con, $filtro = "", $group = "") {

        $sql = "SELECT COUNT(*) FROM (SELECT receituario.id FROM receituario INNER JOIN produto ON produto.id=receituario.id_produto INNER JOIN cultura ON cultura.id=receituario.id_cultura INNER JOIN praga ON receituario.id_praga=praga.id WHERE receituario.excluido=false AND produto.id_empresa=$this->id";

        if ($filtro != "") {

            $sql .= " AND $filtro";
        }

        if ($group != "") {

            $sql .= " GROUP BY $group";
        }

        $sql .= ") k";

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

    public function getTransportadoras($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
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
                . "email_transportadora.id,"
                . "email_transportadora.endereco,"
                . "email_transportadora.senha "
                . "FROM transportadora "
                . "INNER JOIN endereco endereco_transportadora ON endereco_transportadora.id_entidade=transportadora.id AND endereco_transportadora.tipo_entidade='TRA' "
                . "INNER JOIN cidade cidade_transportadora ON endereco_transportadora.id_cidade=cidade_transportadora.id "
                . "INNER JOIN estado estado_transportadora ON estado_transportadora.id=cidade_transportadora.id_estado "
                . "INNER JOIN email email_transportadora ON email_transportadora.id_entidade = transportadora.id AND email_transportadora.tipo_entidade='TRA' "
                . "LEFT JOIN tabela ON tabela.id_transportadora = transportadora.id "
                . "WHERE id_empresa=$this->id AND transportadora.excluida=false ";


        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($tra_id, $cod_tra, $tra_nome, $tra_nome_fantasia, $tra_despacho, $tra_cnpj, $tra_habilitada, $tra_ie, $end_tra_id, $end_tra_rua, $end_tra_numero, $end_tra_bairro, $end_tra_cep, $cid_tra_id, $cid_tra_nome, $est_tra_id, $est_tra_nome, $id_email_tra, $end_email_tra, $sen_email_tra);

        $transportadoras = array();

        while ($ps->fetch()) {


            $transportadora = new Transportadora();
            $transportadora->id = $tra_id;
            $transportadora->codigo = $cod_tra;
            $transportadora->cnpj = new CNPJ($tra_cnpj);
            $transportadora->despacho = $tra_despacho;
            $transportadora->email = new Email($end_email_tra);
            $transportadora->email->id = $id_email_tra;
            $transportadora->email->senha = $sen_email_tra;
            $transportadora->habilitada = $tra_habilitada == 1;
            $transportadora->inscricao_estadual = $tra_ie;
            $transportadora->nome_fantasia = $tra_nome_fantasia;
            $transportadora->razao_social = $tra_nome;
            $transportadora->empresa = $this;

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

            $transportadoras[$tra_id] = $transportadora;
        }

        $ps->close();

        $in_tra = "-1";

        foreach ($transportadoras as $id => $transportadora) {
            $in_tra .= ",";
            $in_tra .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN($in_tra) AND telefone.tipo_entidade='TRA') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $transportadoras;


            $telefone = new Telefone($numero);
            $telefone->id = $id;


            $v[$id_entidade]->telefones[] = $telefone;
        }
        $ps->close();

        $ps = $con->getConexao()->prepare("SELECT tabela.id,tabela.prazo,tabela.nome,tabela.id_transportadora,regra_tabela.id,regra_tabela.condicional,regra_tabela.resultante FROM tabela INNER JOIN regra_tabela ON regra_tabela.id_tabela = tabela.id WHERE tabela.id_transportadora IN ($in_tra) AND tabela.excluida=false");
        $ps->execute();
        $ps->bind_result($id,$prazo, $nome, $id_tra, $idr, $cond, $res);
        while ($ps->fetch()) {

            $t = $transportadoras[$id_tra];


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

        $ps->close();

        $real = array();

        foreach ($transportadoras as $key => $value) {

            $real[] = $value;
        }

        return $real;
    }

    public function getCountTransportadoras($con, $filtro = "") {


        $sql = "SELECT COUNT(*) FROM transportadora LEFT JOIN tabela ON tabela.id_transportadora=transportadora.id WHERE transportadora.id_empresa = $this->id AND transportadora.excluida=false ";

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

    public function getClientes($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "cliente.id,"
                . "cliente.historico_habilitado,"
                . "cliente.preferencial_mix,"
                . "cliente.super_mix,"
                . "cliente.observacao,"
                . "cliente.id_vendedor,"
                . "cliente.emite_receita,"
                . "cliente.cobranca_emocional,"
                . "cliente.recebe_whats,"
                . "cliente.classe_virtual,"
                . "cliente.codigo_contimatic,"
                . "cliente.codigo,"
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
                . "email_cliente.id,"
                . "email_cliente.endereco,"
                . "email_cliente.senha "
                . "FROM cliente "
                . "INNER JOIN endereco endereco_cliente ON endereco_cliente.id_entidade=cliente.id AND endereco_cliente.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON endereco_cliente.id_cidade=cidade_cliente.id "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN categoria_cliente ON cliente.id_categoria=categoria_cliente.id "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade = 'CLI' "
                . "INNER JOIN empresa ON empresa.id=cliente.id_empresa "
                . "WHERE cliente.id_empresa=$this->id AND cliente.excluido=false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);


        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_cliente,$historico_habilitado,$preferencial_mix,$super_mix, $observacao,$id_vendedor,$emite_receita,$cob_em,$rec_w,$classe_virtual, $cod_ctm, $cod_cli, $nome_cliente, $nome_fantasia_cliente, $limite, $inicio, $fim, $pessoa_fisica, $cpf, $cnpj, $rg, $ie, $suf, $i_suf, $cat_id, $cat_nome, $end_cli_id, $end_cli_rua, $end_cli_numero, $end_cli_bairro, $end_cli_cep, $cid_cli_id, $cid_cli_nome, $est_cli_id, $est_cli_nome, $email_cli_id, $email_cli_end, $email_cli_senha);

        $clientes = array();

        while ($ps->fetch()) {

            $cliente = new Cliente();
            $cliente->id = $id_cliente;
            $cliente->historico_habilitado = $historico_habilitado == 1;
            $cliente->preferencial_mix = $preferencial_mix == 1;
            $cliente->super_mix = $super_mix == 1;
            $cliente->observacao = $observacao;
            $cliente->id_vendedor = $id_vendedor;
            $cliente->cobranca_emocional = $cob_em == 1;
            $cliente->classe_virtual = $classe_virtual;
            $cliente->codigo_contimatic = $cod_ctm;
            $cliente->codigo = $cod_cli;
            $cliente->cnpj = new CNPJ($cnpj);
            $cliente->cpf = new CPF($cpf);
            $cliente->rg = new RG($rg);
            $cliente->emite_receita = $emite_receita==1;
            $cliente->recebe_whats = $rec_w == 1;
            $cliente->pessoa_fisica = $pessoa_fisica == 1;
            $cliente->nome_fantasia = $nome_fantasia_cliente;
            $cliente->razao_social = $nome_cliente;
            $cliente->empresa = $this;
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
            $cliente->empresa = $this;
            $cliente->inscricao_estadual = $ie;

            $end = new Endereco();
            $end->id = $end_cli_id;
            $end->bairro = $end_cli_bairro;
            $end->cep = new CEP($end_cli_cep);
            $end->numero = $end_cli_numero;
            $end->rua = $end_cli_rua;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_cli_id;
            $end->cidade->nome = $cid_cli_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_cli_id;
            $end->cidade->estado->sigla = $est_cli_nome;

            $cliente->endereco = $end;

            $clientes[$id_cliente] = $cliente;
        }

        $ps->close();

        $in_cli = "-1";

        foreach ($clientes as $id => $cliente) {
            $in_cli .= ",";
            $in_cli .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN ($in_cli) AND telefone.tipo_entidade='CLI') AND telefone.excluido=false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $clientes;

            $telefone = new Telefone($numero);
            $telefone->id = $id;


            $v[$id_entidade]->telefones[] = $telefone;
        }
        $ps->close();

        $real = array();

        foreach ($clientes as $key => $value) {

            $real[] = $value;
        }

        return $real;
    }

    public function getCountClientes($con, $filtro = "") {

        $sql = "SELECT COUNT(*) FROM cliente "
                . "INNER JOIN empresa ON empresa.id=cliente.id_empresa "
                . "INNER JOIN endereco ON endereco.id_entidade=cliente.id AND endereco.tipo_entidade='CLI' "
                . "INNER JOIN cidade cidade_cliente ON cidade_cliente.id=endereco.id_cidade "
                . "INNER JOIN estado estado_cliente ON estado_cliente.id=cidade_cliente.id_estado "
                . "INNER JOIN email email_cliente ON email_cliente.id_entidade=cliente.id AND email_cliente.tipo_entidade='CLI' "
                . " WHERE cliente.id_empresa=$this->id AND cliente.excluido=false ";

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

    public function getCountFechamento($con, $filtro = "") {

        $sql = "SELECT "
                . "COUNT(*) "
                . "FROM fechamento_caixa "
                . "INNER JOIN banco ON banco.id=fechamento_caixa.id_banco "
                . "WHERE banco.id_empresa = $this->id ";

        if ($filtro !== "") {

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

    public function getFechamentosCaixa($con, $x1, $x2, $filtro = "", $ordem = "") {

        $sql = "SELECT "
                . "fechamento_caixa.id,"
                . "fechamento_caixa.valor,"
                . "UNIX_TIMESTAMP(fechamento_caixa.data)*1000,"
                . "banco.id,"
                . "banco.nome,"
                . "IFNULL(banco.saldo,0),"
                . "banco.conta,"
                . "banco.codigo,"
                . "banco.agencia,"
                . "banco.codigo_contimatic "
                . "FROM fechamento_caixa "
                . "INNER JOIN banco ON banco.id=fechamento_caixa.id_banco "
                . "WHERE banco.id_empresa=$this->id ";

        if ($filtro !== "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem !== "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $fechamentos = array();

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id, $valor, $data, $id_banco, $nome_banco, $saldo_banco, $conta_banco, $codigo_banco, $agencia_banco, $codigo_contimatic_banco);

        while ($ps->fetch()) {

            $f = new FechamentoCaixa();
            $f->id = $id;
            $f->valor = $valor;
            $f->data = $data;

            $b = new Banco();
            $b->id = $id_banco;
            $b->nome = $nome_banco;
            $b->saldo = $saldo_banco;
            $b->conta = $conta_banco;
            $b->codigo = $codigo_banco;
            $b->agencia = $agencia_banco;
            $b->codigo_contimatic = $codigo_contimatic_banco;
            $b->empresa = $this;

            $f->banco = $b;

            $fechamentos[] = $f;
        }

        $ps->close();

        return $fechamentos;
    }

    public function getUsuarios($con, $x1, $x2, $filtro = "", $ordem = "") {


        Sistema::getCargo($con, $this, 0, false);

        $sql = "SELECT "
                . "usuario.id,"
                . " usuario.contrato_fornecedor,"
                . " usuario.faixa_salarial,"
                . " usuario.id_cargo,"
                . " usuario.nome,"
                . " usuario.login,"
                . " usuario.senha,"
                . " usuario.cpf,"
                . " usuario.rg,"
                . " usuario.crc,"
                . " endereco_usuario.id,"
                . " endereco_usuario.rua,"
                . " endereco_usuario.numero,"
                . " endereco_usuario.bairro,"
                . " endereco_usuario.cep,"
                . " cidade_usuario.id,"
                . " cidade_usuario.nome,"
                . " estado_usuario.id,"
                . " estado_usuario.sigla,"
                . " email_usu.id,"
                . " email_usu.endereco,"
                . "email_usu.senha "
                . "FROM usuario "
                . "INNER JOIN endereco endereco_usuario ON endereco_usuario.id_entidade=usuario.id AND endereco_usuario.tipo_entidade='USU' "
                . "INNER JOIN cidade cidade_usuario ON endereco_usuario.id_cidade=cidade_usuario.id "
                . "INNER JOIN estado estado_usuario ON estado_usuario.id=cidade_usuario.id_estado "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE usuario.id_empresa=$this->id AND usuario.excluido = false ";

        if ($filtro != "") {

            $sql .= "AND $filtro ";
        }

        if ($ordem != "") {

            $sql .= "ORDER BY $ordem ";
        }

        $sql .= "LIMIT $x1, " . ($x2 - $x1);

        $ps = $con->getConexao()->prepare($sql);
        $ps->execute();
        $ps->bind_result($id_usu,$contrato_fornecedor,$faixa, $id_cargo, $nome_usu, $login_usu, $senha_usu, $cpf_usu,$rg_usu,$crc_usu, $end_usu_id, $end_usu_rua, $end_usu_numero, $end_usu_bairro, $end_usu_cep, $cid_usu_id, $cid_usu_nome, $est_usu_id, $est_usu_nome, $email_usu_id, $email_usu_end, $email_usu_senha);

        $usuarios = array();

        while ($ps->fetch()) {

            $usuario = new Usuario();

            $usuario->faixa_salarial = $faixa;
            $usuario->cpf = new CPF($cpf_usu);
            $usuario->contrato_fornecedor = $contrato_fornecedor;
            $usuario->cargo = Sistema::getCargo($con, $this, $id_cargo);
            $usuario->email = new Email($email_usu_end);
            $usuario->email->id = $email_usu_id;
            $usuario->email->senha = $email_usu_senha;
            $usuario->empresa = $this;
            $usuario->id = $id_usu;
            $usuario->login = $login_usu;
            $usuario->senha = $senha_usu;
            $usuario->nome = $nome_usu;
            $usuario->empresa = $this;

            $usuario->rg = new RG($rg_usu);
            $usuario->crc = $crc_usu;

            $end = new Endereco();
            $end->id = $end_usu_id;
            $end->bairro = $end_usu_bairro;
            $end->cep = new CEP($end_usu_cep);
            $end->numero = $end_usu_numero;
            $end->rua = $end_usu_rua;

            $end->cidade = new Cidade();
            $end->cidade->id = $cid_usu_id;
            $end->cidade->nome = $cid_usu_nome;

            $end->cidade->estado = new Estado();
            $end->cidade->estado->id = $est_usu_id;
            $end->cidade->estado->sigla = $est_usu_nome;

            $usuario->endereco = $end;

            $usuarios[$usuario->id] = $usuario;
        }

        $ps->close();

        $in_usu = "-1";
        foreach ($usuarios as $id => $usuario) {
            $in_usu .= ",";
            $in_usu .= $id;
        }

        $ps = $con->getConexao()->prepare("SELECT telefone.id_entidade, telefone.tipo_entidade, telefone.id, telefone.numero FROM telefone WHERE (telefone.id_entidade IN ($in_usu) AND telefone.tipo_entidade='USU') AND telefone.excluido = false");
        $ps->execute();
        $ps->bind_result($id_entidade, $tipo_entidade, $id, $numero);
        while ($ps->fetch()) {

            $v = $usuarios;
            $telefone = new Telefone($numero);
            $telefone->id = $id;


            $v[$id_entidade]->telefones[] = $telefone;
        }
        $ps->close();

        
        $rtcs = Sistema::getRTCS();
        foreach ($usuarios as $key => $value) {
            $value->permissoes = Sistema::getPermissoes($value->empresa);
            $rtcemp = $value->empresa->getRTC($con);
            foreach($rtcs as $kr=>$rtc){
                if($rtc->numero === $rtcemp->numero)continue;
                foreach($rtc->permissoes as $k2=>$p){
                    if(!$p->frente){
                        $value->permissoes[] = $p;
                    }
                }
            }
        }

        $ps = $con->getConexao()->prepare("SELECT id_usuario, id_permissao,incluir,deletar,alterar,consultar FROM usuario_permissao WHERE id_usuario IN ($in_usu)");
        $ps->execute();
        $ps->bind_result($id_usuario, $id_permissao, $incluir, $deletar, $alterar, $consultar);

        while ($ps->fetch()) {

            $permissoes = $usuarios[$id_usuario]->permissoes;

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

        $ps->close();

        $real = array();

        foreach ($usuarios as $key => $value) {

            $real[] = $value;
        }

        return $real;
    }

    public function getCountUsuarios($con, $filtro = "") {

        $sql = "SELECT "
                . "COUNT(*) "
                . "FROM usuario "
                . "INNER JOIN email email_usu ON email_usu.id_entidade=usuario.id AND email_usu.tipo_entidade='USU' "
                . "WHERE usuario.id_empresa=$this->id AND usuario.excluido=false ";

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

}
