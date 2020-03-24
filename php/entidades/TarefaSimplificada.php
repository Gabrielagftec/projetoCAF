<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, 
 * and open the template in the editor.
 */
/**
 * Description of Baixo
 *
 * @author Renan
 */
class TarefaSimplificada{
    
    public $id;
    public $descricao;
    public $empresa;
    public $momento;
    public $andamentos;
    public $usuarios;
    public $arquivos;
    public $tipo;
    public $prioridade;
    public $prioridade_real;
    public $produtos;
    public $orcamento;
    public $cliente;
    
    public function __construct() {
        
        $this->id = 0;
        $this->cliente = null;
        $this->descricao = "";
        $this->empresa = null;
        $this->momento = round(microtime(true)*1000);
        $this->andamentos = array();
        $this->usuarios = array();
        $this->arquivos = array();
        $this->tipo = null;
        $this->prioridade = 0;
        $this->prioridade_real = 0;
        $this->produtos = null;
        $this->orcamento = false;
        
    }
    
    public function getProdutos($con){
        
        $produtos = array();
        
        $in_prod = "(-1";
        
        $ps = $con->getConexao()->prepare(
                "SELECT "
                . "id,"
                . "id_produto,"
                . "quantidade,"
                . "influencia,"
                . "valor "
                . "FROM produto_tarefa_simplificada "
                . "WHERE id_tarefa = $this->id"
                );
        $ps->execute();
        $ps->bind_result($id,$id_produto,$quantidade,$influencia,$valor);
        while($ps->fetch()){
            
            $p = new ProdutoTarefaSimplificada();
            $p->id = $id;
            $p->produto = $id_produto;
            $p->quantidade = $quantidade;
            $p->influencia = $influencia;
            $p->valor = $valor;
            $p->tarefa = $this;
            
            $produtos[] = $p;
            
            $in_prod .= ",$id_produto";
            
        }
        $ps->close();
        
        $in_prod .= ")";
        
        $materiais = $this->empresa->getProdutos($con,0,30,"produto.id IN $in_prod");
        $mapa = array();
        
        foreach($materiais as $key=>$value){
            $mapa[$value->id] = $value;
        }
        
        foreach($produtos as $key=>$value){
            if(isset($mapa[$value->produto])){
                $value->produto = $mapa[$value->produto];
            }else{
                $value->produto = null;
                unset($produtos[$key]);
            }
        }
        
        return $produtos;
        
    }
    
    public function merge($con){
                
        $this->prioridade = round($this->tipo->prioridade*($this->prioridade_real/100));
        
        if($this->id === 0){
            
            $ps = $con->getConexao()->prepare("INSERT INTO tarefa_simplificada(descricao,id_empresa,momento,id_tipo_tarefa,prioridade,orcamento,id_cliente) VALUES('".addslashes($this->descricao)."',".$this->empresa->id.",FROM_UNIXTIME($this->momento/1000),".$this->tipo->id.",$this->prioridade,".($this->orcamento?"true":"false").",".($this->cliente===null?"0":$this->cliente->id).")");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        }else{
            
            $ps = $con->getConexao()->prepare("UPDATE tarefa_simplificada SET descricao='".addslashes($this->descricao)."', id_empresa=".$this->empresa->id.", momento=FROM_UNIXTIME($this->momento/1000), id_tipo_tarefa=".$this->tipo->id.",prioridade=$this->prioridade,orcamento=".($this->orcamento?"true":"false").",id_cliente=".($this->cliente===null?"0":$this->cliente->id)." WHERE id=$this->id");
            $ps->execute();
            $ps->close();
            
        }
        
        $ps = $con->getConexao()->prepare("DELETE FROM arquivo_tarefa_simplificada WHERE id_tarefa=$this->id");
        $ps->execute();
        $ps->close();
        
        foreach($this->arquivos as $key=>$value){
            
            $ps = $con->getConexao()->prepare("INSERT INTO arquivo_tarefa_simplificada(link,id_tarefa) VALUES('$value',$this->id)");
            $ps->execute();
            $ps->close();
            
        }
        
        
        $ids_andamentos = "(-1";
        
        foreach($this->andamentos as $key=>$value){
            
            $ids_andamentos .= ",$value->id";
            
        }
        
        $ids_andamentos .= ")";
        
        $ps = $con->getConexao()->prepare("DELETE FROM andamento_tarefa_simplificada WHERE id_tarefa=$this->id AND id NOT IN $ids_andamentos");
        $ps->execute();
        $ps->close();
        
        foreach($this->andamentos as $key=>$value){
            
            $value->merge($con);
            
        }
        
        $ps = $con->getConexao()->prepare("DELETE FROM usuario_tarefa_simplificada WHERE id_tarefa=$this->id");
        $ps->execute();
        $ps->close();
        
        foreach($this->usuarios as $key=>$value){
            
            $minutos = 0;
            
            if(isset($value->minutos_orcamento)){
                
                $minutos = $value->minutos_orcamento;
                
            }
            
            $ps = $con->getConexao()->prepare("INSERT INTO usuario_tarefa_simplificada(id_tarefa,id_usuario,minutos_orcamento) VALUES($this->id,$value->id,$minutos)");
            $ps->execute();
            $ps->close();
            
        }
        
        if($this->produtos !== null){
            
            $atuais = $this->getProdutos($con);
            
            foreach($this->produtos as $key=>$value){
                
                $value->merge($con);
                
            }
            
            foreach($atuais as $key=>$value){
                
                foreach($this->produtos as $key2=>$value2){
                    if($value2->id === $value->id){
                        continue 2;
                    }
                }
                
                $value->delete($con);
                
            }
            
        }

        if($this->orcamento && $this->cliente !== null){


            $c = $this->empresa->getClientes($con,0,1,"cliente.id=".$this->cliente->id);

            if(count($c) > 0){

                $this->cliente = $c[0];

            }

            $ses = new SessionManager();

            if($ses->get('usuario') !== null){
                $this->usuario = $ses->get('usuario');
            }

            $p = array();
            foreach($this->produtos as $key=>$value){
                $p[] = $value;
            }

            foreach($this->produtos as $key=>$value){

                $value->nome = $value->produto->nome;

            }
     
            foreach($this->usuarios as $key=>$value){
                
                $minutos = 0;
                
                if(isset($value->minutos_orcamento)){
                    
                    $minutos = $value->minutos_orcamento;
                    $pp = new stdClass();

                    $pp->nome = $value->minutos_orcamento." minutos do colaborador ".$value->nome;
                    $pp->quantidade = 1;
                    $pp->valor = round(($value->faixa_salarial/(22*8*60))*$value->minutos_orcamento,2);
                    $this->produtos[] = $pp;

                }
                
            }

            try{

                $html = Sistema::getHtml("visualizar-orcamento",$this);

                Sistema::avisoDEVS_MASTER($html);

                $this->empresa->email->enviarEmail(array(
                    new Email("elias@agrofauna.com.br"),
                    $this->cliente->email
                ),"ORCAMENTO",$html);

            }catch(Exception $ex){

            }
            
            $this->produtos = $p;

        }
    
        
    }
    
    public function delete($con){
        
        $produtos = $this->getProdutos($con);
        
        foreach($produtos as $key=>$value){
            $value->delete($con);
        }
        
        $ps = $con->getConexao()->prepare("DELETE FROM tarefa_simplificada WHERE id=$this->id");
        $ps->execute();
        $ps->close();
        
    }
    
}