<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cidade
 *
 * @author Renan
 */
class Receituario {
    
    public static $KG = array(0,"KG",1);
    public static $G = array(1,"g",1000);
    public static $L = array(2,"L",1);
    public static $ML = array(3,"ml",1000);
    
    public static $HECTARE = array(0,"HECTARE",0);
    public static $LITRO_AGUA = array(1,"LITROS DE AGUA",1);
    public static $METROS_TERRA = array(2,"METROS DE TERRA",0);
    public static $KG_SEMENTES = array(3,"KG DE SEMENTES",0);
    public static $TONELADAS_GRAOS = array(4,"TONELADAS DE GRAOS",0);
    public static $COVAS = array(5,"COVAS",2);
    public static $PES = array(6,"PES",2);
    
    public static function getTiposPlantacao(){
        
        return array(
            self::$HECTARE,
            self::$LITRO_AGUA,
            self::$METROS_TERRA,
            self::$KG_SEMENTES,
            self::$TONELADAS_GRAOS,
            self::$COVAS,
            self::$PES
        );
        
    }
    
    public static function getMedidas(){
        
        return array(
            self::$KG,
            self::$G,
            self::$L,
            self::$ML
        );
        
    }
    
    
    public $id;
    public $excluido;
    public $instrucoes;
    public $produto;
    public $cultura;
    public $praga;
    
    public $tipo_plantacao;
    
    public $total_calda_ha;
    public $tipo_total_calda_ha;
    
    public $carencia;
    
    public $qtd_calda;
    public $unidade_qtd_calda;
    
    public $unidade_usada;
    
    public $dosagem_max;
    public $tipo_dosagem_max;
    
    public $epoca_aplicacao;
    
    public $diagnostico;
    
    public $manejo_integrado;
    public $precaucoes;
    
    public $epi;
    
    public $informacoes_adcionais;
    
    function __construct() {
        
        $this->id = 0;
        $this->excluido = false;
        $this->produto = null;
        $this->cultura = null;
        $this->praga = null;
        
        $this->tipo_plantacao = self::$HECTARE;
    
        $this->total_calda_ha = 0;
        
        $this->tipo_total_calda_ha = self::$L;

        $this->carencia = 0;

        $this->qtd_calda = 1;
        $this->unidade_qtd_calda = self::$L;

        $this->unidade_usada = "Litros de Agua";

        $this->dosagem_max = 1;
        $this->tipo_dosagem_max = self::$L;

        $this->epoca_aplicacao = "";

        $this->diagnostico = "";

        $this->manejo_integrado = "";
        $this->precaucoes = "";

        $this->epi = "";

        $this->informacoes_adcionais = "";
        
    }
    
    public function merge($con) {

        $this->cultura->merge($con);
        $this->praga->merge($con);
        
        if ($this->id == 0) {

            $ps = $con->getConexao()->prepare("INSERT INTO receituario(instrucoes,excluido,id_produto,id_praga,id_cultura,tipo_plantacao,total_calda_ha,tipo_total_calda_ha,carencia,qtd_calda,unidade_qtd_calda,unidade_usada,dosagem_max,tipo_dosagem_max,epoca_aplicacao,diagnostico,manejo_integrado,precaucoes,epi,informacoes_adcionais) VALUES('" . addslashes($this->instrucoes) . "',false,".$this->produto->id.",".$this->praga->id.",".$this->cultura->id.",".$this->tipo_plantacao[0].",$this->total_calda_ha,".$this->tipo_total_calda_ha[0].",$this->carencia,$this->qtd_calda,".$this->unidade_qtd_calda[0].",'$this->unidade_usada',$this->dosagem_max,".$this->tipo_dosagem_max[0].",'$this->epoca_aplicacao','$this->diagnostico','$this->manejo_integrado','$this->precaucoes','$this->epi','$this->informacoes_adcionais')");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
            
        }else{
            
            $ps = $con->getConexao()->prepare("UPDATE receituario SET instrucoes = '" . addslashes($this->instrucoes) . "', excluido=false, id_cultura=".$this->cultura->id.", id_praga=".$this->praga->id.", id_produto=".$this->produto->id.",tipo_plantacao=".$this->tipo_plantacao[0].",total_calda_ha=$this->total_calda_ha,tipo_total_calda_ha=$this->tipo_total_calda_ha,carencia=$this->carencia,qtd_calda=$this->qtd_calda,unidade_qtd_calda=$this->unidade_qtd_calda,unidade_usada='$this->unidade_usada',dosagem_max=$this->dosagem_max,tipo_dosagem_max=".$this->tipo_dosagem_max[0].",epoca_aplicacao='$this->epoca_aplicacao',diagnostico='$this->diagnostico',manejo_integrado='$this->manejo_integrado',precaucoes='$this->precaucoes',epi='$this->epi',informacoes_adcionais='$this->informacoes_adcionais' WHERE id = ".$this->id);
            $ps->execute();
            $ps->close();
            
        }
        
    }
    
    public function delete($con){
        
        $ps = $con->getConexao()->prepare("UPDATE receituario SET excluido = true WHERE id = ".$this->id);
        $ps->execute();
        $ps->close();
        
    }
    
}
