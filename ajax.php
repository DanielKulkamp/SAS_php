<?php
//require_once 'simple_html_dom.php';

header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

function sendSSE($progress, $result) {
  $data["progress"] = $progress;
  $data["result"] = $result;
  echo "data: " . json_encode($data) . "\n\n";
  ob_flush();
  flush();
}

class Game {
  public $homeTeam;
  public $awayTeam;
  public $homeScore;
  public $awayScore;
  public $done;
  
  public function __construct($timeCasa, $timeFora, $golsCasa = "", $golsFora = "") {
    $this->homeTeam = $timeCasa;
    $this->awayTeam = $timeFora;
    $this->homeScore = $golsCasa;
    $this->awayScore = $golsFora;
    if ($golsCasa == "" || $golsFora == "") {
      $this->done = false;
    } else {
      $this->done = true;
    }
  }
}

class SiteCBFMiner {
  
  private $gameList;
  private $url;
  private $badges;
  
  public function __construct($url){
    $this->gameList = array();
    $this->badges = array();
    $this->url = $url;
    try {
      ini_set('memory_limit', '256M');
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $doc = curl_exec($ch);
      curl_close($ch);
      
      
      
      $doc = preg_replace('/\r*\n\r*/m', "", $doc); 
      
      /*
      <div class="time pull-left"><span class="time-sigla">AME</span> <img src="https://conteudo.cbf.com.br/cdn/imagens/escudos/59897mg.jpg?v=2023041515" title="América Fc Saf - MG" alt="América Fc Saf - MG" onerror="this.src='https://conteudo.cbf.com.br/cdn/imagens/escudos/empty.jpg'" class="icon escudo x45 pull-right"></div>
      */
      $reHomeTeams = "/time pull-left\"[^\"]*\"time-sigla\"[^\"]*img src=\"([^\?]*)[^\"]*\"[^\"]*\"([^\"]*)/m";
      //$reAwayTeams = '/time pull-right\"[^\"]*\"time-sigla\"[^\"]*img src=\"([^\?]*)[^\"]*\"[^\"]*\"([^\"]*)/m';
      $reAwayTeams = '/time pull-right"[^"]*"time-sigla"[^"]*img src="([^?]*)[^"]*"[^"]*"([^"]*)/m';
      //$reAwayTeams = '/time pull-right"[^"]*\n*[^"]"time-sigla".*\n*[^"]*"([^"]*)[^"]"[^"]*"([^"]*)/m';
      //<strong class="partida-horario center-block">        18:30        </strong>
      $reHorarioPlacar = "/strong class=\"partida-horario center-block\">(.*?)<\/strong>/m";
      $contagemHome = preg_match_all($reHomeTeams, $doc, $badgesAndNamesHome, PREG_SET_ORDER, 0);
      $contagemAway = preg_match_all($reAwayTeams, $doc, $badgesAndNamesAway, PREG_SET_ORDER, 0);
      $contagemPlacar = preg_match_all($reHorarioPlacar, $doc, $placarHorario, PREG_SET_ORDER, 0);
      
      for ($i = 0; $i < $contagemPlacar; ++$i){
        sendSSE(100*$i/$contagemPlacar,"");
        $re = '/(\d+)\s*x\s*(\d+)/m';
        $homeScore = "";
        $awayScore = "";
        $done = false;
        if (preg_match_all($re, $placarHorario[$i][1], $matches, PREG_SET_ORDER, 0)){
          $homeScore = $matches[0][1];
          $awayScore = $matches[0][2];
          $this->badges[$badgesAndNamesHome[$i][2]] = $badgesAndNamesHome[$i][1];
          $this->badges[$badgesAndNamesAway[$i][2]] = $badgesAndNamesAway[$i][1];
          array_push($this->gameList, new Game($badgesAndNamesHome[$i][2], $badgesAndNamesAway[$i][2], $homeScore, $awayScore));
        } else {
          array_push($this->gameList, new Game($badgesAndNamesHome[$i][2], $badgesAndNamesAway[$i][2]));
        }
      }
      
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
  }
  
  public function getBadges() {
    return json_encode($this->badges);
  }
  
  public function getGameList() {      
    return json_encode($this->gameList);      
  }
  
  public function getCombo(){
    $combo = array();
    array_push($combo, $this->gameList);
    array_push($combo, $this->badges);
    sendSSE(100,json_encode($combo));
  }
}



class BundesligaMiner {
  
  private $gameList;
  private $baseURL;
  private $badges;
  private $url;
  
  public function __construct(){
    $this->gameList = array();
    $this->badges = array();
    $this->baseURL = "https://www.bundesliga.com/en/bundesliga/matchday/2022-2023/";
    
    try {
      ini_set('memory_limit', '256M');
      
      for($rodada = 1; $rodada <35; $rodada++){
        sendSSE(100*$i/36,"");
        $url = $this->baseURL . $rodada; 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $doc = curl_exec($ch);
        $doc = preg_replace('/\r*\n\r*/m', "", $doc); 
        curl_close($ch);
        $reHomeTeams = '/side="home".*?class="name.*?">([^<]*?)<\/div>.*?src="(https:\/\/img\.bundesliga[^"]*?)"/m';
        $reAwayTeams = '/side="away".*?class="name.*?">([^<]*?)<\/div>.*?src="(https:\/\/img\.bundesliga[^"]*?)"/m';
        $rePlacar = '/class="score[^"]*">\s?(\d?)\s?<\/div>.*?class="score[^"]*">\s?(\d?)\s?/m';
        $contagemHome = preg_match_all($reHomeTeams, $doc, $badgesAndNamesHome, PREG_SET_ORDER, 0);
        $contagemAway = preg_match_all($reAwayTeams, $doc, $badgesAndNamesAway, PREG_SET_ORDER, 0);
        $contagemPlacar = preg_match_all($rePlacar, $doc, $placar, PREG_SET_ORDER, 0);
        for ($i = 0 ; $i < $contagemHome; $i++){
          $this->badges[$badgesAndNamesAway[$i][1]] = $badgesAndNamesAway[$i][2];
          $this->badges[$badgesAndNamesHome[$i][1]] = $badgesAndNamesHome[$i][2];
          array_push($this->gameList, new Game($badgesAndNamesHome[$i][1], $badgesAndNamesAway[$i][1], $placar[$i][1], $placar[$i][2]));
        }
        
      }
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
  }
  
  public function getBadges() {
    return json_encode($this->badges);
  }
  
  public function getGameList() {      
    return json_encode($this->gameList);      
  }
}

if (isset($_GET['camp'])) {
  switch($_GET['camp']){
    case "SERIE-B":
      $miner = new SiteCBFMiner( "https://www.cbf.com.br/futebol-brasileiro/competicoes/campeonato-brasileiro-serie-b/2023");
      $titulo = "Brasileirão Série B";
      break;
      case "BUNDESLIGA":
        $miner = new BundesligaMiner();
        $titulo = "BUNDESLIGA";
        break;
        default:
        $miner = new SiteCBFMiner( "https://www.cbf.com.br/futebol-brasileiro/competicoes/campeonato-brasileiro-serie-a/2023");        
        $titulo = "Brasileirão Série A";
      }
      
    } else {
      $miner = new SiteCBFMiner( "https://www.cbf.com.br/futebol-brasileiro/competicoes/campeonato-brasileiro-serie-a/2023");        
      $titulo = "Brasileirão Série A";
    }
    

    
    $jsonMatchList = $miner->getGameList();
    $badges = $miner->getBadges();
    $miner->getCombo();
?>