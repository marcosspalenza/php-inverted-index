<?php 
    $wordhash = array();
    $docs = 0;
    $pasta = "/home/markinhos/databases/livros/";
    if ($handle = opendir($pasta)){
        echo("Database [LOADING]\n");
        while (false != ($file = readdir($handle))){
            if (($file != ".") && ($file != "..")){
                $get_txt = strtolower(file_get_contents($pasta.$file, true));
                $get_txt = preg_replace('!\s+!', ' ', $get_txt);
                $tok = strtok($get_txt," ");
                while ($tok != false) {
                    $values[] = trim($tok," \t\n\r\0\x0B");
                    $tok = strtok(" ");
                }
                $values[] = $tok;
                $wordhash = organizearr($wordhash,$values,$docs);
                $docs++;
            }
        }
        echo($docs." DOCUMENTOS\n");    
        closedir($handle);
    }
    echo("Database [OK]\n");
    $word = "";
    do{
        $point = 0;
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
            echo("N = ".sizeof($wordhash[$word])."\n");
            while($opcao!=0 && strcmp("exit",$word)){
                $opcao = readline("\n[ ~ MODO PALAVRA ~ ]
                \n[1]First
                \n[2]Last
                \n[3]Next
                \n[4]Prev
                \n[5]First Doc
                \n[6]Last Doc
                \n[7]Next Doc
                \n[8]Prev Doc
                \n[9]Resetar Ponteiro
                \n[0]Sair
                \n\tFunção:\n");
                switch($opcao){
                    case 1:
                        //firstW
                        echo (firstWord($wordhash, $word, $point));
                        break;
                    case 2:
                        //lastW
                        echo(lastWord($wordhash, $word, $point));
                        break;
                    case 3:
                        //nextW
                        echo(nextWord($wordhash, $word, $point));
                        break;
                    case 4:
                        //prevW
                        echo(prevWord($wordhash, $word, $point));
                        break;
                    case 5:
                        //firstDoc
                        $point = firstDoc($wordhash, $word, $point);
                        echo("First Doc = ".strval(docid($wordhash[$word][$point]))."\n");
                        break;
                    case 6:
                        //lastDoc
                        $point = lastDoc($wordhash, $word, $point);
                        echo("Last Doc = ".strval(docid($wordhash[$word][$point]))."\n");
                        break;
                    case 7:
                        //nextDoc
                        $point = nextDoc($wordhash, $word, $point);
                        echo("Next Doc = ".strval(docid($wordhash[$word][$point]))."\n");
                        break;
                    case 8:
                        //prevDoc
                        $point = prevDoc($wordhash, $word, $point);
                        echo("Prev Doc = ".strval(docid($wordhash[$word][$point]))."\n");
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
                    $idx = array_search($t,$tokens);
                    break;
                }
            }
            if($exists){
                $fval = nextPhrase($wordhash,$tokens,$point);
                if($fval != null){
                    echo("Sequência encontrada\n");
                    print_r($fval);
                    $aux = $fval[sizeof($fval)-1];
                    $point = strval(docid($aux)).":".strval(offset($aux));
                }else{
                    echo("Sequência não encontrada\n");
                }
            }else{
                $point = strval(0).":".strval(0);
                echo("O sistema não reconheceu o token [".$tokens[$idx]."] na base de dados\n");
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
        return preg_replace(array_keys($utf8), array_values($utf8), $txt);
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
    function organizeArr($assocarr, $lista, $did){
        for($i=0; $i < sizeof($lista); $i++){
            $palavra = stdtxt($lista[$i]);
            $palavra = rem_pts($palavra);
            if(array_key_exists($palavra, $assocarr)){
                $assocarr[$palavra][] = $did.":".$i;
            }else{
                $assocarr[$palavra] = array();
                $assocarr[$palavra][] = $did.":".$i;
            }
        }
        return $assocarr;
    }
    function docid($pos){
        if(strpos($pos,":") == false){
            $val = (0);
        }else{
            $val = substr($pos,0,strpos($pos,":"));
        }
        return $val;
    }
    function offset($pos){
        if(strpos($pos,":") == false){
            $val = (0);
        }else{
            $val = substr($pos,strpos($pos,":")+1);
        }
        return $val;
    }
    function nextWord($lista, $palavra, &$pt){
        //next
        if($pt<(sizeof($lista[$palavra])-1)){
            $pt ++; 
        }
        $out = ("[P:".$pt."]\nNext = ".$lista[$palavra][$pt]."\n");
        return $out;
    }
    function firstWord($lista, $palavra, &$pt){
        //first
        $out = ("First = ".$lista[$palavra][0]."\n");
        $pt = 0;
        return $out;
    }
    function lastWord($lista, $palavra, &$pt){
        //last
        $out = ("Last = ".$lista[$palavra][sizeof($lista[$palavra])-1]."\n");
        $pt = sizeof($lista[$palavra])-1;
        return $out;
    }
    function prevWord($lista, $palavra, &$pt){
        //prev
        if($pt>0){
            $pt --; 
        }
        $out = ("[P:".$pt."]\nPrev = ".$lista[$palavra][$pt]."\n");
        return $out;
    }
    function firstDoc($lista, $palavra, $pt){
        //firstDoc
        return 0;
    }
    function lastDoc($lista, $palavra, $pt){
        //lastDoc
        return sizeof($lista[$palavra])-1;
    }
    function prevDoc($lista, $palavra, $pt){
        //prevDoc
        $ndoc = (int)docid($lista[$palavra][$pt]);
        while ($ndoc <= ((int)docid($lista[$palavra][$pt])) && $pt > 0){
            $pt --;
        }
        return $pt;
    }
    function nextDoc($lista, $palavra, $pt){
        //nextDoc
        $ndoc = (int)docid($lista[$palavra][$pt]);
        while ($ndoc >= ((int)docid($lista[$palavra][$pt])) && $pt < sizeof($lista[$palavra])-1){
            $pt ++;
        }
        return $pt;
    }
    function nextPhrase($lista, $frase, $pt){
        if ($pt == 0){
            $pt = strval($lista[$frase[0]][0]); 
        }
        foreach($lista[$frase[0]] as $num){
            $flag = true;
            $find = array();
            if(docid($pt) <= docid($num)){
                if(docid($num) == docid($pt) && offset($num) >= offset($pt)){
                    $flag = false;
                }
                $find[] = docid($num).":".offset($num);
                for($i = 1; $i< sizeof($frase) && $flag == true; $i++){
                    if(!in_array(docid($num).":".(offset($num)+$i),$lista[$frase[$i]])){
                        $flag = false;
                    }else{
                        $find[] = strval(docid($num).":".(offset($num)+$i));
                        print_r($find);
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
