const constants = {
  baseUrl: "http://127.0.0.1:8000/api",
  tick: 100,
  roundOverDelay: 5000
};

const player = { id: undefined, name: undefined }

const match = {
  id: undefined,
  round: { number: 1, status: null, winningMoves: [] },
  cross: undefined,
  turn: undefined,
  opponent: { id: undefined, name: undefined },
  score: { cross: 0, circle: 0, draw: 0 },
  moves: []
};

function resetStates() {
  if (workerIds.updater) clearInterval(workerIds.updater);
  if (workerIds.roundOverState) clearInterval(workerIds.roundOverState);
  if (workerIds.roundOverUI) clearInterval(workerIds.roundOverUI);
  player.id = undefined;
  player.name = undefined;
  match.id = undefined;
  match.round.number = 1;
  match.round.status = null;
  match.round.winningMoves = [];
  match.cross = undefined;
  match.turn = undefined;
  match.opponent.id = undefined;
  match.opponent.name = undefined;
  match.score.cross = 0;
  match.score.circle = 0;
  match.score.draw = 0;
  match.moves = [];
}

const elements = {
  app: document.querySelector(".app"),
  player: document.querySelector(".player"),
  inputName: document.querySelector("input[name=player]"),
  inputMarks: document.querySelectorAll("input[name=mark]"),
  buttonPlay: document.querySelector("#play"),
  queueInfo: document.querySelector("#queue-info"),
  queueTime: document.querySelector("#queue-time"),
  stats: document.querySelector(".stats"),
  opponentNames: document.querySelectorAll(".opponent"),
  playerScore: document.querySelector("#score-player"),
  opponentScore: document.querySelector("#score-opponent"),
  drawScore: document.querySelector("#score-draw"),
  board: document.querySelector(".board"),
  spaces: document.querySelectorAll(".space"),
  status: document.querySelector(".status"),
  buttonNewGame: document.querySelector("#new-game")
}

const workerIds = {
  findMatch: undefined,
  updater: undefined,
  roundOverState: undefined,
  roundOverUI: undefined
}

async function tick() {
  const params = new URLSearchParams();
  params.append("match_id", match.id);
  params.append("round", match.round.number);
  params.append("cross", match.cross);

  const result = await fetch(constants.baseUrl + "/get_moves.php?" + params.toString())
    .then(response => response.json());

  if (parseRoundStateResponse(result)) updateMatchRoundUI();
}

function startUpdater() {
  workerIds.updater = setInterval(tick, constants.tick);
}

function parseRoundStateResponse(result) {
  if (match.moves.length === result["moves"].length) return false;
  console.log("parseRoundStateResponse", result);
  match.round.status = result["status"];
  match.round.winningMoves = result["winning_moves"];
  match.moves = result["moves"];
  switch (match.round.status) {
    case "win":
      if (match.cross) match.score.cross++;
      else match.score.circle++;
      break;
    case "lose":
      if (match.cross) match.score.circle++;
      else match.score.cross++;
      break;
    case "draw":
      match.score.draw++;
      break;
  }
  if (match.round.status) {
    workerIds.roundOverState = setTimeout(
      () => {
        console.log("Round over state change");
        match.turn = match.cross;
        match.round.number++
      },
      constants.roundOverDelay
    );
  } else if (match.moves.length > 0) {
    match.turn = !match.turn;
  }
  return true
}

function changePage(page) {
  switch (page) {
    case "login":
      elements.app.classList.add("center--vertical");
      elements.player.style.display = "unset";
      elements.inputName.placeholder = "Anonymous " + getRandomName();
      toggleLoginInputs(true);
      elements.stats.style.display = "none";
      elements.board.style.display = "none";
      resetBoard();
      elements.status.style.display = "none";
      elements.buttonNewGame.style.display = "none";
      break;
    case "play":
      elements.app.classList.remove("center--vertical");
      elements.player.style.display = "none";
      elements.stats.style.display = "unset";
      elements.board.style.display = "grid";
      elements.status.style.display = "unset";
      elements.buttonNewGame.style.display = "unset";
      break;
  }
}

function toggleLoginInputs(enable) {
  elements.inputName.disabled = !enable;
  elements.inputMarks.forEach(el => {
    el.disabled = !enable;
  });
  elements.buttonPlay.disabled = !enable;
  elements.queueInfo.style.display = enable ? "none" : "";
}

function updateMatchRoundUI() {
  elements.opponentNames.forEach(element => {
    element.textContent = match.opponent.name;
  });
  elements.playerScore.textContent = match.cross ? match.score.cross : match.score.circle;
  elements.opponentScore.textContent = match.cross ? match.score.circle : match.score.cross;
  elements.drawScore.textContent = match.score.draw;

  if (match.turn) elements.board.removeAttribute("disabled");
  else elements.board.setAttribute("disabled", "");

  match.moves.forEach((pos, i) => {
    const el = document.querySelector(".space:nth-child(" + (pos + 1) + ")");
    el.classList.add(i % 2 === 0 ? "space--cross" : "space--circle");
    el.setAttribute("disabled", "");
  });

  if (match.round.status !== null) {
    elements.board.setAttribute("disabled", "");
    elements.spaces.forEach(el => {
      el.setAttribute("disabled", "");
    });
    (match.round.winningMoves ?? []).forEach(pos => {
      const el = document.querySelector(".space:nth-child(" + (pos + 1) + ")");
      el.classList.add("space--highlight-" + match.round.status);
    });
    workerIds.roundOverUI = setTimeout(resetBoard, constants.roundOverDelay);
  }

  let statusText, statusColor;
  switch (match.round.status) {
    case null:
      statusText = match.turn ?
        `Your turn ${match.cross ? "❌" : "⭕"}` :
        `Opponent's turn ${match.cross ? "⭕" : "❌"}`;
      statusColor = match.turn ? "lightblue" : "lightgrey";
      break;
    case "win":
      statusText = "You win! 🥳\n";
      statusColor = "lightgreen";
      break;
    case "lose":
      statusText = "You lose! 😢"
      statusColor = "lightcoral";
      break;
    case "draw":
      statusText = "Draw! 😲";
      statusColor = "lightgrey";
      break;
  }
  elements.status.textContent = statusText;
  elements.status.style.backgroundColor = statusColor;
}

function resetBoard() {
  console.trace("Reset board");
  elements.board.removeAttribute("disabled");
  elements.spaces.forEach(el => {
    el.removeAttribute("disabled");
    el.classList.remove("space--cross");
    el.classList.remove("space--circle")
    el.classList.remove("space--highlight-win");
    el.classList.remove("space--highlight-lose");
  });
}

elements.inputName.placeholder = "Anonymous " + getRandomName();

elements.buttonPlay.addEventListener("click", async () => {
  const queueStart = new Date();
  const mark = document.querySelector("input[name=mark]:checked").value;

  const params = new URLSearchParams();
  params.append(
    "name",
    elements.inputName.value === "" ? elements.inputName.placeholder : elements.inputName.value
  );
  params.append("mark", mark);

  toggleLoginInputs(false);

  workerIds.findMatch = setInterval(
    async () => {
      elements.queueTime.textContent = Math.floor((new Date() - queueStart) / 1000) + " s";

      const result = await fetch(constants.baseUrl + "/find_opponent.php?" + params.toString())
        .then(response => response.json());

      if (result["status"] !== "match_found") return;

      player.id = result["player"]["id"];
      player.name = result["player"]["name"];
      match.id = result["match"]["id"];
      match.cross = mark === "cross";
      match.turn = match.cross;
      match.opponent.id = result["match"]["opponent"]["id"];
      match.opponent.name = result["match"]["opponent"]["name"];
      clearInterval(workerIds.findMatch);
      startUpdater();
      changePage("play");
      updateMatchRoundUI();
    },
    constants.tick
  );
});

elements.spaces.forEach((el, i) => {
  el.addEventListener("click", async () => {
    if (
      el.getAttribute("disabled") === "" ||
      el.parentElement.getAttribute("disabled") === ""
    ) return;

    console.log("Space click " + i);

    el.classList.add(match.cross ? "space--cross" : "space--circle");
    el.setAttribute("disabled", "");

    const body = new FormData();
    body.append("match_id", match.id);
    body.append("position", i.toString());

    const result = await fetch(
      constants.baseUrl + "/make_move.php",
      { method: 'post', body: body })
      .then(response => response.json());

    if (parseRoundStateResponse(result)) updateMatchRoundUI();
  });
});

elements.buttonNewGame.addEventListener("click", async () => {
  await fetch(constants.baseUrl + "/new_game.php");
  resetStates();
  changePage("login");
});
