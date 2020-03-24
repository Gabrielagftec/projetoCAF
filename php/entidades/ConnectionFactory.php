<?php

class ConnectionFactory {

    private static $conexao;

    public function getConexao() {

        if (self::$conexao == null) {
            self::$conexao = new mysqli("www.rtcagro.com.br", "SYSTEMRTC", "PexXujXZgPRQVPxe4hUh", "novo_rtc", 3306);
        }


        return self::$conexao;
    }

}

?>