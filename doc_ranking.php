<?php
    $wordhash = array();
    if(!file_exists("rnk-array.json")){
        $docs = 0;
        $pasta = "/home/markinhos/databases/atribuna/";
        if ($handle = opendir($pasta)){
            echo("Database [LOADING]\n");
            while (false != ($file = readdir($handle))){
                if (($file != ".") && ($file != "..")){
                    $values = array();
                    $get_txt = strtolower(file_get_contents($pasta.$file, true));
                    $get_txt = preg_replace('!\s+!', ' ', $get_txt);
                    $tok = strtok($get_txt," ");
                    while ($tok != false) {
                        $values[] = trim($tok," \t\n\r\0\x0B");
                        $tok = strtok(" ");
                    }
                    $values[] = $tok;
                    for($i=0; $i < sizeof($values); $i++){
                        $palavra = stdtxt($values[$i]);
                        $palavra = rem_pts($palavra);
                        if(!array_key_exists($palavra, $wordhash)){
                            $wordhash[$palavra] = array();
                        }
                        $wordhash[$palavra][] = $docs.":".$i;
                    }
                    $docs++;
                    if($docs % 100 == 0){
                        echo("ID: ".$docs."\n");
                    }
                }
            }
            echo("\n".$docs." DOCUMENTOS\n");
            unset($wordhash[""]);
            closedir($handle);
            file_put_contents("rnk-array.json", json_encode($wordhash));
            echo("Database [OK]\n");
        }
    }else{
        echo("FILE [LOADING]\n");
        $wordhash = json_decode(file_get_contents("rnk-array.json"),$assoc = true);
        echo("FILE [OK]\n");
    }
    $phrase = "temp";
    
    while($phrase != ""){
        $phrase = readline("[exit para sair] Digite a palavra buscada: \n");
        $tokens = array();
        $t = strtok(rem_pts($phrase)," ");
        while ($t != false){
            $tokens[] = trim($t," \t\n\r\0\x0B");
            $t = strtok(" ");
        }
    /////INICIO DA PARTE METODOLOGICA
        $exists = true;
        foreach($tokens as $t){
            if(!isset($wordhash[$t])){
                $exists = false;
                echo("O sistema não reconheceu o token [".$t."] na base de dados\n");
            }
        }
        if($exists){
            $result = prox_ranking($wordhash, $tokens, 4);
            echo("PROX. RNK RESULT:");
            print_r($result);
		}
    }

/////// FUNÇOES ////////////
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
    function docid($idx){
        if(strpos($idx,":") == false){
            $val = (0);
        }else{
            $val = substr($idx,0,strpos($idx,":"));
        }
        return $val;
    }
    function offset($idx){
        if(strpos($idx,":") == false){
            $val = (0);
        }else{
            $val = substr($idx,strpos($idx,":")+1);
        }
        return $val;
    }
    function nextCover($lista, $terms, $point){
        $keymw = 0;
        for($id = 0; $id<sizeof($terms); $id++){
            if (sizeof($lista[$keymw]) > sizeof($lista[$terms[$id]])){
                $keymw = $id;
            }
        }
        $mw = $terms[$keymw];
        foreach($lista[$mw] as $num){
            $flag = true;
            $find = array();
            if(docid($point) > docid($num)){
                $flag = false;
            }else{
                if(docid($point) == docid($num)){
                    if(offset($point) >= offset($num)){
                        $flag = false;
                    }
                }
            }
            for($i = 0; $i< sizeof($terms) && $flag == true; $i++){
                if($lista[$mw] == $lista[$terms[$i]]){
                    $find[] =  strval(docid($num)).":".strval(offset($num));
                }else{
                    foreach($lista[$terms[$i]] as $pos){
                        if(docid($pos) == docid($num)){
                            $find[] = strval(docid($pos)).":".strval(offset($pos));
                            if(sizeof($find) == sizeof($terms)){
                                return $find;
                            }
                        }else{
                            $flag = false;
                        }
                    }
                }
            }
        }
        return null;
    }
	function prox_ranking($lst, $tks, $nret){
        $nmaxval = array_fill(0, $nret, 0);
		$p = strval(0).":".strval(0);
		$respostas = array();
		$result = nextCover($lst, $tks, $p);
		while($result != null){
            $p = strval(docid($result[sizeof($result)-1])).":".strval(offset($result[sizeof($result)-1]));
            foreach($result as $local){
                if(offset($local) > offset($p)){
                    $p = strval(docid($local)).":".strval(offset($local));
                }
            }
            $respostas[] = $result;
            echo($p."\n");
			$result = nextCover($lst, $tks, $p);
		}
		$rnk = array_fill(0, 3000, 0);
		foreach($respostas as $res){
            $rnk[docid($res[0])] += getDiff($res);
        }
        $aux = array();
		for($i=0; $i < sizeof($rnk); $i++){
            if($rnk[$i]!=0){
                $aux[] = (1/(1+$rnk[$i]));
            }else{
                $aux[] = 0;
            }
        }
        for($j=0; $j < sizeof($nmaxval); $j++){
            $big = 0;
            for($i=0; $i < sizeof($aux); $i++){
                if($aux[$big]<$aux[$i]){
                    $big = $i;
                }
            }
            $nmaxval[$j] = "|".$big."-".$aux[$big]."|";
            $aux[$big]=0;
        }
        return $nmaxval;
	}
    function getDiff($resposta){
        $max = offset($resposta[0]);
        $min = offset($resposta[0]);
        foreach($resposta as $r){
            if(offset($r)>$max){
                $max = offset($r);
            }
            if(offset($r)<$min){
                $min = offset($r);
            }
        }
        return ($max - $min);
    }
?>
