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
class RoboRecorrencia {

    public $dia;
    public $mes;
    public $ano;
    public $hora;
    public $minuto;
    public $segundo;
    public $momento;

    public function __construct() {

        date_default_timezone_set("America/Sao_Paulo");

        $this->momento = round(microtime(true) * 1000);

        $str = explode(':', date('d:m:Y:H:i:s', $this->momento / 1000));
        $this->dia = intval($str[0]);
        $this->mes = intval($str[1]);
        $this->ano = intval($str[2]);
        $this->hora = intval($str[3]);
        $this->minuto = intval($str[4]);
        $this->segundo = intval($str[5]);
    }

    public function executar($con) {
        
        $tarefas_clonar = array();
        
        $ps = $con->getConexao()->prepare("SELECT k.id_tarefa FROM (SELECT SUM(o.porcentagem) as 'porcentagem',MAX(o.momento) as 'data',t.recorrencia as 'rec',t.id as 'id_tarefa' FROM tarefa t INNER JOIN observacao o ON o.id_tarefa=t.id WHERE t.excluida=false AND t.recorrencia>0 AND t.recorrencia_efetuada=false GROUP BY t.id) k WHERE k.porcentagem>=100 AND DATE_ADD(cast(k.data as date),INTERVAL k.rec DAY)<=CURRENT_DATE");
        $ps->execute();
        $ps->bind_result($id_tarefa);
        while($ps->fetch()){
            $tarefas_clonar[] = $id_tarefa;
        }
        $ps->close();
        
        foreach ($tarefas_clonar as $key=>$id_tarefa){
            
            $ps = $con->getConexao()->prepare("UPDATE tarefa SET recorrencia_efetuada=true, start_usuario=start_usuario, inicio_minimo=inicio_minimo WHERE id=$id_tarefa");
            $ps->execute();
            $ps->close();
            
            $ps = $con->getConexao()->prepare("INSERT INTO tarefa(inicio_minimo,ordem,porcentagem_conclusao,id_usuario,tipo_entidade_relacionada,id_entidade_relacionada,titulo,descricao,intervalos_execucao,realocavel,excluida,id_tipo_tarefa,prioridade,criada_por,start_usuario,agendamento,sucesso,recorrencia,recorrencia_efetuada) (SELECT CURRENT_TIMESTAMP,t.ordem,0,t.id_usuario,t.tipo_entidade_relacionada,t.id_entidade_relacionada,t.titulo,t.descricao,'',t.realocavel,0,t.id_tipo_tarefa,t.prioridade,t.criada_por,t.start_usuario,null,1,t.recorrencia,false FROM tarefa t WHERE id=$id_tarefa)");
            $ps->execute();
            $ps->close();
            
        }
        
    }

}
