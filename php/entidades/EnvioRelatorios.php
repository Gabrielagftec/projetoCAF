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

class EnvioRelatorios{

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

       date_default_timezone_set("America/Sao_Paulo");
        
        $data = "CURRENT_DATE";
       
        $empresas = array();
        $empresas_inteiras = array();
        
        $ps = $con->getConexao()->prepare("SELECT id,tipo_empresa FROM empresa WHERE rtc>=6");
        $ps->execute();
        $ps->bind_result($id, $tipo_empresa);
        while ($ps->fetch()) {
            $e = Sistema::getEmpresa($tipo_empresa);
            $e->id = $id;
            $empresas[] = $e;
        }
        $ps->close();
        
        $cpfs = array();
        $tasks = array();
        $envios = array();
        $logos = array();   

        foreach ($empresas as $key => $empresa) {

            $usuarios = $empresa->getUsuarios($con, 0, 10000,"usuario.nome NOT LIKE '%Renan%'");
            
            foreach ($usuarios as $key2 => $usuario) {

            
               
                $cpf = md5($usuario->cpf->valor);

                $tarefas = $usuario->getTarefas($con, 'DATE(observacao.momento)=DATE(' . $data . ') AND MONTH(observacao.momento)=MONTH(' . $data . ') AND YEAR(observacao.momento)=YEAR(' . $data . ')', '',true);

                
                
                if (count($tarefas) === 0) {
                    continue;
                }
                
                
                if (isset($empresas_inteiras[$empresa->id])) {
                    $empresa = $empresas_inteiras[$empresa->id];
                } else {
                    $empresas_inteiras[$empresa->id] = new Empresa($empresa->id, $con);
                    $empresa = $empresas_inteiras[$empresa->id];
                }

                $ausencias = $usuario->getAusencias($con, 'ausencia.fim>CURRENT_TIMESTAMP');
                $expedientes = $usuario->getExpedientes($con);

                $tarefas = IATarefas::aplicar($expedientes, $ausencias, $tarefas);


                $org = new Organograma($empresa);
                $superiores = $org->getSuperiores($con, $usuario);
                if ($superiores === null)
                    continue;

                if (!isset($cpfs[$cpf])) {
                    $cpfs[$cpf] = $usuario;
                    $tasks[$cpf] = array();
                    $envios[$cpf] = array();
                }

                foreach ($tarefas as $kk => $tt) {
                    $tasks[$cpf][] = $tt;
                }

                $emails = "(-1";
                foreach ($superiores as $key => $value) {
                    $emails .= ",$value->id_usuario";
                }
                $emails .= ",$usuario->id";
                $emails .= ")";

                $ps = $con->getConexao()->prepare("SELECT id,endereco,senha FROM email WHERE excluido=false AND tipo_entidade='USU' AND id_entidade IN $emails");
                $ps->execute();
                $ps->bind_result($id, $endereco, $senha);
                $emails = array();
                while ($ps->fetch()) {
                    $e = new Email($endereco);
                    $e->id = $id;
                    $e->senha = $senha;
                    $envios[$cpf][] = $e;
                }
                $ps->close();

                if (!isset($logos[$cpf])) {
                    $logos[$cpf] = array($empresa, count($tarefas));
                } else {
                    if ($logos[$cpf][1] < count($tarefas)) {
                        $logos[$cpf] = array($empresa, count($tarefas));
                    }
                }

                
                
    
            }

        }
        
        
        $email = new Email("suporte@agftec.com.br");
        $email->senha = "5Q44Cq2uACTNoUVO";


        foreach ($cpfs as $cpf => $usuario) {

            $obj_relatorio = new stdClass();
            $obj_relatorio->empresa = $logos[$cpf][0];
            $obj_relatorio->usuario = $usuario;
            $obj_relatorio->tarefas = $tasks[$cpf];
            $obj_relatorio->est = $usuario->getEstatisticas($con);
            $obj_relatorio->est_dia = $usuario->getEstatisticas($con,true);
            
            $html = Sistema::getHtml('relatorio_servico', $obj_relatorio);
            $emails = $envios[$cpf];
       
          
                try {
                    
                    $email->enviarEmail($emails, 'Relatorio do ' . $usuario->nome, $html);
                    //Sistema::avisoDEVS_MASTER($html);
                } catch (Exception $ex) {
                    Sistema::avisoDEVS('Erro no envio de email dos relatorios, ' . $ex->getMessage());
                }
            
        }
    }

}
