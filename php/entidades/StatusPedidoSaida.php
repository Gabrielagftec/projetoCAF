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
class StatusPedidoSaida {
   
    public $id;
    public $nome;
    public $estoque;
    public $reserva;
    public $emailCliente;
    public $emailInterno;
    public $volta_status;
    public $altera;
    public $parado;
    public $nota;
    
    function __construct($id=0,$nome="",$estoque=false,$reserva=false,$emailCliente = "",$emailInterno = "",$volta_status=true,$altera_pedido=true,$parado=true,$nota=false) {
        
        $this->id = $id;
        $this->nome = $nome;
        $this->estoque = $estoque;
        $this->reserva = $reserva;
        $this->emailCliente = $emailCliente;
        $this->emailInterno = $emailInterno;
        
        $this->volta_status = $volta_status;
        $this->altera = $altera_pedido;
        $this->parado = $parado;
        $this->nota = $nota;
        
    }
    
    
    public function enviarEmails($pedido){

        $envios = array();
        $html = Sistema::getHtml('visualizar-pedido-print', $pedido);

        if ($this->emailCliente !== "") {
            $envios[] = $pedido->cliente->email->filtro($this->emailCliente);
        } 
        
        if ($this->emailInterno !== "") {
            $envios[] = $pedido->empresa->email->filtro($this->emailInterno);
        }

        if(count($envios) > 0){
            $pedido->empresa->email->enviarEmail($envios, "Pedido numero " . $pedido->id, $html);
        }

    }
  
    
}
