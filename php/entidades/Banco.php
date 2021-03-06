<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CategoriaDocumento
 *
 * @author Renan
 */
class Banco {
    
    private static function normalizarDia($ms) {

        date_default_timezone_set("America/Sao_Paulo");
        
        $d = explode(':', date('H:i:s', $ms / 1000));

        $nm = $ms;

        $nm -= intval($d[0]) * 60 * 60 * 1000;
        $nm -= intval($d[1]) * 60 * 1000;
        $nm -= intval($d[2]) * 1000;

        return $nm;
    }

    public $id;
    public $codigo;
    public $nome;
    public $conta;
    public $agencia;
    public $saldo;
    public $excluido;
    public $empresa;
    public $codigo_contimatic;
    public $fechamento;
    
    function __construct() {

        $this->id = 0;
        $this->saldo = 0;
        $this->codigo = 0;
        $this->agencia = "";
        $this->conta = "";
        $this->excluido = false;
        $this->codigo_contimatic = 0;
        $this->fechamento = false;
        
    }
    
    public function getRelatorioMovimentosFechamento($con){
        
        $obs = "Fechamento de ";
        
        $fechamento = $this->getFechamento($con);
        
        $obs .= date("d/m/Y",doubleval($fechamento->data_anterior."")/1000)." ate ".date("d/m/Y",doubleval($fechamento->data."")/1000).", valor de ".number_format(round($fechamento->valor,2),2,',','').", saldo atual ".number_format(round($this->saldo,2),2,',','').", Fechamento anterior: ".number_format(round($fechamento->valor_anterior,2),2,',','');
        
        $qtd = $this->getCountMovimentosFechamento($con);
        $movimentos = $this->getMovimentosFechamento($con, 0, $qtd,"","movimento.data ASC");
        
        
        $campos = array(
            array("tipo","Tipo",5),
            array("cf","Cliente/Fornecedor",30),
            array("operacao","Op.",10),
            array("data","Data",11),
            array("valor","Valor",10),
            array("juros","Juros",5),
            array("desconto","Desc",5),
            array("saldo","Saldo Atual",12),
            array("nota","Nota",6),
            array("ficha","Ficha",6));
        
        $valores = array();

        if(count($movimentos)>500){
            return;
        }
        
        foreach($movimentos as $key=>$value){
            
            $l = array();
            
            if($value->operacao->debito){
                $l[] = "Deb";
                $l[] = utf8_decode($value->vencimento->nota->fornecedor->nome);
            }else{
                $l[] = "Cred";
                $l[] = utf8_decode($value->vencimento->nota->cliente->razao_social);
            }
            
            $l[] = utf8_decode($value->operacao->nome);
            $l[] = date('d/m/Y',$value->data/1000);
            $l[] = number_format(round($value->valor,2)."",2,',','');
            $l[] = number_format(round($value->juros,2)."",2,',','');
            $l[] = number_format(round($value->descontos,2)."",2,',','');
            $l[] = number_format(round($value->saldo_anterior,2)."",2,',','');
            $l[] = $value->vencimento->nota->numero;
            $l[] = $value->vencimento->nota->ficha;
            
            $valores[] = $l;
            
        }
        
        return Sistema::gerarRelatorio($con, $this->empresa, "Fechamento do banco $this->nome", $obs, $campos, $valores);
        
    }
    
    public function getFechamento($con){
        
        $anterior = 0;
        $data_anterior = 0;
        
        $ps = $con->getConexao()->prepare("SELECT ROUND(valor,2),UNIX_TIMESTAMP(data)*1000 FROM fechamento_caixa WHERE id_banco=$this->id ORDER BY data DESC");
        $ps->execute();
        $ps->bind_result($valor,$data);
        if($ps->fetch()){
            $anterior = $valor;
            $data_anterior = Utilidades::normalizarDia($data);
            $data_anterior += 24*60*60*1000;
        }
        $ps->close();
        $real_anterior = $anterior;
        
        $ps = $con->getConexao()->prepare("SELECT SUM((CASE WHEN operacao.debito THEN -1 ELSE 1 END)*(movimento.valor-movimento.descontos+movimento.juros)),UNIX_TIMESTAMP(MAX(movimento.data))*1000 FROM movimento "
                . "INNER JOIN operacao ON movimento.id_operacao=operacao.id "
                . "INNER JOIN vencimento ON vencimento.id=movimento.id_vencimento "
                . "INNER JOIN nota ON nota.id=vencimento.id_nota "
                . "WHERE movimento.id_banco=$this->id AND movimento.data>=FROM_UNIXTIME($data_anterior/1000)");
        $ps->execute();
        $ps->bind_result($valor,$data);
        

        $ultima_data = round(microtime(true)*1000);

        if($ps->fetch()){
            $anterior += $valor;
            $ultima_data = $data;
        }
        
        $ps->close();
        
        $fechamento = new FechamentoCaixa();
        $fechamento->valor = $anterior;
        $fechamento->data = $ultima_data;
        $fechamento->banco = $this;
        $fechamento->data_anterior = $data_anterior;
        $fechamento->valor_anterior = $real_anterior;
        
        return $fechamento;
        
    }
    
    
    
    public function getCountMovimentosFechamento($con,$filtro2=""){
        
        $filtro = "";
        
        $ps = $con->getConexao()->prepare("SELECT UNIX_TIMESTAMP(MAX(data))*1000 FROM fechamento_caixa WHERE id_banco=$this->id");
        $ps->execute();
        $ps->bind_result($dt);
        if($ps->fetch()){
            if($dt !== null){
                $dt = Utilidades::normalizarDia($dt) + (24*60*60*1000);
                $filtro .= "movimento.data>=FROM_UNIXTIME($dt/1000) AND movimento.id_banco=$this->id";
            }
        }
        $ps->close();
        
        if($filtro2 !== ""){
            
            $filtro .= " AND $filtro2";
            
        }
        
        $qtd = $this->empresa->getCountMovimentos($con, $filtro);
        
        return $qtd;
        
    }

    public function getMovimentosFechamento($con,$x1,$x2,$filtro2="",$ordem=""){
        
        $filtro = "banco.id=$this->id";
        
        $ps = $con->getConexao()->prepare("SELECT UNIX_TIMESTAMP(MAX(data))*1000 FROM fechamento_caixa WHERE id_banco=$this->id");
        $ps->execute();
        $ps->bind_result($dt);
        if($ps->fetch()){
            if($dt !== null){
                $dt = Utilidades::normalizarDia($dt)+(24*60*60*1000);
                $filtro .= " AND movimento.data>=FROM_UNIXTIME($dt/1000)";
            }
        }
        $ps->close();
        
        if($filtro2 !== ""){
            
            $filtro .= " AND $filtro2";
            
        }
        
        $movimentos = $this->empresa->getMovimentos($con,$x1, $x2,$filtro,$ordem);
        
        return $movimentos;
        
    }
    
    public function atualizaSaldo($con) {

        $ps = $con->getConexao()->prepare("SELECT saldo FROM banco WHERE id = $this->id");
        $ps->execute();
        $ps->bind_result($saldo);
        if ($ps->fetch()) {
            $this->saldo = $saldo;
        }
        $ps->close();
    }

    public function merge($con) {

        if ($this->id == 0) {
            
            $ps = $con->getConexao()->prepare("INSERT INTO banco(nome,conta,saldo,excluido,id_empresa,codigo,agencia,fechamento) VALUES('$this->nome','$this->conta',$this->saldo,false," . $this->empresa->id . ",$this->codigo,'" . addslashes($this->agencia) . "',".($this->fechamento?"true":"false").")");
            $ps->execute();
            $this->id = $ps->insert_id;
            $ps->close();
        } else {

            $ps = $con->getConexao()->prepare("UPDATE banco SET nome='$this->nome',conta='$this->conta',saldo=$this->saldo,excluido = false,codigo=$this->codigo, id_empresa=" . $this->empresa->id . ",agencia='" . addslashes($this->agencia) . "',fechamento=".($this->fechamento?"true":"false")." WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }

        if ($this->codigo_contimatic > 0) {

            $ps = $con->getConexao()->prepare("UPDATE banco SET codigo_contimatic=$this->codigo_contimatic WHERE id=$this->id");
            $ps->execute();
            $ps->close();
        }
    }

    public function delete($con) {

        $ps = $con->getConexao()->prepare("UPDATE banco SET excluido=true WHERE id = " . $this->id);
        $ps->execute();
        $ps->close();
    }

}
