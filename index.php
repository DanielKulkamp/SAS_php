<?php
//require_once 'simple_html_dom.php';

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

} else{
  $miner = new SiteCBFMiner( "https://www.cbf.com.br/futebol-brasileiro/competicoes/campeonato-brasileiro-serie-a/2023");        
  $titulo = "Brasileirão Série A";
}

$jsonMatchList = $miner->getGameList();
$badges = $miner->getBadges();
?>
<HTML lang='pt-br'>
  <head>
    <meta charset="utf8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html">
    <title>SAS - <?php echo $titulo ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type='text/Javascript'>let listOfMatches <?php echo "= " . $jsonMatchList . ";" ?>
    let badgesDictionary <?php echo "= " . $badges . ";" ?>
    
    </script>
    <script type='text/Javascript' src='./script.js'></script>
  </head>
  <body>
    <style>
      table {
        text-align: center;
        font-size: x-large;
      }
      .progress {
        height: 20px;
        margin-bottom: 20px;
        overflow: hidden;
        background-color: #f5f5f5;
        border-radius: 4px;
      }

      .progress-bar {
        float: left;
        width: 0%;
        height: 100%;
        font-size: 12px;
        line-height: 20px;
        color: #fff;
        text-align: center;
        background-color: #337ab7;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
      }
    </style>
    <h1>Simulador SAS - <?php echo $titulo ?></h1>
    <div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
</div>
    <span class="navbar">      
      <button id="btMatches">Lista de Jogos</button>
      <button id="btRatings">Ratings e Campanha</button>
      <button id="btNextMatches">Próximos jogos</button>
      <button id="btSummary">Resultado Simulação</button>
      <button id="btGraphs">Gráficos</button>
      <button id="btNewSim">Nova Simulação</button>
      <button id="btFAQ">Ajuda</button>
      <button id="btAdvanced">Avançado</button>
    </span>
    <div class="panel" id="divMatches" ></div>
    <div class="panel" id="divRatings" ></div>
    <div class="panel" id="divNextMatches" ></div>
    <div class="panel" id="divSummary"></div>
    <div class="panel" id="divGraphs">
      <h3>Selecione 1 ou mais times e clique em "Gerar gráfico":</h3>
        <select id="item-select" multiple></select><button id="plot-button">Gerar gráfico</button>
        <h2>Probabilidade por colocação final:</h2>
        <canvas id="histogram-chart"></canvas>
    </div>
    <div class="panel" id="divFAQ">
      <h2>Perguntas Frequentes</h2>
      
      <h3>Como usar o simulador</h3>
      <p>O uso mais simples é simplesmente abrir a página e aguardar os resultados. Após os resultados estiverem prontos, é possivel clicar no botão gráficos para visualizar as diferentes chances para cada equipe e e cada posição. Para isso, selecionar um ou mais times na lista e clicar em gerar gráfico.
        É possível também ver as probabilidades calculadas para os próximos jogos, e um resumo das campanhas e ratings de cada time.
      Há ainda uma forma de visualizar toda a lista de jogos do campeonato.</p>

      <h3>Pra que existe o botão "Editar" na lista de jogos</h3>
      <p>Caso o site da CBF, de onde os dados dos resultados ainda não esteja atualizado, você pode editar o jogo em questão, clicando em Editar, preenchendo os placares e depois clicando em salvar. Após isso é necessário clicar em "Nova Simulação". Também pode ser utilizado para especular como ficariam as chances em diferentes cenários. </p>

      <h3>Como funciona o simulador?</h3>
      <p>A partir dos resultados dos jogos já realizados, ele simula o resto do campeonato 100000 vezes e contabiliza em quantas dessas simulações cada time ficou em cada posição no campeonato. Isso é utilizado como estimativa para chances de título, G4, rebaixamento, etc.</p>

      <h3>Como o simulador leva em consideração a força de cada time?</h3>
      <p>Utilizando a metodologia similar ao Rating ELO, muito utilizada no xadrez e agora também utilizada no ranking da FIFA. No caso desse simulador, os ratings de todos os times começam iguais (1000.0). Todos a cada resultado, real ou simulado o rating dos times envolvidos é atualizado.  Ganhar de um time mais fraco aumenta pouco o rating, ganhar de um time mais forte aumenta bastante o rating. Um time fraco ganhando de um time forte aumenta bastante o rating do time fraco e abaixa bastante o rating do time forte. </p>

      <h3>Qual a diferença desse simulador pra outros como Chance De Gol e Infobola?</h3>
      <p>Resposta curta: Eu que fiz esse! <br>
      Resposta longa: A ideia em geral por trás de todos é a mesma, mas alguns parâmetros variam. Esse aqui leva em consideração o "momento" dos jogos simulados. Outros simuladores podem congelar os parâmetros de força dos times apenas com resultados reais. Mas no fim das contas todos apresentam resultados coerentes entre si. </p>

      <h3>Um resultado de 100.0% de acesso/título/rebaixamento significa garantia de acesso no mundo real?</h3>
      <p>Não necessariamente. Significa apenas que em 100000 simulações em todas elas o resultado foi aquele. O que é indica que muito provavelmente na vida real a chance seja de 100% Da mesma forma um resultado de 0.0% não significa necessariamente que seja impossível. Mas a gente vai saber quando for 0 de fato ou 100 de fato, então não tem relevância em fazer as contas todas para verificar isso exatamente.</p>

      <h3>Tem diferença para jogos em casa e fora?</h3>
      <p>Sim. Um bônus no rating do time da casa é aplicado antes de calcular as probabilidades de vitória empate e derrota.</p>

      <h3>Tem como usar esse simulador para outros campeonatos?</h3>
      Sim. No momento ele funciona para série A e série B do campeonato brasileiro. Basta adicionar "?camp=SERIE-B" na url para acessar a versão da Série B.
      Está em desenvolvimento simulação para Premier League e Bundesliga. Breve!
    </div>
  </body>
</html>

        