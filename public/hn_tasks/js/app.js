let data = null;
let user = null;
let score = 0;
let answers = {};

const LEVELS = ['a1', 'a2', 'b1', 'b2'];

const app = document.getElementById('app');

/* INIT */

async function start() {
  try {
    const res = await fetch('/hn_tasks/api/auth.php?action=me');
    user = await res.json();

    if (!user || user.error) {
      loginScreen();
    } else {
      menu();
    }
  } catch {
    loginScreen();
  }
}

/* LOGIN */

function loginScreen() {
  app.innerHTML = `
    <h2>Innlogging</h2>
    <input id="email" placeholder="E-post"><br><br>
    <input id="password" type="password" placeholder="Passord"><br><br>
    <button onclick="login()">Logg inn</button>
    <p>eller</p>
    <button onclick="registerScreen()">Registrer</button>
  `;
}

function registerScreen() {
  app.innerHTML = `
    <h2>Registrer</h2>
    <input id="name" placeholder="Navn"><br><br>
    <input id="email" placeholder="E-post"><br><br>
    <input id="password" type="password" placeholder="Passord"><br><br>
    <button onclick="register()">Opprett</button><br><br>
    <button onclick="loginScreen()">Tilbake</button>
  `;
}

async function login() {
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();

  const res = await fetch('/hn_tasks/api/auth.php?action=login', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ email, password })
  });

  const result = await res.json();

  if (result.error) {
    alert(result.error);
    return;
  }

  user = result.user;
  menu();
}

async function register() {
  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();

  const res = await fetch('/hn_tasks/api/auth.php?action=register', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ name, email, password })
  });

  const result = await res.json();

  if (result.error) {
    alert(result.error);
    return;
  }

  alert('Bruker opprettet');
  loginScreen();
}

/* MENU */

async function menu() {

  const res = await fetch('/hn_tasks/api/progress.php');
  const progress = await res.json();

  const completed = progress
    .filter(p => p.completed == 1)
    .map(p => p.level);

  app.innerHTML = `<h2>Velg nivå</h2><p>${user.name}</p>`;

  LEVELS.forEach(level => {

    const locked =
      LEVELS.indexOf(level) > 0 &&
      !completed.includes(LEVELS[LEVELS.indexOf(level) - 1]);

    app.innerHTML += `
      <button onclick="load('${level}')" ${locked ? 'disabled' : ''}>
        ${level.toUpperCase()}
      </button><br><br>
    `;
  });

  app.innerHTML += `<button onclick="logout()">Logg ut</button>`;
}

/* LOGOUT */

async function logout() {
  await fetch('/hn_tasks/api/auth.php?action=logout');
  user = null;
  loginScreen();
}

/* LOAD */

async function load(level) {

  const res = await fetch(`/hn_tasks/api/tasks.php?level=${level}`);

  if (res.status === 401) {
    loginScreen();
    return;
  }

  data = await res.json();

  data.level = level;
  score = 0;
  answers = {};

  renderTasks();
}

/* RENDER */

function renderTasks() {

  app.innerHTML = `
    <h3>${data.title}</h3>
    <p>${data.instruction}</p>

    <form id="taskForm">
      ${data.tasks.map((t, i) => `
        <div class="task">
          <p>${i + 1}. ${t.q.replace('___', `
            <select name="q${i}">
              <option value="">–</option>
              ${(t.options || data.options).map(o => `<option value="${o}">${o}</option>`).join('')}
            </select>
          `)}</p>
          <div id="fb${i}" class="feedback"></div>
        </div>
      `).join('')}

      <button type="button" onclick="checkAll()">Sjekk svar</button>
    </form>
  `;
}

/* CHECK */

function checkAll() {

  const form = new FormData(document.getElementById('taskForm'));

  score = 0;
  answers = {};

  data.tasks.forEach((t, i) => {

    const val = form.get(`q${i}`);
    answers[i] = val;

    const fb = document.getElementById(`fb${i}`);

    if (val === t.correct) {
      fb.innerHTML = '✔';
      fb.className = 'feedback correct';
      score++;
    } else {
      fb.innerHTML = `✖ ${t.correct}`;
      fb.className = 'feedback wrong';
    }
  });

  app.innerHTML += `<button onclick="showResult()">Se resultat</button>`;
}

/* RESULT */

function showResult() {

  const passed = score >= 8;
  const idx = LEVELS.indexOf(data.level);

  let next = '';

  if (passed && idx < LEVELS.length - 1) {
    next = `<button onclick="load('${LEVELS[idx + 1]}')">Neste nivå</button>`;
  }

  app.innerHTML += `
    <h2>${score}/10</h2>
    <p>${passed ? 'Bestått' : 'Ikke bestått'}</p>
    <button onclick="save()">Lagre</button>
    ${next}
    <button onclick="menu()">Til meny</button>
  `;
}

/* SAVE */

function save() {
  fetch('/hn_tasks/api/save.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      level: data.level,
      score,
      answers,
      task_ids: data.tasks.map(t => t.id),
      correct: data.tasks.map(t => t.correct)
    })
  });
}

start();