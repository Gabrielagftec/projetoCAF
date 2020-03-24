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
class Suporte{

        public static $TEMPO_FILA = 60;//60 segundos
    
	public $id;
	public $usuario;
	public $atendente;
	public $inicio;
	public $fim;
        public $ultima_mensagem;
        public $mensagens_retorno;
        public $mensagens;
        
	function __construct()
	{
		
		$this->id = 0;
		$this->usuario = null;
		$this->atendente = null;
		$this->inicio = round(microtime(true)*1000);
		$this->fim = null;
                $this->ultima_mensagem = 0;
                $this->mensagens_retorno = array();
                $this->mensagens = array();
	}
        
        public function finalizar($con){
            
            $ps = $con->getConexao()->prepare("UPDATE suporte SET inicio=inicio,fim=CURRENT_TIMESTAMP WHERE id=$this->id");
            $ps->execute();
            $ps->close();
            
            $m = $this->getMensagens($con);
            
            if(count($m) > 0){
            
                $ps = $con->getConexao()->prepare("SELECT id_empresa FROM usuario WHERE id=".$this->atendente->id);
                $ps->execute();
                $ps->bind_result($id_empresa);
                if(!$ps->fetch()){
                    return;
                }
                $ps->close();
                
                $t = new Tarefa();
                $t->tipo_tarefa = Sistema::TT_SUPORTE_CLIENTE($id_empresa); 
                $t->titulo = "CHAT de suporte ao usuario ".$this->usuario->nome;
                $t->descricao = "Prestacao de atendimento ao Usuario ".$this->usuario->nome;
                $t->id_entidade_relacionada = $this->id;
                $t->tipo_entidade_relacionada = "SUP";
                $t->porcentagem_conclusao = 0;
                
                $this->atendente->addTarefa($con, $t);
                
                $p = ceil(100/count($m));
                
                foreach($m as $key=>$value){
                    
                    $o = new ObservacaoTarefa();
                    $o->observacao = $value->usuario->nome." - ".$value->texto;
                    $o->porcentagem = $p;
                    
                    $t->addObservacao($con, $this->atendente, $o);
                    
                }
            
            }
            
        }
        
        public function atribuir($con,$fila_se_nao_existir_disponivel=false){
            
            $empresa_atual = 0;
            $usuario_atual = 0;
            
            $ps = $con->getConexao()->prepare("SELECT id_empresa,id_usuario FROM counter_suporte");
            $ps->execute();
            $ps->bind_result($id_empresa,$id_usuario);
            while($ps->fetch()){
                
                $empresa_atual = $id_empresa;
                $usuario_atual = $id_usuario;
                
            }
            $ps->close();
            
            
            $empresas = array();
            
            $ps = $con->getConexao()->prepare("SELECT id FROM empresa WHERE tipo_empresa=3");
            $ps->execute();
            $ps->bind_result($id);
            while($ps->fetch()){
                
                $empresas[] = $id;
                
            }
            $ps->close();

            if(count($empresas) === 0){
                
                return false;
                
            }
            
            if($empresa_atual === 0){
               
               $empresa_atual = $empresas[0];
                
            }
            
            $index = 0;
            
            foreach($empresas as $key=>$value){
                if($value===$empresa_atual){
                    $index = $key;
                    break;
                }
            }
            
            $cargos = array(
               Virtual::CF_ASSISTENTE_VIRTUAL_SUPORTE(null),
               Virtual::CF_ASSISTENTE_VIRTUAL_PROSPECCAO(null),
               Virtual::CF_ASSISTENTE_VIRTUAL_RECEPCAO(null)
            );
            
            $ids_cargos = "(-2";
            
            foreach($cargos as $key=>$value){
                $ids_cargos .= ",$value->id";
            }
            
            $ids_cargos .= ")";
            
            $encontrou = false;
            
            $t = 0;
            while($t<=count($empresas)){
             
                $ps = $con->getConexao()->prepare("SELECT u.id FROM usuario u "
                        . "INNER JOIN expediente e ON e.id_usuario = u.id AND (DAYOFWEEK(CURRENT_DATE)-1)=e.dia_semana AND HOUR(CURRENT_TIMESTAMP)>=e.inicio AND HOUR(CURRENT_TIMESTAMP)<=e.fim "
                        . "LEFT JOIN (SELECT COUNT(*) as 'qtd',s.id_atendente as 'idt' FROM suporte s WHERE s.fim IS NULL GROUP BY s.id_atendente) a ON a.idt=u.id"
                        . " WHERE u.id_cargo IN $ids_cargos AND u.id_empresa=$empresa_atual AND u.id>$usuario_atual AND IFNULL(a.qtd,0)<1 ORDER BY u.id ASC");
                $ps->execute();
                $ps->bind_result($id);
                $ok = $ps->fetch();
                $ps->close();
                
                if($ok){
                    
                    $usuario_atual = $id;
                    $encontrou = true;
                    break;
                    
                }else{
                    
                    $usuario_atual = 0;
                    $empresa_atual = $empresas[($index+1)%(count($empresas))];  
                    
                }
                
                $t++;
                
            }
            
            $ps = $con->getConexao()->prepare("SELECT id_usuario FROM fila_chat WHERE ABS(UNIX_TIMESTAMP(CURRENT_TIMESTAMP)-UNIX_TIMESTAMP(ultima_atualizacao)) < ".self::$TEMPO_FILA." ORDER BY id ASC");
                $ps->execute();
                $ps->bind_result($id);
                if($ps->fetch()){
                    
                    if($id !== $this->usuario->id){
                        
                        $encontrou = false;
                        
                    }
                    
                }
                $ps->close();
                
            
            if($encontrou){
                
                $this->atendente = new Usuario();
                $this->atendente->id = $usuario_atual;


                $ps = $con->getConexao()->prepare("SELECT nome FROM usuario WHERE id=$usuario_atual");
                $ps->execute();
                $ps->bind_result($nome);
                if($ps->fetch()){
                    $this->atendente->nome = $nome;
                }
                $ps->close();

                $this->merge($con);
                
                $ps = $con->getConexao()->prepare("UPDATE counter_suporte SET id_empresa=$empresa_atual,id_usuario=$usuario_atual");
                $ps->execute();
                $ps->close();
                
                $ps = $con->getConexao()->prepare("DELETE FROM fila_chat WHERE id_usuario=".$this->usuario->id);
                $ps->execute();
                $ps->close();
                
                return true;
                
            }else if($fila_se_nao_existir_disponivel){
                
                $ps = $con->getConexao()->prepare("SELECT id FROM fila_chat WHERE ABS(UNIX_TIMESTAMP(CURRENT_TIMESTAMP)-UNIX_TIMESTAMP(ultima_atualizacao)) < ".self::$TEMPO_FILA." AND id_usuario=".$this->usuario->id);
                $ps->execute();
                $ps->bind_result($id);
                if($ps->fetch()){
                    $ps->close();
                
                    $ps = $con->getConexao()->prepare("UPDATE fila_chat SET ultima_atualizacao=CURRENT_TIMESTAMP WHERE id=$id");
                    $ps->execute();
                    $ps->close();
                   
                }else{
                    
                    $ps->close();
                    
                    $ps = $con->getConexao()->prepare("INSERT INTO fila_chat(id_usuario,ultima_atualizacao) VALUES(".$this->usuario->id.",CURRENT_TIMESTAMP)");
                    $ps->execute();
                    $ps->close();
                    
                }
                
            }
            
            return false;
            
        }


	public function getMensagens($con,$last_id=0){

		$ret = array();

		$ps = $con->getConexao()->prepare("SELECT m.id,u.id,u.nome,UNIX_TIMESTAMP(m.momento)*1000,m.texto FROM mensagem_suporte m INNER JOIN usuario u ON m.id_usuario=u.id WHERE m.id_suporte=$this->id  AND m.id>$last_id");
		$ps->execute();
		$ps->bind_result($id,$id_usuario,$nome_usuario,$momento,$texto);
		
		while($ps->fetch()){

			$m = new MensagemSuporte();
			$m->id = $id;

			$u = new Usuario();
			$u->id = $id_usuario;
			$u->nome = $nome_usuario;

			$m->usuario = $u;

			$m->momento = $momento;
			$m->texto = $texto;

			$ret[] = $m;

		}

		$ps->close();

		return $ret;


	}

	public function addMensagem($con,$msg){

		$msg->merge($con);

		$ps = $con->getConexao()->prepare("UPDATE mensagem_suporte SET id_suporte=$this->id WHERE id=$msg->id");
		$ps->execute();
		$ps->close();

	}


	public function merge($con){

		if($this->id === 0){

			$ps = $con->getConexao()->prepare("INSERT INTO suporte(id_usuario,id_atendente,inicio,fim) VALUES(".$this->usuario->id.",".$this->atendente->id.",FROM_UNIXTIME($this->inicio/1000),".($this->fim!==null?"FROM_UNIXTIME($this->fim/1000)":"null").")");
			$ps->execute();
			$this->id = $ps->insert_id;
			$ps->close();

		}else{

			$ps = $con->getConexao()->prepare("UPDATE suporte SET id_usuario=".$this->usuario->id.", id_atendente=".$this->atendente->id.", inicio=FROM_UNIXTIME($this->inicio/1000),fim=".($this->fim!==null?"FROM_UNIXTIME($this->fim/1000)":"null")." WHERE id=$this->id");
			$ps->execute();
			$ps->close();

		}


	}


}
