function checkAnswers(){

let inputs=document.querySelectorAll("[data-answer]")

let correct=0

inputs.forEach(el=>{

let answer=el.dataset.answer

if(!answer)return

if(el.value.trim().toLowerCase()==answer.toLowerCase()){
el.style.background="#c8f7c5"
correct++
}else{
el.style.background="#f7c5c5"
}

})

document.getElementById("result").innerText=
"Riktige svar: "+correct+" / "+inputs.length

}