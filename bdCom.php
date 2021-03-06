<?php

setlocale(LC_ALL, 'pt_BR.UTF8');
include '../functions.inc2.php';
include 'valSes.php';
$puxaBD = new Crud();
$puxaBD->conn();
$puxaBD->setCharSet();
//setVar bancos
$file = file_get_contents("bd.cfg");
$contents = utf8_encode($file);
$bdinfo = json_decode($contents, true);

$db = $defaultdb = $bdinfo["default_db"];
$qExperimental = $bdinfo["questionarioExperimental"];
$qResumido = $bdinfo["qpjbr"];

$action = (string) filter_input(INPUT_GET, 'action');
$grupoid = filter_input(INPUT_GET, 'grupoid'); //codigo do grupo de pesquisa
$codgrp = (string) filter_input(INPUT_GET, 'codgrp'); //codigo do grupoExperimental
$sigla = (string) filter_input(INPUT_GET, 'sigla');
$sigla = (string) filter_input(INPUT_GET, 'senha');
//$usuario =(string)filter_input(INPUT_GET, 'user');





if (isset($_GET["db"])) {
    $banco = (string) filter_input(INPUT_GET, 'db');
    if ($banco === "qpjbr") {
        $db = $qResumido;
    } else if ($banco == "experimental") {
        $db = $qExperimental;
    } else {
        $db = "ambos";
    }
} else {
    $db = "ambos";
}


if ($logado) {
    $grupoid = $usuario["idUser"];
    $sigla = $usuario["Sigla"];
}



$submitCadastrar = filter_input(INPUT_POST, 'submitCadastrar');
$submitExperimental = filter_input(INPUT_POST, 'submitExperimental');
$submitLogin = filter_input(INPUT_POST, 'submitLogin');
$submitEmail = filter_input(INPUT_POST, 'submitEmail');
//   $retorno ="";
/*  foreach ($_GET as $index => $a) {
  $retorno +=  "  $index  $a  ";
  }
  echo "$action  $retorno"    ;
  exit(); */
/*   if($submitCadastrar) {
  echo "funciona";
  exit();

  }else {echo
  "nop";
  exit();
  } */





if ($action == "testeBD") {
    echo "Iniciando teste <br>";
    echo "<br>";
    echo "db = " . $db;
    echo "<br>";
    echo "qExperimental = " . $qExperimental;
    echo "<br>";
    echo "Codgrp =  $codgrp";
    echo "<br>";
    echo "logado = $logado";
    echo "<br>";
    echo "qResumido $qResumido";
    echo "<br>";
    echo "grupoid = " . $grupoid;
    echo "<br>";
    echo "sigla = " . $sigla;
}


if (($action === "Login") || ($submitLogin)) {
    $puxaBD->selectDB($defaultdb);
    $usuario = utf8_decode((string) filter_input(INPUT_POST, 'usuario'));
    $senha = (string) filter_input(INPUT_POST, 'senha');


    //        $emailResponsavel = utf8_decode((string)filter_input(INPUT_GET, 'emailResponsavel'));
    //        $senha =(string)filter_input(INPUT_GET, 'senha');

    $string = "Select GP.* from grupo_pesquisa GP WHERE GP.Sigla = '$usuario' "
            . "AND  GP.Senha = '" . $senha . "' order by GP.ID Desc";
    $res = $puxaBD->selectCustomQuery($string);
    //

    /*    //                Debug
      echo " Debugando as variaveis <br><br><br>";
      echo $emailResponsavel ."<br>";
      echo $senha."<br>";
      echo $string."<br>";
      $resultado = $res->fetch_assoc();
      echo $jason = json_encode($resultado).
      "<br><br><br> Fim do Debug <br><br><br>";
     */
    $row_cnt = $res->num_rows;

    if ($row_cnt > 0) {
        $resultado = $res->fetch_assoc();

        /* $jason = json_encode($resultado);
          echo $jason; */
        //  session_start();
        $num = rand(10000, 1000000);
        $_SESSION["idUser"] = $resultado["ID"];
        $_SESSION["emailResp"] = $resultado["Email_resp"];
        $_SESSION["nomeGrp"] = $resultado["Nome_Grupo"];
        $_SESSION["Sigla"] = $resultado["Sigla"];
        $_SESSION["numLogin"] = $num;
        echo "./index.php?ses=" . $num;
    } else {
        echo "usuário ou senha incorretos";
    }
}

if (($action === "confereEmail") || ($submitEmail)) {
    $puxaBD->selectDB($defaultdb);
    $email = utf8_decode((string) filter_input(INPUT_POST, 'email'));
    $sigla = utf8_decode((string) filter_input(INPUT_POST, 'sigla'));
    $string = "Select GP.* from grupo_pesquisa GP WHERE GP.Sigla = '$sigla' "
            . "AND  GP.Email_grupo = '" . $email . "' order by GP.ID Desc";
    $res = $puxaBD->selectCustomQuery($string);
    $row_cnt = $res->num_rows;

    if ($row_cnt > 0) {
        $row = $res->fetch_assoc();
        if (enviarLembrete($row)) {
            echo "existe";
        }
    } else {
        echo 'email não encontrado';
    }
}




if ($action === "verificaSigla") {
    $puxaBD->selectDB($defaultdb);
    $verifica = (string) filter_input(INPUT_GET, 'verifica');
    $string = "Select Sigla from grupo_pesquisa where Sigla = '$verifica'";
    $res = $puxaBD->selectCustomQuery($string);

    $row_cnt = $res->num_rows;

    if ($row_cnt > 0) {
        echo 'existe';
    } else {
        echo 'ok';
    }
}


if ($action === "GruposExperimentais") {
    $puxaBD->selectDB($defaultdb);
    /* $string = "(Select GE.*
      from grupo_experimental GE
      where GE.grupo_pesquisa_ID = ".$grupoid." "
      .   "order by GE.ID DESC)"; */
    $string = "SELECT `ID` as 'id', `Grupo_Pesquisa_ID` as 'grupoId', `Codigo_G_Exp` as 'codgrupo', "
            . "`Descricao` as 'descricao', `Populacao` as 'populacao', `DATETIME` as 'datadaentrada' "
            . "FROM `grupo_experimental` WHERE Grupo_Pesquisa_ID =$grupoid";
    $res = $puxaBD->selectCustomQuery($string);

    //echo "<br> String: ".$string . "<br><br> row count: ";
    $row_cnt = $res->num_rows;


    if ($row_cnt > 0) {
        if (method_exists($res, 'fetch_all')) {
            $resultado = $res->fetch_all(MYSQLI_ASSOC);
        } else {

            $resultado = fetchAll($res);
        }
        $jason = json_encode($resultado);

        echo $jason;
    } else {
        echo $jason = null;
    }
}



if (($action === "exportar") && ($logado)) {
    //$puxaBD->selectDB($db);
    if ($db === "ambos") {
        $puxaBD->selectDB($qResumido);


        $string = " ( Select r.Id as 'id', r.Codigo_G_Exp as 'grupo', r.nome as 'nome', r.idade as 'idade', r.email as 'email', r.genero, r.escolaridade, s.achiever as 'realizacao', s.relatedness as 'social', s.imersao, s.majoritario, "
                . " s.mecanica, s.competicao, s.avanco, s.relacionamento, s.trabalhoemequipe, s.roleplaying, s.customizacao, s.descoberta, s.escapismo,s.imersao, s.socializacao, "
                . " r.DATETIME as 'dataentrada', "
                . " 'Experimental' as 'TipoQuestionario' "
                . " from $qExperimental.soma s inner join $qExperimental.resposta r on s.idresposta = r.id "
                . " where r.codigo_g_exp = '" . $codgrp . "'"
                . " order by r.DATETIME DESC"
                . " ) UNION ( "
                . " Select r.Id as 'id', r.Codigo_G_Exp as 'grupo', r.nome as 'nome', r.idade as 'idade', r.email as 'email', r.genero, r.escolaridade, s.achiever as 'realizacao', s.relatedness as 'social', s.imersao, s.majoritario,"
                . " s.mecanica, s.competicao, s.avanco, s.relacionamento, s.trabalhoemequipe, s.roleplaying, s.customizacao, s.descoberta, s.escapismo,s.imersao, s.socializacao, "
                . " r.DATETIME as 'dataentrada', "
                . " 'QPJBR' as 'TipoQuestionario' "
                . " from " . $qResumido . ".soma s inner join " . $qResumido . ".resposta r on s.idresposta = r.id "
                . " where r.codigo_g_exp = '" . $codgrp . "'"
                . " order by r.DATETIME DESC "
                . " ) ";
    } else {
        $puxaBD->selectDB($db);

        $string = " Select r.Id as 'id', r.Codigo_G_Exp as 'grupo', r.nome as 'nome', r.idade as 'idade', r.email as 'email', r.genero, r.escolaridade, s.achiever as 'realizacao', s.relatedness as 'social', s.imersao, s.majoritario, "
                . " s.mecanica, s.competicao, s.avanco, s.relacionamento, s.trabalhoemequipe, s.roleplaying, s.customizacao, s.descoberta, s.escapismo,s.imersao, s.socializacao,"
                . " r.DATETIME as 'dataentrada'"
                . " from soma s inner join resposta r on s.idresposta = r.id"
                . " where r.codigo_g_exp = '" . $codgrp . "'"                
                . " order by r.DATETIME ASC";
    }

    $res = $puxaBD->selectCustomQuery($string);
    //echo $string;
    if ($res->num_rows > 0) {

        if (method_exists($res, 'fetch_all')) {
            $resultado = $res->fetch_all(MYSQLI_ASSOC);
        } else {
            $resultado = fetchAll($res);
        }
        $jason = json_encode($resultado);

        echo $jason;
    } else {
        echo $jason = null;
    }
}


if (($action == "inserir_novo_grupo") || ($submitCadastrar)) {
    $puxaBD->selectDB($defaultdb);
    $nomeGrupo = utf8_decode((string) filter_input(INPUT_POST, 'nomeGrupo'));
    $sigla = utf8_decode((string) filter_input(INPUT_POST, 'sigla'));
    $afiliacao = utf8_decode((string) filter_input(INPUT_POST, 'afiliacao'));
    $emailGrupo = utf8_decode((string) filter_input(INPUT_POST, 'emailGrupo'));
    $responsavel = utf8_decode((string) filter_input(INPUT_POST, 'responsavel'));
    $emailResponsavel = utf8_decode((string) filter_input(INPUT_POST, 'emailResponsavel'));
    $senha = utf8_decode((string) filter_input(INPUT_POST, 'senha'));
    $contato = utf8_decode((string) filter_input(INPUT_POST, 'contato'));
    $descricao = utf8_decode((string) filter_input(INPUT_POST, 'descricao'));

    $string = "INSERT INTO "
            . "`grupo_pesquisa`(`Nome_Grupo`, `Sigla`, `Email_grupo`, `Responsavel`, `Senha`, `Email_resp`, `Contato`, `Descricao`, `Afiliacao`) "
            . " VALUES "
            . "( '$nomeGrupo','$sigla','$emailGrupo','$responsavel','$senha','$emailResponsavel','$contato','$descricao','$afiliacao')";
    $query1 = $puxaBD->selectCustomQuery($string);

    //echo $string;
    if ($puxaBD->getAffectedRows() > 0) {
        echo ("Novo grupo inserido com sucesso.");
    } else {
        echo ("Nao foi possivel inserir o registro");
    }
}

if (($action == "inserir_novo_experimental") || ($submitExperimental)) {
    $puxaBD->selectDB($defaultdb);

    $string = "(Select GE.ID, GP.sigla "
            . " from grupo_experimental GE Join grupo_pesquisa GP "
            . "   on  GE.Grupo_Pesquisa_ID = GP.ID "
            . " where GE.Grupo_Pesquisa_ID = " . $grupoid . " "
            . " order by GE.ID DESC)";

    $res = $puxaBD->selectCustomQuery($string);
    $row = $res->fetch_assoc();
    $newid = $row["ID"] + 1;
    $novocodgrp = $sigla . $newid;


    //$idGrupoPesquisa =(string)filter_input(INPUT_POST, 'id_grupo_pesquisa');            
    $descricao = (string) filter_input(INPUT_POST, 'descricao');
    $populacao = (string) filter_input(INPUT_POST, 'populacao');
    $string = "INSERT INTO "
            . " `grupo_experimental`(`Grupo_Pesquisa_ID`, `Codigo_G_Exp`, `Descricao`, `Populacao`)"
            . " VALUES "
            . "( '$grupoid','$novocodgrp','$descricao','$populacao"
            . " ')";
    //     echo $string;
    $query1 = $puxaBD->selectCustomQuery($string);
    if ($puxaBD->getAffectedRows() > 0) {
        echo ("Novo grupo inserido com sucesso.");
    } else {
        echo ("Nao foi possivel inserir o registro");
    }
}




$puxaBD->close();

function inserir_novo_experimental() {
    
}

function inserir_novo_grupo() {
    
}

function login() {
    
}

function exportar() {
    
}

function retornar() {
    
}

function enviarLembrete($usuario) {
    $message = "Estamos enviando o lembrete de senha de acesso ao gerenciador de grupos para o grupo:\r\n " .
            $usuario['Sigla'] . " \r\n  senha de acesso = " . $usuario['senha'];
    return mail($usuario["Email_grupo"], "Lembrete de Email!", $message, null, 'no-reply@caed-lab.com');
}

?>
