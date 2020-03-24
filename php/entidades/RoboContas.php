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
class RoboContas {

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
        
        $empresas = array();
        
        $ps = $con->getConexao()->prepare("SELECT id FROM empresa WHERE tipo_empresa=5");
        $ps->execute();
        $ps->bind_result($id_empresa);
        while($ps->fetch()){
            $empresas[] = $id_empresa;
        }
        $ps->close();
        
        foreach($empresas as $key=>$value){
            
            $inicio = (round(microtime(true))-5*365*24*60*60)*1000;
            $fim = round(microtime(true))*1000;
            $adm = new Administracao($value,$con);            

            $adm->tipo_empresa = 5;
            
            $contas_pagar = new RelatorioFinanceiro($adm);
            $contas_pagar->campos[0]->inicio = $inicio;
            $contas_pagar->campos[0]->fim = $fim;
            $contas_pagar->campos[0]->filtro = "k.data >= FROM_UNIXTIME($inicio/1000) AND k.data <= FROM_UNIXTIME($fim/1000)";
            $contas_pagar->order = "k.nome_empresa DESC,k.data DESC";
            
            $pdf_pagar = $contas_pagar->getPdf($con, $adm);
            
            $contas_receber = new RelatorioFinanceiroReceber($adm);
            $contas_receber->campos[0]->inicio = $inicio;
            $contas_receber->campos[0]->fim = $fim;
            $contas_receber->campos[0]->filtro = "k.data >= FROM_UNIXTIME($inicio/1000) AND k.data <= FROM_UNIXTIME($fim/1000)";
            $contas_receber->order = "k.nome_empresa DESC,k.data DESC";
            
            $pdf_receber = $contas_receber->getPdf($con, $adm);
            
            $conteudo = "Contas a pagar: <a href='".$pdf_pagar."'>LINK PARA O RELATORIO</a><hr>";
            $conteudo .= "Contas a receber: <a href='".$pdf_receber."'>LINK PARA O RELATORIO</a><hr>";
            
            $adm->email->enviarEmail($adm->email->filtro(Email::$ADMINISTRATIVO), "Relatorio contas $adm->nome", $conteudo);
                        
        }
        
    }

}
