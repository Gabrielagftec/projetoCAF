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
class Protocolo {

    public $id;
    public $titulo;
    public $descricao;
    public $tipo;
    public $empresa;
    public $chat;
    public $tipo_entidade;
    public $id_entidade;
    public $inicio;
    public $fim;
    public $iniciado_por;
    public $precedente;
    public $alertar;
    public $aprovado;
    
    public $usuarios;

    public function __construct() {

        $this->id = 0;
        $this->titulo = "";
        $this->descricao = "";
        $this->tipo = null;
        $this->empresa = null;
        $this->chat = array();
        $this->inicio = round(microtime(true) * 1000);
        $this->fim = null;

        $this->id_entidade = 0;
        $this->tipo_entidade = 0;
        $this->iniciado_por = "";
        $this->alertar = true;
        $this->usuarios = array();
        $this->aprovado = 0;
        
    }
    
    public function init($con){
        
        if($this->tipo_entidade === "Cliente"){
            
            $ps = $con->getConexao()->prepare("SELECT razao_social FROM cliente WHERE id=$this->id_entidade");
            $ps->execute();
            $ps->bind_result($razao_social);
            if($ps->fetch()){
                $this->precedente = $razao_social;
            }
            $ps->close();
            
        }else if($this->tipo_entidade === "Pedido"){
            
            $ps = $con->getConexao()->prepare("SELECT p.id,c.razao_social FROM pedido p INNER JOIN cliente c ON c.id=p.id_cliente WHERE p.id=$this->id_entidade");
            $ps->execute();
            $ps->bind_result($pedido,$razao_social);
            if($ps->fetch()){
                $this->precedente = "Pedido: $pedido, do cliente: $razao_social";
            }
            $ps->close();
            
        }
        
    }

    public function getMensagensPosteriores($con, $ultimo_id = 0) {

        $retorno = array();
        
        foreach ($this->chat as $key => $value) {
            if ($value->id > $ultimo_id) {
                $ultimo_id = $value->id;
            }
        }

        $ps = $con->getConexao()->prepare("SELECT id,mensagem,UNIX_TIMESTAMP(momento)*1000,dados_usuario FROM mensagem_protocolo WHERE id_protocolo = $this->id AND id > $ultimo_id");
        $ps->execute();
        $ps->bind_result($id, $mensagem, $momento, $dados_usuario);
        while ($ps->fetch()) {

            $m = new MensagemProtocolo();
            $m->id = $id;
            $m->mensagem = $mensagem;
            $m->momento = $momento;
            $m->dados_usuario = $dados_usuario;
            $m->protocolo = $this;

            $this->chat[] = $m;

            $retorno[] = $m;
        }

        $ps->close();

        return $retorno;
    }

    public function terminar($con){
        
        $ps = $con->getConexao()->prepare("UPDATE protocolo SET inicio=inicio,fim=CURRENT_TIMESTAMP WHERE id=$this->id");
        $ps->execute();
        $ps->close();
     
        if($this->tipo_entidade === "PEC"){

            $responsaveis = array();

            $ps = $con->getConexao()->prepare("SELECT l.id_usuario,u.id_empresa FROM lote l INNER JOIN usuario u ON u.id=l.id_usuario INNER JOIN produto_pedido_entrada pp ON pp.lote_cadastrado LIKE CONCAT(CONCAT('%;',l.id),';%') WHERE pp.id_pedido=$this->id_entidade AND l.id_usuario>0 GROUP BY l.id_usuario ");
            $ps->execute();
            $ps->bind_result($id_usuario,$id_empresa);
            while($ps->fetch()){

                $u = new Usuario();
                $u->id = $id_usuario;

                $e = new Empresa($id_empresa);
                $u->empresa = $e;

                $responsaveis[] = $u;

            }
            $ps->close();

            if(count($responsaveis) === 0){

                //usuario default de separacao, ou seja, gambiarra

                $u = new Usuario();
                $u->id = 4611;
                
                $e = new Empresa();
                $e->id = 1735;
                $u->empresa = $e;

                $responsaveis[] = $u;

            }


            foreach($responsaveis as $key=>$u){

                $t = new Tarefa();
                $t->tipo_tarefa = Sistema::TT_COMPRA($u->empresa->id);
                $t->descricao = "Recepcao de mercadoria do pedido $this->id_entidade Finalizada";
                $t->titulo = "Recepcao Finalizada";
                $t->tipo_entidade = "PEC";
                $t->id_entidade = $this->id_entidade;

                $u->addTarefa($con,$t);

            }


        }

    }
    
    public function delete($con) {

        $ps = $con->getConexao()->prepare("DELETE FROM protocolo WHERE id=$this->id");
        $ps->execute();
        $ps->close();
    }

    public function aprovar($con){
        
        $this->merge($con);
        
        $ps = $con->getConexao()->prepare("UPDATE protocolo SET aprovado=1 WHERE id=$this->id");
        $ps->execute();
        $ps->close();
        
    }
    
    public function reprovar($con){
        
        $this->merge($con);
        
        $ps = $con->getConexao()->prepare("UPDATE protocolo SET aprovado=-1 WHERE id=$this->id");
        $ps->execute();
        $ps->close();
        
    }
    
    public function merge($con) {

        $inicio = $this->inicio;
        $fim = $this->fim;

        if ($inicio !== null) {
            $inicio /= 1000;
            $inicio = "FROM_UNIXTIME($inicio)";
        } else {
            $inicio = "null";
        }

        if ($fim !== null) {
            $fim /= 1000;
            $fim = "FROM_UNIXTIME($fim)";
        } else {
            $fim = "null";
        }

        if ($this->id === 0) {
            
            if($this->tipo->aprovador === null){
                
                $this->aprovado = 1;
                
            }

            $ps = $con->getConexao()->prepare("INSERT INTO protocolo(titulo,descricao,id_tipo,inicio,fim,tipo_entidade,id_entidade,id_empresa,iniciado_por,aprovado) VALUES('" . addslashes($this->titulo) . "','" . addslashes($this->descricao) . "'," . $this->tipo->id . ",$inicio,$fim,'$this->tipo_entidade',$this->id_entidade," . $this->empresa->id . ",'$this->iniciado_por',$this->aprovado)");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE protocolo SET titulo='" . addslashes($this->titulo) . "',descricao='" . addslashes($this->descricao) . "',id_tipo=" . $this->tipo->id . ",inicio=$inicio,fim=$fim,tipo_entidade='$this->tipo_entidade',id_entidade=$this->id_entidade,id_empresa=" . $this->empresa->id . ",iniciado_por='$this->iniciado_por',aprovado=$this->aprovado WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }


        $ps = $con->getConexao()->prepare("DELETE FROM protocolo_usuario WHERE id_protocolo=$this->id");
        $ps->execute();
        $ps->close();


        foreach($this->usuarios as $k2=>$value2){

            $ps = $con->getConexao()->prepare("INSERT INTO protocolo_usuario(id_usuario,id_protocolo) VALUES($value2->id,$this->id)");
            $ps->execute();
            $ps->close();
            
        }

    }

}
