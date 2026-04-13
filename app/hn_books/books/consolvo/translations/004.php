<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tekst 4 – Planlegging og framdrift (NO / EN / HR / LT / PL)</title>

<style>
:root {
    --bg:#f5f7f9;
    --panel-bg:#ffffff;
    --primary:#003366;
    --accent:#e84343;
    --border:#d0d7de;
    --text:#222222;
}
*{box-sizing:border-box}
body{
    margin:0;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
    background:var(--bg);
    color:var(--text)
}
header{
    background:var(--primary);
    color:#fff;
    padding:12px 18px
}
header h1{margin:0;font-size:1.4rem}
header small{display:block;opacity:.9}
.toolbar{
    display:flex;
    gap:10px;
    align-items:center;
    padding:10px 18px;
    border-bottom:1px solid var(--border);
    background:#fff
}
select{padding:4px 8px}
.container{
    display:flex;
    gap:16px;
    padding:16px 18px 24px
}
.panel{
    background:var(--panel-bg);
    border-radius:6px;
    border:1px solid var(--border);
    padding:10px 14px;
    flex:1;
    max-height:80vh;
    overflow-y:auto
}
.panel h2{
    margin-top:0;
    font-size:1.2rem;
    border-bottom:1px solid var(--border);
    padding-bottom:6px
}
details{border-bottom:1px solid #eee;padding:6px 0}
details:last-of-type{border-bottom:none}
summary{
    font-weight:600;
    cursor:pointer;
    list-style:none
}
summary::-webkit-details-marker{display:none}
summary::before{
    content:"▶";
    display:inline-block;
    margin-right:6px;
    font-size:.8rem;
    transition:transform .15s ease
}
details[open] summary::before{transform:rotate(90deg)}
details div{
    margin-top:6px;
    padding-left:18px;
    font-size:.95rem;
    line-height:1.45
}
@media(max-width:900px){
    .container{flex-direction:column}
    .panel{max-height:none}
}
</style>
</head>

<body>

<header>
    <h1>Tekst 4: Planlegging og framdrift</h1>
    <small>Norsk tekst · Oversettelse avsnitt for avsnitt</small>
</header>

<div class="toolbar">
    <label for="language">Språk i høyre kolonne:</label>
    <select id="language">
        <option value="en">Engelsk</option>
        <option value="hr">Kroatisk</option>
        <option value="lt">Litauisk</option>
        <option value="pl">Polsk</option>
    </select>
</div>

<div class="container">
<section class="panel">
<h2>Norsk</h2>
<div id="accordion-nor"></div>
</section>

<section class="panel">
<h2 id="other-title">Engelsk</h2>
<div id="accordion-other"></div>
</section>
</div>

<script>
const sections = [

{
id:"a",
titleNo:"Arbeidshverdag og ansvar",
titleEn:"Daily work and responsibility",
titleHr:"Radni dan i odgovornost",
titleLt:"Kasdienis darbas ir atsakomybė",
titlePl:"Codzienna praca i odpowiedzialność",
bodyNo:`<p>På Sundvikbrua er god planlegging en viktig del av arbeidshverdagen. Arbeidet er variert, fysisk krevende og ofte komplekst, og mange oppgaver må løses parallelt. Som bas leder jeg det daglige arbeidet på byggeplassen. Jeg jobber tett sammen med laget mitt og har ansvar for at oppgavene blir utført på en trygg og effektiv måte, og i riktig rekkefølge. Samtidig må jeg ha oversikt over både framdrift, kvalitet og HMS i praksis.</p>`,
bodyEn:`<p>At Sundvik Bridge, good planning is an important part of everyday work. The work is varied, physically demanding and often complex, and many tasks must be carried out in parallel. As a site foreman, I lead the daily work on the construction site. I work closely with my team and am responsible for ensuring that tasks are carried out safely, efficiently and in the correct order. At the same time, I must maintain an overview of progress, quality and health and safety in practice.</p>`,
bodyHr:`<p>Na mostu Sundvik dobro planiranje važan je dio svakodnevnog rada. Posao je raznolik, fizički zahtjevan i često složen, a mnogi se zadaci moraju obavljati paralelno. Kao vođa grupe vodim svakodnevni rad na gradilištu i odgovoran sam da se zadaci izvršavaju sigurno, učinkovito i pravilnim redoslijedom. Istovremeno moram imati pregled nad napretkom, kvalitetom i zaštitom na radu u praksi.</p>`,
bodyLt:`<p>Sundvik tilte geras planavimas yra svarbi kasdienio darbo dalis. Darbas yra įvairus, fiziškai sunkus ir dažnai sudėtingas, o daug užduočių atliekama lygiagrečiai. Kaip brigadininkas vadovauju kasdieniam darbui statybvietėje ir esu atsakingas už saugų, efektyvų ir tinkama tvarka atliekamą darbą. Taip pat stebiu darbų eigą, kokybę ir darbuotojų saugą.</p>`,
bodyPl:`<p>Na moście Sundvik dobre planowanie jest ważną częścią codziennej pracy. Praca jest zróżnicowana, fizycznie wymagająca i często skomplikowana, a wiele zadań realizuje się równolegle. Jako brygadzista kieruję codziennymi pracami na placu budowy i odpowiadam za ich bezpieczne, efektywne i właściwie zaplanowane wykonanie. Jednocześnie nadzoruję postęp prac, jakość i BHP.</p>`
},

{
id:"b",
titleNo:"Oppstart av dagen",
titleEn:"Start of the day",
titleHr:"Početak radnog dana",
titleLt:"Darbo dienos pradžia",
titlePl:"Początek dnia pracy",
bodyNo:`<p>Arbeidsdagen starter vanligvis med en kort gjennomgang sammen med formannen. Først gir formannen en overordnet status for prosjektet og informerer om framdrift, prioriteringer og eventuelle endringer i planen. Vi snakker om hva som er viktig denne dagen, og om forhold som kan påvirke arbeidet, som vær, leveranser eller andre aktiviteter på byggeplassen. Etter dette går jeg videre til laget mitt.</p>`,
bodyEn:`<p>The workday usually starts with a short briefing with the supervisor. First, the supervisor provides an overall status of the project and informs us about progress, priorities and any changes to the plan. We discuss what is important for the day and whether there are factors that may affect the work, such as weather, deliveries or other activities on the site. After that, I move on to my team.</p>`,
bodyHr:`<p>Radni dan obično započinje kratkim sastankom s poslovođom. On daje opći pregled projekta te informira o napretku, prioritetima i mogućim promjenama plana. Razgovaramo o tome što je važno toga dana i o čimbenicima koji mogu utjecati na rad, poput vremena, isporuka ili drugih aktivnosti na gradilištu. Nakon toga odlazim do svog tima.</p>`,
bodyLt:`<p>Darbo diena paprastai prasideda trumpu pasitarimu su darbų vadovu. Jis pateikia bendrą projekto būklę, informuoja apie darbų eigą, prioritetus ir galimus plano pakeitimus. Aptariame veiksnius, galinčius paveikti darbą, pavyzdžiui, orą, tiekimus ar kitus darbus statybvietėje. Po to einu pas savo komandą.</p>`,
bodyPl:`<p>Dzień pracy zazwyczaj zaczyna się krótkim spotkaniem z kierownikiem robót. Przedstawia on ogólny stan projektu, informuje o postępie prac, priorytetach i ewentualnych zmianach w planie. Omawiamy czynniki mogące wpłynąć na pracę, takie jak pogoda, dostawy czy inne prace na budowie. Następnie przechodzę do zespołu.</p>`
},

{
id:"c",
titleNo:"Praktisk gjennomgang",
titleEn:"Practical briefing",
titleHr:"Praktični dogovor",
titleLt:"Praktinis aptarimas",
titlePl:"Omówienie praktyczne",
bodyNo:`<p>Sammen med fagarbeiderne tar jeg en praktisk gjennomgang av dagens arbeid. Jeg forklarer hva vi skal gjøre, hvilke oppgaver som har høyest prioritet, og hvordan arbeidet skal utføres i praksis. Jeg fordeler oppgaver og avklarer hvem som har ansvar for hvilke deler av arbeidet.</p>`,
bodyEn:`<p>Together with the skilled workers, I carry out a practical briefing of the day’s work. I explain what we are going to do, which tasks have the highest priority and how the work should be carried out in practice. I assign tasks and clarify responsibilities.</p>`,
bodyHr:`<p>Zajedno s kvalificiranim radnicima provodim praktični dogovor o dnevnim zadacima. Objašnjavam što ćemo raditi, koji su prioriteti i kako će se posao izvoditi u praksi. Raspodjeljujem zadatke i pojašnjavam odgovornosti.</p>`,
bodyLt:`<p>Kartu su kvalifikuotais darbininkais praktiškai aptariame dienos darbus. Paaiškinu, ką darysime, kurios užduotys svarbiausios ir kaip darbas bus atliekamas. Paskirstau užduotis ir atsakomybes.</p>`,
bodyPl:`<p>Wraz z wykwalifikowanymi pracownikami omawiamy praktycznie zadania na dany dzień. Wyjaśniam, co będziemy robić, które zadania są najważniejsze i jak praca ma być wykonana. Przydzielam zadania i ustalam odpowiedzialność.</p>`
},

{
id:"d",
titleNo:"Oppfølging i løpet av dagen",
titleEn:"Follow-up during the day",
titleHr:"Praćenje tijekom dana",
titleLt:"Darbų stebėjimas dienos metu",
titlePl:"Nadzór w ciągu dnia",
bodyNo:`<p>Som bas er jeg mye ute på byggeplassen. Jeg følger opp arbeidet underveis og jobber ofte sammen med fagarbeiderne. Hvis det oppstår spørsmål, tekniske utfordringer eller behov for justeringer, tar jeg raske avklaringer med laget.</p>`,
bodyEn:`<p>As a site foreman, I spend a lot of time on the construction site. I follow up the work as it progresses and often work together with the skilled workers. If questions or technical challenges arise, I make quick clarifications with the team.</p>`,
bodyHr:`<p>Kao poslovođa većinu vremena provodim na gradilištu. Pratim rad i često radim zajedno s radnicima. Ako se pojave pitanja ili tehnički problemi, brzo ih rješavamo zajedno.</p>`,
bodyLt:`<p>Kaip brigadininkas daug laiko praleidžiu statybvietėje. Stebiu darbus ir dažnai dirbu kartu su darbuotojais. Jei kyla klausimų ar techninių problemų, greitai juos išsiaiškiname.</p>`,
bodyPl:`<p>Jako brygadzista spędzam dużo czasu na placu budowy. Nadzoruję prace i często pracuję razem z zespołem. Jeśli pojawiają się pytania lub problemy techniczne, szybko je wyjaśniamy.</p>`
},

{
id:"e",
titleNo:"HMS i praksis",
titleEn:"Health and safety in practice",
titleHr:"Zaštita na radu u praksi",
titleLt:"Darbuotojų sauga praktikoje",
titlePl:"BHP w praktyce",
bodyNo:`<p>HMS er en sentral del av ansvaret mitt. Jeg følger med på arbeidsmetoder, bruk av verneutstyr og sikring av arbeidsområder. Hvis jeg oppdager farlige situasjoner, stopper jeg arbeidet og justerer planen. Sikkerhet kommer alltid før framdrift.</p>`,
bodyEn:`<p>Health and safety are a central part of my responsibility. I monitor work methods, the use of protective equipment and the securing of work areas. If I detect dangerous situations, I stop the work and adjust the plan. Safety always comes before progress.</p>`,
bodyHr:`<p>Zaštita na radu ključan je dio moje odgovornosti. Pratim radne metode, korištenje zaštitne opreme i osiguranje radnih područja. Ako primijetim opasne situacije, zaustavljam rad i prilagođavam plan.</p>`,
bodyLt:`<p>Darbuotojų sauga yra svarbi mano atsakomybės dalis. Stebiu darbo metodus, apsaugos priemonių naudojimą ir darbo zonų saugumą. Pastebėjęs pavojų, sustabdau darbus ir koreguoju planą.</p>`,
bodyPl:`<p>BHP jest kluczową częścią mojej odpowiedzialności. Kontroluję metody pracy, użycie sprzętu ochronnego i zabezpieczenie miejsc pracy. Jeśli zauważę zagrożenie, wstrzymuję pracę i koryguję plan.</p>`
},

{
id:"f",
titleNo:"Avslutning av dagen",
titleEn:"End of the day",
titleHr:"Završetak dana",
titleLt:"Dienos pabaiga",
titlePl:"Zakończenie dnia",
bodyNo:`<p>Til slutt avslutter vi arbeidsdagen med en kort oppsummering. Jeg går gjennom hva vi har fått gjort, hva som er ferdigstilt, og om det er noe som må følges opp neste dag. Denne informasjonen gir jeg videre til formannen.</p>`,
bodyEn:`<p>At the end of the day, we finish with a short summary. I go through what has been completed and what needs to be followed up the next day, and pass this information on to the supervisor.</p>`,
bodyHr:`<p>Na kraju dana završavamo rad kratkim sažetkom. Prolazimo što je učinjeno i što treba nastaviti sljedeći dan, a informacije prenosim poslovođi.</p>`,
bodyLt:`<p>Dienos pabaigoje trumpai apibendriname darbus. Peržiūrime, kas atlikta ir ką reikės tęsti kitą dieną, o informaciją perduodu darbų vadovui.</p>`,
bodyPl:`<p>Na koniec dnia robimy krótkie podsumowanie. Omawiamy, co zostało wykonane i co trzeba kontynuować następnego dnia, a informacje przekazuję kierownikowi.</p>`
},

{
id:"g",
titleNo:"Planlegging og samarbeid",
titleEn:"Planning and cooperation",
titleHr:"Planiranje i suradnja",
titleLt:"Planavimas ir bendradarbiavimas",
titlePl:"Planowanie i współpraca",
bodyNo:`<p>Når planleggingen er tydelig og ansvaret er klart fordelt, fungerer arbeidet bedre. Laget vet hva de skal gjøre, samarbeidet blir sterkere, og arbeidsdagen blir tryggere og mer forutsigbar. For meg som bas handler planlegging om å ta gode beslutninger i hverdagen – til beste for laget, sikkerheten og kvaliteten på arbeidet.</p>`,
bodyEn:`<p>When planning is clear and responsibilities are well defined, the work runs more smoothly. The team knows what to do, cooperation improves, and the workday becomes safer and more predictable. For me as a site foreman, planning is about making good everyday decisions – for the benefit of the team, safety and quality.</p>`,
bodyHr:`<p>Kada je planiranje jasno, a odgovornosti dobro raspodijeljene, posao bolje funkcionira. Tim zna što treba raditi, suradnja je jača, a radni dan sigurniji i predvidljiviji. Za mene kao poslovođu planiranje znači donošenje dobrih svakodnevnih odluka.</p>`,
bodyLt:`<p>Kai planavimas aiškus, o atsakomybės tinkamai paskirstytos, darbas vyksta sklandžiau. Komanda žino, ką daryti, bendradarbiavimas stiprėja, o darbo diena tampa saugesnė ir labiau nuspėjama. Man, kaip brigadininkui, planavimas reiškia gerus kasdienius sprendimus.</p>`,
bodyPl:`<p>Gdy planowanie jest jasne, a odpowiedzialność dobrze podzielona, praca przebiega sprawniej. Zespół wie, co robić, współpraca się poprawia, a dzień pracy jest bezpieczniejszy i bardziej przewidywalny. Dla mnie jako brygadzisty planowanie oznacza podejmowanie dobrych decyzji na co dzień.</p>`
}

];

// render
function render(lang){
const nor=document.getElementById("accordion-nor");
const oth=document.getElementById("accordion-other");
const title=document.getElementById("other-title");
title.textContent={en:"Engelsk",hr:"Kroatisk",lt:"Litauisk",pl:"Polsk"}[lang];
nor.innerHTML="";oth.innerHTML="";
sections.forEach(s=>{
const d1=document.createElement("details");
const s1=document.createElement("summary");
s1.textContent=s.titleNo;
const c1=document.createElement("div");
c1.innerHTML=s.bodyNo;
d1.dataset.id=s.id;d1.append(s1,c1);nor.append(d1);

const d2=document.createElement("details");
const s2=document.createElement("summary");
s2.textContent=s["title"+lang.charAt(0).toUpperCase()+lang.slice(1)];
const c2=document.createElement("div");
c2.innerHTML=s["body"+lang.charAt(0).toUpperCase()+lang.slice(1)];
d2.dataset.id=s.id;d2.append(s2,c2);oth.append(d2);
});
function sync(a,b){
a.querySelectorAll("details").forEach(d=>{
d.addEventListener("toggle",()=>{
const p=b.querySelector(`details[data-id="${d.dataset.id}"]`);
if(p)p.open=d.open;
});
});
}
sync(nor,oth);sync(oth,nor);
}
render("en");
document.getElementById("language").addEventListener("change",e=>render(e.target.value));
</script>

</body>
</html>
