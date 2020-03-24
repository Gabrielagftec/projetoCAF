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
class RoboProtocolo {

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
      
        $status = array(
            Sistema::STATUS_SEPARACAO(),
            Sistema::STATUS_FATURAMENTO(),
        );
        
        
        $str_status = "(-1";
        
        foreach($status as $key=>$value){
            $str_status .= ",".$value->id;
        }
        
        $str_status .= ")";
        
        $empresas = Sistema::getEmpresas($con, 'empresa.rtc>=5 AND empresa.id NOT IN (1733)');
        
        
        foreach($empresas as $key=>$empresa){
            
            $emergencias = $empresa->getPedidos($con,0,10,"DATE_ADD(pedido.data,INTERVAL 96 HOUR)<CURRENT_TIMESTAMP AND pedido.id_status IN $str_status AND DATE_ADD(pedido.data,INTERVAL 10 DAY)>=CURRENT_DATE AND pedido.id NOT IN (SELECT id_entidade FROM protocolo WHERE tipo_entidade='Pedido' GROUP BY id_entidade)");
            
            $servicos = $empresa->getPedidos($con,0,10,"DATE_ADD(pedido.data,INTERVAL 48 HOUR)<CURRENT_TIMESTAMP AND pedido.id_status IN $str_status AND DATE_ADD(pedido.data,INTERVAL 10 DAY)>=CURRENT_DATE AND pedido.id NOT IN (SELECT id_entidade FROM protocolo WHERE tipo_entidade='Pedido' GROUP BY id_entidade)");
           
            foreach($servicos as $key2=>$value){
                foreach($emergencias as $key3=>$value2){
                    if($value->id === $value2->id){
                        unset($servicos[$key2]);
                        break;
                    }
                }
            }
            
            foreach($servicos as $key=>$value){
                
                $dir_e = Empresa::CF_DIRETOR($value->empresa);
                $sep_e = Empresa::CF_SEPARADOR($value->empresa);
                $enc_e = Empresa::CF_ENCARREGADO_LOGISTICA($value->empresa);
                $cord_e = Empresa::CF_COORDENADOR_LOGISTICA($value->empresa);
                $fat_e = Empresa::CF_FATURISTA($value->empresa);

                $cargos = array($dir_e,$sep_e,$enc_e,$cord_e,$fat_e);

                $in_cargos = "(-1";

                foreach($cargos as $key2=>$c){
                    $in_cargos .= ",$c->id";
                }

                $in_cargos .= ")";

                $usuarios = array();
                $tipos_protocolo = $empresa->getTiposProtocolo($con);
                $tipo_protocolo = null;
                
                foreach($tipos_protocolo as $tp=>$prot){
                    
                    //:( Protocolo de Serviço, gambiarra, pfv retirar isso depois de maneira sofisticada
                   
                    if(strpos($prot->nome, "Servi") === false){
                        continue;
                    }
                    
                    if($tipo_protocolo === null){
                        $tipo_protocolo = $prot;
                    }else{
                        if($tipo_protocolo->prioridade<$prot->prioridade){
                            $tipo_protocolo = $prot;
                        }
                    }
                    
                }
                
                if($tipo_protocolo === null){
                    continue;
                }
                
                
                if($value->logistica !== null){

                    $usuarios = $value->logistica->getUsuarios($con,0,10,"usuario.id_cargo IN $in_cargos");

                }else{

                    $usuarios = $value->empresa->getUsuarios($con,0,10,"usuario.id_cargo IN $in_cargos");

                }
              
                $protocolo = new Protocolo();
                $protocolo->tipo = $tipo_protocolo;
                $protocolo->tipo_entidade = "Pedido";
                $protocolo->id_entidade = $value->id;
                $protocolo->usuarios = $usuarios;
                $protocolo->empresa = $value->empresa;
                $protocolo->titulo = "Pedido pendente na etapa de ".$value->status->nome;
                $protocolo->descricao = "Pedido do cliente ".$value->cliente->razao_social.", pendente na etapa de ".$value->status->nome." desde ".date("d/m/Y",$value->data/1000);
                
                $protocolo->merge($con);
                
                
            }
            
            foreach($emergencias as $key2=>$value){
              
                $dir_e = Empresa::CF_DIRETOR($value->empresa);
                $sep_e = Empresa::CF_SEPARADOR($value->empresa);
                $enc_e = Empresa::CF_ENCARREGADO_LOGISTICA($value->empresa);
                $cord_e = Empresa::CF_COORDENADOR_LOGISTICA($value->empresa);
                $fat_e = Empresa::CF_FATURISTA($value->empresa);

                $cargos = array($dir_e,$sep_e,$enc_e,$cord_e,$fat_e);

                $in_cargos = "(-1";

                foreach($cargos as $key2=>$c){
                    $in_cargos .= ",$c->id";
                }

                $in_cargos .= ")";

                $usuarios = array();
                $tipos_protocolo = $empresa->getTiposProtocolo($con);
                $tipo_protocolo = null;
                
                foreach($tipos_protocolo as $tp=>$prot){
                    
                    if($tipo_protocolo === null){
                        $tipo_protocolo = $prot;
                    }else{
                        if($tipo_protocolo->prioridade<$prot->prioridade){
                            $tipo_protocolo = $prot;
                        }
                    }
                    
                }
                
                if($value->logistica !== null){

                    $usuarios = $value->logistica->getUsuarios($con,0,10,"usuario.id_cargo IN $in_cargos");

                }else{

                    $usuarios = $value->empresa->getUsuarios($con,0,10,"usuario.id_cargo IN $in_cargos");

                }
              
                $protocolo = new Protocolo();
                $protocolo->tipo = $tipo_protocolo;
                $protocolo->tipo_entidade = "Pedido";
                $protocolo->id_entidade = $value->id;
                $protocolo->usuarios = $usuarios;
                $protocolo->empresa = $value->empresa;
                $protocolo->titulo = "Pedido pendente na etapa de ".$value->status->nome;
                $protocolo->descricao = "Pedido do cliente ".$value->cliente->razao_social.", pendente na etapa de ".$value->status->nome." desde ".date("d/m/Y",$value->data/1000);
                
                $protocolo->merge($con);
                
            }
            
            
        }
        
    }

}
