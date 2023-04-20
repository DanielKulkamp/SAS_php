const DIVISOR_ELO = 400.0;
const IMPORTANCE = 30.0;
const N_SIMS = 100_000;
const HFA = 40;
const PCT_VITORIA_SALDO_5 = 0.01;
const PCT_VITORIA_SALDO_4 = 0.06;
const PCT_VITORIA_SALDO_3 = 0.17;
const PCT_VITORIA_SALDO_2 = 0.45;
const PCT_EMPATE_0 = 0.35;
const PCT_EMPATE_1 = 0.89;
const PCT_EMPATE_2 = 0.99;
const PCT_DERROTA_1 = 0.80;
const PCT_DERROTA_2 = 0.99;

var chart;

class Summary {
    constructor(nome) {
      this.nome = nome;
      this.titulos = 0;
      this.g4s = 0;
      this.z4s = 0;
      this.histograma = new Array(20).fill(0);
    }
    compareTo(other) {
      if (this.titulos > other.titulos) return -1;
      if (this.titulos < other.titulos) return 1;
      if (this.g4s > other.g4s) return -1;
      if (this.g4s < other.g4s) return 1;
      if (this.z4s > other.z4s) return 1;
      if (this.z4s < other.z4s) return -1;
      return 0;
    }
  }

class Expectancy {
  constructor(casa, fora) {
    const deltaRating = casa.rating + HFA - fora.rating;
    const divisor = 1 + Math.pow(10, -deltaRating / DIVISOR_ELO) + Math.pow(10, deltaRating / DIVISOR_ELO);
    this.win = Math.pow(10, deltaRating / DIVISOR_ELO) / divisor;
    this.draw = 1 / divisor;
    this.loss = 1 - this.win - this.draw;
  }
}

class Team {
  static INITIAL_RATING = 1000.0;
  points = 0;
  wins = 0;
  rating = Team.INITIAL_RATING;
  goalsFor = 0;
  goalsAgainst = 0;
  goalDiff = 0;
  name = "";
  badge = "";
  
  constructor(name, badge) {
    this.name = name;
    this.badge = badge;
    this.wins = 0;
    this.points = 0;
    this.rating = Team.INITIAL_RATING;
    this.goalsAgainst = 0;
    this.goalsFor = 0;
    this.goalDiff = 0;
  } 
  
  compareTo(b) {
    if (this.points > b.points) return -1;
    if (this.points < b.points) return 1;
    if (this.wins > b.wins) return -1;
    if (this.wins < b.wins) return 1;
    if (this.goalDiff > b.goalDiff) return -1;
    if (this.goalDiff < b.goalDiff) return 1;
    if (this.goalsFor > b.goalsFor) return -1;
    if (this.goalsFor < b.goalsFor) return 1;
    return 0; 
  }
}

function computeMatch(homeTeam, awayTeam, homeScore, awayScore) {
  let homePoints = 0;
  let awayPoints = 0; 
  let homeWins = 0;
  let awayWins = 0;
  let result = 0.5;
  
  if (homeScore > awayScore) {
    homePoints = 3;
    homeWins = 1;
    result = 1;
  } else if (homeScore == awayScore) {
    homePoints = 1;
    awayPoints = 1;
  } else {
    awayPoints = 3;
    awayWins = 1;
    result = 0;
  }

  const goalDiff = Math.abs(homeScore - awayScore);
  let factor = 1;
  if (goalDiff == 2) factor = 1.5;
  if (goalDiff == 3) factor = 1.75;
  if (goalDiff > 3) factor = (1.75 + ((goalDiff - 3.0) / 8.0));

  homeTeam.goalsFor += homeScore;
  homeTeam.goalsAgainst += awayScore;
  homeTeam.goalDiff = homeTeam.goalsFor - homeTeam.goalsAgainst;
  homeTeam.points += homePoints;
  homeTeam.wins += homeWins;

  awayTeam.goalsFor += awayScore;
  awayTeam.goalsAgainst += homeScore;
  awayTeam.goalDiff = awayTeam.goalsFor - awayTeam.goalsAgainst;
  awayTeam.points += awayPoints;
  awayTeam.wins += awayWins;
  
  const deltaRating = homeTeam.rating - awayTeam.rating +HFA;
  const winExpectancy = (1 / (1 + Math.pow(10, -(deltaRating) / DIVISOR_ELO)));
  const adjust = IMPORTANCE * factor * (result - winExpectancy);
  homeTeam.rating += adjust;
  awayTeam.rating -= adjust;

}

function simulateMatchELOHFA(casa, fora) {
  const exp = new Expectancy(casa, fora);
  const winExpectancy = exp.win;
  const drawExpectancy = exp.draw;
  const alea = Math.random();
  const alea2 = Math.random();
  let saldo = 0;
  let minGols = 0;
  
  if (alea < winExpectancy * PCT_VITORIA_SALDO_5) {
    saldo = 5;
    minGols = 0;
  } else if (alea < winExpectancy * PCT_VITORIA_SALDO_4) {
    saldo = 4;
    if (alea2 < 0.9) 
      minGols = 1;
  } else if (alea < winExpectancy * PCT_VITORIA_SALDO_3) {
    saldo = 3;
    
    if ((alea2 > 0.7) && (alea2 < 0.95)) {
      minGols = 1;
    }
    if (alea2 > 0.95) {
      minGols = 2;
    }
  } else if (alea < winExpectancy * PCT_VITORIA_SALDO_2) {
    saldo = 2;
    if ((alea2 > 0.70) && (alea2 < 0.95)) {
      minGols = 1;
    }
    if (alea2 > 0.95) {
      minGols = 2;
    }

  } else if (alea < winExpectancy) {
    saldo = 1;
    if ((alea2 > 0.60) && (alea2 < 0.95)) {
      minGols = 1;
    }
    if (alea2 > 0.95) {
      minGols = 2;
    }
    
  } else if (alea - winExpectancy < drawExpectancy) {
      saldo = 0;
      if ((alea2 > 0.60) && (alea2 < 0.95)) {
        minGols = 1;
      }
      if (alea2 > 0.95) {
        minGols = 2;
      }

  } else {
    if (Math.random() < PCT_DERROTA_1) {
      saldo = -1;
      if ((alea2 > 0.60) && (alea2 < 0.95)) {
        minGols = 1;
      }
      if (alea2 > 0.95) {
        minGols = 2;
      }            
    } else {
          saldo = -2;
          if ((alea2 > 0.60) && (alea2 < 0.95)) {
            minGols = 2;
          }
          if (alea2 > 0.95) {
            minGols =3 ;
          }
    }
  }
  computeMatch(casa, fora, minGols + saldo, minGols);
}

function showPanel(id){
  let panels = document.getElementsByClassName("panel");
  for (let index = 0; index < panels.length; index++) {
    const element = panels[index];
    if (element.id == id){
      element.style.display = "block";
    } else {
      element.style.display = "none";
    }          
  }
}

function displayRatings(ratings){
  let rows = [];
  for (let i = 0; i < ratings.length; i++){
    let t = ratings[i];
    let badge = badgesDictionary[t.name];
    rows.push(`<tr><td>${i+1}</td><td><img height='40' width='40' src='${badge}' alt='${t.name}'></img</td><td>${t.rating.toFixed(2)}</td><td>${t.points}</td><td>${t.wins}</td><td>${t.goalDiff}</td><td>${t.goalsFor}</td></tr>`);
  }
  let table = "<h2>Ratings e Campanha</h2><table border='1'><tr><th>#</th><th>Time</th><th>Rating</th><th>PG</th><th>Vitórias</th><th>Saldo</th><th>Gols pró</th></tr>" + rows.join("") + "</table>";
  document.getElementById("divRatings").innerHTML = table;
  showPanel("divRatings");
}


function displayListOfMatches(listOfMatches) {
  let table = "<h2>Lista completa de jogos</h2><table border='1'><tr><th>#</th><th>Mandante</th><th></th><th>x</th><th></th><th>Visitante</th></tr>";
  listOfMatches.forEach((game, i) => {
    let homeBadge = badgesDictionary[game.homeTeam];
    let awayBadge = badgesDictionary[game.awayTeam];
    table += `<tr>
        <td>${i}</td>
        <td><img height='40' width='40' src='${homeBadge}' title='${game.homeTeam}'</img></td>
        <td id="homeScore_${i}">${game.homeScore}</td>
        <td>X</td>
        <td id="homeScore_${i}">${game.awayScore}</td>
        <td><img height='40' width='40' src='${awayBadge}' title='${game.awayTeam}'</img>
        <td><button id="editButton_${i}">Editar</button></td>
    </tr>`;
  });
  table += "</table>";
  document.getElementById("divMatches").innerHTML = table;
  showPanel("divMatches");
}

function displayNextMatches(homeTeams, awayTeams, expectancies){
  rows = homeTeams.map((homeTeam, i) => {return `
    <tr>
      <td><img height='40' width='40' src='${badgesDictionary[homeTeam.name]}' title='${homeTeam.name}'></img></td>
      <td>${homeTeam.rating.toFixed(2)}</td>
      <td>${(100*expectancies[i].win).toFixed(2)}%</td>
      <td>${(100*expectancies[i].draw).toFixed(2)}%</td>
      <td>${(100*expectancies[i].loss).toFixed(2)}%</td>
      <td>${awayTeams[i].rating.toFixed(2)}</td>
      <td><img height='40' width='40' src='${badgesDictionary[awayTeams[i].name]}' title='${awayTeams[i].name}'></img></td>
    </tr>
  `});

  let table = `<h2>Probabilidades nos Próximos Jogos</h2><table border='1'>
  <tr>
      <th>Mandante</th>
      <th>Rating</th>
      <th>Vitória Mandante</th>
      <th>Empate</th>
      <th>Vitória visitante</th>
      <th>Rating</th>
      <th>Visitante</th>
  </tr>${rows.join("")}</table>`;
  
  document.getElementById("divNextMatches").innerHTML = table;
  showPanel("divNextMatches");
  

}

function computePastMatches(alistOfMatches) {
    const pastMatches = alistOfMatches.filter(match => match.done);
    
    let realCampaign = new Map();
    for (let aMatch of pastMatches) {
        if (!realCampaign.has(aMatch.homeTeam)) {
        realCampaign.set(aMatch.homeTeam, new Team(aMatch.homeTeam, aMatch.homeBadge));
        }
        if (!realCampaign.has(aMatch.awayTeam)) {
        realCampaign.set(aMatch.awayTeam, new Team(aMatch.awayTeam, aMatch.awayBadge));
        }
        let tCasa = realCampaign.get(aMatch.homeTeam);
        let tFora = realCampaign.get(aMatch.awayTeam);
        computeMatch(tCasa, tFora, aMatch.homeScore, aMatch.awayScore);
    }

    const ranking = [];
    for ([name, team] of realCampaign) {
        ranking.push(team);
    }
    ranking.sort((a, b) => b.rating - a.rating);
    return [ranking, realCampaign];
}

function calculateNextExpectancies(upcomingMatches, realCampaign){
    const mandantes = [];
    const visitantes = [];
    const expectancies = [];
    
    for (let i = 0; i < Math.min(10, upcomingMatches.length); i++) {
        let casa = realCampaign.get(upcomingMatches[i].homeTeam);
        if (casa === undefined) {
        casa = new Team(upcomingMatches[i].homeTeam);
        }
        let fora = realCampaign.get(upcomingMatches[i].awayTeam);
        if (fora === undefined) {
        fora = new Team(upcomingMatches[i].awayTeam);
        }
        const exp = new Expectancy(casa, fora);
        mandantes.push(casa);
        visitantes.push(fora);
        expectancies.push(exp);
    }
    return [ mandantes, visitantes, expectancies];
}

function displaySummary(summary){
    let table = `<h2>Resumo da Simulação</h2>
                    <table border='1'>
                    <tr><th>#</th><th>Time</th>
                    <th>Título</th>
                    <th>G4</th>
                    <th>G6</th>
                    <th>G7-12</th>
                    <th>G13-16</th>
                    <th>Z4</th>
                    </tr>`;
    summary.forEach((stats, i) => {
        
        table += `<tr><td>${i+1}</td>
                    <td><img height='40' width='40' src='${badgesDictionary[stats.nome]}' title='${stats.nome}'</img></td>
                    <td>${(100*stats.titulos/N_SIMS).toFixed(2)}</td>
                    <td>${(100*stats.g4s/N_SIMS).toFixed(2)}</td>
                    <td>${(100*stats.histograma.slice(0,6).reduce((a,b) => a+b)/N_SIMS).toFixed(2)}</td>
                    <td>${(100*stats.histograma.slice(6,12).reduce((a,b) => a+b)/N_SIMS).toFixed(2)}</td>
                    <td>${(100*stats.histograma.slice(12,16).reduce((a,b) => a+b)/N_SIMS).toFixed(2)}</td>
                    <td>${(100*stats.z4s/N_SIMS).toFixed(2)}</td>
                </tr>`;
    });
    table += "</table>";
    table += `<select id="item-select" multiple></select><button id="plot-button">Gerar gráfico</button>

	          <canvas id="histogram-chart"></canvas>`;
    document.getElementById("divSummary").innerHTML = table;
    showPanel("divSummary");

    // Get a reference to the select element and the button element
    var itemSelect = document.getElementById("item-select");
    var plotButton = document.getElementById("plot-button");

    // Populate the select element with the names of the items in the array
    for (var i = 0; i < summary.length; i++) {
        var option = document.createElement("option");
        option.text = summary[i].nome;
        itemSelect.add(option);
    }

    // Add an event listener to the button element
    plotButton.addEventListener("click", function() {
        // Find the selected items in the array
        var selectedItems = [];
        var selectedOptions = Array.from(itemSelect.selectedOptions);
        for (var i = 0; i < selectedOptions.length; i++) {
            var selectedItem = summary.find(function(item) {
                return item.nome === selectedOptions[i].value;
            });
            if (selectedItem) {
                selectedItems.push(selectedItem);
            }
        }

        var colorPalette = [    "#a6cee3",    "#1f78b4",    "#b2df8a",    "#33a02c",    "#fb9a99",    "#e31a1c",    "#fdbf6f",    "#ff7f00",    "#cab2d6",    "#6a3d9a",    "#ffff99",    "#b15928",    "#8dd3c7",    "#ffffb3",    "#bebada",    "#fb8072",    "#80b1d3",    "#fdb462",    "#fccde5",    "#d9d9d9"];
        // Update the chart data object
        var chartData = {
            labels: selectedItems[0].histograma.map(function(_, index) {
                return index + 1;
            }),
            datasets: selectedItems.map(function(item, index) {
                return {
                    label: item.nome,
                    backgroundColor: colorPalette[index % colorPalette.length],
                    borderColor: colorPalette[index % colorPalette.length],
                    borderWidth: 1,
                    data: item.histograma.map(a => 100.0*a/N_SIMS)
                };
            })
        };

        // Update the chart options object
        var chartOptions = {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        };

        // Destroy any existing chart objects
        if (chart !== undefined) {
            chart.destroy();
        }

        // Create the chart object
        var histogramCanvas = document.getElementById("histogram-chart");
        chart = new Chart(histogramCanvas, {
            type: 'bar',
            data: chartData,
            options: chartOptions
        });
    });
}
 
//Main Function of simulation
const runSimulation = alistOfMatches => {
    let [ranking, realCampaign] = computePastMatches(alistOfMatches);
    displayRatings(ranking);

    const upcomingMatches = alistOfMatches.filter(match => !match.done);
    let [ mandantes, visitantes, expectancies] = calculateNextExpectancies(upcomingMatches, realCampaign);
    displayNextMatches(mandantes, visitantes, expectancies);

    let stats = new Map();
    for (let t of Object.values(realCampaign)) {
    stats.set(t.name, new Summary(t.name));
    }
    for (let i = 0; i < N_SIMS; i++) {
        document.getElementsByClassName("progress-bar")[0].style.width = 100*i/N_SIMS + "%";
        let dicTimes = new Map();
        for (let t of Object.values(realCampaign)) {
            dicTimes.set(t.name, new Team(t));
            classif.concat(dicTimes.set(t.name));
        }

        for (let j of upcomingMatches) {
            if (!dicTimes.has(j.homeTeam)) {
                dicTimes.set(j.homeTeam, new Team(j.homeTeam, j.homeBadge));
            }
            if (!stats.has(j.homeTeam)) {
                stats.set(j.homeTeam, new Summary(j.homeTeam));
            }
            if (!dicTimes.has(j.awayTeam)) {
                dicTimes.set(j.awayTeam, new Team(j.awayTeam, j.awayBadge));
            }
            if (!stats.has(j.awayTeam)) {
                stats.set(j.awayTeam, new Summary(j.awayTeam));
            }
            simulateMatchELOHFA(dicTimes.get(j.homeTeam), dicTimes.get(j.awayTeam));

        }

        let classif = [];
        for (let t of dicTimes.values()) {
            classif.push(t);
        }
        
        classif.sort((a, b) => a.compareTo(b));
        
        for (let pos = 0; pos < classif.length; pos++) {
            stats.get(classif[pos].name).histograma[pos] = stats.get(classif[pos].name).histograma[pos]+1;
        }
        stats.get(classif[0].name).titulos += 1;
        for (let j = 0; j < 4; j++) {
            stats.get(classif[j].name).g4s += 1;
            stats.get(classif[19 - j].name).z4s += 1;
        }

    }
    let tabelaProbs = [];
    for (let e of stats.values()) {
        tabelaProbs.push(e);
    }
    tabelaProbs.sort((a, b) => a.compareTo(b));
    displaySummary(tabelaProbs);
    
    
};


document.addEventListener('DOMContentLoaded', (event) => {
    
    displayListOfMatches(listOfMatches);
    
    runSimulation(listOfMatches);  
    document.getElementById("btMatches").addEventListener('click', () => {showPanel('divMatches');});
    document.getElementById("btRatings").addEventListener('click', () => {showPanel('divRatings');});
    document.getElementById("btNextMatches").addEventListener('click', () => {showPanel('divNextMatches');});
    document.getElementById("btSummary").addEventListener('click', () => {showPanel('divSummary');});

});
