<?php


class SuperNodo{

    protected $ip;
    protected $port;
    protected $peers;
    protected $socket;

    public function __construct($ip, $port){
        $this->ip = $ip;
        $this->port = $port;
        $this->peers = array();
        $this->createSocket();
    }

    private function createSocket(){
        if(!($this->socket = socket_create(AF_INET, SOCK_DGRAM, 0))){
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            die("Can't create socket -> [$errorcode] $errormsg \n");
        }
        if( !socket_bind($this->socket, $this->ip , $this->port) ) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            die("Can't create socket ->  [$errorcode] $errormsg \n");
        }
    }

    public function startServer(){
        echo "Reaaaady server\n";
        while(1){
            echo str_pad("=", 80 , "=")."\n";
            //socket_sendto($this->socket, $mensagem, 100 , 0 , $this->config['ipDestino'], $this->config['porta']);
            socket_recvfrom($this->socket, $mensagem, 512, 0, $remoteIp, $remotePort);
            if($mensagem != ''){
                echo "Mensagem recebida: ".$mensagem."\n";
            }
            
            $this->trataMensagem($mensagem,$remoteIp);
            print_r($this->peers);
            
            $retorno = "Valeu é nós!!";

            //se for o ip local manda pra ele de novo, senao manda pro da frente
            $remote = array($remoteIp, $remotePort);

            //Envia a mensagem de novo pro cliente
            socket_sendto($this->socket, $retorno[1], 100, 0, $remote[0], $remote[1]);
        }
    }

    private function trataMensagem($mensagem,$remoteIp){

        $mensagem = json_decode($mensagem, 1);
        if(is_array($mensagem) && $mensagem['action']){
            switch ($mensagem['action']){
                case "loadfile":
                    $this->setFiles($remoteIp, $mensagem['file'], $mensagem['hash']);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    public function setFiles($ipNode, $file, $hash){
        $this->peers[$ipNode][] = array(
            $hash => $file
        );
    }



}