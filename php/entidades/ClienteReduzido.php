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

class ClienteReduzido {
    
    public $id;
    public $codigo;
    public $razao_social;
    public $cnpj;
    public $email;
    public $id_empresa;
    
    function __construct($cliente = null) {
        
        if($cliente !== null){
        
            $this->id = $cliente->id;
            $this->codigo = $cliente->codigo;
            $this->razao_social = $cliente->razao_social;
            $this->cnpj = $cliente->cnpj;
            $this->email = $cliente->email;
            $this->id_empresa = $cliente->empresa->id;
            
        }
        
    }
    
    public function getCliente($con){
        
        $empresa = new Empresa($this->id_empresa,$con);
        
        $cliente = $empresa->getClientes($con, 0, 1,"cliente.id=$this->id_real");
        
        return $cliente[0];
        
    }

}
