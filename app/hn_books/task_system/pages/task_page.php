<?php
declare(strict_types=1);

$book=$_GET["book"] ?? "";
$text=$_GET["text"] ?? "";

$file=__DIR__."/../data/$book/$text.json";

if(!file_exists($file)){
    die("Ingen oppgaver funnet.");
}

$data=json_decode(file_get_contents($file),true);

$perPage=8;
$page=max(1,(int)($_GET["page"] ?? 1));

$total=count($data["tasks"]);
$pages=(int)ceil($total/$perPage);

$tasks=array_slice(
    $data["tasks"],
    ($page-1)*$perPage,
    $perPage
);

?>

<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Oppgaver</title>

<style>

body{
font-family:system-ui;
max-width:800px;
margin:40px auto;
}

.task{
margin-bottom:25px;
}

.correct{
background:#c8f7c5;
}

.wrong{
background:#ffd6d6;
}

</style>

</head>

<body>

<h1>Oppgaver</h1>

<form id="taskForm">

<?php foreach($tasks as $i=>$task): ?>

<div class="task">

<?php if($task["type"]==="fill"): ?>

<p><?=$task["sentence"]?></p>

<input type="text" data-answer="<?=$task["answer"]?>">

<?php endif; ?>

<?php if($task["type"]==="order"): ?>

<p>Sett ordene i riktig rekkefølge:</p>

<p><?=implode(" ",$task["words"])?></p>

<input type="text" data-answer="<?=$task["answer"]?>">

<?php endif; ?>

<?php if($task["type"]==="mc_verb"): ?>

<p><?=$task["question"]?></p>

<p><strong><?=$task["verb"]?></strong></p>

<?php foreach($task["options"] as $opt): ?>

<label>

<input type="radio" name="mc<?=$i?>" value="<?=$opt?>" data-answer="<?=$task["answer"]?>">

<?=$opt?>

</label><br>

<?php endforeach; ?>

<?php endif; ?>

<?php if($task["type"]==="mc_context"): ?>

<p><?=$task["sentence"]?></p>

<?php foreach($task["options"] as $opt): ?>

<label>

<input type="radio" name="mc<?=$i?>" value="<?=$opt?>" data-answer="<?=$task["answer"]?>">

<?=$opt?>

</label><br>

<?php endforeach; ?>

<?php endif; ?>

<?php if($task["type"]==="writing"): ?>

<p><?=$task["question"]?></p>

<textarea rows="5"></textarea>

<?php endif; ?>

</div>

<?php endforeach; ?>

<button type="button" onclick="checkAnswers()">Sjekk svar</button>

<p id="score"></p>

</form>

<div>

<?php for($i=1;$i<=$pages;$i++): ?>

<?php if($i==$page): ?>

<strong><?=$i?></strong>

<?php else: ?>

<a href="?book=<?=$book?>&text=<?=$text?>&page=<?=$i?>"><?=$i?></a>

<?php endif; ?>

<?php endfor; ?>

</div>

<script>

function checkAnswers(){

let correct=0;

document.querySelectorAll("input[data-answer]").forEach(input=>{

let answer=input.dataset.answer.toLowerCase();
let user=input.value.toLowerCase();

if(user===answer){

input.classList.add("correct");
correct++;

}else{

input.classList.add("wrong");

}

});

document.querySelectorAll("input[type=radio]:checked").forEach(input=>{

let answer=input.dataset.answer.toLowerCase();
let user=input.value.toLowerCase();

if(user===answer){
correct++;
}

});

document.getElementById("score").innerText="Riktige svar: "+correct;

}

</script>

</body>
</html>