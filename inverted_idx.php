<?php 
    $file = "/home/local.txt";
    $get_txt = strtolower(file_get_contents($file, true));
    //$get_txt = rem_pts($get_txt);
    $get_txt = preg_replace('!\s+!', ' ', $get_txt);
    $tok = strtok($get_txt," ");
    while ($tok != false) {
        $values[] = trim($tok," \t\n\r\0\x0B");
        $tok = strtok(" ");
    }
    $values[] = $tok;
    $wordhash = organizearr($values);
    $word = "";
    do{
        /*a variavel point tem 2 caracteristicas
         - No Modo palavra ela verifica a posicao no vetor individual da palavra
         - No Modo frase ela verifica a posicao real no vetor
         */
        $opcao=1;
        $word = readline("[exit para sair] Digite a palavra buscada: \n");
        $word = stdtxt($word);
        if(isset($wordhash[$word])){
            $point = 0;
            print_r($wordhash[$word]);
            while($opcao!=0 && strcmp("exit",$word)){
                $opcao = readline("\n[ ~ MODO PALAVRA ~ ]
                \n[1]First
                \n[2]Last
                \n[3]Next
                \n[4]Prev
                \n[8]Mostrar Lista
                \n[9]Resetar Ponteiro
                \n[0]Sair
                \n\tFunção:\n");
                switch($opcao){
                    case 1:
                        //first
                        echo("First = ".$wordhash[$word][0]."\n");
                        $point = 0;
                        break;
                    case 2:
                        //last
                        echo("Last = ".$wordhash[$word][sizeof($wordhash[$word])-1]."\n");
                        $point = sizeof($wordhash[$word])-1;
                        break;
                    case 3:
                        //next
                        if($point<(sizeof($wordhash[$word])-1)){
                            $point ++; 
                        }
                        echo("[P:".$point."]\n");
                        echo("Next = ".$wordhash[$word][$point]."\n");
                        break;
                    case 4:
                        //prev
                        if($point>0){
                            $point --; 
                        }
                        echo("[P:".$point."]\n");
                        echo("Prev = ".$wordhash[$word][$point]."\n");
                        break;
                    case 8:
                        //show_data
                        print_r($wordhash[$word]);
                        break;
                    case 9:
                        //reset_value
                        $point = 0;
                        echo("Ponteiro resetado! [P = ".$point."]\n");
                        break;
                    case 0:
                        //exit
                        echo ("Você saiu!\n");
                        break;
                }
            }
        }else{
            /*** Metodo de apresentação do formato da palavra bo texto ***
            echo ("Palavra[".$word."]:\n");
            for($ind = 0; $ind < sizeof($wordhash[$word]); $ind++ ){
                echo("[idx = ".($wordhash[$word][$ind])."] -> ".$values[$wordhash[$word][$ind]]."\n");
            }*/
            $tokens = array();
            $t = strtok(rem_pts($word)," ");
            while ($t != false) {
                    $tokens[] = trim($t," \t\n\r\0\x0B");
                    $t = strtok(" ");
            }
            $exists = true;
            foreach($tokens as $t){
                if(!isset($wordhash[$t])){
                    $exists = false;
                    $point = array_search($t,$tokens);
                    break;
                }
            }
            if($exists){
                $fval = nextPhrase($wordhash,$tokens,$point);
                if($fval != null){
                    echo("Sequência encontrada\n");
                    print_r($fval);
                    $point = $fval[sizeof($fval)-1];
                }else{
                    echo("Sequência não encontrada\n");
                }
            }else{
                $point = 0;
                echo("O sistema não reconheceu o token [".$tokens[$point]."] na base de dados\n");
            }
        }
        
    }while(strcmp("exit",$word));

    function stdtxt($txt) {
        $utf8 = array(
            '/[áàâãªä]/u'   =>   'a',
            '/[ÁÀÂÃÄ]/u'    =>   'A',
            '/[ÍÌÎÏ]/u'     =>   'I',
            '/[íìîï]/u'     =>   'i',
            '/[éèêë]/u'     =>   'e',
            '/[ÉÈÊË]/u'     =>   'E',
            '/[óòôõºö]/u'   =>   'o',
            '/[ÓÒÔÕÖ]/u'    =>   'O',
            '/[úùûü]/u'     =>   'u',
            '/[ÚÙÛÜ]/u'     =>   'U',
            /*'/ç/'           =>   'c',  não é interessante fazer essa modificacao
            '/Ç/'           =>   'C',*/
            '/ñ/'           =>   'n',
            '/Ñ/'           =>   'N',
            '/–/'           =>   ' ',
            '/[’‘‹›‚]/u'    =>   ' ', // Literally a single quote
            '/[“”«»„]/u'    =>   ' ', // Double quote
            '/ /'           =>   ' ' // nonbreaking space (equiv. to 0x160)
        );
        $aux = preg_replace(array_keys($utf8), array_values($utf8), $txt);
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', $aux);//remove os sinais gráficos do texto
    }

    function rem_pts($txt){
        $txt = str_replace(":", "", $txt);
        $txt = str_replace(",", "", $txt);
        $txt = str_replace(";", "", $txt);
        $txt = str_replace(".", "", $txt);
        $txt = str_replace("!", "", $txt);
        $txt = str_replace("?", "", $txt);
        return $txt;
    }
    function organizeArr($lista){
        $assocarr = array(); 
        for($i=0; $i < sizeof($lista); $i++){
            $palavra = stdtxt($lista[$i]);
            $palavra = rem_pts($palavra);
            if(array_key_exists($palavra, $assocarr)){
                $assocarr[$palavra][] = $i;
            }else{
                $assocarr[$palavra] = array();
                $assocarr[$palavra][] = $i;
            }
        }
        return $assocarr;
    }
    function nextPhrase($lista, $frase, $pos){
        foreach($lista[$frase[0]] as $num){
            $flag = true;
            $find = array();
            if($num >= $pos){
                $find[] = $num;
                for($i = 1; $i< sizeof($frase) && $flag == true; $i++){
                    if(!in_array($num+$i,$lista[$frase[$i]])){
                        $flag = false;
                    }else{
                        $find[] = $num+$i;
                    }
                }
                if($flag == true){
                    return $find;
                }
            }
        }
        return null;
    }
?>
