let index = 0
let score = 0

const progress = document.getElementById("progress")
const question = document.getElementById("question")
const options = document.getElementById("options")
const nextBtn = document.getElementById("nextBtn")

function render() {

    if (index >= TASKS.length) {

        question.innerHTML = "<h2>Ferdig!</h2>"
        options.innerHTML = ""
        nextBtn.style.display = "none"

        return
    }

    const task = TASKS[index]

    progress.innerText = `Oppgave ${index + 1} / ${TASKS.length}`

    options.innerHTML = ""
    nextBtn.style.display = "none"

    switch (task.type) {

        case "true_false":
            renderTrueFalse(task)
            break

        case "fill":
            renderFill(task)
            break

        case "word_order":
            renderWordOrder(task)
            break

        case "preposition":
            renderFill(task)
            break

        case "verb_inflection":
            renderVerb(task)
            break

        case "writing":
            renderWriting(task)
            break

        default:
            question.innerText = "Oppgavetype ikke støttet: " + task.type
    }

}

function renderTrueFalse(task) {

    question.innerText = task.statement

    const btn1 = document.createElement("button")
    btn1.innerText = "Riktig"

    const btn2 = document.createElement("button")
    btn2.innerText = "Feil"

    btn1.onclick = next
    btn2.onclick = next

    options.appendChild(btn1)
    options.appendChild(btn2)

}

function renderFill(task) {

    question.innerText = task.sentence

    const input = document.createElement("input")
    input.type = "text"

    const btn = document.createElement("button")
    btn.innerText = "Svar"

    btn.onclick = next

    options.appendChild(input)
    options.appendChild(btn)

}

function renderWordOrder(task) {

    question.innerText = task.question

    const words = task.scrambled.split(" ")

    words.sort(() => Math.random() - 0.5)

    words.forEach(w => {

        const btn = document.createElement("button")
        btn.innerText = w

        options.appendChild(btn)

    })

    nextBtn.style.display = "block"
}

function renderVerb(task) {

    question.innerText = "Bøy verbet: " + task.lemma

    const input = document.createElement("input")

    const btn = document.createElement("button")
    btn.innerText = "Svar"

    btn.onclick = next

    options.appendChild(input)
    options.appendChild(btn)

}

function renderWriting(task) {

    question.innerText = task.task

    const textarea = document.createElement("textarea")

    const btn = document.createElement("button")
    btn.innerText = "Neste"

    btn.onclick = next

    options.appendChild(textarea)
    options.appendChild(btn)

}

function next() {

    index++
    render()

}

render()